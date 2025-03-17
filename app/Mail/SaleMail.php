<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $sale;
    /**
     * Create a new message instance.
     */
    public function __construct($user,$sale)
    {
        $this->user = $user;
        $this->sale = $sale;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Gracias por tu compra '.$this->user->name.' '.$this->user->surname,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = $this->user;
        $sale = $this->sale;
        return new Content(
            view: 'mail.sale',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
