<?php

namespace App\Notifications;

use App\Enums\ExportReason;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends a union's records (App\Services\UnionExport) to its members when it's
 * deleted, or to a member who leaves or is removed. Sent to an email address on
 * demand, since the account may have been deleted.
 */
class UnionDataExported extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $union,
        public string $downloadUrl,
        public CarbonInterface $expiresAt,
        public ?string $attachPath = null,
        public ExportReason $reason = ExportReason::UnionDeleted,
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
            ->subject($this->reason->subject($this->union))
            ->line($this->reason->intro($this->union))
            ->line(($this->reason === ExportReason::UnionDeleted ? 'Here is a copy of everything members could see' : 'Here is a copy of everything members can see, as it is today').': the constitution, policies, roles, referendum results, bulletins, discussions, mail and landlord contacts. Each section is a PDF in the zip, and README.txt lists them. Votes were secret, so only totals are included.')
            ->action('Download the records', $this->downloadUrl)
            ->line('The link works until '.$this->expiresAt->format('j F Y').'. Keep the records somewhere safe: they include other members\' names and what they wrote.');

        if ($this->attachPath) {
            $message->line('The records are also attached to this email.')
                ->attach($this->attachPath, ['as' => basename($this->attachPath), 'mime' => 'application/zip']);
        }

        return $message;
    }
}
