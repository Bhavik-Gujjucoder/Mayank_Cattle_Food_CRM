<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $backupFilename,
        public string $backupPath,
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'System backup created — '.$this->backupFilename,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup.created',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! is_file($this->backupPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->backupPath)
                ->as($this->backupFilename),
        ];
    }
}
