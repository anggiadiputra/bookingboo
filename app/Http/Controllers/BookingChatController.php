<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Chat customer-caregiver terkait booking (polling MVP).
 */
class BookingChatController extends Controller
{
    public const CHAT_ACTIVE_STATUSES = [
        Booking::STATUS_REQUESTED,
        Booking::STATUS_ACCEPTED,
        Booking::STATUS_CONFIRMED,
        Booking::STATUS_IN_PROGRESS,
    ];

    /** Cek apakah user terlibat dalam booking; return perannya. */
    private function participantOf(Booking $booking, Request $request): ?string
    {
        $user = $request->user();
        $caregiver = $user->caregiver;
        $customer = $user->customer;

        if ($caregiver && $booking->caregiver_id === $caregiver->id) {
            return 'caregiver';
        }

        if ($customer && $booking->customer_id === $customer->id) {
            return 'customer';
        }

        return null;
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $role = $this->participantOf($booking, $request);
        if (! $role) {
            abort(403);
        }

        $booking->load(['customer.user', 'caregiver.user']);

        $other = $role === 'customer'
            ? $booking->caregiver->user->name
            : $booking->customer->user->name;

        return response()->json([
            'role' => $role,
            'other' => $other,
            'status' => $booking->status,
            'can_send' => in_array($booking->status, self::CHAT_ACTIVE_STATUSES, true),
        ]);
    }

    public function fetch(Request $request, Booking $booking): JsonResponse
    {
        $role = $this->participantOf($booking, $request);
        if (! $role) {
            abort(403);
        }

        $after = (int) $request->query('after', 0);

        return DB::transaction(function () use ($booking, $request, $after) {
            $messages = $booking->messages()
                ->with('sender')
                ->where('id', '>', $after)
                ->orderBy('id')
                ->limit(500)
                ->get()
                ->map(fn (Message $m) => [
                    'id' => $m->id,
                    'sender_id' => $m->sender_id,
                    'mine' => $m->sender_id === $request->user()->id,
                    'sender_name' => $m->sender->name,
                    'content' => $m->content,
                    'created_at' => $m->created_at->format('d M Y, H:i'),
                ]);

            $maxId = $messages->max('id') ?? $after;

            if ($maxId > $after) {
                $booking->messages()
                    ->where('sender_id', '!=', $request->user()->id)
                    ->where('id', '<=', $maxId)
                    ->whereNull('read_at')
                    ->update(['read_at' => now(), 'read_status' => 'read']);
            }

            return response()->json(['messages' => $messages, 'last_id' => $maxId]);
        });
    }

    public function send(Request $request, Booking $booking): JsonResponse
    {
        $role = $this->participantOf($booking, $request);
        if (! $role) {
            abort(403);
        }

        if (! in_array($booking->status, self::CHAT_ACTIVE_STATUSES, true)) {
            return response()->json(['message' => 'Chat tidak lagi aktif untuk booking ini.'], 409);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'booking_id' => $booking->id,
            'sender_id' => $request->user()->id,
            'content' => $validated['body'],
            'read_status' => 'unread',
        ]);

        return response()->json([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'mine' => true,
            'sender_name' => $request->user()->name,
            'content' => $message->content,
            'created_at' => $message->created_at->format('d M Y, H:i'),
        ], 201);
    }
}
