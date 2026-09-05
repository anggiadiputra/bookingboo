<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard khusus staf: admin (/admin/dashboard), finance (/finance/dashboard),
 * dan CS (/cs/dashboard) — akses sesuai role serta konten khas tiap peran.
 */
class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'approved_at' => now()]);
    }

    private function makeBookingWithPaidPayment(): array
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $customer = Customer::create(['user_id' => $customerUser->id]);

        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta',
            'hourly_rate' => 50000,
            'verification_status' => 'verified',
        ]);

        $booking = Booking::create([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 12:00:00',
            'status' => Booking::STATUS_COMPLETED,
            'total_amount' => 150000,
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 150000,
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return [$booking, $caregiver];
    }

    // ---------- Akses ----------

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->makeStaff('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Pengguna');
    }

    public function test_finance_can_access_finance_dashboard(): void
    {
        $finance = $this->makeStaff('finance');

        $this->actingAs($finance)->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Finance')
            ->assertSee('Pencairan Dana');
    }

    public function test_support_can_access_support_dashboard(): void
    {
        $support = $this->makeStaff('support');

        $this->actingAs($support)->get(route('support.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Customer Service')
            ->assertSee('Antrean Komplain');
    }

    public function test_finance_cannot_access_admin_dashboard(): void
    {
        $finance = $this->makeStaff('finance');

        $this->actingAs($finance)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_support_cannot_access_finance_dashboard(): void
    {
        $support = $this->makeStaff('support');

        $this->actingAs($support)->get(route('finance.dashboard'))->assertForbidden();
    }

    public function test_admin_cannot_access_support_dashboard(): void
    {
        $admin = $this->makeStaff('admin');

        $this->actingAs($admin)->get(route('support.dashboard'))->assertForbidden();
    }

    public function test_customer_cannot_access_staff_dashboards(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('finance.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('support.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('finance.dashboard'))->assertRedirect(route('login'));
        $this->get(route('support.dashboard'))->assertRedirect(route('login'));
    }

    public function test_unapproved_staff_cannot_access_dedicated_dashboard(): void
    {
        $pending = User::factory()->create(['role' => 'support', 'status' => User::STATUS_PENDING]);

        $this->actingAs($pending)->get(route('support.dashboard'))
            ->assertForbidden();
    }

    // ---------- Pengalihan /dashboard lama ----------

    public function test_old_dashboard_redirects_staff_to_dedicated_dashboard(): void
    {
        $admin = $this->makeStaff('admin');
        $finance = $this->makeStaff('finance');
        $support = $this->makeStaff('support');

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($finance)->get(route('dashboard'))->assertRedirect(route('finance.dashboard'));
        $this->actingAs($support)->get(route('dashboard'))->assertRedirect(route('support.dashboard'));
    }

    // ---------- Konten finance ----------

    public function test_finance_dashboard_shows_payout_stats_and_queue(): void
    {
        [, $caregiver] = $this->makeBookingWithPaidPayment();

        Payout::create([
            'caregiver_id' => $caregiver->id,
            'amount' => 100000,
            'status' => Payout::STATUS_PENDING,
            'note' => 'Cairkan minggu ini',
        ]);

        $finance = $this->makeStaff('finance');

        $this->actingAs($finance)->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Proses')
            ->assertSee('Cairkan minggu ini')
            ->assertSee($caregiver->user->name)
            ->assertSee('Rp 100.000');
    }

    public function test_finance_dashboard_shows_pending_and_paid_payments(): void
    {
        [$booking] = $this->makeBookingWithPaidPayment();

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 75000,
            'status' => Payment::STATUS_PENDING,
        ]);

        $finance = $this->makeStaff('finance');

        $this->actingAs($finance)->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee('Pembayaran Menunggu')
            ->assertSee('Pembayaran Lunas Terbaru');
    }

    // ---------- Konten CS ----------

    public function test_support_dashboard_shows_complaint_queue(): void
    {
        [$booking] = $this->makeBookingWithPaidPayment();
        $customerUser = $booking->customer->user;

        $complaint = Complaint::create([
            'booking_id' => $booking->id,
            'reporter_id' => $customerUser->id,
            'reason' => 'Caregiver tidak hadir',
            'description' => 'Tidak muncul sampai jadwal selesai.',
            'status' => Complaint::STATUS_OPEN,
        ]);

        $support = $this->makeStaff('support');

        $this->actingAs($support)->get(route('support.dashboard'))
            ->assertOk()
            ->assertSee('Caregiver tidak hadir')
            ->assertSee($booking->booking_code)
            ->assertSee($complaint->labelStatus());
    }

    public function test_support_dashboard_shows_recent_reviews(): void
    {
        [$booking] = $this->makeBookingWithPaidPayment();

        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $booking->customer->user->id,
            'reviewee_id' => $booking->caregiver->user->id,
            'rating' => 5,
            'comment' => 'Pelayanan sangat memuaskan',
            'status' => Review::STATUS_PUBLISHED,
            'visibility' => Review::VISIBILITY_PUBLIC,
        ]);

        $support = $this->makeStaff('support');

        $this->actingAs($support)->get(route('support.dashboard'))
            ->assertOk()
            ->assertSee('Review Terbaru')
            ->assertSee('Pelayanan sangat memuaskan')
            ->assertSee('5/5');
    }
}
