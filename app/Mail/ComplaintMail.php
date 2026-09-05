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
 * Mailable dasar notifikasi komplain.
 */
abstract class ComplaintMail extends Mailable implements ShouldQueue
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
                'info' => [
                    'Kode Booking' => $this->booking->booking_code,
                    'Status Booking' => $this->booking->label(),
                ],
            ],
        );
    }

    abstract protected function bodyLines(): array;
}
