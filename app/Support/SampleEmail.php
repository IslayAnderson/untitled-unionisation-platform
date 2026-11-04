<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

/**
 * One of the platform's emails, with made-up or demo details: see SampleEmails.
 */
final readonly class SampleEmail
{
    /**
     * @param  object  $notifiable  Who a notification goes to: an on-demand address, or a user for Laravel's own email.
     */
    public function __construct(
        public string $slug,
        public string $label,
        public string $recipient,
        public Mailable|Notification $message,
        public object $notifiable,
    ) {}

    public function subject(): string
    {
        return $this->message instanceof Mailable
            ? $this->message->envelope()->subject
            : $this->message->toMail($this->notifiable)->subject;
    }

    public function html(): string
    {
        return $this->message instanceof Mailable
            ? $this->message->render()
            : (string) $this->message->toMail($this->notifiable)->render();
    }
}
