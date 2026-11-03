<?php

namespace App\Notifications;

use App\Models\Referendum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a member a referendum has been counted. Like ReferendumOpened, it
 * leaves out what was decided: members read the result on the platform.
 */
class ReferendumDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Referendum $referendum)
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
        $union = $this->referendum->building->name;

        return (new MailMessage)
            ->subject("{$union}: a referendum has been decided")
            ->line("Voting has closed in a referendum in {$union}, and the result is in.")
            ->action('See the result', route('referendums.show', $this->referendum));
    }
}
