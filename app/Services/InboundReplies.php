<?php

namespace App\Services;

use App\Enums\UnionMailStatus;
use App\Mail\ForwardedReply;
use App\Models\Contact;
use App\Models\Reply;
use App\Models\UnionMail;
use App\Services\InboundMail\InboundEmail;
use App\Services\InboundMail\InboundMailbox;
use App\Support\EmailReplyText;
use App\Support\ReplyAddress;
use App\Support\RichText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Turns email replies to union mail into replies on the mail's bulletin, and
 * forwards a copy to the union's own inbox. See docs/architecture/mail.md.
 */
class InboundReplies
{
    public function __construct(private InboundMailbox $mailbox) {}

    /**
     * Process every waiting email, returning how many replies were added.
     */
    public function fetch(): int
    {
        $added = 0;

        foreach ($this->mailbox->unprocessed() as $email) {
            if ($this->process($email)) {
                $added++;
            }

            $this->mailbox->markProcessed($email);
        }

        return $added;
    }

    private function process(InboundEmail $email): bool
    {
        $mail = $this->unionMailFor($email);

        if (! $mail) {
            Log::info('Inbound email is not a reply to sent union mail.', ['id' => $email->id, 'message_id' => $email->messageId]);

            return false;
        }

        if ($email->messageId && Reply::where('message_id', $email->messageId)->exists()) {
            return false;
        }

        $mail->bulletin->replies()->create([
            'union_mail_id' => $mail->id,
            'contact_id' => $this->senderContact($mail, $email)?->id,
            'from_name' => $email->fromName,
            'from_email' => $email->fromEmail,
            'message_id' => $email->messageId,
            'rich_text' => $this->richText($mail, $email),
            'published_at' => $email->date ?? now(),
        ]);

        $this->forwardToUnion($mail, $email);

        return true;
    }

    private function unionMailFor(InboundEmail $email): ?UnionMail
    {
        $token = ReplyAddress::tokenFrom($email->recipients);

        if (! $token) {
            return null;
        }

        return UnionMail::with(['bulletin', 'building', 'mailingList'])
            ->where('reply_token', $token)
            ->where('status', UnionMailStatus::Sent)
            ->whereNotNull('bulletin_id')
            ->first();
    }

    /**
     * The contact on the mail's list who sent the reply, if the sender matches one.
     */
    private function senderContact(UnionMail $mail, InboundEmail $email): ?Contact
    {
        return $mail->mailingList?->contacts()
            ->external()
            ->whereRaw('lower(email) = ?', [strtolower($email->fromEmail)])
            ->first();
    }

    /**
     * Only the new part of the reply, as escaped paragraphs; never the sender's HTML.
     */
    private function richText(UnionMail $mail, InboundEmail $email): string
    {
        $text = $email->text ?? RichText::toPlainText(RichText::clean($email->html ?? ''));
        $visible = EmailReplyText::strip($text) ?: trim($text);
        $html = RichText::fromPlainText($visible) ?: '<p><em>This reply has no text.</em></p>';

        if ($count = count($email->attachments)) {
            $note = sprintf(
                'This reply had %d %s, forwarded to %s.',
                $count,
                str('attachment')->plural($count),
                $mail->building->contact_email ? 'the union’s inbox' : 'every member by email',
            );

            $html .= '<p><em>'.e($note).'</em></p>';
        }

        return $html;
    }

    /**
     * Send a full copy, with attachments, to the union's inbox, or to every
     * member separately if the union hasn't set one.
     */
    private function forwardToUnion(UnionMail $mail, InboundEmail $email): void
    {
        $building = $mail->building;
        $recipients = $building->contact_email
            ? collect([[$building->contact_email, $building->name]])
            : $building->contacts()->confirmedTenants()->enrolled()->with('user')->get()
                ->filter(fn (Contact $member) => $member->user?->email)
                ->map(fn (Contact $member) => [$member->user->email, $member->name]);

        foreach ($recipients as [$address, $name]) {
            try {
                Mail::to($address, $name)->send(new ForwardedReply($mail, $email, toMembers: ! $building->contact_email));
            } catch (Throwable $e) {
                // The reply is already on the bulletin; a failed copy shouldn't stop the rest.
                report($e);
            }
        }
    }
}
