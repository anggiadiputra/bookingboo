<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Kelola papan pengumuman homepage (promo, pengumuman, iklan).
 */
class AnnouncementController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Daftar pengumuman beserta filter tipe/status.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        $query = Announcement::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $announcements = $query->paginate(15)->withQueryString();

        return view('admin.announcements.index', compact('announcements', 'type', 'status'));
    }

    /**
     * Form pengumuman baru.
     */
    public function create(): View
    {
        return view('admin.announcements.create');
    }

    /**
     * Simpan pengumuman baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);

        $announcement = Announcement::create($validated + [
            'image_path' => $this->storeImage($request),
        ]);

        $this->audit->log(
            $request->user(),
            'announcement.created',
            "Membuat pengumuman {$announcement->title}.",
            $announcement,
        );

        return redirect()->route('admin.announcements.index')
            ->with('status', 'Pengumuman berhasil dibuat.');
    }

    /**
     * Form edit pengumuman.
     */
    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    /**
     * Perbarui pengumuman.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $this->validateData($request);

        $imagePath = $this->storeImage($request);

        if ($imagePath !== null && $announcement->image_path) {
            // Gambar baru diunggah: hapus file lama.
            $this->deleteImage($announcement);
        } elseif ($imagePath === null && $request->boolean('remove_image') && $announcement->image_path) {
            $this->deleteImage($announcement);
            $imagePath = '';
        }

        $announcement->update($validated + ($imagePath !== null ? ['image_path' => $imagePath === '' ? null : $imagePath] : []));

        $this->audit->log(
            $request->user(),
            'announcement.updated',
            "Memperbarui pengumuman {$announcement->title}.",
            $announcement,
        );

        return redirect()->route('admin.announcements.index')
            ->with('status', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Aktif/nonaktifkan pengumuman tanpa membuka form.
     */
    public function toggle(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);

        $this->audit->log(
            $request->user(),
            'announcement.toggled',
            ($announcement->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." pengumuman {$announcement->title}.",
            $announcement,
        );

        return back()->with('status', 'Status pengumuman diperbarui.');
    }

    /**
     * Hapus pengumuman.
     */
    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->audit->log(
            $request->user(),
            'announcement.deleted',
            "Menghapus pengumuman {$announcement->title}.",
            $announcement,
        );

        $this->deleteImage($announcement);

        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('status', 'Pengumuman dihapus.');
    }

    /**
     * Validasi data pengumuman dari form create/edit.
     *
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:promo,announcement,ad'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:255'],
            'badge_label' => ['nullable', 'string', 'max:30'],
            'link_label' => ['nullable', 'string', 'max:50'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'in:0,1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);
    }

    /**
     * Simpan gambar latar jika diunggah. Return null bila tidak ada file baru.
     */
    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image') || ! $request->file('image')->isValid()) {
            return null;
        }

        return $request->file('image')->store('announcements', 'public');
    }

    /**
     * Hapus file gambar lama dari storage publik.
     */
    private function deleteImage(Announcement $announcement): void
    {
        if (! $announcement->image_path) {
            return;
        }

        Storage::disk('public')->delete($announcement->image_path);
    }
}
