<?php

namespace App\Support;

use App\Enums\ExportReason;
use App\Enums\UnionMailKind;
use App\Mail\ForwardedReply;
use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Referendum;
use App\Models\UnionMail;
use App\Models\User;
use App\Notifications\CleanupWarning;
use App\Notifications\FeedbackReceived;
use App\Notifications\MailApprovalRequested;
use App\Notifications\ReferendumDecided;
use App\Notifications\ReferendumOpened;
use App\Notifications\UnionDataExported;
use App\Services\InboundMail\InboundEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

/**
 * One of every email the platform sends, for `php artisan mail:samples` and the
 * demo's emails page. For a saved union (the demo) they use its own mail and
 * referendums where it has them, so links lead somewhere; otherwise made-up ones
 * that aren't saved.
 */
final class SampleEmails
{
    /**
     * @return array<string, SampleEmail> Keyed by slug
     */
    public static function for(Building $building, string $address): array
    {
        $to = Notification::route('mail', $address);
        $mail = self::unionMail($building);
        $openReferendum = self::referendum($building, open: true);
        $decidedReferendum = self::referendum($building, open: false);
        $deadline = now()->addWeek();

        $reply = new InboundEmail(
            id: 'sample',
            messageId: null,
            fromEmail: 'lettings@example.com',
            fromName: 'Example Lettings',
            recipients: [],
            subject: 'Re: '.$mail->subject,
            text: "Thank you for your email.\n\nAn engineer will visit on Monday to look at the lift. We'll let you know when it's fixed.\n\nExample Lettings",
            html: null,
        );

        $samples = [
            ['Union mail to a landlord', 'The landlord or agent, from the union', new UnionMessage(
                $building,
                'Repairs to the lift',
                '<p>Dear Example Lettings,</p><p>The lift in our building has been out of order for <strong>three weeks</strong>. Tenants with prams, shopping and mobility needs are using the stairs.</p><ul><li>Please fix it within 7 days.</li><li>Tell us when an engineer will visit.</li></ul><p>We look forward to your reply.</p>',
                $address,
            )],
            ['Landlord\'s reply, forwarded to the union\'s inbox', 'The union\'s inbox for replies', new ForwardedReply($mail, $reply)],
            ['Landlord\'s reply, forwarded to members', 'Every member, when the union has no inbox for replies', new ForwardedReply($mail, $reply, toMembers: true)],
            ['Mail needs approval', 'Members asked to approve a mail', new MailApprovalRequested($mail)],
            ['Referendum opened', 'Every member', new ReferendumOpened($openReferendum)],
            ['Referendum decided', 'Every member', new ReferendumDecided($decidedReferendum)],
            ...array_map(fn (ExportReason $reason) => [
                match ($reason) {
                    ExportReason::UnionDeleted => 'Records of a deleted union',
                    ExportReason::Left => 'Records for a member who left',
                    ExportReason::Removed => 'Records for a member who was removed',
                    ExportReason::AccountDeleted => 'Records for a member who deleted their account',
                },
                'The member',
                new UnionDataExported($building->name, route('home'), now()->addDays(30), reason: $reason),
            ], ExportReason::cases()),
            ['Account clean-up warning', 'An account holder who hasn\'t confirmed their email', new CleanupWarning('You signed up but didn\'t confirm your email address.', $deadline)],
            ['Union clean-up warning', 'Everyone in a union an admin is closing', new CleanupWarning("The platform's administrators are closing {$building->name}.", $deadline, $building->name)],
            ['Feedback', 'The platform\'s privacy contact', new FeedbackReceived('Sam Sample', $address, 'The vouching page made it much easier to explain to my neighbours.')],
            ['Anonymous feedback', 'The platform\'s privacy contact', new FeedbackReceived(null, null, 'Could the flyers come in large print too?')],
        ];

        $emails = [];

        foreach ($samples as [$label, $recipient, $message]) {
            $slug = str($label)->slug()->value();
            $emails[$slug] = new SampleEmail($slug, $label, $recipient, $message, $to);
        }

        // Laravel's own email goes to a user, not an address.
        $emails['confirm-your-email-address'] = new SampleEmail(
            'confirm-your-email-address',
            'Confirm your email address',
            'Someone who has just signed up',
            new VerifyEmail,
            (new User)->forceFill(['id' => 0, 'email' => $address]),
        );

        return $emails;
    }

    private static function unionMail(Building $building): UnionMail
    {
        return ($building->exists ? $building->unionMails()->whereNotNull('bulletin_id')->latest('id')->first() : null)
            ?? (new UnionMail)->forceFill([
                'id' => 0,
                'bulletin_id' => 0,
                'kind' => UnionMailKind::Instant,
                'subject' => 'Repairs to the lift',
                'expires_at' => now()->addDays(2),
            ])->setRelation('building', $building);
    }

    private static function referendum(Building $building, bool $open): Referendum
    {
        $saved = $building->exists
            ? $building->referendums()->when($open, fn ($query) => $query->whereNull('closed_at'), fn ($query) => $query->whereNotNull('closed_at'))->latest('id')->first()
            : null;

        return $saved ?? (new Referendum)->forceFill([
            'id' => 0,
            'referendable_type' => 'policy',
            'closes_at' => now()->addDays(14),
        ])->setRelation('building', $building);
    }
}
