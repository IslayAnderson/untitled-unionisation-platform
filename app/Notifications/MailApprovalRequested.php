<?php

namespace App\Notifications;

use App\Models\UnionMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a member a union mail needs their approval. Deliberately leaves out
 * the mail's content: members read it on the platform.
 */
class MailApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UnionMail $mail)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $union = $this->mail->building->name;

        return (new MailMessage)
            ->subject("{$union}: mail needs your approval")
            ->line("A member of {$union} has written a mail that needs your approval before it is sent ({$this->mail->kind->label()}).")
            ->action('Review the mail', route('mail.show', $this->mail))
            ->line("It expires {$this->mail->expires_at->format('j M Y, H:i')} if not approved.");
    }
}
