<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catatan privat caregiver ke customer pada booking selesai.
 * Reuse tabel reviews dengan visibility=private (caregiver->customer).
 */
class CaregiverNoteController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        $caregiver = $request->user()->caregiver;

        if (! $caregiver || $booking->caregiver_id !== $caregiver->id) {
            abort(403);
        }
        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return back()->withErrors(['note' => 'Catatan hanya dapat diberikan pada booking yang sudah selesai.']);
        }
        if ($booking->reviews()->where('visibility', Review::VISIBILITY_PRIVATE)->exists()) {
            return back()->withErrors(['note' => 'Booking ini sudah Anda buat catatan sebelumnya.']);
        }
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'note' => ['required', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($booking, $validated): void {
            Review::create([
                'booking_id' => $booking->id,
                'reviewer_id' => $booking->caregiver->user_id,
                'reviewee_id' => $booking->customer->user_id,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['note'],
                'visibility' => Review::VISIBILITY_PRIVATE,
            ]);
        });

        return back()->with('status', 'Catatan privat tersimpan. Hanya Anda yang dapat melihatnya.');
    }
}
