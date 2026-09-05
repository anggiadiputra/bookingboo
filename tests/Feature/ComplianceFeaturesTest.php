<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\NotificationService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur baru: pengaturan Turnstile, proteksi form publik,
 * laporan transaksi (UC-13), notifikasi in-app (FR-13).
 */
class ComplianceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active', 'approved_at' => now()]);
    }

    private function seedSettings(): void
    {
        $this->seed(SettingSeeder::class);

        // Hapus kunci enabled dari DB agar override provider tidak menimpa
        // keputusan env testing (TURNSTILE_ENABLED=false di phpunit.xml).
        Setting::where('key', 'turnstile.enabled')->delete();

        $provider = app()->getProvider(AppServiceProvider::class);
        $provider?->applyDatabaseSettings();
    }

    private function enableTurnstile(): void
    {
        config(['turnstile.enabled' => true]);
        config(['turnstile.site_key' => '1x00000000000000000000AA']);
        config(['turnstile.secret_key' => '1x0000000000000000000000000000000AA']);
    }

    // ---------- Turnstile settings tab ----------

    public function test_security_tab_renders_and_saves_turnstile(): void
    {
        $this->seedSettings();

        $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['tab' => 'security']))
            ->assertOk()
            ->assertSee('Cloudflare Turnstile')
            ->assertSee('Aktifkan Turnstile');

        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'security',
                'enabled' => '1',
                'site_key' => '0x4AAAAAAA-test-site',
                'secret_key' => '0x4AAAAAAA-test-secret',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'turnstile.site_key', 'value' => '0x4AAAAAAA-test-site']);
    }

    // ---------- Turnstile on forgot/reset forms ----------

    public function test_forgot_password_page_shows_turnstile_when_enabled(): void
    {
        $this->seedSettings();
        $this->enableTurnstile();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('cf-turnstile');
    }

    public function test_forgot_password_requires_turnstile_when_enabled(): void
    {
        $this->seedSettings();
        $this->enableTurnstile();

        $this->post(route('password.email'), ['email' => 'someone@example.com'])
            ->assertSessionHasErrors('cf-turnstile-response');
    }

    public function test_reset_password_page_shows_turnstile(): void
    {
        $this->seedSettings();
        $this->enableTurnstile();

        $this->get(route('password.reset', ['token' => 'abc']))
            ->assertOk()
            ->assertSee('cf-turnstile');
    }

    // ---------- Report page ----------

    public function test_staff_roles_can_open_report(): void
    {
        foreach (['support', 'finance', 'admin'] as $role) {
            $this->actingAs($this->staff($role))
                ->get(route('admin.reports.index'))
                ->assertOk()
                ->assertSee('Laporan Platform')
                ->assertSee('Pembayaran Lunas')
                ->assertSee('Pendapatan Platform');
        }
    }

    public function test_customer_cannot_open_report(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
    }

    // ---------- In-app notifications ----------

    public function test_notification_page_lists_and_mark_read(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $n = Notification::create([
            'user_id' => $user->id,
            'type' => 'booking',
            'content' => 'Booking Anda dikonfirmasi.',
            'channel' => Notification::CHANNEL_IN_APP,
            'status' => Notification::STATUS_UNREAD,
            'sent_at' => now(),
        ]);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Booking Anda dikonfirmasi.');

        $this->actingAs($user)->patch(route('notifications.read', $n))->assertRedirect();
        $this->assertDatabaseHas('notifications', ['id' => $n->id, 'status' => Notification::STATUS_READ]);
    }

    public function test_notification_service_writes_in_app_record(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $customer = Customer::create(['user_id' => $customerUser->id]);
        $caregiverUser = User::factory()->create(['role' => 'caregiver', 'status' => 'active', 'approved_at' => now()]);
        $caregiver = Caregiver::create([
            'user_id' => $caregiverUser->id,
            'skills' => 'x', 'service_area' => 'Jakarta', 'hourly_rate' => 50000, 'verification_status' => 'verified',
        ]);
        $booking = Booking::create([
            'booking_code' => Booking::generateCode(),
            'customer_id' => $customer->id,
            'caregiver_id' => $caregiver->id,
            'start_time' => now()->addDay(), 'end_time' => now()->addDay()->addHours(2),
            'status' => Booking::STATUS_REQUESTED, 'total_amount' => 100000,
        ]);

        app(NotificationService::class)->bookingRequested($booking);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $caregiverUser->id,
            'type' => 'booking',
            'channel' => Notification::CHANNEL_IN_APP,
            'status' => Notification::STATUS_UNREAD,
        ]);
    }
}
