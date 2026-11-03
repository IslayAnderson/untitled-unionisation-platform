<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a deleted union's members its records (App\Services\UnionExport). Sent
 * to an email address on demand, since their accounts are deleted with it.
 */
class UnionDataExported extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $union,
        public string $downloadUrl,
        public CarbonInterface $expiresAt,
        public ?string $attachPath = null,
    ) {
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
        $message = (new MailMessage)
            ->subject("{$this->union} has been deleted: your union's records")
            ->line("{$this->union} has been deleted from ".config('app.name').', along with your account.')
            ->line('Here is a copy of everything members could see: the constitution, policies, roles, referendum results, bulletins, discussions, mail and landlord contacts. Open index.html in the zip to read it. Votes were secret, so only totals are included.')
            ->action('Download the records', $this->downloadUrl)
            ->line('The link works until '.$this->expiresAt->format('j F Y').'. Keep the records somewhere safe: they include other members\' names and what they wrote.');

        if ($this->attachPath) {
            $message->line('The records are also attached to this email.')
                ->attach($this->attachPath, ['as' => basename($this->attachPath), 'mime' => 'application/zip']);
        }

        return $message;
    }
}
