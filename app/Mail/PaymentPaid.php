<?php

namespace App\Mail;

/**
 * Notifikasi ke customer & caregiver: pembayaran lunas diterima.
 */
class PaymentPaid extends BookingMail
{
    protected function subjectLine(): string
    {
        return 'Pembayaran Diterima: '.$this->booking->booking_code;
    }

    protected function bodyLines(): array
    {
        return [
            'Pembayaran sebesar Rp '.number_format($this->booking->total_amount, 0, ',', '.').' telah diterima.',
            'Booking kini terkonfirmasi dan layanan dapat berjalan sesuai jadwal.',
        ];
    }
}
