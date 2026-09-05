<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chat customer-caregiver terkait booking (polling MVP).
 * Endpoint JSON: show (meta), fetch (incremental + tandai read), send.
 */
class BookingChatTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpActors();
    }

    private function setUpActors(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $customerUser->id]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        $this->ids = [
            'customer' => $customerUser->id,
            'caregiver_user' => $caregiverUser->id,
            'caregiver' => $caregiver->id,
        ];
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_code' => Booking::generateCode(),
            'customer_id' => Customer::where('user_id', $this->ids['customer'])->first()->id,
            'caregiver_id' => $this->ids['caregiver'],
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_REQUESTED,
            'total_amount' => 150000,
        ], $overrides));
    }

    public function test_participants_can_view_chat_meta(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);

        $res = $this->actingAs($customerUser)
            ->getJson(route('chat.show', $booking));

        $res->assertOk()->assertJson([
            'role' => 'customer',
            'status' => 'requested',
            'can_send' => true,
        ]);

        $caregiverUser = User::find($this->ids['caregiver_user']);
        $this->actingAs($caregiverUser)
            ->getJson(route('chat.show', $booking))
            ->assertOk()
            ->assertJson(['role' => 'caregiver', 'can_send' => true]);
    }

    public function test_non_participant_gets_403(): void
    {
        $booking = $this->makeBooking();
        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)->getJson(route('chat.show', $booking))->assertForbidden();
        $this->actingAs($other)->getJson(route('chat.fetch', $booking))->assertForbidden();
        $this->actingAs($other)->postJson(route('chat.send', $booking), ['body' => 'x'])->assertForbidden();
    }

    public function test_customer_sends_message_and_caregiver_polls_it(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);
        $caregiverUser = User::find($this->ids['caregiver_user']);

        $this->actingAs($customerUser)
            ->postJson(route('chat.send', $booking), ['body' => 'Selamat pagi, saya sudah di lokasi.'])
            ->assertCreated()
            ->assertJson(['content' => 'Selamat pagi, saya sudah di lokasi.', 'mine' => true]);

        $this->assertDatabaseHas('messages', [
            'booking_id' => $booking->id,
            'sender_id' => $this->ids['customer'],
            'content' => 'Selamat pagi, saya sudah di lokasi.',
        ]);

        // Caregiver fetch pertama: dapat pesan
        $first = $this->actingAs($caregiverUser)
            ->getJson(route('chat.fetch', $booking).'?after=0')
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJson(['messages' => [['sender_name' => $customerUser->name, 'mine' => false]]]);

        $lastId = $first->json('last_id');

        // Caregiver fetch kedua dengan after=lastId: tidak ada pesan baru
        $this->actingAs($caregiverUser)
            ->getJson(route('chat.fetch', $booking).'?after='.$lastId)
            ->assertOk()
            ->assertJsonCount(0, 'messages');

        // Pesan caregiver ditandai dibaca setelah customer fetch
        $this->actingAs($caregiverUser)
            ->postJson(route('chat.send', $booking), ['body' => 'Baik, saya segera ke lokasi.'])
            ->assertCreated();

        $this->actingAs($customerUser)
            ->getJson(route('chat.fetch', $booking).'?after=1')
            ->assertOk()
            ->assertJsonCount(1, 'messages');

        $this->assertDatabaseHas('messages', [
            'booking_id' => $booking->id,
            'sender_id' => $this->ids['caregiver_user'],
            'read_status' => 'read',
        ]);
    }

    public function test_message_body_is_required_and_bounded(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);

        $this->actingAs($customerUser)
            ->postJson(route('chat.send', $booking), ['body' => ''])
            ->assertStatus(422);

        $this->actingAs($customerUser)
            ->postJson(route('chat.send', $booking), ['body' => str_repeat('a', 2001)])
            ->assertStatus(422);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_chat_closed_after_completion_but_history_readable(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);
        $customerUser = User::find($this->ids['customer']);
        $caregiverUser = User::find($this->ids['caregiver_user']);

        $this->actingAs($customerUser)
            ->postJson(route('chat.send', $booking), ['body' => 'coba kirim'])
            ->assertStatus(409);

        $this->assertDatabaseCount('messages', 0);

        // Riwayat tetap terbaca: seed pesan lama lalu fetch
        Message::create([
            'booking_id' => $booking->id,
            'sender_id' => $this->ids['caregiver_user'],
            'content' => 'Pesan lama sebelum booking selesai.',
            'read_status' => 'unread',
        ]);

        $this->actingAs($customerUser)
            ->getJson(route('chat.fetch', $booking).'?after=0')
            ->assertOk()
            ->assertJsonCount(1, 'messages');
    }

    public function test_meta_shows_chat_closed_for_completed_booking(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);

        $this->actingAs(User::find($this->ids['customer']))
            ->getJson(route('chat.show', $booking))
            ->assertOk()
            ->assertJson(['can_send' => false]);
    }

    public function test_guest_cannot_access_chat(): void
    {
        $booking = $this->makeBooking();

        $this->getJson(route('chat.show', $booking))->assertStatus(401);
    }

    public function test_chat_history_persists_across_status_changes(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);
        $caregiverUser = User::find($this->ids['caregiver_user']);

        $this->actingAs($customerUser)
            ->postJson(route('chat.send', $booking), ['body' => 'Pesan saat booking aktif.'])
            ->assertCreated();

        // Status berubah sepanjang siklus booking — pesan tidak boleh hilang
        $booking->status = Booking::STATUS_ACCEPTED;
        $booking->save();
        $this->assertDatabaseHas('messages', ['booking_id' => $booking->id]);

        $booking->status = Booking::STATUS_IN_PROGRESS;
        $booking->save();
        $booking->status = Booking::STATUS_COMPLETED;
        $booking->save();

        // Setelah selesai: riwayat tetap terbaca untuk keperluan sengketa
        $this->actingAs($caregiverUser)
            ->getJson(route('chat.fetch', $booking).'?after=0')
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJson(['messages' => [['content' => 'Pesan saat booking aktif.']]]);

        // Dan meta menandai chat ditutup
        $this->actingAs($customerUser)
            ->getJson(route('chat.show', $booking))
            ->assertOk()
            ->assertJson(['can_send' => false, 'status' => 'completed']);
    }

    public function test_chat_access_matrix(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_ACCEPTED]);

        // Tamu (belum login): 401 — dicek sebelum actingAs agar benar-benar tanpa user
        $this->getJson(route('chat.show', $booking))->assertStatus(401);

        $otherCustomer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $otherCustomer->id]);
        $otherCaregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);

        $customerUser = User::find($this->ids['customer']);
        $caregiverUser = User::find($this->ids['caregiver_user']);

        // Peserta boleh akses semua endpoint
        $this->actingAs($customerUser)->getJson(route('chat.show', $booking))->assertOk();
        $this->actingAs($caregiverUser)->getJson(route('chat.fetch', $booking).'?after=0')->assertOk();
        $this->actingAs($caregiverUser)->postJson(route('chat.send', $booking), ['body' => 'ok'])->assertCreated();

        // Non-peserta: customer lain, caregiver lain, staf (support/finance/admin) — semua 403
        foreach ([$otherCustomer, $otherCaregiverUser] as $outsider) {
            $this->actingAs($outsider)->getJson(route('chat.show', $booking))->assertForbidden();
        }
        foreach ([User::ROLE_SUPPORT, User::ROLE_FINANCE, User::ROLE_ADMIN] as $staffRole) {
            $staff = User::factory()->create(['role' => $staffRole, 'status' => 'active']);
            $this->actingAs($staff)->getJson(route('chat.show', $booking))->assertForbidden();
            $this->actingAs($staff)->getJson(route('chat.fetch', $booking))->assertForbidden();
            $this->actingAs($staff)->postJson(route('chat.send', $booking), ['body' => 'x'])->assertForbidden();
        }

    }
}
