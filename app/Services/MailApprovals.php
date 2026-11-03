<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\UnionMailKind;
use App\Enums\UnionMailStatus;
use App\Exceptions\ImageRejected;
use App\Exceptions\UnionMailRefused;
use App\Mail\UnionMessage;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\MailingList;
use App\Models\UnionMail;
use App\Notifications\MailApprovalRequested;
use App\Support\ReplyAddress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Instant mail and bulletin mail: creating, approving, sending and expiring.
 * Rules are in docs/architecture/mail.md; thresholds come from the constitution.
 */
class MailApprovals
{
    public function __construct(private MailAttachments $attachments) {}

    /**
     * @param  list<UploadedFile>  $attachments
     *
     * @throws UnionMailRefused
     * @throws ImageRejected
     */
    public function createInstant(Contact $author, MailingList $list, string $subject, string $body, array $attachments = []): UnionMail
    {
        $rules = $author->building->rules();
        $members = $this->membersOtherThan($author);
        $memberCount = $members->count() + 1;

        $required = $this->percentOf($memberCount, $rules->instantMailApprovalPercent);
        $poolSize = max($required, $this->percentOf($memberCount, $rules->instantMailApproverPercent));

        return $this->create($author, $list, [
            'kind' => UnionMailKind::Instant,
            'subject' => $subject,
            'body' => $body,
            'approvals_required' => $required,
            'expires_at' => now()->addHours($rules->instantMailExpiryHours),
        ], $members->shuffle()->take($poolSize), $attachments);
    }

    /**
     * Bulletin mail. The author can rework the subject and message from the
     * bulletin (for example after discussion in its replies); otherwise the
     * bulletin's title and text are used.
     *
     * @param  list<UploadedFile>  $attachments
     *
     * @throws UnionMailRefused
     * @throws ImageRejected
     */
    public function createFromBulletin(Contact $author, Bulletin $bulletin, MailingList $list, ?string $subject = null, ?string $body = null, array $attachments = []): UnionMail
    {
        if ($bulletin->author_id !== $author->id) {
            throw new UnionMailRefused('Only the author of a bulletin can turn it into mail.');
        }

        if ($bulletin->hasActiveMail()) {
            throw new UnionMailRefused('This bulletin already has mail waiting for approval or sent.');
        }

        $rules = $author->building->rules();
        $members = $this->membersOtherThan($author);

        return $this->create($author, $list, [
            'kind' => UnionMailKind::Bulletin,
            'bulletin_id' => $bulletin->id,
            'subject' => $subject ?? $bulletin->title,
            'body' => $body ?? $bulletin->rich_text,
            'approvals_required' => $this->percentOf($members->count() + 1, $rules->bulletinMailApprovalPercent),
            'expires_at' => now()->addHours($rules->bulletinMailExpiryHours),
        ], $members, $attachments);
    }

    /**
     * Record an approver's decision, then send or reject the mail if that settles it.
     *
     * @throws UnionMailRefused
     */
    public function decide(Contact $approver, UnionMail $mail, ApprovalDecision $decision): UnionMail
    {
        $this->expireIfOverdue($mail);

        return DB::transaction(function () use ($approver, $mail, $decision) {
            // Serialise decisions on the same mail so it is sent at most once.
            $mail = UnionMail::lockForUpdate()->findOrFail($mail->id);

            if (! $mail->isPending()) {
                throw new UnionMailRefused("This mail is no longer waiting for approval ({$mail->status->label()}).");
            }

            $seat = $mail->approvers()->where('contact_id', $approver->id)->first();

            if (! $seat) {
                throw new UnionMailRefused("You're not one of this mail's approvers.");
            }

            if ($seat->decision) {
                throw new UnionMailRefused("You've already decided on this mail.");
            }

            $seat->update(['decision' => $decision, 'decided_at' => now()]);
            $mail->load('approvers');

            if ($mail->approvalCount() >= $mail->approvals_required) {
                $this->send($mail);
            } elseif (! $mail->canStillPass()) {
                $mail->update(['status' => UnionMailStatus::Rejected, 'decided_at' => now()]);
            }

            return $mail;
        });
    }

    /**
     * @throws UnionMailRefused
     */
    public function withdraw(Contact $author, UnionMail $mail): void
    {
        if ($mail->author_contact_id !== $author->id) {
            throw new UnionMailRefused('Only the author can withdraw this mail.');
        }

        $updated = UnionMail::whereKey($mail->id)
            ->pending()
            ->update(['status' => UnionMailStatus::Withdrawn, 'decided_at' => now()]);

        if (! $updated) {
            throw new UnionMailRefused('This mail is no longer waiting for approval.');
        }
    }

    /**
     * Mark every overdue pending mail as expired.
     */
    public function expireOverdue(): int
    {
        return UnionMail::pending()
            ->where('expires_at', '<=', now())
            ->update(['status' => UnionMailStatus::Expired, 'decided_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  Collection<int, Contact>  $approvers
     * @param  list<UploadedFile>  $attachments
     *
     * @throws UnionMailRefused
     * @throws ImageRejected
     */
    private function create(Contact $author, MailingList $list, array $attributes, Collection $approvers, array $attachments): UnionMail
    {
        if ($list->building_id !== $author->building_id) {
            throw new UnionMailRefused('That mailing list belongs to another union.');
        }

        if ($this->recipients($list)->isEmpty()) {
            throw new UnionMailRefused('Nobody on this mailing list has an email address.');
        }

        if ($approvers->count() < $attributes['approvals_required']) {
            throw new UnionMailRefused(sprintf(
                'This needs %d %s, but your union only has %d other %s.',
                $attributes['approvals_required'],
                str('approval')->plural($attributes['approvals_required']),
                $approvers->count(),
                str('member')->plural($approvers->count()),
            ));
        }

        $stored = [];

        try {
            return DB::transaction(function () use ($author, $list, $attributes, $approvers, $attachments, &$stored) {
                $mail = UnionMail::create([
                    ...$attributes,
                    'building_id' => $author->building_id,
                    'mailing_list_id' => $list->id,
                    'author_contact_id' => $author->id,
                    'reply_token' => ReplyAddress::newToken(),
                    'status' => UnionMailStatus::Pending,
                ]);

                foreach ($attachments as $file) {
                    $stored[] = $this->attachments->store($author, $mail, $file);
                }

                $mail->approvers()->createMany($approvers->map(fn (Contact $contact) => ['contact_id' => $contact->id]));

                Notification::send(
                    $approvers->load('user')->pluck('user')->filter(),
                    new MailApprovalRequested($mail),
                );

                return $mail;
            });
        } catch (Throwable $e) {
            // The rows were rolled back, but the files they pointed to were already stored.
            array_map($this->attachments->discard(...), $stored);

            throw $e;
        }
    }

    /**
     * Send to every recipient separately, and post instant mail as a bulletin.
     * Replies go to the mail's reply address, so they can be added to the bulletin.
     */
    private function send(UnionMail $mail): void
    {
        $recipients = $this->recipients($mail->mailingList);

        // Mail created before reply tokens existed doesn't have one yet.
        $mail->reply_token ??= ReplyAddress::newToken();

        $recipients->each(fn (Contact $recipient) => Mail::to($recipient->email, $recipient->name)
            ->queue(new UnionMessage($mail->building, $mail->subject, $mail->body, ReplyAddress::for($mail->reply_token), $mail->attachments)));

        if ($mail->kind === UnionMailKind::Instant) {
            $bulletin = $mail->building->bulletins()->create([
                'author_id' => $mail->author_contact_id,
                'title' => $mail->subject,
                'rich_text' => $mail->body,
                'published_at' => now(),
            ]);

            $mail->bulletin_id = $bulletin->id;
        }

        $mail->fill([
            'status' => UnionMailStatus::Sent,
            'decided_at' => now(),
            'recipient_count' => $recipients->count(),
        ])->save();
    }

    private function expireIfOverdue(UnionMail $mail): void
    {
        if ($mail->isPending() && $mail->isOverdue()) {
            $mail->update(['status' => UnionMailStatus::Expired, 'decided_at' => now()]);

            throw new UnionMailRefused('This mail expired before it was approved.');
        }
    }

    /**
     * @return Collection<int, Contact>
     */
    private function membersOtherThan(Contact $author): Collection
    {
        return $author->building->contacts()
            ->confirmedTenants()
            ->enrolled()
            ->whereKeyNot($author->id)
            ->get();
    }

    /**
     * @return Collection<int, Contact>
     */
    private function recipients(?MailingList $list): Collection
    {
        return $list?->contacts()->external()->whereNotNull('email')->get() ?? collect();
    }

    /**
     * A percentage of the members, rounded up, and never less than 1.
     */
    private function percentOf(int $members, int $percent): int
    {
        return max(1, intdiv($members * $percent + 99, 100));
    }
}
