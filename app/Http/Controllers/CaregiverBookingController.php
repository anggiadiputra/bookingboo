<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use App\Services\CheckinService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Booking dari sisi caregiver (terima/tolak).
 */
class CaregiverBookingController extends Controller
{
    public function __construct(private BookingService $bookings, private CheckinService $checkins) {}

    /**
     * Daftar booking masuk milik caregiver yang login.
     */
    public function index(Request $request): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $bookings = $caregiver->bookings()
            ->with(['customer.user', 'statusHistories'])
            ->latest()
            ->paginate(10);

        return view('caregiver.bookings.index', compact('caregiver', 'bookings'));
    }

    /**
     * Detail booking milik caregiver yang login.
     */
    public function show(Request $request, Booking $booking): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        $booking->load(['customer.user', 'statusHistories.actor', 'reviews', 'complaints']);

        return view('caregiver.bookings.show', compact('booking'));
    }

    /**
     * Invoice booking milik caregiver yang login.
     */
    public function invoice(Request $request, Booking $booking): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        $booking->load(['caregiver.user', 'customer.user', 'payments']);

        return view('customer.bookings.invoice', ['booking' => $booking, 'viewer' => 'caregiver']);
    }

    /**
     * Terima booking (requested -> accepted).
     */
    public function accept(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        [$ok, $message] = $this->bookings->accept($booking, $request->user());

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Tolak booking (requested -> rejected) dan lepas jadwal.
     */
    public function reject(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        [$ok, $message] = $this->bookings->reject($booking, $request->user(), $validated['reason']);

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Batalkan booking yang sudah disetujui (accepted/confirmed).
     */
    public function cancel(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        [$ok, $message] = $this->bookings->cancelByCaregiver(
            $booking,
            $request->user(),
            $request->input('reason'),
        );

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Check-in layanan (confirmed -> in_progress), oleh caregiver.
     */
    public function checkIn(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        [$ok, $message] = $this->checkins->checkIn($booking, $request->user());

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Check-out layanan (in_progress -> completed), oleh caregiver.
     */
    public function checkOut(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }

        [$ok, $message] = $this->checkins->checkOut($booking, $request->user());

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return back()->with('status', $message);
    }
}
