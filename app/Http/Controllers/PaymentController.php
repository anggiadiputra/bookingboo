<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\MidtransService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pembayaran invoice via Midtrans Snap.
 *
 * Tanpa kredensial Midtrans (mode simulasi), tombol bayar menampilkan panel
 * "Bayar Sekarang (simulasi)" yang memanggil endpoint settle/expire untuk
 * mengubah status payment — menggantikan callback Snap asli.
 */
class PaymentController extends Controller
{
    public function __construct(private MidtransService $midtrans, private NotificationService $notifications, private AuditLogService $audit) {}

    /**
     * Halaman checkout: siapkan payment pending + snap token, tampilkan Snap popup.
     */
    public function checkout(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->customer_id !== $user->customer->id) {
            abort(403);
        }

        if ($booking->latestPayment()?->status === 'paid') {
            return redirect()->route('customer.bookings.invoice', $booking)
                ->with('status', 'Booking ini sudah lunas.');
        }

        [$token, $error] = $this->midtrans->createSnapToken($booking);

        if ($error) {
            return redirect()->route('customer.bookings.invoice', $booking)
                ->withErrors(['payment' => $error]);
        }

        $payment = $this->midtrans->ensurePendingPayment($booking, $this->midtrans->orderCode($booking));
        $payment->refresh();

        return view('payment.checkout', [
            'booking' => $booking,
            'payment' => $payment,
            'snapToken' => $token,
            'clientKey' => (string) config('midtrans.client_key'),
            'snapUrl' => (string) config('midtrans.snap_url'),
            'simulated' => ! $this->midtrans->enabled(),
        ]);
    }

    /**
     * Settle payment (webhook Midtrans atau simulasi lokal).
     */
    public function settle(Request $request, Booking $booking)
    {
        $this->authorizePaymentAction($request, $booking);

        $payment = $booking->latestPayment();

        if (! $payment || $payment->status === 'paid') {
            return back()->withErrors(['payment' => 'Tidak ada pembayaran yang bisa dilunaskan.']);
        }

        DB::transaction(function () use ($payment, $booking, $request): void {
            // Transisi atomik: hanya satu request yang berhasil menandai lunas;
            // webhook + simulasi yang datang bersamaan tidak mencatat dobel.
            $affected = Payment::query()
                ->whereKey($payment->id)
                ->where('status', '!=', 'paid')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_type' => $request->input('payment_type', 'simulation'),
                ]);

            if ($affected === 0) {
                return;
            }

            $booking->refresh();
            $this->recordHistory($booking, $payment->payment_code);
            $this->notifications->paymentPaid($booking);

            $this->audit->log($request->user(), 'payment.settled',
                "Pembayaran {$payment->payment_code} diterima (Rp ".number_format((float) $payment->amount, 0, ',', '.').').',
                $booking,
                ['payment_id' => $payment->id, 'payment_code' => $payment->payment_code, 'amount' => (float) $payment->amount]);
        });

        return redirect()
            ->route($request->user()->customer ? 'customer.bookings.invoice' : 'customer.bookings.show', $booking)
            ->with('status', 'Pembayaran berhasil diterima. Terima kasih!');
    }

    /**
     * Tandai pembayaran gagal/kedaluwarsa (simulasi expire/cancel webhook).
     */
    public function expire(Request $request, Booking $booking)
    {
        $this->authorizePaymentAction($request, $booking);

        $payment = $booking->latestPayment();

        if (! $payment || $payment->status !== 'pending') {
            return back()->withErrors(['payment' => 'Tidak ada pembayaran pending yang bisa ditandai gagal.']);
        }

        $payment->update(['status' => 'failed']);

        $this->audit->log($request->user(), 'payment.failed',
            "Pembayaran {$payment->payment_code} ditandai gagal/kedaluwarsa.",
            $booking,
            ['payment_id' => $payment->id, 'payment_code' => $payment->payment_code]);

        return redirect()
            ->route('customer.bookings.invoice', $booking)
            ->with('status', 'Pembayaran ditandai gagal. Anda dapat mencoba bayar lagi.');
    }

    /**
     * Webhook notifikasi Midtrans (Payment Notification).
     * Endpoint publik (tanpa auth) — keamanan via verifikasi signature_key.
     */
    public function webhook(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'string'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'string'],
            'signature_key' => ['required', 'string'],
            'transaction_status' => ['required', 'string'],
            'payment_type' => ['nullable', 'string', 'max:50'],
        ]);

        $orderId = $data['order_id'];
        $booking = Booking::whereHas('payments', fn ($q) => $q->where('external_id', $orderId))->first()
            ?? Booking::where('id', (int) substr($orderId, 4))->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        if (! $this->midtrans->verifySignature($orderId, $data['status_code'], $data['gross_amount'], $data['signature_key'])) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $payment = $booking->latestPayment();

        if (! $payment) {
            return response()->json(['message' => 'Payment tidak ditemukan.'], 404);
        }

        $transactionStatus = $data['transaction_status'];
        $map = [
            'settlement' => 'paid',
            'capture' => 'paid',
            'pending' => 'pending',
            'deny' => 'failed',
            'expire' => 'failed',
            'cancel' => 'failed',
        ];

        $newStatus = $map[$transactionStatus] ?? null;

        if ($newStatus === null) {
            return response()->json(['message' => 'Status transaksi tidak dikenal.'], 422);
        }

        DB::transaction(function () use ($payment, $booking, $newStatus, $data, $transactionStatus): void {
            // Transisi atomik: webhook yang datang berulang/simultan (Midtrans
            // mengirim notifikasi ganda) hanya diproses sekali per perubahan status.
            $affected = Payment::query()
                ->whereKey($payment->id)
                ->where('status', '!=', $newStatus)
                ->update([
                    'status' => $newStatus,
                    'payment_type' => $data['payment_type'] ?? 'midtrans',
                    'external_id' => $data['order_id'],
                    'paid_at' => $newStatus === 'paid' ? now() : $payment->paid_at,
                ]);

            if ($affected > 0 && $newStatus === 'paid') {
                $this->recordHistory($booking, $payment->payment_code);
                $this->notifications->paymentPaid($booking);
            }

            $this->audit->logSystem('payment.'.$newStatus,
                "Webhook Midtrans: {$payment->payment_code} menjadi {$newStatus} (transaksi {$transactionStatus}).",
                $booking,
                ['payment_id' => $payment->id, 'payment_code' => $payment->payment_code,
                    'order_id' => $data['order_id'], 'transaction_status' => $transactionStatus]);
        });

        return response()->json(['message' => 'OK']);
    }

    /**
     * Halaman kembali dari Snap (finish/error/unfinish).
     */
    public function finish(Request $request, Booking $booking): View
    {
        return view('payment.finish', ['booking' => $booking, 'status' => $request->query('status', 'success')]);
    }

    private function authorizePaymentAction(Request $request, Booking $booking): void
    {
        // Di mode asli, aksi ini hanya boleh lewat webhook; di mode simulasi dari customer pemilik.
        if ($this->midtrans->enabled()) {
            abort(403, 'Aksi hanya tersedia melalui webhook Midtrans.');
        }

        if ($booking->customer_id !== $request->user()?->customer?->id) {
            abort(403);
        }
    }

    private function recordHistory(Booking $booking, string $paymentCode): void
    {
        $booking->statusHistories()->create([
            'from_status' => $booking->status,
            'to_status' => $booking->status,
            'changed_by' => null,
            'note' => 'Pembayaran diterima: '.$paymentCode,
        ]);
    }
}
