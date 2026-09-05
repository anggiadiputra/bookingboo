<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Booking dari sisi customer (pasien/keluarga).
 */
class CustomerBookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Form pengajuan booking untuk caregiver tertentu.
     */
    public function create(Caregiver $caregiver): View
    {
        // Hanya caregiver terverifikasi yang bisa dibooking
        if ($caregiver->verification_status !== Caregiver::VERIFICATION_VERIFIED) {
            abort(404);
        }

        return view('customer.bookings.create', compact('caregiver'));
    }

    /**
     * Simpan pengajuan booking.
     */
    public function store(Request $request, Caregiver $caregiver)
    {
        if ($caregiver->verification_status !== Caregiver::VERIFICATION_VERIFIED) {
            abort(404);
        }

        $validated = $request->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'needs' => ['nullable', 'string', 'max:2000'],
        ]);

        // Interpretasikan waktu sebagai WIB (Asia/Jakarta) — app default UTC,
        // sedangkan semua pengguna berada di WIB. Tanpa ini, jam WIB yang sudah
        // lewat dianggap masih masa depan oleh server (pergeseran 7 jam).
        $tz = config('app.booking_timezone', 'Asia/Jakarta');
        $start = Carbon::parse($validated['start_time'], $tz);
        $end = Carbon::parse($validated['end_time'], $tz);

        if ($start->isPast()) {
            return back()->withErrors(['start_time' => 'Waktu mulai tidak boleh di masa lalu.'])->withInput();
        }

        if ($end->lessThanOrEqualTo($start)) {
            return back()->withErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.'])->withInput();
        }

        $validated['start_time'] = $start;
        $validated['end_time'] = $end;

        [$booking, $error] = $this->bookings->createRequest(
            $caregiver,
            $request->user(),
            $validated,
        );

        if ($error) {
            return back()->withErrors(['start_time' => $error])->withInput();
        }

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('status', 'Pengajuan booking berhasil dikirim. Menunggu konfirmasi caregiver.');
    }

    /**
     * Daftar booking milik customer yang login.
     */
    public function index(Request $request): View
    {
        $bookings = $request->user()->customer->bookings()
            ->with(['caregiver.user', 'statusHistories'])
            ->latest()
            ->paginate(10);

        return view('customer.bookings.index', compact('bookings'));
    }

    /**
     * Detail booking milik customer yang login.
     */
    public function show(Request $request, Booking $booking): View
    {
        // Pastikan booking milik customer yang login
        if ($booking->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        $booking->load(['caregiver.user', 'statusHistories.actor', 'reviews', 'complaints']);

        return view('customer.bookings.show', compact('booking'));
    }

    /**
     * Invoice booking milik customer yang login.
     */
    public function invoice(Request $request, Booking $booking): View
    {
        if ($booking->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        $booking->load(['caregiver.user', 'customer.user', 'payments']);

        return view('customer.bookings.invoice', ['booking' => $booking, 'viewer' => 'customer']);
    }

    /**
     * Batalkan booking (customer) - sementara membatalkan tanpa refund dulu.
     */
    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        if (! in_array($booking->status, [Booking::STATUS_REQUESTED, Booking::STATUS_ACCEPTED], true)) {
            return back()->withErrors([
                'booking' => 'Booking hanya dapat dibatalkan sebelum dikonfirmasi.',
            ]);
        }

        [$ok, $message] = $this->bookings->cancel(
            $booking,
            $request->user(),
            $request->input('reason'),
        );

        if (! $ok) {
            return back()->withErrors(['booking' => $message]);
        }

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('status', 'Booking berhasil dibatalkan.');
    }
}
