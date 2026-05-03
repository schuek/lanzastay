<?php

namespace App\Mail;

use App\Models\Habitacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Habitacion $room,
        public string $pdfOutput,
    ) {
        $this->attachData($this->pdfOutput, 'Factura-LanzaStay.pdf', [
            'mime' => 'application/pdf',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu factura de estancia en LanzaStay',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'room' => $this->room,
            ],
        );
    }

}
