<?php

namespace App\Services;

use App\Mail\AttendanceUpdated;
use App\Mail\BookingCancelled;
use App\Mail\BookingRequested;
use App\Mail\BookingResponded;
use App\Mail\ComplaintFiled;
use App\Mail\ComplaintUpdated;
use App\Mail\PaymentPaid;
use App\Mail\PayoutProcessed;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Pusat pengiriman notifikasi email (MVP: log/array driver).
 * Semua email dikirim via queue agar tidak memblokir request.
 */
class NotificationService
{
    /** Booking baru diajukan -> caregiver. */
    public function bookingRequested(Booking $booking): void
    {
        Mail::to($booking->caregiver->user->email)->queue(new BookingRequested($booking));
        $this->inApp($booking->caregiver->user, 'booking', 'Booking baru masuk: '.$booking->booking_code.'. Mohon konfirmasi.');
    }

    /** Booking diterima/ditolak caregiver -> customer. */
    public function bookingResponded(Booking $booking, bool $accepted): void
    {
        Mail::to($booking->customer->user->email)->queue(new BookingResponded($booking, $accepted));
        $this->inApp(
            $booking->customer->user,
            'booking',
            $accepted
                ? 'Booking '.$booking->booking_code.' diterima caregiver dan menunggu pembayaran.'
                : 'Booking '.$booking->booking_code.' ditolak caregiver.',
        );
    }

    /** Pembayaran lunas -> customer & caregiver. */
    public function paymentPaid(Booking $booking): void
    {
        Mail::to($booking->customer->user->email)->queue(new PaymentPaid($booking));
        Mail::to($booking->caregiver->user->email)->queue(new PaymentPaid($booking));
        $this->inApp($booking->customer->user, 'payment', 'Pembayaran booking '.$booking->booking_code.' telah diterima.');
        $this->inApp($booking->caregiver->user, 'payment', 'Pembayaran booking '.$booking->booking_code.' telah dikonfirmasi.');
    }

    /** Booking dibatalkan -> customer & caregiver (dengan info refund bila ada). */
    public function bookingCancelled(Booking $booking, ?string $reason = null, float $refundAmount = 0.0): void
    {
        Mail::to($booking->customer->user->email)->queue(new BookingCancelled($booking, $reason, $refundAmount));
        Mail::to($booking->caregiver->user->email)->queue(new BookingCancelled($booking, $reason, $refundAmount));
        $this->inApp($booking->customer->user, 'booking', 'Booking '.$booking->booking_code.' dibatalkan.');
        $this->inApp($booking->caregiver->user, 'booking', 'Booking '.$booking->booking_code.' dibatalkan oleh customer.');
    }

    /** Check-in/check-out caregiver -> customer. */
    public function attendanceUpdated(Booking $booking, bool $checkedIn): void
    {
        Mail::to($booking->customer->user->email)->queue(new AttendanceUpdated($booking, $checkedIn));
        $this->inApp(
            $booking->customer->user,
            'booking',
            $checkedIn
                ? 'Caregiver sudah check-in untuk booking '.$booking->booking_code.'.'
                : 'Layanan booking '.$booking->booking_code.' telah selesai (check-out).',
        );
    }

    /** Payout diproses admin -> caregiver. */
    public function payoutProcessed(Payout $payout): void
    {
        Mail::to($payout->caregiver->user->email)->queue(new PayoutProcessed($payout));
        $this->inApp($payout->caregiver->user, 'payout', 'Payout Anda sebesar Rp '.number_format($payout->amount, 0, ',', '.').' telah diproses.');
    }

    /** Komplain baru diajukan -> staf support & admin. */
    public function complaintFiled(Complaint $complaint): void
    {
        $recipients = User::whereIn('role', [User::ROLE_SUPPORT, User::ROLE_ADMIN])
            ->where('status', 'active')
            ->get();

        foreach ($recipients as $staff) {
            Mail::to($staff->email)->queue(new ComplaintFiled($complaint->booking));
            $this->inApp($staff, 'complaint', 'Komplain baru untuk booking '.$complaint->booking->booking_code.': '.$complaint->reason);
        }
    }

    /** Komplain ditindaklanjuti staf -> reporter komplain. */
    public function complaintResolved(Complaint $complaint): void
    {
        if ($complaint->reporter) {
            Mail::to($complaint->reporter->email)->queue(
                new ComplaintUpdated($complaint->booking, $complaint->status, $complaint->resolution)
            );
            $this->inApp($complaint->reporter, 'complaint', 'Komplain Anda untuk booking '.$complaint->booking->booking_code.' diperbarui: '.($complaint->resolution ?? $complaint->labelStatus()));
        }
    }

    /** Notifikasi ke satu user (untuk kebutuhan admin). */
    public function notifyUser(User $user, Mailable $mailable): void
    {
        Mail::to($user->email)->queue($mailable);
    }

    /**
     * Tulis notifikasi in-app untuk satu user (fire-and-forget, tidak pernah
     * mengganggu alur utama). Dipakai dari method event di atas.
     */
    public function inApp(User $user, string $type, string $content): void
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'content' => $content,
                'channel' => Notification::CHANNEL_IN_APP,
                'status' => Notification::STATUS_UNREAD,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Jumlah notifikasi in-app belum dibaca milik user.
     */
    public function unreadCount(User $user): int
    {
        return $user->notifications()
            ->where('channel', Notification::CHANNEL_IN_APP)
            ->where('status', Notification::STATUS_UNREAD)
            ->count();
    }
}
