<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi in-app untuk user (customer, caregiver, staf).
 * Data ditulis NotificationService saat event penting; halaman ini hanya menampilkan.
 */
class NotificationController extends Controller
{
    /**
     * Daftar notifikasi milik user yang login.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Tandai semua notifikasi user sebagai dibaca.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()
            ->where('status', Notification::STATUS_UNREAD)
            ->update(['status' => Notification::STATUS_READ]);

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai satu notifikasi (milik user) sebagai dibaca.
     */
    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['status' => Notification::STATUS_READ]);

        return back();
    }
}
