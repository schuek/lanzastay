<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StayCheckoutMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $pdfContent,
        public string $roomNumber,
        public float $total,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura de estancia LanzaStay',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.stay-checkout',
            with: [
                'roomNumber' => $this->roomNumber,
                'total' => $this->total,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                "Factura-LanzaStay-Habitacion-{$this->roomNumber}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
