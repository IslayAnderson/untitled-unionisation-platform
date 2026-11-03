<?php

namespace App\Notifications;

use App\Models\Referendum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a member a referendum is open. Like mail approval requests, it leaves
 * out what's being decided: members read it on the platform.
 */
class ReferendumOpened extends Notification implements ShouldQueue
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
        $subject = $this->referendum->isAboutConstitution() ? 'a change to the constitution' : 'a policy';

        return (new MailMessage)
            ->subject("{$union}: a referendum is open")
            ->line("A member of {$union} has proposed {$subject}. Every member gets a secret vote.")
            ->action('Read it and vote', route('referendums.show', $this->referendum))
            ->line("Voting closes {$this->referendum->closes_at->format('j M Y, H:i')}.");
    }
}
