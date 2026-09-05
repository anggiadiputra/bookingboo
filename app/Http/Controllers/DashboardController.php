<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            'customer' => view('dashboard.customer', ['favorites' => $this->customerFavorites($user)]),
            'caregiver' => view('dashboard.caregiver'),
            'support' => redirect()->route('support.dashboard'),
            'finance' => redirect()->route('finance.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            default => abort(403),
        };
    }

    /**
     * Dashboard operasional admin: ringkasan platform end-to-end.
     */
    public function adminDashboard(Request $request)
    {
        return view('dashboard.admin', ['stats' => $this->adminStats()]);
    }

    /**
     * Dashboard finance: pembayaran, pencairan dana, refund, dan sengketa finansial.
     */
    public function financeDashboard(Request $request)
    {
        $payoutStatuses = [
            'pending' => Payout::where('status', Payout::STATUS_PENDING)->count(),
            'paid' => Payout::where('status', Payout::STATUS_PAID)->count(),
            'rejected' => Payout::where('status', Payout::STATUS_REJECTED)->count(),
        ];

        $payoutTotals = [
            'pending_amount' => (float) Payout::where('status', Payout::STATUS_PENDING)->sum('amount'),
            'paid_amount' => (float) Payout::where('status', Payout::STATUS_PAID)->sum('amount'),
        ];

        $disputeAmounts = [
            'refunded' => (float) Payment::whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])->sum('refunded_amount'),
        ];

        $paymentTotals = [
            'pending_count' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'pending_amount' => (float) Payment::where('status', Payment::STATUS_PENDING)->sum('amount'),
        ];

        $pendingPayments = Payment::with('booking')
            ->where('status', Payment::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get();

        $payoutQueue = Payout::with(['caregiver.user', 'processor'])
            ->where('status', Payout::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get();

        $recentPaid = Payment::with('booking')
            ->where('status', Payment::STATUS_PAID)
            ->latest('paid_at')
            ->limit(5)
            ->get();

        return view('dashboard.finance', compact(
            'payoutStatuses', 'payoutTotals', 'disputeAmounts', 'paymentTotals',
            'pendingPayments', 'payoutQueue', 'recentPaid'
        ));
    }

    /**
     * Dashboard CS/support: antrean komplain dan aktivitas dukungan.
     */
    public function supportDashboard(Request $request)
    {
        $complaintCounts = [
            'open' => Complaint::where('status', Complaint::STATUS_OPEN)->count(),
            'in_review' => Complaint::where('status', Complaint::STATUS_IN_REVIEW)->count(),
            'resolved' => Complaint::where('status', Complaint::STATUS_RESOLVED)->count(),
            'rejected' => Complaint::where('status', Complaint::STATUS_REJECTED)->count(),
        ];

        $queue = Complaint::with(['booking.customer.user', 'booking.caregiver.user', 'reporter'])
            ->whereIn('status', [Complaint::STATUS_OPEN, Complaint::STATUS_IN_REVIEW])
            ->latest()
            ->limit(5)
            ->get();

        $handledToday = Complaint::whereDate('updated_at', today())
            ->whereIn('status', [Complaint::STATUS_RESOLVED, Complaint::STATUS_REJECTED])
            ->count();

        $recentReviews = Review::with('booking')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.support', compact(
            'complaintCounts', 'queue', 'handledToday', 'recentReviews'
        ));
    }

    /**
     * Caregiver favorit milik customer yang sedang login.
     */
    private function customerFavorites(User $user)
    {
        $customer = $user->customer;

        if (! $customer) {
            return collect();
        }

        return $customer->favoriteCaregivers()
            ->where('verification_status', Caregiver::VERIFICATION_VERIFIED)
            ->with('user')
            ->withCount(['reviews' => fn ($q) => $q
                ->where('visibility', Review::VISIBILITY_PUBLIC)
                ->where('status', Review::STATUS_PUBLISHED)])
            ->get();
    }

    /**
     * Ringkasan pengguna, booking, pembayaran, dan komplain (dashboard admin).
     */
    private function adminStats(): array
    {
        $userCounts = [
            'total' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'caregivers' => User::where('role', 'caregiver')->count(),
            'caregivers_verified' => Caregiver::where('verification_status', 'verified')->count(),
            'staff' => User::whereIn('role', ['support', 'finance', 'admin'])->count(),
        ];

        $activeStatuses = [Booking::STATUS_REQUESTED, Booking::STATUS_ACCEPTED, Booking::STATUS_CONFIRMED, Booking::STATUS_IN_PROGRESS];

        $bookingCounts = [
            'total' => Booking::count(),
            'active' => Booking::whereIn('status', $activeStatuses)->count(),
            'completed' => Booking::where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => Booking::whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REJECTED, Booking::STATUS_NO_SHOW, Booking::STATUS_EXPIRED])->count(),
        ];

        $paymentTotals = [
            'paid_count' => Payment::where('status', Payment::STATUS_PAID)->count(),
            'paid_amount' => (float) Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
            'refunded_amount' => (float) Payment::whereIn('status', [Payment::STATUS_REFUNDED, Payment::STATUS_PAID])->sum('refunded_amount'),
            'platform_revenue' => (float) Booking::where('status', Booking::STATUS_COMPLETED)
                ->whereHas('payments', fn ($q) => $q->where('status', Payment::STATUS_PAID))
                ->get()
                ->sum(fn ($b) => $b->platformCommission()),
        ];

        $complaintCounts = [
            'open' => Complaint::where('status', 'open')->count(),
            'in_review' => Complaint::where('status', 'in_review')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
        ];

        $recentBookings = Booking::with(['customer.user', 'caregiver.user'])
            ->latest()
            ->limit(5)
            ->get();

        return compact('userCounts', 'bookingCounts', 'paymentTotals', 'complaintCounts', 'recentBookings');
    }
}
