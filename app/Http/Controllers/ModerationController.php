<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel moderasi admin: review dan penonaktifan akun.
 */
class ModerationController extends Controller
{
    public function __construct(private ModerationService $moderation) {}

    /**
     * Daftar review untuk dimoderasi.
     */
    public function reviewsIndex(Request $request): View
    {
        $tab = $request->query('tab', 'all');

        $query = Review::with(['reviewer', 'reviewee', 'booking'])
            ->latest();

        if ($tab === 'published') {
            $query->where('status', Review::STATUS_PUBLISHED);
        } elseif ($tab === 'hidden') {
            $query->where('status', Review::STATUS_HIDDEN);
        }

        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'tab'));
    }

    /**
     * Sembunyikan review dari publik dan hitung ulang rating.
     */
    public function hideReview(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->moderation->hideReview($review, $request->user(), $validated['reason']);

        return back()->with('status', 'Review disembunyikan dari publik.');
    }

    /**
     * Tampilkan kembali review yang disembunyikan.
     */
    public function unhideReview(Request $request, Review $review): RedirectResponse
    {
        $this->moderation->unhideReview($review, $request->user());

        return back()->with('status', 'Review ditampilkan kembali.');
    }

    /**
     * Daftar user untuk moderasi akun (customer dan caregiver).
     */
    public function usersIndex(Request $request): View
    {
        $tab = $request->query('tab', 'all');
        $search = trim((string) $request->query('q', ''));

        $query = User::whereIn('role', [User::ROLE_CUSTOMER, User::ROLE_CAREGIVER])
            ->orderBy('created_at', 'desc');

        if ($tab === 'suspended') {
            $query->where('status', User::STATUS_SUSPENDED);
        } elseif ($tab === 'active') {
            $query->where('status', User::STATUS_ACTIVE);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users', 'tab', 'search'));
    }

    /**
     * Nonaktifkan akun user.
     */
    public function suspendUser(Request $request, User $user): RedirectResponse
    {
        if ($user->isStaff()) {
            abort(403, 'Akun staf tidak dapat dinonaktifkan melalui moderasi akun.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->moderation->suspendUser($user, $request->user(), $validated['reason']);

        return back()->with('status', "Akun {$user->name} telah dinonaktifkan.");
    }

    /**
     * Aktifkan kembali akun user.
     */
    public function reactivateUser(Request $request, User $user): RedirectResponse
    {
        $this->moderation->reactivateUser($user, $request->user());

        return back()->with('status', "Akun {$user->name} telah diaktifkan kembali.");
    }
}
