<?php

use App\Enums\ApprovalDecision;
use App\Enums\ExportReason;
use App\Enums\ReferendumAnswer;
use App\Enums\ReferendumOutcome;
use App\Enums\UnionMailStatus;
use App\Exceptions\ReferendumRefused;
use App\Jobs\SendUnionExport;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\MailingList;
use App\Models\Referendum;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UnionDataExported;
use App\Services\MailApprovals;
use App\Services\Membership;
use App\Services\Referendums;
use App\Services\UnionExport;
use App\Services\Vouching;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    Mail::fake();
    Bus::fake([SendUnionExport::class]);

    $this->building = Building::factory()->create();
    $this->members = Contact::factory()->count(5)->tenantOf($this->building)->enrolled()->create();
    [$this->proposer, $this->member, $this->other] = $this->members->all();

    $this->list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $this->list->contacts()->attach(Contact::factory()->create(['email' => 'agent@example.test']));
});

/**
 * Members other than the one being removed vote in the latest referendum, which is then counted.
 *
 * @param  list<ReferendumAnswer>  $answers
 */
function decideRemoval(array $answers): Referendum
{
    $referendum = Referendum::latest('id')->first();
    $voters = test()->members->reject(fn (Contact $member) => $member->is(test()->member))->values();

    foreach ($answers as $i => $answer) {
        app(Referendums::class)->vote($voters[$i], $referendum, $answer);
    }

    test()->travelTo($referendum->closes_at->addMinute());
    test()->artisan('referendums:close');
    test()->travelBack();

    return $referendum->fresh();
}

it('lets a member leave, ending their roles, nominations and approvals, and emails them the records', function () {
    $role = Role::factory()->create(['building_id' => $this->building->id]);
    $role->contacts()->attach($this->member);
    $other = Role::factory()->create(['building_id' => $this->building->id]);
    $nomination = app(Referendums::class)->proposeElection($this->proposer, $other, $this->member, null);

    $mail = app(MailApprovals::class)->createFromBulletin(
        $this->proposer,
        Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->proposer->id]),
        $this->list,
    );

    $this->actingAs($this->member->user)->post(route('membership.leave'))->assertSessionHasErrors('confirm');
    $this->actingAs($this->member->user)->post(route('membership.leave'), ['confirm' => '1'])
        ->assertRedirect(route('dashboard'));

    $this->member->refresh();
    expect($this->member->isEnrolled())->toBeFalse()
        ->and($this->member->isRemoved())->toBeFalse()
        ->and($this->member->vouchesReceived()->count())->toBe(0)
        ->and($this->member->roles()->count())->toBe(0)
        ->and($nomination->fresh()->outcome)->toBe(ReferendumOutcome::Withdrawn)
        ->and($mail->approvers()->where('contact_id', $this->member->id)->exists())->toBeFalse();

    Bus::assertDispatched(SendUnionExport::class, fn (SendUnionExport $job) => $job->buildingId === $this->building->id
        && $job->email === $this->member->user->email
        && $job->reason === ExportReason::Left);

    $this->actingAs($this->member->user)->get(route('bulletins.index'))->assertRedirect(route('dashboard'));

    // Neighbours can vouch for them again.
    expect(app(Vouching::class)->refusal($this->proposer, $this->member))->toBeNull();
});

it('rejects pending mail that can no longer get enough approvals when an approver leaves', function () {
    $mail = app(MailApprovals::class)->createFromBulletin(
        $this->proposer,
        Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->proposer->id]),
        $this->list,
    );
    $needed = $mail->approvals_required;
    $approvers = $mail->approvers()->with('contact')->get()->pluck('contact');

    // Reject until exactly enough approvers are left, then one of them leaves.
    $approvers->take($approvers->count() - $needed)->each(fn (Contact $approver) => app(MailApprovals::class)->decide($approver, $mail, ApprovalDecision::Reject));
    expect($mail->fresh()->status)->toBe(UnionMailStatus::Pending);

    app(Membership::class)->leave($approvers->last());

    expect($mail->fresh()->status)->toBe(UnionMailStatus::Rejected);
});

it('removes a member by referendum, with a reason, and they can\'t be vouched for again', function () {
    $this->actingAs($this->proposer->user)->post(route('member-removals.store'), ['contact_id' => $this->member->id])
        ->assertSessionHasErrors('reason');

    $this->actingAs($this->proposer->user)->post(route('member-removals.store'), [
        'contact_id' => $this->member->id,
        'reason' => 'They moved out in June.',
    ])->assertRedirect();

    $referendum = Referendum::sole();
    expect($referendum->title)->toBe("Remove {$this->member->name} from the union")
        ->and($referendum->isMemberRemoval())->toBeTrue()
        ->and($referendum->candidate_id)->toBe($this->member->id)
        ->and($referendum->pass_percent)->toBe(50);

    $this->actingAs($this->member->user)->get(route('dashboard'))->assertSeeText('Members are voting on removing you from the union');
    $this->actingAs($this->member->user)->get(route('referendums.show', $referendum))
        ->assertSeeText('This is about you')
        ->assertSeeText('They moved out in June.');

    decideRemoval([ReferendumAnswer::For, ReferendumAnswer::Against, ReferendumAnswer::For]);

    $this->member->refresh();
    expect($referendum->fresh()->outcome)->toBe(ReferendumOutcome::Passed)
        ->and($this->member->isEnrolled())->toBeFalse()
        ->and($this->member->isRemoved())->toBeTrue()
        ->and(app(Vouching::class)->refusal($this->proposer, $this->member))->toContain('removed');

    Bus::assertDispatched(SendUnionExport::class, fn (SendUnionExport $job) => $job->email === $this->member->user->email
        && $job->reason === ExportReason::Removed);

    $this->actingAs($this->member->user)->get(route('membership.show'))
        ->assertSeeText('Members removed you from the union')
        ->assertSeeText('They moved out in June.');
    $this->actingAs($this->member->user)->get(route('bulletins.show', $this->building->vouchingBulletin))->assertNotFound();
});

it('uses the constitution\'s removal threshold, and keeps the member if it fails', function () {
    $this->building->currentConstitution->update(['rules' => [...$this->building->rules()->toArray(), 'removal_pass_percent' => 66]]);

    app(Referendums::class)->proposeMemberRemoval($this->proposer, $this->member, 'Broke the constitution.');

    $referendum = decideRemoval([ReferendumAnswer::For, ReferendumAnswer::Against, ReferendumAnswer::For, ReferendumAnswer::Against]);

    expect($referendum->pass_percent)->toBe(66)
        ->and($referendum->outcome)->toBe(ReferendumOutcome::Rejected)
        ->and($this->member->fresh()->isEnrolled())->toBeTrue();
    Bus::assertNotDispatched(SendUnionExport::class);
});

it('refuses removals it shouldn\'t hold', function () {
    $referendums = app(Referendums::class);
    $waiting = Contact::factory()->tenantOf($this->building)->create();
    $elsewhere = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();

    expect(fn () => $referendums->proposeMemberRemoval($this->proposer, $this->proposer, 'x'))->toThrow(ReferendumRefused::class, 'You can\'t propose removing yourself')
        ->and(fn () => $referendums->proposeMemberRemoval($this->proposer, $waiting, 'x'))->toThrow(ReferendumRefused::class, 'Only members')
        ->and(fn () => $referendums->proposeMemberRemoval($this->proposer, $elsewhere, 'x'))->toThrow(ReferendumRefused::class, 'Only members')
        ->and(fn () => $referendums->proposeMemberRemoval($waiting, $this->member, 'x'))->toThrow(ReferendumRefused::class, 'Only members can take part');

    $referendums->proposeMemberRemoval($this->proposer, $this->member, 'x');
    expect(fn () => $referendums->proposeMemberRemoval($this->other, $this->member, 'x'))->toThrow(ReferendumRefused::class, 'already a referendum');
});

it('still bars a member who left while the vote on removing them was open, without a second export', function () {
    app(Referendums::class)->proposeMemberRemoval($this->proposer, $this->member, 'Moved out.');
    app(Membership::class)->leave($this->member);

    $referendum = decideRemoval([ReferendumAnswer::For, ReferendumAnswer::For, ReferendumAnswer::Against]);

    expect($referendum->outcome)->toBe(ReferendumOutcome::Passed)
        ->and($this->member->fresh()->isRemoved())->toBeTrue();
    Bus::assertDispatchedTimes(SendUnionExport::class, 1);
});

it('deletes an account after checking the password, emailing a member the records', function () {
    $user = $this->member->user;
    $email = $user->email;

    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password'])->assertRedirect(route('home'));

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull()
        ->and(Contact::find($this->member->id))->toBeNull();
    Bus::assertDispatched(SendUnionExport::class, fn (SendUnionExport $job) => $job->email === $email && $job->reason === ExportReason::AccountDeleted);
});

it('lets a tenant waiting to be vouched for delete their account, without an export', function () {
    $waiting = Contact::factory()->tenantOf($this->building)->create();

    $this->actingAs($waiting->user)->get(route('membership.show'))
        ->assertSeeText('You\'re not a member yet')
        ->assertDontSeeText('Leave the union');
    $this->actingAs($waiting->user)->post(route('membership.leave'), ['confirm' => '1'])->assertRedirect(route('dashboard'));
    $this->actingAs($waiting->user)->delete(route('account.destroy'), ['password' => 'password'])->assertRedirect(route('home'));

    expect(User::find($waiting->user->id))->toBeNull();
    Bus::assertNotDispatched(SendUnionExport::class);
});

it('emails the leaver the union\'s records', function () {
    Bus::fake([]);
    Notification::fake();

    (new SendUnionExport($this->building->id, 'gone@example.test', ExportReason::Left))->handle(app(UnionExport::class));

    Notification::assertSentOnDemand(UnionDataExported::class, function (UnionDataExported $notification, array $channels, object $notifiable) {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === 'gone@example.test'
            && $mail->subject === "You've left {$this->building->name}: your union's records"
            && str_contains(implode(' ', $mail->introLines), 'as it is today');
    });
});
