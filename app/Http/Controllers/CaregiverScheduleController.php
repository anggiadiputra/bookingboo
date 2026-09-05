<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CaregiverScheduleController extends Controller
{
    /**
     * Tampilkan daftar jadwal caregiver.
     */
    public function index(Request $request): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $schedules = $caregiver->schedules()
            ->orderBy('start_time')
            ->paginate(10);

        return view('caregiver.schedules', compact('caregiver', 'schedules'));
    }

    /**
     * Simpan jadwal baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $validated = $request->validate([
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        // Normalisasi ke format datetime standar (input datetime-local memakai separator 'T')
        $start = Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
        $end = Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s');

        // Serialisasi agar dua penambahan simultan tidak membuat jadwal yang
        // tumpang tindih (keduanya lolos pengecekan overlap).
        $lock = Cache::lock('schedule:'.$caregiver->id, 15);

        if (! $lock->get()) {
            return back()->withErrors([
                'start_time' => 'Jadwal sedang diproses. Silakan coba lagi.',
            ])->withInput();
        }

        try {
            // Cegah jadwal ganda / tumpang tindih
            // (jadwal yang bersinggungan di batas, mis. 08-12 dan 12-16, tetap boleh)
            $overlap = $caregiver->schedules()
                ->where('status', '!=', Schedule::STATUS_BLOCKED)
                ->overlapping($start, $end)
                ->exists();

            if ($overlap) {
                return back()->withErrors([
                    'start_time' => 'Jadwal ini tumpang tindih dengan jadwal yang sudah ada.',
                ])->withInput();
            }

            $caregiver->schedules()->create([
                'start_time' => $start,
                'end_time' => $end,
                'status' => Schedule::STATUS_AVAILABLE,
            ]);
        } finally {
            $lock->release();
        }

        return back()->with('status', 'Jadwal ketersediaan berhasil ditambahkan.');
    }

    /**
     * Hapus jadwal.
     */
    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $caregiver = $request->user()->caregiver;

        // Pastikan jadwal milik caregiver yang login
        if (! $caregiver || $schedule->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        // Hanya boleh hapus jadwal yang belum dibooking
        if ($schedule->status === Schedule::STATUS_BOOKED) {
            return back()->withErrors([
                'schedule' => 'Jadwal yang sudah dibooking tidak dapat dihapus.',
            ]);
        }

        $schedule->delete();

        return back()->with('status', 'Jadwal berhasil dihapus.');
    }
}
