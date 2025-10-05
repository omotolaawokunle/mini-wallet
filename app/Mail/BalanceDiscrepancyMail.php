<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;

class BalanceDiscrepancyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $flaggedUsers
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Balance Discrepancy Alert - ' . $this->flaggedUsers->count() . ' User(s) Flagged',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.balance-discrepancy',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

