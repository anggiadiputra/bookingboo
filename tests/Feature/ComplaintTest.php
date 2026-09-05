<?php

namespace Tests\Feature;

use App\Mail\ComplaintFiled;
use App\Mail\ComplaintUpdated;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\RefundPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Komplain per booking + bantuan darurat (FR-21, FR-23).
 */
class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
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

        $supportUser = User::factory()->create(['role' => 'support', 'status' => 'active']);
        $financeUser = User::factory()->create(['role' => 'finance', 'status' => 'active']);
        $adminUser = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->ids = [
            'customer' => $customerUser->id,
            'caregiver_user' => $caregiverUser->id,
            'caregiver' => $caregiver->id,
            'support' => $supportUser->id,
            'finance' => $financeUser->id,
            'admin' => $adminUser->id,
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

    public function test_customer_files_complaint_and_staff_notified(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_IN_PROGRESS]);
        $customerUser = User::find($this->ids['customer']);

        $res = $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), [
                'reason' => 'Caregiver tidak hadir',
                'description' => 'Sudah menunggu 30 menit, caregiver tidak muncul.',
            ]);

        $res->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('complaints', [
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'Caregiver tidak hadir',
            'status' => 'open',
        ]);

        // Staf support & admin menerima notifikasi
        Mail::assertQueued(ComplaintFiled::class, 2);
    }

    public function test_caregiver_can_also_file_complaint(): void
    {
        $booking = $this->makeBooking();
        $caregiverUser = User::find($this->ids['caregiver_user']);

        $this->actingAs($caregiverUser)
            ->post(route('caregiver.bookings.complaint.store', $booking), [
                'reason' => 'Customer tidak kooperatif',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('complaints', [
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['caregiver_user'],
        ]);
    }

    public function test_only_one_active_complaint_per_booking(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);

        $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), ['reason' => 'Pertama'])
            ->assertSessionHas('status');

        $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), ['reason' => 'Kedua'])
            ->assertSessionHasErrors(['reason']);

        $this->assertDatabaseCount('complaints', 1);

        // Setelah resolved, boleh komplain lagi
        Complaint::where('booking_id', $booking->id)->update(['status' => 'resolved']);
        $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), ['reason' => 'Masalah baru'])
            ->assertSessionHas('status');

        $this->assertDatabaseCount('complaints', 2);
    }

    public function test_non_participant_cannot_file_complaint(): void
    {
        $booking = $this->makeBooking();
        $other = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        Customer::create(['user_id' => $other->id]);

        $this->actingAs($other)
            ->post(route('customer.bookings.complaint.store', $booking), ['reason' => 'x'])
            ->assertForbidden();

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_complaint_validation(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);

        $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), ['reason' => ''])
            ->assertSessionHasErrors(['reason']);

        $this->actingAs($customerUser)
            ->post(route('customer.bookings.complaint.store', $booking), [
                'reason' => str_repeat('x', 101),
            ])
            ->assertSessionHasErrors(['reason']);

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_staff_can_review_and_update_complaint(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);
        $supportUser = User::find($this->ids['support']);

        Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'Layanan tidak sesuai',
            'description' => 'Detail masalah.',
        ]);

        // Support melihat daftar & detail
        $this->actingAs($supportUser)->get(route('complaints.staff.index'))
            ->assertOk()->assertSee($booking->booking_code);

        $complaint = Complaint::where('booking_id', $booking->id)->first();
        $this->actingAs($supportUser)->get(route('complaints.staff.show', $complaint))
            ->assertOk()->assertSee('Layanan tidak sesuai');

        // Update ke in_review lalu resolved (resolusi wajib)
        $this->actingAs($supportUser)
            ->patch(route('complaints.staff.update', $complaint), [
                'status' => 'in_review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'in_review',
            'handled_by' => $this->ids['support'],
        ]);

        $this->actingAs($supportUser)
            ->patch(route('complaints.staff.update', $complaint), [
                'status' => 'resolved',
                'resolution' => 'Refund 50% diproses.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolution' => 'Refund 50% diproses.',
        ]);

        // Reporter menerima notifikasi untuk tiap perubahan status (2x)
        Mail::assertQueued(ComplaintUpdated::class, 2);
    }

    public function test_resolved_requires_resolution_text(): void
    {
        $booking = $this->makeBooking();
        $supportUser = User::find($this->ids['support']);

        $complaint = Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'x',
        ]);

        $this->actingAs($supportUser)
            ->from(route('complaints.staff.show', $complaint))
            ->patch(route('complaints.staff.update', $complaint), [
                'status' => 'resolved',
                'resolution' => null,
            ])
            ->assertSessionHasErrors(['resolution']);
    }

    public function test_customer_cannot_access_staff_panel(): void
    {
        $customerUser = User::find($this->ids['customer']);

        $this->actingAs($customerUser)->get(route('complaints.staff.index'))->assertForbidden();
    }

    public function test_finance_can_access_staff_panel(): void
    {
        $financeUser = User::factory()->create(['role' => 'finance', 'status' => 'active']);

        $this->actingAs($financeUser)->get(route('complaints.staff.index'))->assertOk();
    }

    public function test_help_page_shows_emergency_contacts(): void
    {
        $customerUser = User::find($this->ids['customer']);

        $res = $this->actingAs($customerUser)->get(route('help'));

        $res->assertOk()
            ->assertSee('Kontak Darurat Platform')
            ->assertSee(config('support.hotline'))
            ->assertSee('119');
    }

    public function test_help_page_lists_my_active_complaints(): void
    {
        $booking = $this->makeBooking();
        $customerUser = User::find($this->ids['customer']);

        Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'Komplain aktif saya',
        ]);

        $this->actingAs($customerUser)->get(route('help'))
            ->assertOk()
            ->assertSee('Komplain aktif saya');
    }

    public function test_guest_cannot_access_help(): void
    {
        $this->get(route('help'))->assertRedirect(route('login'));
    }

    public function test_detail_page_shows_complaint_form_and_active_state(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_IN_PROGRESS]);
        $customerUser = User::find($this->ids['customer']);

        // Awal: tampil form komplain
        $this->actingAs($customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Ajukan Komplain');

        // Setelah komplain: tampil status aktif, form hilang
        Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'Tidak hadir',
            'status' => 'in_review',
        ]);

        $this->actingAs($customerUser)
            ->get(route('customer.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Komplain aktif')
            ->assertDontSee('Ajukan Komplain');
    }

    // ---- Penyelesaian sengketa (dispute) oleh staf: FR-26, UC-08 ----

    /** Komplain in_review pada booking completed + pembayaran lunas. */
    private function makeDisputeContext(): array
    {
        $booking = $this->makeBooking([
            'status' => Booking::STATUS_COMPLETED,
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
        ]);
        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 150000,
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);
        $complaint = Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $this->ids['customer'],
            'reason' => 'Layanan tidak sesuai',
            'status' => 'in_review',
        ]);

        return [$booking, $complaint];
    }

    public function test_finance_can_record_dispute_refund(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();

        $res = $this->actingAs(User::find($this->ids['finance']))
            ->post(route('complaints.resolve.refund', $complaint), [
                'amount' => 75000,
                'note' => 'Refund 50% atas layanan tidak sesuai.',
            ]);

        $res->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => Payment::STATUS_REFUNDED,
            'refunded_amount' => 75000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dispute.refunded',
            'user_id' => $this->ids['finance'],
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_refund_defaults_to_full_remaining_amount(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();

        $this->actingAs(User::find($this->ids['admin']))
            ->post(route('complaints.resolve.refund', $complaint))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => Payment::STATUS_REFUNDED,
            'refunded_amount' => 150000,
        ]);
    }

    public function test_refund_amount_cannot_exceed_billable(): void
    {
        [, $complaint] = $this->makeDisputeContext();

        $this->actingAs(User::find($this->ids['finance']))
            ->post(route('complaints.resolve.refund', $complaint), ['amount' => 200000])
            ->assertRedirect()
            ->assertSessionHasErrors('refund');

        $this->assertDatabaseMissing('audit_logs', ['action' => 'dispute.refunded']);
    }

    public function test_support_cannot_resolve_refund(): void
    {
        [, $complaint] = $this->makeDisputeContext();

        $this->actingAs(User::find($this->ids['support']))
            ->post(route('complaints.resolve.refund', $complaint), ['amount' => 50000])
            ->assertForbidden();
    }

    public function test_finance_can_cancel_pending_payout_for_dispute(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();
        $caregiver = Caregiver::find($this->ids['caregiver']);
        $payout = Payout::create([
            'caregiver_id' => $caregiver->id,
            'amount' => 100000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $res = $this->actingAs(User::find($this->ids['finance']))
            ->post(route('complaints.resolve.payout-cancel', $complaint), [
                'note' => 'Dana ditahan selama sengketa.',
            ]);

        $res->assertRedirect()->assertSessionHas('status');

        $payout->refresh();
        $this->assertSame(Payout::STATUS_REJECTED, $payout->status);
        $this->assertSame($this->ids['finance'], $payout->processed_by);
        $this->assertStringContainsString('sengketa', (string) $payout->admin_note);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'dispute.payout_cancelled',
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_payout_request_is_held_while_dispute_open(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();
        $caregiver = Caregiver::find($this->ids['caregiver']);

        $service = app(RefundPayoutService::class);
        [$payout, $message] = $service->requestPayout($caregiver, null);

        $this->assertNull($payout);
        $this->assertStringContainsString('ditahan', $message);
        $this->assertSame(0, Payout::where('caregiver_id', $caregiver->id)->count());
    }

    public function test_payout_request_allowed_after_dispute_resolved(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();
        $caregiver = Caregiver::find($this->ids['caregiver']);

        $complaint->update(['status' => 'resolved', 'resolution' => 'Disepakati refund parsial.']);

        $service = app(RefundPayoutService::class);
        [$payout, $message] = $service->requestPayout($caregiver, null);

        $this->assertNotNull($payout);
        $this->assertStringContainsString('berhasil', $message);
    }

    public function test_pending_payout_cannot_be_paid_while_dispute_open(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();
        $caregiver = Caregiver::find($this->ids['caregiver']);
        $payout = Payout::create([
            'caregiver_id' => $caregiver->id,
            'amount' => 100000,
            'status' => Payout::STATUS_PENDING,
        ]);

        $service = app(RefundPayoutService::class);
        [$ok, $message] = $service->processPayout($payout, 'pay', User::find($this->ids['admin']));

        $this->assertFalse($ok);
        $this->assertStringContainsString('sengketa', $message);

        $payout->refresh();
        $this->assertSame(Payout::STATUS_PENDING, $payout->status);
    }

    public function test_resolved_dispute_blocks_further_resolution_actions(): void
    {
        [$booking, $complaint] = $this->makeDisputeContext();
        $complaint->update(['status' => 'resolved', 'resolution' => 'Selesai.']);

        $this->actingAs(User::find($this->ids['finance']))
            ->post(route('complaints.resolve.refund', $complaint), ['amount' => 1000])
            ->assertStatus(422);
    }

    public function test_dispute_panel_shows_resolution_actions_for_finance(): void
    {
        [, $complaint] = $this->makeDisputeContext();

        $this->actingAs(User::find($this->ids['finance']))
            ->get(route('complaints.staff.show', $complaint))
            ->assertOk()
            ->assertSee('Resolusi Finansial Sengketa')
            ->assertSee('Catat Refund')
            ->assertSee('Batalkan Payout');
    }

    public function test_support_does_not_see_resolution_actions(): void
    {
        [, $complaint] = $this->makeDisputeContext();

        $this->actingAs(User::find($this->ids['support']))
            ->get(route('complaints.staff.show', $complaint))
            ->assertOk()
            ->assertDontSee('Resolusi Finansial Sengketa');
    }
}
