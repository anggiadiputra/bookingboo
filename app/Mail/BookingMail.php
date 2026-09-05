<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable dasar untuk notifikasi terkait booking.
 * Dikirim via queue agar tidak memblokir request.
 */
abstract class BookingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    abstract protected function subjectLine(): string;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.booking-notification',
            with: [
                'subject' => $this->subjectLine(),
                'lines' => $this->bodyLines(),
                'info' => $this->bookingInfo(),
            ],
        );
    }

    /** Baris isi email per notifikasi. */
    abstract protected function bodyLines(): array;

    /**
     * Info booking standar untuk template.
     *
     * @return array<string, string>
     */
    protected function bookingInfo(): array
    {
        return [
            'Kode Booking' => $this->booking->booking_code,
            'Pasien/Keluarga' => $this->booking->customer->user->name,
            'Caregiver' => $this->booking->caregiver->user->name,
            'Waktu Layanan' => $this->booking->start_time->format('d M Y H:i').' - '.$this->booking->end_time->format('H:i'),
            'Total' => 'Rp '.number_format($this->booking->total_amount, 0, ',', '.'),
        ];
    }
}
