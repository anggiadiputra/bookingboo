<?php

namespace App\Mail;

/**
 * Notifikasi ke caregiver: booking baru diajukan.
 */
class BookingRequested extends BookingMail
{
    protected function subjectLine(): string
    {
        return 'Booking Baru: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        return [
            'Ada pengajuan booking baru untuk Anda.',
            'Silakan buka dashboard untuk menerima atau menolak booking.',
        ];
    }
}
