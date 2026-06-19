<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DispatchCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        $orderId = $this->payload['order']['unique_order_id'] ?? 'Order';

        return new Envelope(
            subject: 'Dispatch recorded — ' . $orderId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dispatch.created',
        );
    }
}
