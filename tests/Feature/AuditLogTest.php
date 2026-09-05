<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Review;
use App\Models\User;
use App\Services\RefundPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Audit log aktivitas penting platform (docs/07 todo: audit log).
 * Mencakup: login/logout, percobaan login akun nonaktif, persetujuan staf,
 * verifikasi caregiver, pembayaran, payout/refund, moderasi, dan panel admin.
 */
class AuditLogTest extends TestCase
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

    private function makePayment(Booking $booking, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'booking_id' => $booking->id,
            'payment_code' => Payment::generateCode(),
            'amount' => 150000,
            'status' => Payment::STATUS_PENDING,
        ], $overrides));
    }

    // ---- Autentikasi ----

    public function test_login_is_audited(): void
    {
        $res = $this->post(route('login'), [
            'email' => User::find($this->ids['customer'])->email,
            'password' => 'password',
        ]);

        $res->assertRedirect(route('dashboard', absolute: false));

        $log = AuditLog::where('action', 'auth.login')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($this->ids['customer'], $log->user_id);
        $this->assertSame('customer', $log->actor_role);
    }

    public function test_logout_is_audited(): void
    {
        $customer = User::find($this->ids['customer']);
        $this->actingAs($customer)->post(route('logout'));

        $log = AuditLog::where('action', 'auth.logout')->latest()->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id); // dicatat sebagai sistem, tapi dengan nama user di deskripsi
        $this->assertStringContainsString($customer->name, (string) $log->description);
    }

    public function test_suspended_login_attempt_is_audited(): void
    {
        $customer = User::find($this->ids['customer']);
        $customer->update(['status' => User::STATUS_SUSPENDED]);

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $log = AuditLog::where('action', 'auth.suspended_login_attempt')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($this->ids['customer'], $log->subject_id);
        $this->assertSame(User::class, $log->subject_type);
    }

    public function test_registration_is_audited(): void
    {
        $res = $this->post(route('register'), [
            'name' => 'Pasien Baru',
            'email' => 'pasienbaru@bookingboo.test',
            'phone' => '081234567890',
            'role' => 'customer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect();

        $log = AuditLog::where('action', 'auth.register')->latest()->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Pasien Baru', (string) $log->description);
    }

    // ---- Persetujuan staf ----

    public function test_staff_approval_is_audited(): void
    {
        $pendingStaff = User::factory()->create(['role' => 'support', 'status' => User::STATUS_PENDING]);

        $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.staff.approve', $pendingStaff))
            ->assertRedirect();

        $log = AuditLog::where('action', 'staff.approved')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($this->ids['admin'], $log->user_id);
        $this->assertSame($pendingStaff->id, $log->subject_id);
    }

    public function test_staff_rejection_is_audited(): void
    {
        $pendingStaff = User::factory()->create(['role' => 'finance', 'status' => User::STATUS_PENDING]);

        $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.staff.reject', $pendingStaff))
            ->assertRedirect();

        $this->assertSame(1, AuditLog::where('action', 'staff.rejected')->where('user_id', $this->ids['admin'])->count());
    }

    // ---- Verifikasi caregiver ----

    public function test_caregiver_verification_is_audited(): void
    {
        $caregiver = Caregiver::find($this->ids['caregiver']);
        $caregiver->update(['verification_status' => 'pending']);

        $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.caregivers.approve', $caregiver))
            ->assertRedirect();

        $log = AuditLog::where('action', 'caregiver.verified')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($this->ids['admin'], $log->user_id);
        $this->assertSame(Caregiver::class, $log->subject_type);
        $this->assertSame($caregiver->id, $log->subject_id);
    }

    // ---- Pembayaran ----

    public function test_payment_settle_is_audited(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $this->makePayment($booking);

        $res = $this->actingAs(User::find($this->ids['customer']))
            ->post(route('payment.settle', $booking));

        $res->assertRedirect();

        $log = AuditLog::where('action', 'payment.settled')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($this->ids['customer'], $log->user_id);
        $this->assertSame(Booking::class, $log->subject_type);
        $this->assertSame($booking->id, $log->subject_id);
    }

    public function test_payment_webhook_is_audited_as_system(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $payment = $this->makePayment($booking, ['external_id' => $booking->invoiceNumber()]);

        // Signature Midtrans: sha512(order_id + status_code + gross_amount + server_key)
        $serverKey = (string) config('midtrans.server_key');
        $signature = hash('sha512', $booking->invoiceNumber().'200'.'150000.00'.$serverKey);

        $res = $this->post(route('payment.webhook'), [
            'order_id' => $booking->invoiceNumber(),
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ]);

        $res->assertOk();

        $log = AuditLog::where('action', 'payment.paid')->latest()->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertSame('system', $log->actor_role);
        $this->assertSame($booking->id, $log->subject_id);
    }

    // ---- Payout & refund ----

    public function test_payout_process_is_audited(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);
        $this->makePayment($booking, ['status' => Payment::STATUS_PAID, 'paid_at' => now()]);

        $caregiver = Caregiver::find($this->ids['caregiver']);
        $svc = app(RefundPayoutService::class);
        [$payout] = $svc->requestPayout($caregiver, null);

        $this->assertNotNull(Payout::where('action', 'payout.requested')->count());

        $admin = User::find($this->ids['admin']);
        $svc->processPayout(Payout::find($payout->id), 'pay', $admin);

        $log = AuditLog::where('action', 'payout.paid')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($payout->id, $log->subject_id);
        $this->assertSame(Payout::class, $log->subject_type);
    }

    public function test_refund_recorded_is_audited_as_system(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_CONFIRMED]);
        $this->makePayment($booking, ['status' => Payment::STATUS_PAID, 'paid_at' => now()]);

        app(RefundPayoutService::class)->recordRefund($booking, 100000);

        $log = AuditLog::where('action', 'refund.recorded')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('system', $log->actor_role);
        $this->assertSame($booking->id, $log->subject_id);
    }

    // ---- Moderasi ----

    public function test_review_hide_and_unhide_are_audited(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_COMPLETED]);
        $review = Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $this->ids['customer'],
            'reviewee_id' => $this->ids['caregiver_user'],
            'rating' => 2,
            'comment' => 'Review untuk audit.',
            'visibility' => Review::VISIBILITY_PUBLIC,
            'status' => Review::STATUS_PUBLISHED,
        ]);

        $admin = User::find($this->ids['admin']);
        $this->actingAs($admin)
            ->patch(route('admin.reviews.hide', $review), ['reason' => 'Alasan audit'])
            ->assertRedirect();

        $hiddenLog = AuditLog::where('action', 'review.hidden')->latest()->first();
        $this->assertNotNull($hiddenLog);
        $this->assertSame($admin->id, $hiddenLog->user_id);
        $this->assertSame('Alasan audit', $hiddenLog->properties['reason'] ?? null);

        $this->actingAs($admin)
            ->patch(route('admin.reviews.unhide', $review))
            ->assertRedirect();

        $this->assertSame(1, AuditLog::where('action', 'review.unhidden')->where('user_id', $admin->id)->count());
    }

    public function test_user_suspend_and_reactivate_are_audited(): void
    {
        $customer = User::find($this->ids['customer']);
        $admin = User::find($this->ids['admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.suspend', $customer), ['reason' => 'Pelanggaran audit'])
            ->assertRedirect();

        $suspendLog = AuditLog::where('action', 'user.suspended')->latest()->first();
        $this->assertNotNull($suspendLog);
        $this->assertSame($admin->id, $suspendLog->user_id);
        $this->assertSame($customer->id, $suspendLog->subject_id);

        $this->actingAs($admin)
            ->patch(route('admin.users.reactivate', $customer))
            ->assertRedirect();

        $this->assertSame(1, AuditLog::where('action', 'user.reactivated')->where('user_id', $admin->id)->count());
    }

    // ---- Panel admin ----

    public function test_admin_and_support_can_view_audit_log_panel(): void
    {
        AuditLog::create([
            'user_id' => $this->ids['admin'],
            'actor_role' => 'admin',
            'action' => 'staff.approved',
            'description' => 'Menyetujui akun staf Sari Wulandari.',
        ]);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.audit-logs.index'));

        $res->assertOk()
            ->assertSee('Menyetujui akun staf Sari Wulandari.')
            ->assertSee('Setujui staf');
    }

    public function test_customer_cannot_view_audit_log_panel(): void
    {
        $this->actingAs(User::find($this->ids['customer']))
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_caregiver_cannot_view_audit_log_panel(): void
    {
        $this->actingAs(User::find($this->ids['caregiver_user']))
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_log_panel_filters_by_action(): void
    {
        AuditLog::create(['user_id' => $this->ids['admin'], 'actor_role' => 'admin', 'action' => 'staff.approved', 'description' => 'Log A']);
        AuditLog::create(['user_id' => null, 'actor_role' => 'system', 'action' => 'payment.paid', 'description' => 'Log B']);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.audit-logs.index', ['action' => 'payment.paid']));

        $res->assertOk()
            ->assertSee('Log B')
            ->assertDontSee('Log A');
    }

    public function test_audit_log_panel_filters_by_actor(): void
    {
        AuditLog::create(['user_id' => $this->ids['admin'], 'actor_role' => 'admin', 'action' => 'staff.approved', 'description' => 'Log Admin']);
        AuditLog::create(['user_id' => $this->ids['support'], 'actor_role' => 'support', 'action' => 'complaint.updated', 'description' => 'Log Support']);

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->get(route('admin.audit-logs.index', ['actor' => $this->ids['support']]));

        $res->assertOk()
            ->assertSee('Log Support')
            ->assertDontSee('Log Admin');
    }

    // ---- Ketahanan ----

    public function test_audit_failure_does_not_break_action(): void
    {
        // Simulasi kegagalan DB: drop tabel audit_logs, aksi utama tetap sukses.
        Schema::drop('audit_logs');

        $res = $this->actingAs(User::find($this->ids['admin']))
            ->patch(route('admin.users.suspend', User::find($this->ids['customer'])), ['reason' => 'Tes ketahanan']);

        $res->assertRedirect()->assertSessionHas('status');
        $this->assertSame(User::STATUS_SUSPENDED, User::find($this->ids['customer'])->status);

        Schema::create('audit_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }
}
