<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_active_announcements(): void
    {
        $promo = Announcement::create([
            'type' => 'promo',
            'title' => 'Promo Khusus Uji',
            'message' => 'Potongan 20% pengguna baru.',
            'link_url' => '/caregivers',
            'link_label' => 'Lihat',
            'badge_label' => 'Promo',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Promo Khusus Uji')
            ->assertSee('Potongan 20% pengguna baru.')
            ->assertSee('Cari caregiver, pendamping RS, rawat lansia');
    }

    public function test_home_hides_inactive_announcements(): void
    {
        Announcement::create([
            'type' => 'promo',
            'title' => 'Promo Nonaktif',
            'message' => 'Tidak boleh tampil.',
            'is_active' => false,
        ]);

        $this->get('/')->assertOk()->assertDontSee('Promo Nonaktif');
    }

    public function test_scheduled_announcement_respects_time_window(): void
    {
        // Masa tayang sudah lewat -> tidak tampil
        Announcement::create([
            'type' => 'announcement',
            'title' => 'Pengumuman Kadaluarsa',
            'message' => 'Masa tayang berakhir.',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        // Masa tayang belum mulai -> tidak tampil
        Announcement::create([
            'type' => 'announcement',
            'title' => 'Pengumuman Belum Mulai',
            'message' => 'Tayang nanti.',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        // Aktif sekarang -> tampil
        $live = Announcement::create([
            'type' => 'announcement',
            'title' => 'Pengumuman Aktif',
            'message' => 'Sedang tayang.',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Pengumuman Kadaluarsa')
            ->assertDontSee('Pengumuman Belum Mulai')
            ->assertSee('Pengumuman Aktif');
    }

    public function test_no_announcements_renders_no_board(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('Promo New Member', false)
            ->assertDontSee('x-data', false);
    }

    public function test_announcements_are_ordered_by_sort_order(): void
    {
        Announcement::create(['type' => 'ad', 'title' => 'Slide Kedua', 'message' => 'm2', 'sort_order' => 2]);
        Announcement::create(['type' => 'promo', 'title' => 'Slide Pertama', 'message' => 'm1', 'sort_order' => 1]);

        $this->assertSame(
            ['Slide Pertama', 'Slide Kedua'],
            Announcement::live()->pluck('title')->all()
        );
    }
}
