<?php

namespace App\Mail;

use App\Models\Booking;

/**
 * Notifikasi: komplain sudah ditangani staf (resolved/rejected/in_review).
 */
class ComplaintUpdated extends ComplaintMail
{
    public function __construct(Booking $booking, public string $complaintStatus, public ?string $resolution = null)
    {
        parent::__construct($booking);
    }

    protected function subjectLine(): string
    {
        return 'Komplain Diperbarui: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        $lines = ['Status komplain Anda kini: '.$this->complaintStatus.'.'];

        if ($this->resolution) {
            $lines[] = 'Resolusi: '.$this->resolution;
        }

        return $lines;
    }
}
