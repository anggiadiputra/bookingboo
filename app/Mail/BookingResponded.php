<?php

namespace App\Mail;

use App\Models\Booking;

/**
 * Notifikasi ke customer: booking diterima atau ditolak caregiver.
 */
class BookingResponded extends BookingMail
{
    public function __construct(Booking $booking, public bool $accepted)
    {
        parent::__construct($booking);
    }

    protected function subjectLine(): string
    {
        return $this->accepted
            ? 'Booking Diterima: '.$this->booking->booking_code
            : 'Booking Ditolak: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        return $this->accepted
            ? ['Caregiver telah menerima booking Anda.', 'Silakan lanjutkan ke pembayaran pada halaman invoice.']
            : ['Mohon maaf, caregiver menolak booking Anda.', 'Anda dapat mencari caregiver lain pada halaman cari caregiver.'];
    }
}
