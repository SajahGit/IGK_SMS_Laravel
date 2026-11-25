<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $changedAt,
        public readonly ?string $ipAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name', 'IGK School').' — Your Password Was Changed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.PasswordChangedConfirmation',
        );
    }
}
