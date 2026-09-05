<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Services\RefundPayoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pencairan dana caregiver (payout).
 */
class PayoutController extends Controller
{
    public function __construct(private RefundPayoutService $payouts) {}

    /**
     * Halaman pencairan milik caregiver yang login.
     */
    public function index(Request $request): View
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $payouts = $caregiver->payouts()->latest()->paginate(10);
        $eligible = Payout::eligibleAmount($caregiver);
        $hasPending = $payouts->getCollection()->contains(fn ($p) => $p->status === Payout::STATUS_PENDING);

        return view('caregiver.payouts.index', compact('caregiver', 'payouts', 'eligible', 'hasPending'));
    }

    /**
     * Caregiver mengajukan pencairan.
     */
    public function store(Request $request)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver) {
            abort(404, 'Profil caregiver tidak ditemukan.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        [$payout, $message] = $this->payouts->requestPayout($caregiver, $validated['note'] ?? null);

        if (! $payout) {
            return back()->withErrors(['payout' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Daftar pengajuan pencairan (admin).
     */
    public function adminIndex(): View
    {
        $payouts = Payout::with(['caregiver.user', 'processor'])->latest()->paginate(15);

        return view('admin.payouts.index', compact('payouts'));
    }

    /**
     * Admin memproses pengajuan (bayar / tolak).
     */
    public function process(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:pay,reject'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        [$ok, $message] = $this->payouts->processPayout(
            $payout,
            $validated['action'],
            $request->user(),
            $validated['admin_note'] ?? '',
        );

        if (! $ok) {
            return back()->withErrors(['payout' => $message]);
        }

        return back()->with('status', $message);
    }
}
