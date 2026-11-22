<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Error alert for the operators (BQ-021, BQ-173). Sent synchronously on purpose: the queue may be
 * what is failing. The body carries only what the log already holds, which never contains secrets.
 */
class OperationsAlertMail extends Mailable
{
    /**
     * @param  array<string, string>  $details
     */
    public function __construct(public string $level, public string $summary, public array $details) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[BookQu '.config('app.env').'] '.strtoupper($this->level).': '.mb_strimwidth($this->summary, 0, 120, '…'));
    }

    public function content(): Content
    {
        return new Content(text: 'emails.operations-alert');
    }
}
