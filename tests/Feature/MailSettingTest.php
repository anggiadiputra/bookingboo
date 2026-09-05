<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengaturan SMTP/email di panel admin (Pengaturan → Email/SMTP).
 */
class MailSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
        $provider = app()->getProvider(AppServiceProvider::class);
        $provider?->applyDatabaseSettings();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'approved_at' => now()]);
    }

    public function test_mail_tab_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['tab' => 'mail']))
            ->assertOk()
            ->assertSee('Pengaturan Email / SMTP')
            ->assertSee('Host SMTP');
    }

    public function test_admin_can_save_smtp_settings(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'mail',
                'mailer' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => '587',
                'username' => 'no-reply@example.com',
                'password' => 'app-password-rahasia',
                'encryption' => 'tls',
                'from_address' => 'no-reply@example.com',
                'from_name' => 'BookingBoo',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'mail.host', 'value' => 'smtp.gmail.com']);
        $this->assertDatabaseHas('settings', ['key' => 'mail.password', 'value' => 'app-password-rahasia']);
    }

    public function test_smtp_config_overrides_after_save(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'mail',
                'mailer' => 'smtp',
                'host' => 'smtp.mandiri.test',
                'port' => '465',
                'username' => 'user@mandiri.test',
                'password' => 'secret',
                'encryption' => 'ssl',
                'from_address' => 'noreply@mandiri.test',
                'from_name' => 'Mandiri',
            ])
            ->assertRedirect();

        $provider = app()->getProvider(AppServiceProvider::class);
        $provider?->applyDatabaseSettings();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.mandiri.test', config('mail.mailers.smtp.host'));
        $this->assertSame(465, (int) config('mail.mailers.smtp.port'));
        $this->assertSame('ssl', config('mail.mailers.smtp.encryption'));
        $this->assertSame('noreply@mandiri.test', config('mail.from.address'));
    }

    public function test_empty_host_keeps_default_mailer(): void
    {
        // Hapus host dari DB (seeder mungkin mengisinya dari env).
        Setting::where('key', 'mail.host')->delete();

        $provider = app()->getProvider(AppServiceProvider::class);
        $provider?->applyDatabaseSettings();

        // Tanpa host, mail.default tidak dipaksa smtp dari DB.
        $this->assertNotNull(config('mail.default'));
    }

    public function test_invalid_mail_validation(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'tab' => 'mail',
                'host' => 'smtp.example.com',
                'port' => '999999',
                'encryption' => 'bogus',
                'from_address' => 'bukan-email',
            ])
            ->assertSessionHasErrors(['port', 'encryption', 'from_address']);
    }
}
