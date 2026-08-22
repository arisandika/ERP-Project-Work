<?php
// app/Mail/CRM/FollowUpMailable.php

namespace App\Mail;

use App\Models\CRM\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Deal $deal,
        public string $subject,
        public string $body
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.crm.follow-up', // Buat blade view HTML sederhana
            with: [
                'deal' => $this->deal,
                'body' => $this->body,
            ],
        );
    }

    // Attachment/notes jika perlu
    public function attachments(): array
    {
        return [];
    }
}
