<?php

namespace App\Mail;

use App\Models\Booking;

/**
 * Notifikasi: komplain baru diajukan peserta booking.
 */
class ComplaintFiled extends ComplaintMail
{
    protected function subjectLine(): string
    {
        return 'Komplain Baru: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        return [
            'Komplain baru telah diajukan terkait booking ini.',
            'Tim support kami akan meninjau dan menindaklanjuti.',
        ];
    }
}
