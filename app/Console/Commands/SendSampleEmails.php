<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Sends one of every email the platform can send, with made-up details, to
 * check how they look in a real inbox. Nothing is saved: the union, mail and
 * referendum in them don't exist, so their links lead nowhere.
 */
#[Signature('mail:samples {email : Where to send them}')]
#[Description('Send a sample of every email the platform sends to one address')]
class SendSampleEmails extends Command
{
    public function handle(): int
    {
        $to = $this->argument('email');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("{$to} isn't an email address.");

            return self::FAILURE;
        }

        foreach ($this->samples($to) as $label => $email) {
            $email instanceof Mailable
                ? Mail::to($to)->sendNow($email)
                : Notification::route('mail', $to)->notifyNow($email);

            $this->line("Sent: {$label}");
        }

        // Laravel's own email goes to a user, not an address.
        (new User)->forceFill(['id' => 0, 'email' => $to])->notifyNow(new VerifyEmail);
        $this->line('Sent: Confirm your email address');

        $this->info("Sent every sample email to {$to}.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, Mailable|BaseNotification>
     */
    private function samples(string $to): array
    {
        $building = (new Building)->forceFill([
            'id' => 0,
            'name' => 'Sample Court Tenants\' Union',
            'address_line_1' => '1 Sample Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);

        $mail = (new UnionMail)->forceFill([
            'id' => 0,
            'bulletin_id' => 0,
            'kind' => UnionMailKind::Instant,
            'subject' => 'Repairs to the lift',
            'expires_at' => now()->addDays(2),
        ])->setRelation('building', $building);

        $referendum = (new Referendum)->forceFill([
            'id' => 0,
            'referendable_type' => 'policy',
            'closes_at' => now()->addDays(14),
        ])->setRelation('building', $building);

        $reply = new InboundEmail(
            id: 'sample',
            messageId: null,
            fromEmail: 'agent@example.com',
            fromName: 'Sample Lettings',
            recipients: [],
            subject: 'Re: Repairs to the lift',
            text: "Thank you for your email.\n\nAn engineer will visit on Monday to look at the lift.\n\nSample Lettings",
            html: null,
        );

        $deadline = now()->addWeek();

        return [
            'Union mail to a landlord' => new UnionMessage(
                $building,
                'Repairs to the lift',
                '<p>Dear Sample Lettings,</p><p>The lift in our building has been out of order for <strong>three weeks</strong>. Tenants with prams, shopping and mobility needs are using the stairs.</p><ul><li>Please fix it within 7 days.</li><li>Tell us when an engineer will visit.</li></ul><p>We look forward to your reply.</p>',
                $to,
            ),
            'Landlord reply, forwarded to the union\'s inbox' => new ForwardedReply($mail, $reply),
            'Landlord reply, forwarded to each member' => new ForwardedReply($mail, $reply, toMembers: true),
            'Mail needs approval' => new MailApprovalRequested($mail),
            'Referendum opened' => new ReferendumOpened($referendum),
            'Referendum decided' => new ReferendumDecided($referendum),
            ...collect(ExportReason::cases())->mapWithKeys(fn (ExportReason $reason) => [
                "Union records ({$reason->value})" => new UnionDataExported($building->name, route('home'), now()->addDays(30), reason: $reason),
            ])->all(),
            'Account clean-up warning' => new CleanupWarning('You signed up but didn\'t confirm your email address.', $deadline),
            'Union clean-up warning' => new CleanupWarning('The platform\'s administrators are closing Sample Court Tenants\' Union.', $deadline, $building->name),
            'Feedback, with a reply address' => new FeedbackReceived('Sam Sample', $to, 'The vouching page made it much easier to explain to my neighbours.'),
            'Anonymous feedback' => new FeedbackReceived(null, null, 'Could the flyers come in large print too?'),
        ];
    }
}
