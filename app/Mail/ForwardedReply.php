<?php

namespace App\Mail;

use App\Models\UnionMail;
use App\Services\InboundMail\InboundEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A copy of an email reply to union mail, for the union's own inbox. Sent
 * straight away rather than queued, so attachments aren't serialised.
 */
class ForwardedReply extends Mailable
{
    public function __construct(
        public UnionMail $unionMail,
        public InboundEmail $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), $this->unionMail->building->name),
            subject: 'Reply from '.($this->email->fromName ?: $this->email->fromEmail).': '.$this->unionMail->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.forwarded-reply-text',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $attachment) => Attachment::fromData(fn () => $attachment['contents'], $attachment['filename'])
                ->withMime($attachment['content_type']),
            $this->email->attachments,
        );
    }
}
