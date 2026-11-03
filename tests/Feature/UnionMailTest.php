<?php

use App\Enums\ApprovalDecision;
use App\Enums\UnionMailKind;
use App\Enums\UnionMailStatus;
use App\Exceptions\UnionMailRefused;
use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;
use App\Models\UnionMail;
use App\Notifications\MailApprovalRequested;
use App\Services\MailApprovals;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    Notification::fake();

    $this->building = Building::factory()->create();
    $this->approvals = app(MailApprovals::class);

    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $this->list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $this->list->contacts()->attach([
        Contact::factory()->atLandlord($landlord)->create(['email' => 'pat@acme.test'])->id,
        Contact::factory()->atLandlord($landlord)->create(['email' => 'sam@acme.test'])->id,
    ]);
});

/**
 * Create a union of $count enrolled members and return them; the first is the author.
 *
 * @return array<int, Contact>
 */
function members(Building $building, int $count): array
{
    return Contact::factory()->count($count)->tenantOf($building)->enrolled()->create()->all();
}

function instantMail(Contact $author, MailingList $list): UnionMail
{
    return app(MailApprovals::class)->createInstant($author, $list, 'Broken lift', 'The lift has been broken for three weeks.');
}

it('picks 20% of members at random, excluding the author, and needs 10% to approve', function (int $memberCount, int $approvers, int $required) {
    [$author] = members($this->building, $memberCount);

    $mail = instantMail($author, $this->list);

    expect($mail->kind)->toBe(UnionMailKind::Instant)
        ->and($mail->status)->toBe(UnionMailStatus::Pending)
        ->and($mail->approvals_required)->toBe($required)
        ->and($mail->approvers)->toHaveCount($approvers)
        ->and($mail->approvers->pluck('contact_id'))->not->toContain($author->id)
        ->and($mail->expires_at->diffInHours(now(), true))->toEqualWithDelta(48, 0.1);
})->with([
    '3 members' => [3, 1, 1],
    '10 members' => [10, 2, 1],
    '11 members' => [11, 3, 2],
    '30 members' => [30, 6, 3],
]);

it('reads instant mail thresholds from the constitution', function () {
    $this->building->currentConstitution->update(['rules' => [
        'instant_mail_approval_percent' => 50,
        'instant_mail_approver_percent' => 100,
        'instant_mail_expiry_hours' => 2,
    ]]);
    [$author] = members($this->building, 5);

    $mail = instantMail($author, $this->list);

    expect($mail->approvals_required)->toBe(3)
        ->and($mail->approvers)->toHaveCount(4)
        ->and($mail->expires_at->diffInHours(now(), true))->toEqualWithDelta(2, 0.1);
});

it('emails the chosen approvers without the mail content', function () {
    [$author] = members($this->building, 10);

    $mail = instantMail($author, $this->list);
    $approverUsers = $mail->approvers->map(fn ($seat) => $seat->contact->user);

    Notification::assertSentTo($approverUsers, MailApprovalRequested::class);
    Notification::assertNotSentTo($author->user, MailApprovalRequested::class);
    Notification::assertCount(2);

    $email = (new MailApprovalRequested($mail))->toMail($approverUsers->first());
    expect(implode(' ', $email->introLines))->not->toContain('three weeks')
        ->and($email->actionUrl)->toBe(route('mail.show', $mail));
});

it('sends instant mail once approved and posts it as a bulletin', function () {
    [$author] = members($this->building, 10);
    $mail = instantMail($author, $this->list);
    $approver = $mail->approvers->first()->contact;

    $mail = $this->approvals->decide($approver, $mail, ApprovalDecision::Approve);

    expect($mail->status)->toBe(UnionMailStatus::Sent)
        ->and($mail->recipient_count)->toBe(2)
        ->and($mail->bulletin->title)->toBe('Broken lift')
        ->and($mail->bulletin->rich_text)->toBe('The lift has been broken for three weeks.')
        ->and($mail->bulletin->author_id)->toBe($author->id);

    Mail::assertQueuedCount(2);
    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $email) => $email->hasTo('pat@acme.test') && $email->messageBody === $mail->body);
});

it('does not send before enough approvals', function () {
    [$author] = members($this->building, 11);
    $mail = instantMail($author, $this->list);

    $mail = $this->approvals->decide($mail->approvers[0]->contact, $mail, ApprovalDecision::Approve);

    expect($mail->status)->toBe(UnionMailStatus::Pending);
    Mail::assertNothingQueued();
});

it('rejects the mail as soon as it can no longer pass', function () {
    [$author] = members($this->building, 11);
    $mail = instantMail($author, $this->list);

    $mail = $this->approvals->decide($mail->approvers[0]->contact, $mail, ApprovalDecision::Reject);
    expect($mail->status)->toBe(UnionMailStatus::Pending);

    $mail = $this->approvals->decide($mail->approvers[1]->contact, $mail, ApprovalDecision::Reject);

    expect($mail->status)->toBe(UnionMailStatus::Rejected);
    Mail::assertNothingQueued();
});

it('refuses decisions from non-approvers, the author, and repeat decisions', function () {
    [$author] = members($this->building, 10);
    $mail = instantMail($author, $this->list);
    $approverIds = $mail->approvers->pluck('contact_id');
    $outsider = $this->building->contacts()->enrolled()->whereKeyNot($author->id)->whereNotIn('id', $approverIds)->first();
    $approver = $mail->approvers[0]->contact;

    expect(fn () => $this->approvals->decide($outsider, $mail, ApprovalDecision::Approve))->toThrow(UnionMailRefused::class, "You're not one of this mail's approvers.")
        ->and(fn () => $this->approvals->decide($author, $mail, ApprovalDecision::Approve))->toThrow(UnionMailRefused::class, "You're not one of this mail's approvers.");

    $this->approvals->decide($approver, $mail, ApprovalDecision::Reject);

    expect(fn () => $this->approvals->decide($approver, $mail, ApprovalDecision::Approve))->toThrow(UnionMailRefused::class, "You've already decided on this mail.");
});

it('expires overdue mail and refuses late decisions', function () {
    [$author] = members($this->building, 10);
    $mail = instantMail($author, $this->list);
    $other = instantMail($author, $this->list);

    $this->travel(49)->hours();

    expect(fn () => $this->approvals->decide($mail->approvers[0]->contact, $mail, ApprovalDecision::Approve))
        ->toThrow(UnionMailRefused::class, 'This mail expired before it was approved.')
        ->and($mail->fresh()->status)->toBe(UnionMailStatus::Expired);

    $this->artisan('union-mail:expire')->expectsOutput('Expired 1 mail.')->assertSuccessful();

    expect($other->fresh()->status)->toBe(UnionMailStatus::Expired);
    Mail::assertNothingQueued();
});

it('lets only the author withdraw pending mail', function () {
    [$author, $other] = members($this->building, 10);
    $mail = instantMail($author, $this->list);

    expect(fn () => $this->approvals->withdraw($other, $mail))->toThrow(UnionMailRefused::class, 'Only the author can withdraw this mail.');

    $this->approvals->withdraw($author, $mail);

    expect($mail->fresh()->status)->toBe(UnionMailStatus::Withdrawn)
        ->and(fn () => $this->approvals->decide($mail->approvers[0]->contact, $mail, ApprovalDecision::Approve))
        ->toThrow(UnionMailRefused::class, 'This mail is no longer waiting for approval (Withdrawn).');
});

it('refuses mail when there are not enough other members', function () {
    [$author] = members($this->building, 1);

    expect(fn () => instantMail($author, $this->list))
        ->toThrow(UnionMailRefused::class, 'This needs 1 approval, but your union only has 0 other members.');
});

it('refuses mail to a list nobody on which has an email', function () {
    [$author] = members($this->building, 3);
    $emptyList = MailingList::factory()->create(['building_id' => $this->building->id]);

    expect(fn () => instantMail($author, $emptyList))
        ->toThrow(UnionMailRefused::class, 'Nobody on this mailing list has an email address.');
});

it('turns a bulletin into mail that every other member approves, needing 50%', function (int $memberCount, int $required) {
    [$author] = members($this->building, $memberCount);
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $author->id]);

    $mail = $this->approvals->createFromBulletin($author, $bulletin, $this->list);

    expect($mail->kind)->toBe(UnionMailKind::Bulletin)
        ->and($mail->bulletin_id)->toBe($bulletin->id)
        ->and($mail->subject)->toBe($bulletin->title)
        ->and($mail->body)->toBe($bulletin->rich_text)
        ->and($mail->approvals_required)->toBe($required)
        ->and($mail->approvers)->toHaveCount($memberCount - 1)
        ->and($mail->expires_at->diffInDays(now(), true))->toEqualWithDelta(7, 0.01);
})->with([
    '3 members' => [3, 2],
    '4 members' => [4, 2],
    '5 members' => [5, 3],
]);

it('sends bulletin mail once half the union approves, without a new bulletin', function () {
    [$author] = members($this->building, 4);
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $author->id]);
    $mail = $this->approvals->createFromBulletin($author, $bulletin, $this->list);

    $this->approvals->decide($mail->approvers[0]->contact, $mail, ApprovalDecision::Approve);
    $mail = $this->approvals->decide($mail->approvers[1]->contact, $mail, ApprovalDecision::Approve);

    expect($mail->status)->toBe(UnionMailStatus::Sent)
        ->and($mail->bulletin_id)->toBe($bulletin->id)
        ->and(Bulletin::whereNull('system_key')->count())->toBe(1);
    Mail::assertQueuedCount(2);
});

it('only lets the bulletin author send it, once', function () {
    [$author, $other] = members($this->building, 3);
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $author->id]);

    expect(fn () => $this->approvals->createFromBulletin($other, $bulletin, $this->list))
        ->toThrow(UnionMailRefused::class, 'Only the author of a bulletin can turn it into mail.');

    $this->approvals->createFromBulletin($author, $bulletin, $this->list);

    expect(fn () => $this->approvals->createFromBulletin($author, $bulletin, $this->list))
        ->toThrow(UnionMailRefused::class, 'This bulletin already has mail waiting for approval or sent.');
});

it('writes, approves and shows instant mail through the pages', function () {
    [$author] = members($this->building, 3);

    $this->actingAs($author->user)->get('/mail/create')
        ->assertOk()
        ->assertSee('Never include personal or private information.');

    $this->actingAs($author->user)->post('/mail', [
        'mailing_list_id' => $this->list->id,
        'subject' => 'Broken lift',
        'body' => 'Please fix it.',
    ])->assertRedirect();

    $mail = UnionMail::sole();
    $approver = $mail->approvers->sole()->contact;

    $this->actingAs($author->user)->get("/mail/{$mail->id}")
        ->assertOk()
        ->assertSee("You wrote this, so you can't approve it.")
        ->assertDontSee('>Approve</button>', false);

    $this->actingAs($approver->user)->get('/dashboard')->assertSee('Mail waiting for your approval');

    $this->actingAs($approver->user)->post("/mail/{$mail->id}/decision", ['decision' => 'approve'])
        ->assertRedirect(route('mail.show', $mail))
        ->assertSessionHas('status', 'Approved. That was the last approval needed, so the mail is being sent.');

    expect($mail->fresh()->status)->toBe(UnionMailStatus::Sent);
});

it('shows a refusal when creating mail fails', function () {
    [$author] = members($this->building, 1);

    $this->actingAs($author->user)->post('/mail', [
        'mailing_list_id' => $this->list->id,
        'subject' => 'Hi',
        'body' => 'Hello',
    ])->assertSessionHasErrors(['mailing_list_id' => 'This needs 1 approval, but your union only has 0 other members.']);
});

it('hides other unions\' mail and rejects their lists', function () {
    [$author] = members($this->building, 3);
    $otherBuilding = Building::factory()->create();
    [$otherAuthor] = members($otherBuilding, 3);
    $otherList = MailingList::factory()->create(['building_id' => $otherBuilding->id]);
    $otherList->contacts()->attach(Contact::factory()->create(['building_id' => $otherBuilding->id, 'email' => 'x@y.test']));
    $otherMail = instantMail($otherAuthor, $otherList);

    $this->actingAs($author->user)->get("/mail/{$otherMail->id}")->assertNotFound();
    $this->actingAs($author->user)->post("/mail/{$otherMail->id}/decision", ['decision' => 'approve'])->assertNotFound();
    $this->actingAs($author->user)->post('/mail', ['mailing_list_id' => $otherList->id, 'subject' => 'Hi', 'body' => 'Hello'])
        ->assertSessionHasErrors(['mailing_list_id' => 'The selected mailing list id is invalid.']);
});

it('turns a bulletin into mail through the bulletin page', function () {
    [$author] = members($this->building, 3);

    $this->actingAs($author->user)->post('/bulletins', ['title' => 'Rent rise', 'body' => 'We oppose the rent rise.'])->assertRedirect();
    $bulletin = Bulletin::whereNull('system_key')->sole();

    $this->actingAs($author->user)->get("/bulletins/{$bulletin->id}")
        ->assertOk()
        ->assertSee('Send as mail')
        ->assertSee('Never include personal or private information.');

    $this->actingAs($author->user)->post("/bulletins/{$bulletin->id}/mail", ['mailing_list_id' => $this->list->id])->assertRedirect();

    expect(UnionMail::sole()->kind)->toBe(UnionMailKind::Bulletin);

    $this->actingAs($author->user)->get("/bulletins/{$bulletin->id}")->assertDontSee('Send as mail');
});

it('shows bulletins only to members of the same union', function () {
    [$member] = members($this->building, 1);
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'title' => 'Our bulletin']);
    $otherBulletin = Bulletin::factory()->create(['title' => 'Their bulletin']);
    $tenant = Contact::factory()->tenantOf($this->building)->create();

    $this->actingAs($member->user)->get('/bulletins')->assertSee('Our bulletin')->assertDontSee('Their bulletin');
    $this->actingAs($member->user)->get("/bulletins/{$otherBulletin->id}")->assertNotFound();
    $this->actingAs($tenant->user)->get("/bulletins/{$bulletin->id}")->assertNotFound();
    $this->actingAs($tenant->user)->get('/bulletins')->assertRedirect(route('dashboard'));
});
