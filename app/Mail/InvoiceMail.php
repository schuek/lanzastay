<?php

namespace App\Mail;

use App\Models\Habitacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Order>|\Illuminate\Database\Eloquent\Collection  $orders
     */
    public function __construct(
        public Habitacion $room,
        public Collection $orders,
        public string $pdfContent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu factura de estancia en LanzaStay - Habitación '.$this->room->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'room' => $this->room,
                'orders' => $this->orders,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, 'Factura_LanzaStay.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
