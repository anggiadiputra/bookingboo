<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload gambar latar untuk papan pengumuman.
 */
class AnnouncementImageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function test_announcement_can_be_created_with_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), [
                'type' => 'promo',
                'title' => 'Promo Lebaran',
                'message' => 'Diskon spesial.',
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('promo.jpg', 800, 200),
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::first();

        $this->assertNotNull($announcement);
        $this->assertNotNull($announcement->image_path);
        Storage::disk('public')->assertExists($announcement->image_path);
    }

    public function test_announcement_can_be_created_without_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), [
                'type' => 'announcement',
                'title' => 'Tanpa Gambar',
                'message' => 'Cukup gradien.',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertNull(Announcement::first()->image_path);
    }

    public function test_image_upload_rejects_non_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.announcements.store'), [
                'type' => 'announcement',
                'title' => 'Coba',
                'message' => 'Isi.',
                'is_active' => '1',
                'image' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertNull(Announcement::first());
    }

    public function test_update_replaces_image_and_removes_old_file(): void
    {
        Storage::fake('public');

        $old = UploadedFile::fake()->image('lama.jpg', 800, 200);
        $announcement = Announcement::create([
            'type' => 'promo',
            'title' => 'Lama',
            'message' => 'Isi lama.',
            'is_active' => true,
            'image_path' => $old->store('announcements', 'public'),
        ]);
        $oldPath = $announcement->image_path;

        $this->actingAs($this->admin())
            ->patch(route('admin.announcements.update', $announcement), [
                'type' => 'promo',
                'title' => 'Baru',
                'message' => 'Isi baru.',
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('baru.png', 800, 200),
            ])
            ->assertRedirect();

        $announcement->refresh();

        $this->assertNotEquals($oldPath, $announcement->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($announcement->image_path);
    }

    public function test_update_can_remove_image_without_uploading_new(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('lama.jpg', 800, 200);
        $announcement = Announcement::create([
            'type' => 'promo',
            'title' => 'Lama',
            'message' => 'Isi lama.',
            'is_active' => true,
            'image_path' => $file->store('announcements', 'public'),
        ]);
        $oldPath = $announcement->image_path;

        $this->actingAs($this->admin())
            ->patch(route('admin.announcements.update', $announcement), [
                'type' => 'promo',
                'title' => 'Lama',
                'message' => 'Isi lama.',
                'is_active' => '1',
                'remove_image' => '1',
            ])
            ->assertRedirect();

        $this->assertNull($announcement->refresh()->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_destroy_deletes_announcement_image_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('hapus.jpg', 800, 200);
        $announcement = Announcement::create([
            'type' => 'announcement',
            'title' => 'Hapus',
            'message' => 'Isi.',
            'is_active' => true,
            'image_path' => $file->store('announcements', 'public'),
        ]);
        $path = $announcement->image_path;

        $this->actingAs($this->admin())
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
