<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrasi pembayaran Midtrans Snap.
 *
 * Tanpa MIDTRANS_SERVER_KEY/CLIENT_KEY di .env, service berjalan dalam mode
 * simulasi: token dibuat lokal dan status pembayaran bisa di-set manual dari
 * halaman invoice untuk keperluan pengujian MVP.
 */
class MidtransService
{
    public function enabled(): bool
    {
        return (bool) config('midtrans.enabled');
    }

    /**
     * Kode order yang dikirim ke Midtrans untuk booking ini.
     */
    public function orderCode(Booking $booking): string
    {
        return $booking->invoiceNumber();
    }

    /**
     * Ambil Snap token untuk booking. Saat mode simulasi token dibuat lokal.
     *
     * @return array{0: ?string, 1: string} [token, pesan error]
     */
    public function createSnapToken(Booking $booking): array
    {
        if (! $this->enabled()) {
            return [bin2hex(random_bytes(20)), ''];
        }

        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->post(config('midtrans.api_url').'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $this->orderCode($booking),
                    'gross_amount' => (int) round($booking->estimateTotal()),
                ],
                'item_details' => [[
                    'id' => 'booking-'.$booking->id,
                    'name' => 'Pendampingan caregiver '.$booking->start_time->format('d M Y'),
                    'price' => (int) round($booking->estimateTotal()),
                    'quantity' => 1,
                ]],
                'customer_details' => [
                    'first_name' => $booking->customer->user->name,
                    'email' => $booking->customer->user->email,
                ],
                'callbacks' => [
                    'finish' => route('payment.finish', ['booking' => $booking->id]),
                    'error' => route('payment.finish', ['booking' => $booking->id]),
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Midtrans snap token gagal', ['response' => $response->json()]);

            return [null, 'Gagal membuat token pembayaran. Coba lagi nanti.'];
        }

        $data = $response->json();

        // Respons Snap API sukses berisi field `token` (tanpa status_code).
        if (empty($data['token'])) {
            Log::warning('Midtrans snap token: respons tanpa token', ['response' => $data]);

            return [null, $data['status_message'] ?? 'Gagal membuat token pembayaran.'];
        }

        return [$data['token'], ''];
    }

    /**
     * Ambil status transaksi terbaru dari Midtrans API.
     *
     * @return ?array null saat gagal/mode simulasi
     */
    public function fetchTransactionStatus(string $orderId): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $response = Http::withBasicAuth(config('midtrans.server_key'), '')
            ->get(config('midtrans.api_url').'/v2/'.$orderId.'/status');

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Verifikasi signature_key webhook: sha512(order_id + status_code + gross_amount + server_key).
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $serverKey = (string) config('midtrans.server_key');

        return hash_equals(
            $signature,
            hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey),
        );
    }

    /**
     * Buat payment pending untuk booking (dipanggil saat tombol bayar ditekan).
     */
    public function ensurePendingPayment(Booking $booking, string $orderCode): Payment
    {
        $payment = $booking->payments()->latest()->first();

        if ($payment && $payment->status === 'pending') {
            return $payment;
        }

        return $booking->payments()->create([
            'payment_code' => Payment::generateCode(),
            'amount' => $booking->estimateTotal(),
            'method' => 'midtrans',
            'status' => 'pending',
            'external_id' => $orderCode,
        ]);
    }
}
