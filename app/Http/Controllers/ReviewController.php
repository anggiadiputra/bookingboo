<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Review customer ke caregiver setelah booking selesai.
 * Review tampil publik pada profil caregiver; rating caregiver dihitung ulang.
 */
class ReviewController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        if ($booking->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return back()->withErrors([
                'review' => 'Review hanya dapat diberikan pada booking yang sudah selesai.',
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        // Transaksi + kunci baris booking + indeks unik (booking_id, reviewer_id)
        // menjamin satu review per reviewer per booking, termasuk saat dua submit
        // simultan (klik ganda / dobel tab).
        $created = DB::transaction(function () use ($booking, $validated): bool {
            Booking::whereKey($booking->id)->lockForUpdate()->first();

            if ($booking->reviews()->where('reviewer_id', $booking->customer->user_id)->exists()) {
                return false;
            }

            Review::create([
                'booking_id' => $booking->id,
                'reviewer_id' => $booking->customer->user_id,
                'reviewee_id' => $booking->caregiver->user_id,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'visibility' => Review::VISIBILITY_PUBLIC,
            ]);

            // Hitung ulang rating rata-rata caregiver dari semua review publik.
            $average = (float) Review::where('reviewee_id', $booking->caregiver->user_id)
                ->where('visibility', Review::VISIBILITY_PUBLIC)
                ->where('status', Review::STATUS_PUBLISHED)
                ->avg('rating');

            $booking->caregiver->update(['rating' => round($average, 2)]);

            return true;
        });

        if (! $created) {
            return back()->withErrors([
                'review' => 'Booking ini sudah Anda review sebelumnya.',
            ]);
        }

        return back()->with('status', 'Terima kasih! Review Anda telah dipublikasikan.');
    }
}
