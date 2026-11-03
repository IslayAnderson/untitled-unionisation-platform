<?php

use App\Enums\ReferendumAnswer;
use App\Enums\ReferendumOutcome;
use App\Models\Building;
use App\Models\Contact;
use App\Models\Referendum;
use App\Models\Role;
use App\Services\Referendums;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->building = Building::factory()->create();
    $this->members = Contact::factory()->count(4)->tenantOf($this->building)->enrolled()->create();
    [$this->proposer, $this->member, $this->candidate] = $this->members->all();
    $this->role = Role::factory()->create(['building_id' => $this->building->id, 'title' => 'Secretary']);
});

/**
 * Two of four members vote for, then the referendum is counted.
 */
function passRoleReferendum(): Referendum
{
    $referendum = Referendum::latest('id')->first();
    app(Referendums::class)->vote(test()->members[0], $referendum, ReferendumAnswer::For);
    app(Referendums::class)->vote(test()->members[1], $referendum, ReferendumAnswer::For);
    test()->travelTo($referendum->closes_at->addMinute());
    test()->artisan('referendums:close');
    test()->travelBack();

    return $referendum->fresh();
}

it('creates a role with its responsibilities when the referendum passes', function () {
    $this->actingAs($this->proposer->user)->post(route('roles.store'), [
        'title' => 'Repairs coordinator',
        'description' => 'Chases repairs.',
        'responsibilities' => "Keep the repairs log\n\n  Chase the agent weekly  \n",
    ])->assertRedirect();

    $role = Role::where('title', 'Repairs coordinator')->sole();
    expect($role->isInForce())->toBeFalse()
        ->and($role->actions->pluck('title')->all())->toBe(['Keep the repairs log', 'Chase the agent weekly'])
        ->and(Referendum::sole()->title)->toBe('Create role: Repairs coordinator');

    expect(passRoleReferendum()->outcome)->toBe(ReferendumOutcome::Passed)
        ->and($role->fresh()->isInForce())->toBeTrue();
});

it('elects a member to a role, and removes them, by referendum', function () {
    $this->actingAs($this->proposer->user)->post(route('roles.propose.store', [$this->role, 'elect']), ['contact_id' => $this->candidate->id])
        ->assertRedirect();
    expect(Referendum::sole()->title)->toBe("Elect {$this->candidate->name} as Secretary");

    passRoleReferendum();
    expect($this->role->contacts()->pluck('contacts.id')->all())->toBe([$this->candidate->id]);

    $this->actingAs($this->member->user)->get(route('roles.index'))->assertSee($this->candidate->name);

    $this->actingAs($this->member->user)->post(route('roles.propose.store', [$this->role, 'remove']), ['contact_id' => $this->candidate->id]);
    passRoleReferendum();
    expect($this->role->contacts()->count())->toBe(0);
});

it('refuses elections it shouldn\'t hold', function (Contact $candidate, string $message) {
    $this->actingAs($this->proposer->user)->post(route('roles.propose.store', [$this->role, 'elect']), ['contact_id' => $candidate->id])
        ->assertSessionHasErrors(['contact_id' => str_replace(':name', $candidate->name, $message)]);

    expect(Referendum::where('candidate_id', $candidate->id)->count())->toBeLessThanOrEqual(1);
})->with([
    'a tenant who isn\'t a member' => [fn () => Contact::factory()->tenantOf($this->building)->create(), 'Only members of the union can be elected.'],
    'someone already holding it' => [function () {
        $this->role->contacts()->attach($this->candidate);

        return $this->candidate;
    }, ':name already holds this role.'],
    'someone already nominated' => [function () {
        app(Referendums::class)->proposeElection($this->member, $this->role, $this->candidate, null);

        return $this->candidate;
    }, "There's already a referendum about :name and this role."],
]);

it('refuses changes to roles that aren\'t in force or belong to another union', function () {
    $proposed = Role::factory()->create(['building_id' => $this->building->id, 'published_at' => null]);
    $elsewhere = Role::factory()->create();

    $this->actingAs($this->proposer->user)->post(route('roles.propose.store', [$proposed, 'elect']), ['contact_id' => $this->candidate->id])
        ->assertSessionHasErrors(['contact_id' => 'That role isn\'t in force.']);
    $this->actingAs($this->proposer->user)->post(route('roles.propose.store', [$elsewhere, 'abolish']))->assertNotFound();
    $this->actingAs($this->proposer->user)->get(route('roles.show', $elsewhere))->assertNotFound();
});

it('lets a nominee decline by withdrawing their election', function () {
    $referendum = app(Referendums::class)->proposeElection($this->proposer, $this->role, $this->candidate, null);

    $this->actingAs($this->candidate->user)->get(route('referendums.show', $referendum))->assertSee('Decline the nomination');
    $this->actingAs($this->candidate->user)->post(route('referendums.withdraw', $referendum))->assertSessionHasNoErrors();

    expect($referendum->fresh()->outcome)->toBe(ReferendumOutcome::Withdrawn);
});

it('abolishes a role, and everyone holding it stops', function () {
    $this->role->contacts()->attach($this->candidate);

    $this->actingAs($this->proposer->user)->post(route('roles.propose.store', [$this->role, 'abolish']), ['reason' => 'Not needed.']);
    passRoleReferendum();

    expect($this->role->fresh()->abolished_at)->not->toBeNull()
        ->and($this->role->contacts()->count())->toBe(0);
});

it('lets a member step down from a role without a vote', function () {
    $this->role->contacts()->attach($this->candidate);

    $this->actingAs($this->candidate->user)->post(route('roles.step-down', $this->role))->assertRedirect(route('roles.show', $this->role));
    expect($this->role->contacts()->count())->toBe(0);

    $this->actingAs($this->member->user)->post(route('roles.step-down', $this->role))->assertSessionHas('status', 'You don\'t hold this role.');
});

it('doesn\'t elect a nominee who left the union while the vote was open', function () {
    app(Referendums::class)->proposeElection($this->proposer, $this->role, $this->candidate, null);
    $this->candidate->update(['enrolled_at' => null]);

    expect(passRoleReferendum()->outcome)->toBe(ReferendumOutcome::Passed)
        ->and($this->role->contacts()->count())->toBe(0);
});
