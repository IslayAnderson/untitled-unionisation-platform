<?php

namespace App\Mail;

use App\Models\Building;
use App\Models\UnionMailAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message from a union to an external contact. It names the union only,
 * never the member who wrote it. The body is HTML cleaned by RichText.
 */
class UnionMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Building $building,
        public string $messageSubject,
        public string $messageBody,
        public ?string $replyAddress = null,
        /** @var EloquentCollection<int, UnionMailAttachment>|null */
        public ?EloquentCollection $files = null,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $replyTo = $this->replyAddress ?? $this->building->contact_email;

        return new Envelope(
            from: new Address(config('mail.from.address'), $this->building->name),
            replyTo: $replyTo ? [new Address($replyTo, $this->building->name)] : [],
            subject: $this->messageSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.union-message',
            text: 'emails.union-message-text',
            with: ['acceptsReplies' => (bool) ($this->replyAddress ?? $this->building->contact_email)],
        );
    }

    /**
     * @return list<UnionMailAttachment>
     */
    public function attachments(): array
    {
        return $this->files?->all() ?? [];
    }
}
