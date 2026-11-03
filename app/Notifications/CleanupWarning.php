<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an account holder that their account, or their whole union, will be
 * deleted unless they log in by the deadline. See docs/architecture/admin.md.
 */
class CleanupWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $reason, public CarbonInterface $deadline, public ?string $union = null)
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
        $date = $this->deadline->format('j F Y');

        $message = (new MailMessage)
            ->subject($this->union ? "{$this->union} will be deleted on {$date}" : "Your account will be deleted on {$date}")
            ->line($this->reason);

        if ($this->union) {
            $message->line("To keep the union, log in before {$date}. If nobody from the union logs in by then, we'll delete it and everything in it, including your account.");
        } else {
            $message->line("To keep your account, log in before {$date}. If you don't, we'll delete it and the details you gave us.");
        }

        return $message
            ->action('Log in', route('login'))
            ->line('If you no longer need it, you don\'t have to do anything.');
    }
}
