<?php

namespace App\Mail;

use App\Models\Booking;

/**
 * Notifikasi ke customer & caregiver: booking dibatalkan.
 */
class BookingCancelled extends BookingMail
{
    public function __construct(Booking $booking, public ?string $reason = null, public float $refundAmount = 0.0)
    {
        parent::__construct($booking);
    }

    protected function subjectLine(): string
    {
        return 'Booking Dibatalkan: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        $lines = ['Booking telah dibatalkan.'.($this->reason ? ' Alasan: '.$this->reason : '')];

        if ($this->refundAmount > 0) {
            $lines[] = 'Refund sebesar Rp '.number_format($this->refundAmount, 0, ',', '.').' akan diproses ke metode pembayaran Anda.';
        }

        return $lines;
    }
}
