<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Laporan transaksi platform (UC-13): rekap pembayaran, payout, dan
 * performa caregiver. Diakses admin, finance, dan support (sesuai peran).
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period', '30d');

        [$from, $to] = $this->dateRange($period);

        // Rekap pembayaran.
        $paymentsQuery = Payment::query()
            ->whereBetween('created_at', [$from, $to]);

        $paidAmount = (float) (clone $paymentsQuery)
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        $refundedAmount = (float) (clone $paymentsQuery)
            ->whereIn('status', [Payment::STATUS_REFUNDED, Payment::STATUS_PAID])
            ->sum('refunded_amount');

        $paymentCounts = [
            'total' => (clone $paymentsQuery)->count(),
            'paid' => (clone $paymentsQuery)->where('status', Payment::STATUS_PAID)->count(),
            'pending' => (clone $paymentsQuery)->where('status', Payment::STATUS_PENDING)->count(),
            'refunded' => (clone $paymentsQuery)->where('status', Payment::STATUS_REFUNDED)->count(),
        ];

        // Rekap booking.
        $bookingCounts = [
            'total' => Booking::whereBetween('created_at', [$from, $to])->count(),
            'completed' => Booking::whereBetween('created_at', [$from, $to])->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => Booking::whereBetween('created_at', [$from, $to])->whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED, Booking::STATUS_NO_SHOW, Booking::STATUS_EXPIRED])->count(),
        ];

        // Komisi platform: dari booking selesai yang dibayar.
        $completedBookings = Booking::whereBetween('created_at', [$from, $to])
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereHas('payments', fn ($q) => $q->where('status', Payment::STATUS_PAID))
            ->get();
        $platformRevenue = $completedBookings->sum(fn ($b) => $b->platformCommission());

        // Rekap payout (finance/admin).
        $payoutQuery = Payout::query()->whereBetween('created_at', [$from, $to]);
        $payoutCounts = [
            'total' => (clone $payoutQuery)->count(),
            'paid_amount' => (float) (clone $payoutQuery)->where('status', Payout::STATUS_PAID)->sum('amount'),
            'pending_amount' => (float) (clone $payoutQuery)->where('status', Payout::STATUS_PENDING)->sum('amount'),
        ];

        // Rincian transaksi terbaru.
        $payments = Payment::with(['booking.customer.user', 'booking.caregiver.user'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(50)
            ->get();

        // Top caregiver (performa) pada periode: jumlah booking selesai & nilai.
        $topCaregivers = Booking::whereBetween('created_at', [$from, $to])
            ->where('status', Booking::STATUS_COMPLETED)
            ->with('caregiver.user')
            ->get()
            ->groupBy('caregiver_id')
            ->map(function ($rows) {
                $caregiver = $rows->first()->caregiver;

                return [
                    'name' => $caregiver?->user->name ?? '—',
                    'bookings' => $rows->count(),
                    'revenue' => round($rows->sum(fn ($b) => $b->billableTotal()), 2),
                ];
            })
            ->sortByDesc('bookings')
            ->take(5)
            ->values();

        // Ringkasan pengguna baru.
        $newUsers = User::whereBetween('created_at', [$from, $to])->count();

        return view('admin.reports.index', compact(
            'period', 'from', 'to',
            'paidAmount', 'refundedAmount', 'paymentCounts',
            'bookingCounts', 'platformRevenue',
            'payoutCounts', 'payments', 'topCaregivers', 'newUsers',
        ));
    }

    /**
     * Rentang tanggal berdasarkan periode (30d|90d|12m|all).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(string $period): array
    {
        $to = now()->endOfDay();

        $from = match ($period) {
            '90d' => now()->subDays(90)->startOfDay(),
            '12m' => now()->subMonths(12)->startOfDay(),
            'all' => now()->subYears(20)->startOfDay(),
            default => now()->subDays(30)->startOfDay(),
        };

        return [$from, $to];
    }
}
