<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daftar transaksi & invoice utk finance/admin (FR-22 / UC-13).
 */
class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $customerUser;

    private Customer $customer;

    private User $caregiverUser;

    private Caregiver $caregiver;

    private string $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create(['user_id' => $this->customerUser->id]);

        $this->caregiverUser = User::factory()->create([
            'role' => 'caregiver',
            'status' => 'active',
            'approved_at' => now(),
        ]);
        $this->caregiver = Caregiver::create([
            'user_id' => $this->caregiverUser->id,
            'skills' => 'Perawatan lansia',
            'service_area' => 'Jakarta Selatan',
            'hourly_rate' => 50000,
            'rating' => 4.8,
            'verification_status' => 'verified',
        ]);

        $this->day = now()->addDays(3)->format('Y-m-d');
    }

    private function makeSchedule(string $start = '08:00', string $end = '16:00'): Schedule
    {
        return Schedule::create([
            'caregiver_id' => $this->caregiver->id,
            'start_time' => $this->day.' '.$start.':00',
            'end_time' => $this->day.' '.$end.':00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);
    }

    private function finance(): User
    {
        return User::factory()->create(['role' => 'finance', 'status' => 'active', 'approved_at' => now()]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);
    }

    private function support(): User
    {
        return User::factory()->create(['role' => 'support', 'status' => 'active', 'approved_at' => now()]);
    }

    private function makePaidBooking(): Booking
    {
        $this->makeSchedule();
        [$booking] = app(BookingService::class)->createRequest($this->caregiver, $this->customerUser, [
            'start_time' => $this->day.' 09:00',
            'end_time' => $this->day.' 12:00',
        ]);

        $booking->update(['status' => Booking::STATUS_COMPLETED]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 150000,
            'platform_fee' => 30000,
            'commission' => 30000,
            'method' => 'bank_transfer',
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return $booking;
    }

    public function test_finance_can_access_transaction_list(): void
    {
        $this->actingAs($this->finance())
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('Daftar Transaksi');
    }

    public function test_admin_can_access_transaction_list(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.transactions.index'))
            ->assertOk();
    }

    public function test_support_cannot_access_transaction_list(): void
    {
        $this->actingAs($this->support())
            ->get(route('admin.transactions.index'))
            ->assertForbidden();
    }

    public function test_transaction_list_shows_payment_rows(): void
    {
        $booking = $this->makePaidBooking();
        $payment = $booking->payments()->first();

        $this->actingAs($this->finance())
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee($payment->payment_code)
            ->assertSee($booking->booking_code)
            ->assertSee('150.000');
    }

    public function test_transaction_filter_by_status(): void
    {
        $booking = $this->makePaidBooking();

        $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => 75000,
            'method' => 'bank_transfer',
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($this->finance())
            ->get(route('admin.transactions.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('75.000')
            ->assertDontSee($booking->payments()->first()->payment_code);
    }

    public function test_transaction_search_by_booking_code(): void
    {
        $booking = $this->makePaidBooking();

        $this->actingAs($this->finance())
            ->get(route('admin.transactions.index', ['q' => $booking->booking_code]))
            ->assertOk()
            ->assertSee($booking->payments()->first()->payment_code);
    }

    public function test_finance_can_view_invoice_of_payment(): void
    {
        $booking = $this->makePaidBooking();
        $payment = $booking->payments()->first();

        $this->actingAs($this->finance())
            ->get(route('admin.transactions.invoice', $payment))
            ->assertOk()
            ->assertSee($booking->invoiceNumber())
            ->assertSee($payment->payment_code)
            ->assertSee('Komisi platform')
            ->assertSee('Pendapatan caregiver');
    }

    public function test_admin_can_view_invoice(): void
    {
        $booking = $this->makePaidBooking();
        $payment = $booking->payments()->first();

        $this->actingAs($this->admin())
            ->get(route('admin.transactions.invoice', $payment))
            ->assertOk()
            ->assertSee('Lunas');
    }
}
