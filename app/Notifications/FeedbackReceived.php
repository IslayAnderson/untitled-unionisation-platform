<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Feedback about the platform, sent to config('privacy.email'). If they gave
 * an email address, replying goes to them.
 */
class FeedbackReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $name, public ?string $email, public string $details) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $from = $this->name ?? 'Someone';

        $message = (new MailMessage)->subject("Feedback from {$from}");

        if ($this->email) {
            $message->replyTo($this->email, $this->name);
        }

        return $message
            ->line($from.($this->email ? " ({$this->email})" : '').' sent feedback about '.config('app.name').':')
            ->line($this->details)
            ->line($this->email ? 'Reply to this email to answer them.' : 'They didn\'t leave an email address, so you can\'t reply.');
    }
}
