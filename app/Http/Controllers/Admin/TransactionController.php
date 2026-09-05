<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar transaksi & invoice untuk finance/admin.
 * Melengkapi UC-13 (laporan) dengan rincian per-transaksi (FR-22).
 */
class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::with([
            'booking.customer.user',
            'booking.caregiver.user',
        ])->latest();

        // Filter status.
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter kata kunci: kode payment, kode booking, nama customer/caregiver.
        if ($request->filled('q')) {
            $q = trim($request->query('q'));
            $query->where(function ($w) use ($q) {
                $w->where('payment_code', 'like', "%{$q}%")
                    ->orWhereHas('booking', function ($b) use ($q) {
                        $b->where('booking_code', 'like', "%{$q}%")
                            ->orWhereHas('customer.user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                            ->orWhereHas('caregiver.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                    });
            });
        }

        // Filter rentang tanggal (dari & sampai).
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $payments = $query->paginate(15)->withQueryString();

        // Ringkasan untuk header.
        $summary = [
            'all' => Payment::count(),
            'paid' => Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
            'pending' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'refunded' => Payment::where('status', Payment::STATUS_REFUNDED)->sum('refunded_amount'),
        ];

        return view('admin.transactions.index', compact('payments', 'summary'));
    }

    /**
     * Invoice transaksi utk finance/admin — dari payment + booking terkait.
     */
    public function invoice(Payment $payment): View
    {
        $payment->loadMissing(['booking.customer.user', 'booking.caregiver.user']);

        abort_if(! $payment->booking, 404, 'Transaksi tidak memiliki booking.');

        return view('admin.transactions.invoice', ['payment' => $payment]);
    }
}
