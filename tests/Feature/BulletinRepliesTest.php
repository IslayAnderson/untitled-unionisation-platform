<?php

use App\Enums\ApprovalDecision;
use App\Mail\ForwardedReply;
use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;
use App\Models\Reply;
use App\Models\UnionMail;
use App\Services\InboundMail\Drivers\FakeDriver;
use App\Services\InboundMail\InboundEmail;
use App\Services\InboundMail\InboundMailbox;
use App\Services\MailApprovals;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    config(['inbound.address' => 'replies@union.test']);

    $this->mailbox = new FakeDriver;
    app()->instance(InboundMailbox::class, $this->mailbox);

    $this->building = Building::factory()->create(['contact_email' => 'union@example.test']);
    [$this->author, $this->member] = Contact::factory()->count(3)->tenantOf($this->building)->enrolled()->create()->all();

    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $this->pat = Contact::factory()->atLandlord($landlord)->create(['name' => 'Pat Manager', 'email' => 'Pat@Acme.test']);
    $this->list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $this->list->contacts()->attach($this->pat);
});

/**
 * Instant mail from the author, approved and sent.
 */
function sentMail(Contact $author, MailingList $list): UnionMail
{
    $approvals = app(MailApprovals::class);
    $mail = $approvals->createInstant($author, $list, 'Broken lift', '<p>The lift has been broken for three weeks.</p>');

    return $approvals->decide($mail->approvers->first()->contact, $mail, ApprovalDecision::Approve);
}

function inboundReply(UnionMail $mail, array $overrides = []): InboundEmail
{
    return new InboundEmail(...[
        'id' => (string) fake()->unique()->randomNumber(),
        'messageId' => '<'.fake()->uuid().'@acme.test>',
        'fromEmail' => 'pat@acme.test',
        'fromName' => 'Pat M',
        'recipients' => ["replies+{$mail->reply_token}@union.test"],
        'subject' => 'Re: Broken lift',
        'text' => "We'll send an engineer on Monday.\n\nOn Tue, 3 Nov 2026 at 09:00, Example Court <hello@example.com> wrote:\n> The lift has been broken for three weeks.",
        'html' => null,
        ...$overrides,
    ]);
}

it('sends union mail with a reply address for that mail', function () {
    $mail = sentMail($this->author, $this->list);

    expect($mail->reply_token)->toMatch('/^[a-f0-9]{24}$/');
    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $email) => $email->hasReplyTo("replies+{$mail->reply_token}@union.test")
        && ! $email->hasReplyTo('union@example.test'));
});

it('sends replies to the union inbox when inbound mail is not set up', function () {
    config(['inbound.address' => null]);

    sentMail($this->author, $this->list);

    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $email) => $email->hasReplyTo('union@example.test'));
});

it('adds an email reply to the bulletin without the quoted message, and forwards a copy', function () {
    $mail = sentMail($this->author, $this->list);
    $email = inboundReply($mail, ['attachments' => [
        ['filename' => 'schedule.pdf', 'content_type' => 'application/pdf', 'contents' => '%PDF-1.4'],
    ]]);
    $this->mailbox->receive($email);

    $this->artisan('union-mail:fetch-replies')->expectsOutput('Added 1 reply.')->assertSuccessful();

    $reply = $mail->bulletin->replies()->sole();
    expect($reply->union_mail_id)->toBe($mail->id)
        ->and($reply->contact_id)->toBe($this->pat->id)
        ->and($reply->author_id)->toBeNull()
        ->and($reply->from_email)->toBe('pat@acme.test')
        ->and($reply->rich_text)->toStartWith('<p>We&#039;ll send an engineer on Monday.</p>')
        ->and($reply->rich_text)->not->toContain('three weeks')
        ->and($reply->rich_text)->toContain('This reply had 1 attachment, forwarded to the union’s inbox.')
        ->and($this->mailbox->processed)->toBe([$email->id]);

    Mail::assertSent(ForwardedReply::class, fn (ForwardedReply $forward) => $forward->hasTo('union@example.test')
        && $forward->hasAttachment(Attachment::fromData(fn () => '%PDF-1.4', 'schedule.pdf')->withMime('application/pdf')));
});

it('shows email replies and members\' posts in the bulletin thread', function () {
    $mail = sentMail($this->author, $this->list);
    $this->mailbox->receive(inboundReply($mail));
    $this->mailbox->receive(inboundReply($mail, ['fromEmail' => 'stranger@else.test', 'fromName' => null, 'text' => 'Who is this?']));
    $this->artisan('union-mail:fetch-replies');
    Reply::factory()->create(['replyable_id' => $mail->bulletin_id, 'author_id' => $this->member->id, 'rich_text' => '<p>Let’s chase on Tuesday.</p>']);

    $this->actingAs($this->author->user)->get(route('bulletins.show', $mail->bulletin_id))
        ->assertOk()
        ->assertSeeInOrder(['Email reply', 'Pat Manager', 'engineer on Monday', 'Email reply', 'stranger@else.test', 'Not on the mailing list', 'Member', $this->member->name, 'chase on Tuesday']);
});

it('ignores emails that are not replies to sent union mail, and duplicates', function () {
    $mail = sentMail($this->author, $this->list);
    $pending = app(MailApprovals::class)->createInstant($this->author, $this->list, 'Pending', '<p>Not sent yet.</p>');
    $reply = inboundReply($mail);

    $this->mailbox->receive(inboundReply($mail, ['recipients' => ['replies+0123456789abcdef01234567@union.test']]));
    $this->mailbox->receive(inboundReply($mail, ['recipients' => ['someone@union.test']]));
    $this->mailbox->receive(inboundReply($pending));
    $this->mailbox->receive($reply);
    $this->mailbox->receive(inboundReply($mail, ['id' => 'again', 'messageId' => $reply->messageId]));

    $this->artisan('union-mail:fetch-replies')->expectsOutput('Added 1 reply.');

    expect(Reply::count())->toBe(1)
        ->and($this->mailbox->processed)->toHaveCount(5);
});

it('does nothing when inbound mail is not set up', function () {
    config(['inbound.address' => null]);

    $this->artisan('union-mail:fetch-replies')
        ->expectsOutput('Inbound mail is not set up. Set INBOUND_MAIL_ADDRESS to fetch replies.')
        ->assertSuccessful();
});

it('lets members post cleaned replies in a bulletin thread', function () {
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->member->user)
        ->post(route('bulletins.replies.store', $bulletin), ['reply' => '<p>Agreed, <strong>chase them</strong>.</p><img src=x onerror="alert(1)">'])
        ->assertRedirect();

    expect($bulletin->replies()->sole())
        ->author_id->toBe($this->member->id)
        ->rich_text->toBe('<p>Agreed, <strong>chase them</strong>.</p>');
    Mail::assertNothingOutgoing();
});

it('rejects blank replies and hides other unions\' bulletins', function () {
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->member->user)
        ->post(route('bulletins.replies.store', $bulletin), ['reply' => '<p>&nbsp;</p>'])
        ->assertSessionHasErrors('reply');

    $this->actingAs($this->member->user)
        ->post(route('bulletins.replies.store', Bulletin::factory()->create()), ['reply' => '<p>Hello</p>'])
        ->assertNotFound();

    expect(Reply::count())->toBe(0);
});

it('cleans bulletin HTML when it is posted', function () {
    $this->actingAs($this->author->user)->post(route('bulletins.store'), [
        'title' => 'Rent increase',
        'body' => '<h2 style="color:red">Our response</h2><p><a href="javascript:alert(1)">bad</a> <a href="https://example.org">good</a></p><script>x()</script>',
    ])->assertRedirect();

    expect(Bulletin::whereNull('system_key')->sole()->rich_text)->toBe('<h2>Our response</h2><p><a rel="noopener noreferrer nofollow">bad</a> <a href="https://example.org" rel="noopener noreferrer nofollow">good</a></p>');
});
