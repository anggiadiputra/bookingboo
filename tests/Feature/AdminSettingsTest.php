<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Panel Pengaturan & Papan Pengumuman admin:
 * akses, simpan setting (kontak/payment/komisi), CRUD announcement, dan audit log.
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $this->reloadSettingsConfig();
    }

    private function reloadSettingsConfig(): void
    {
        $provider = app()->getProvider(AppServiceProvider::class);
        $provider?->applyDatabaseSettings();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    private function nonAdmin(string $role = 'finance'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    // ---------- Akses ----------

    public function test_admin_can_open_settings_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Platform')
            ->assertSeeText('Kontak & Bantuan');
    }

    public function test_settings_tabs_render(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['tab' => 'payment']))
            ->assertOk()
            ->assertSee('Payment Gateway');

        $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['tab' => 'commission']))
            ->assertOk()
            ->assertSee('Komisi Platform');
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $this->actingAs($this->nonAdmin())
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    // ---------- Simpan kontak (general) ----------

    private function updateSettings(array $payload): TestResponse
    {
        $response = $this->actingAs($this->admin())->patch(route('admin.settings.update'), $payload);

        // Muat ulang nilai DB ke config agar assertion berikutnya konsisten.
        $this->reloadSettingsConfig();

        return $response;
    }

    public function test_admin_can_update_contact_settings(): void
    {
        $this->updateSettings([
            'tab' => 'general',
            'hotline' => '+62 812 3456 7890',
            'whatsapp' => '+62 812 0000 1111',
            'email' => 'care@bookingboo.test',
            'hours' => 'Senin-Jumat, 08.00-20.00 WIB',
        ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'general']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'support.hotline', 'value' => '+62 812 3456 7890']);
        $this->assertDatabaseHas('settings', ['key' => 'support.email', 'value' => 'care@bookingboo.test']);
        $this->assertEquals('care@bookingboo.test', config('support.email'));
    }

    public function test_update_contact_requires_valid_email(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'general',
                'hotline' => '+62 811 0000 0000',
                'whatsapp' => '+62 811 0000 0000',
                'email' => 'bukan-email',
                'hours' => 'Senin-Minggu, 07.00-21.00 WIB',
            ])
            ->assertSessionHasErrors('email');
    }

    // ---------- Simpan payment gateway ----------

    public function test_admin_can_save_midtrans_keys(): void
    {
        $this->updateSettings([
            'tab' => 'payment',
            'server_key' => 'SB-Mid-server-abc123',
            'client_key' => 'SB-Mid-client-abc123',
            'is_production' => '0',
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'midtrans.server_key', 'value' => 'SB-Mid-server-abc123']);
        $this->assertEquals('SB-Mid-server-abc123', config('midtrans.server_key'));
        $this->assertTrue(config('midtrans.enabled'));
    }

    // ---------- Simpan komisi & refund ----------

    public function test_admin_can_update_commission_and_tiers(): void
    {
        $this->updateSettings([
            'tab' => 'commission',
            'platform_percent' => '25',
            'default_refund_percent' => '10',
            'caregiver_refund_percent' => '100',
            'grace_hours' => '3',
            'tiers' => [
                ['hours' => 48, 'percent' => 100],
                ['hours' => 12, 'percent' => 50],
            ],
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'booking.commission.platform_percent', 'value' => '25']);
        $this->assertSame(25, config('booking.commission.platform_percent'));
        $this->assertSame(3, config('booking.checkin.grace_hours'));

        $raw = Setting::where('key', 'booking.cancellation.customer_refund_tiers')->value('value');
        $this->assertJson($raw);

        $tiers = json_decode((string) $raw, true);
        $this->assertEqualsCanonicalizing([
            ['hours' => 48, 'percent' => 100],
            ['hours' => 12, 'percent' => 50],
        ], $tiers);
    }

    public function test_commission_validation_rejects_out_of_range(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'commission',
                'platform_percent' => '150',
                'default_refund_percent' => '0',
                'caregiver_refund_percent' => '100',
                'grace_hours' => '2',
                'tiers' => [['hours' => 24, 'percent' => 50]],
            ])
            ->assertSessionHasErrors('platform_percent');
    }

    // ---------- Audit log ----------

    public function test_updating_settings_is_audited(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), [
                'tab' => 'general',
                'hotline' => '+62 811 1111 1111',
                'whatsapp' => '+62 811 1111 1111',
                'email' => 'support@bookingboo.test',
                'hours' => 'Senin-Minggu, 07.00-21.00 WIB',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }

    public function test_admin_can_open_announcement_list(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Papan Pengumuman');
    }

    public function test_non_admin_cannot_access_announcements(): void
    {
        $this->actingAs($this->nonAdmin())
            ->get(route('admin.announcements.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_announcement(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), [
                'type' => 'promo',
                'title' => 'Promo Akhir Tahun',
                'message' => 'Diskon 25% semua layanan.',
                'badge_label' => 'Promo',
                'link_label' => 'Klaim',
                'link_url' => 'https://bookingboo.test/caregivers',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.announcements.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('announcements', [
            'type' => 'promo',
            'title' => 'Promo Akhir Tahun',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_announcement(): void
    {
        $item = Announcement::create([
            'type' => 'announcement',
            'title' => 'Lama',
            'message' => 'Isi lama.',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('admin.announcements.update', $item), [
                'type' => 'promo',
                'title' => 'Baru',
                'message' => 'Isi baru.',
                'sort_order' => 2,
                'is_active' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'id' => $item->id,
            'title' => 'Baru',
            'type' => 'promo',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_and_delete_announcement(): void
    {
        $item = Announcement::create([
            'type' => 'announcement',
            'title' => 'Hapus nanti',
            'message' => 'Isi.',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('admin.announcements.toggle', $item))
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', ['id' => $item->id, 'is_active' => false]);

        $this->actingAs($this->admin())
            ->delete(route('admin.announcements.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $item->id]);
    }

    public function test_live_scope_only_returns_active_scheduled_announcements(): void
    {
        Announcement::create(['type' => 'announcement', 'title' => 'Tampil', 'message' => 'x', 'is_active' => true]);
        Announcement::create(['type' => 'announcement', 'title' => 'Nonaktif', 'message' => 'x', 'is_active' => false]);
        Announcement::create([
            'type' => 'announcement',
            'title' => 'Mendatang',
            'message' => 'x',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $live = Announcement::live()->pluck('title');

        $this->assertTrue($live->contains('Tampil'));
        $this->assertFalse($live->contains('Nonaktif'));
        $this->assertFalse($live->contains('Mendatang'));
    }
}
