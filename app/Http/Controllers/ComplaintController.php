<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\RefundPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Komplain per booking oleh customer/caregiver,
 * ditangani staf support/admin lewat panel komplain.
 * Aksi resolusi finansial (refund, pembatalan payout) oleh finance/admin.
 */
class ComplaintController extends Controller
{
    public const OPEN_STATUSES = ['open', 'in_review'];

    public function __construct(
        private NotificationService $notifications,
        private RefundPayoutService $refundPayouts,
        private AuditLogService $audit,
    ) {}

    /** Cek keterlibatan user pada booking; return perannya. */
    private function participantOf(Booking $booking, Request $request): ?string
    {
        $user = $request->user();

        if ($user->caregiver && $booking->caregiver_id === $user->caregiver->id) {
            return 'caregiver';
        }

        if ($user->customer && $booking->customer_id === $user->customer->id) {
            return 'customer';
        }

        return null;
    }

    /** Simpan komplain baru dari peserta booking. */
    public function store(Request $request, Booking $booking)
    {
        if (! $this->participantOf($booking, $request)) {
            abort(403);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Satu komplain aktif per booking agar tidak dobel tiket. Transaksi + kunci
        // baris booking menutup celah dua komplain simultan yang sama-sama lolos cek.
        $created = DB::transaction(function () use ($booking, $request, $data): bool {
            Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (Complaint::where('booking_id', $booking->id)->whereIn('status', self::OPEN_STATUSES)->exists()) {
                return false;
            }

            Complaint::create([
                'booking_id' => $booking->id,
                'reporter_id' => $request->user()->id,
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'status' => 'open',
            ]);

            return true;
        });

        if (! $created) {
            return redirect()
                ->back()
                ->withErrors(['reason' => 'Sudah ada komplain aktif untuk booking ini.']);
        }

        $this->notifications->complaintFiled(Complaint::where('booking_id', $booking->id)->latest()->first());

        return redirect()
            ->back()
            ->with('status', 'Komplain Anda terkirim. Tim kami akan menindaklanjuti.');
    }

    /** Daftar komplain untuk staf (support/admin). */
    public function staffIndex(Request $request)
    {
        $status = $request->query('status');

        $complaints = Complaint::query()
            ->with(['booking.customer.user', 'booking.caregiver.user', 'reporter', 'handler'])
            ->when($status && in_array($status, ['open', 'in_review', 'resolved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('staff.complaints.index', [
            'complaints' => $complaints,
            'status' => $status,
            'openCount' => Complaint::whereIn('status', self::OPEN_STATUSES)->count(),
        ]);
    }

    /** Detail komplain + form penanganan. */
    public function staffShow(Complaint $complaint)
    {
        $complaint->load(['booking.customer.user', 'booking.caregiver.user', 'reporter', 'handler']);

        return view('staff.complaints.show', ['complaint' => $complaint]);
    }

    /** Update status/resolusi komplain oleh staf. */
    public function staffUpdate(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,in_review,resolved,rejected'],
            'resolution' => ['required_if:status,resolved,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        $complaint->status = $data['status'];
        $complaint->resolution = $data['resolution'] ?? $complaint->resolution;
        $complaint->handled_by = $request->user()->id;
        $complaint->save();

        $this->notifications->complaintResolved($complaint);

        return redirect()
            ->route('complaints.staff.show', $complaint)
            ->with('status', 'Komplain diperbarui.');
    }

    /**
     * Aksi resolusi sengketa oleh finance/admin: catat refund pada pembayaran
     * lunas booking sengketa (penuh atau parsial), tanpa mengubah status komplain.
     */
    public function resolveRefund(Request $request, Complaint $complaint)
    {
        $this->authorizeResolvable($complaint);

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $complaint->booking;
        $payment = $booking->payments()->where('status', Payment::STATUS_PAID)->latest()->first();

        if (! $payment) {
            return back()->withErrors(['refund' => 'Tidak ada pembayaran lunas pada booking ini untuk direfund.']);
        }

        $billable = (float) $booking->billableTotal();
        $refunded = (float) $payment->refunded_amount;
        $requested = isset($data['amount']) ? round((float) $data['amount'], 2) : $billable - $refunded;

        if ($requested <= 0 || $requested > $billable - $refunded) {
            return back()->withErrors(['refund' => 'Nominal refund tidak valid. Maksimal Rp '.number_format($billable - $refunded, 0, ',', '.').'.']);
        }

        [$ok, $message] = $this->refundPayouts->recordRefund($booking, $requested);

        if (! $ok) {
            return back()->withErrors(['refund' => $message]);
        }

        $this->audit->log($request->user(), 'dispute.refunded',
            'Resolusi sengketa #'.$complaint->id.' (booking '.$booking->booking_code.'): refund Rp '.number_format($requested, 0, ',', '.').'.',
            $complaint,
            ['complaint_id' => $complaint->id, 'booking_id' => $booking->id, 'refund_amount' => $requested,
                'note' => $data['note'] ?? null]);

        return back()->with('status', 'Refund sengketa dicatat: Rp '.number_format($requested, 0, ',', '.').'.');
    }

    /**
     * Aksi resolusi sengketa oleh finance/admin: batalkan payout pending
     * caregiver pada booking sengketa agar dana ditahan sampai sengketa tuntas.
     */
    public function cancelPayout(Request $request, Complaint $complaint)
    {
        $this->authorizeResolvable($complaint);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payouts = Payout::query()
            ->where('caregiver_id', $complaint->booking->caregiver_id)
            ->where('status', Payout::STATUS_PENDING)
            ->get();

        if ($payouts->isEmpty()) {
            return back()->withErrors(['payout' => 'Tidak ada pengajuan pencairan pending untuk caregiver ini.']);
        }

        $staff = $request->user();
        $cancelled = DB::transaction(function () use ($payouts, $staff, $data, $complaint): int {
            $count = 0;

            foreach ($payouts as $payout) {
                $payout->update([
                    'status' => Payout::STATUS_REJECTED,
                    'admin_note' => ($data['note'] ?? null)
                        ?: ('Dibatalkan karena sengketa aktif pada booking '.$complaint->booking->booking_code.'.'),
                    'processed_by' => $staff->id,
                    'processed_at' => now(),
                ]);
                $this->audit->log($staff, 'dispute.payout_cancelled',
                    'Membatalkan pencairan #'.$payout->id.' (sengketa #'.$complaint->id.', booking '.$complaint->booking->booking_code.').',
                    $complaint,
                    ['complaint_id' => $complaint->id, 'payout_id' => $payout->id, 'amount' => (float) $payout->amount]);
                $count++;
            }

            return $count;
        });

        $this->notifications->payoutProcessed($payouts->first());

        return back()->with('status', $cancelled.' pengajuan pencairan dibatalkan. Dana caregiver ditahan sampai sengketa selesai.');
    }

    /** Aksi resolusi hanya untuk staf finance/admin pada komplain yang masih aktif. */
    private function authorizeResolvable(Complaint $complaint): void
    {
        if (! in_array(auth()->user()->role, ['finance', 'admin'], true)) {
            abort(403, 'Aksi resolusi hanya untuk staf finance atau admin.');
        }

        if (! in_array($complaint->status, self::OPEN_STATUSES, true)) {
            abort(422, 'Sengketa sudah selesai; tidak ada aksi resolusi lagi.');
        }
    }

    /** Halaman bantuan: kontak darurat platform + panduan. */
    public function help()
    {
        $openComplaints = null;

        if (auth()->user()->customer || auth()->user()->caregiver) {
            $openComplaints = Complaint::query()
                ->whereHas('booking', function ($q) {
                    $user = auth()->user();
                    $q->when($user->customer, fn ($qq) => $qq->where('customer_id', $user->customer->id))
                        ->when($user->caregiver, fn ($qq) => $qq->orWhere('caregiver_id', $user->caregiver->id));
                })
                ->whereIn('status', self::OPEN_STATUSES)
                ->latest()
                ->get();
        }

        return view('help', ['openComplaints' => $openComplaints]);
    }
}
