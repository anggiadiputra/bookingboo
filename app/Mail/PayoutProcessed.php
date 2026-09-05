<?php

namespace App\Mail;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifikasi ke caregiver: pengajuan pencairan diproses (dibayar/ditolak).
 */
class PayoutProcessed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payout $payout) {}

    public function envelope(): Envelope
    {
        $paid = $this->payout->status === Payout::STATUS_PAID;

        return new Envelope(subject: $paid
            ? 'Pencairan Dibayar: Rp '.number_format($this->payout->amount, 0, ',', '.')
            : 'Pengajuan Pencairan Ditolak');
    }

    public function content(): Content
    {
        $paid = $this->payout->status === Payout::STATUS_PAID;

        $lines = $paid
            ? ['Pencairan sebesar Rp '.number_format($this->payout->amount, 0, ',', '.').' telah ditransfer.']
            : ['Mohon maaf, pengajuan pencairan Anda ditolak.'.($this->payout->admin_note ? ' Catatan admin: '.$this->payout->admin_note : '')];

        return new Content(
            markdown: 'mail.booking-notification',
            with: [
                'subject' => $paid ? 'Pencairan Dibayar' : 'Pengajuan Pencairan Ditolak',
                'lines' => $lines,
                'info' => [
                    'Caregiver' => $this->payout->caregiver->user->name,
                    'Jumlah' => 'Rp '.number_format($this->payout->amount, 0, ',', '.'),
                    'Status' => $this->payout->label(),
                ],
            ],
        );
    }
}
