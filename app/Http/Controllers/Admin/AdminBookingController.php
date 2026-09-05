<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Daftar booking yang dibatalkan caregiver dan menunggu caregiver pengganti.
     */
    public function index(): View
    {
        $waiting = Booking::with(['customer.user', 'caregiver.user'])
            ->where('needs_replacement', true)
            ->orderBy('start_time')
            ->get();

        return view('admin.bookings.replacement', compact('waiting'));
    }

    /**
     * Detail booking untuk admin: kandidat caregiver pengganti.
     */
    public function show(Booking $booking): View
    {
        $booking->load(['customer.user', 'caregiver.user', 'statusHistories.actor']);

        // Kandidat: caregiver terverifikasi yang jadwalnya mencakup rentang booking dan tidak bentrok booking aktif lain.
        $candidates = Caregiver::with('user')
            ->where('verification_status', Caregiver::VERIFICATION_VERIFIED)
            ->where('id', '!=', $booking->caregiver_id)
            ->get()
            ->filter(fn (Caregiver $caregiver) => app(AvailabilityService::class)
                ->validateBookingSlot($caregiver, $booking->start_time, $booking->end_time))
            ->values();

        return view('admin.bookings.replacement-show', compact('booking', 'candidates'));
    }

    /**
     * Tugaskan caregiver pengganti.
     */
    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'caregiver_id' => ['required', 'integer', 'exists:caregivers,id'],
        ]);

        $replacement = Caregiver::findOrFail($validated['caregiver_id']);

        [$ok, $message] = $this->bookings->assignReplacement($booking, $replacement, $request->user());

        if (! $ok) {
            return back()->withErrors(['caregiver_id' => $message]);
        }

        return redirect()
            ->route('admin.bookings.replacement.index')
            ->with('status', $message);
    }
}
