<?php

namespace App\Mail;

use App\Models\Booking;

/**
 * Notifikasi ke customer: check-in/check-out caregiver.
 */
class AttendanceUpdated extends BookingMail
{
    public function __construct(Booking $booking, public bool $checkedIn)
    {
        parent::__construct($booking);
    }

    protected function subjectLine(): string
    {
        return $this->checkedIn
            ? 'Caregiver Check-in: '.$this->booking->booking_code
            : 'Caregiver Check-out: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        return $this->checkedIn
            ? ['Caregiver telah melakukan check-in dan layanan sedang berlangsung.']
            : ['Caregiver telah melakukan check-out. Layanan selesai, terima kasih.'];
    }
}
