<?php

use App\Enums\ReferendumAnswer;
use App\Enums\ReferendumOutcome;
use App\Models\Building;
use App\Models\Constitution;
use App\Models\Contact;
use App\Models\Policy;
use App\Models\Referendum;
use App\Notifications\ReferendumOpened;
use App\Services\Referendums;
use App\Support\ConstitutionRules;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Notification::fake();

    $this->building = Building::factory()->create();
    $this->members = Contact::factory()->count(4)->tenantOf($this->building)->enrolled()->create();
    [$this->proposer, $this->member] = $this->members->all();
});

function amendment(array $rules = [], array $overrides = []): array
{
    return [
        'title' => 'Need three vouches',
        'reason' => 'Two is too easy to fake.',
        'body' => '<p>The amended constitution.</p>',
        'rules' => [...ConstitutionRules::DEFAULTS, ...$rules],
        ...$overrides,
    ];
}

/**
 * Cast these votes, then count the referendum after its deadline.
 *
 * @param  list<ReferendumAnswer>  $answers
 */
function decide(Referendum $referendum, array $answers): Referendum
{
    $service = app(Referendums::class);
    foreach ($answers as $i => $answer) {
        $service->vote(test()->members[$i], $referendum, $answer);
    }

    test()->travelTo($referendum->closes_at->addMinute());
    test()->artisan('referendums:close')->assertSuccessful();

    return $referendum->fresh();
}

it('puts a constitutional amendment to a referendum with a discussion, and tells the other members', function () {
    $this->actingAs($this->proposer->user)->post(route('constitution.propose'), amendment(['vouches_required' => 3]))
        ->assertRedirect(route('referendums.show', Referendum::sole()));

    $referendum = Referendum::sole();
    $draft = $referendum->referendable;

    expect($draft)->toBeInstanceOf(Constitution::class)
        ->and($draft->published_at)->toBeNull()
        ->and($draft->rules['vouches_required'])->toBe(3)
        ->and($referendum->quorum_percent)->toBe(50)
        ->and($referendum->closes_at->isSameMinute(now()->addDays(14)))->toBeTrue()
        ->and($referendum->thread->title)->toBe('Referendum: Need three vouches')
        ->and($referendum->thread->rich_text)->toBe('<p>Two is too easy to fake.</p>')
        ->and($this->building->fresh()->current_constitution_id)->not->toBe($draft->id);

    Notification::assertSentTo($this->member->user, ReferendumOpened::class);
    Notification::assertNotSentTo($this->proposer->user, ReferendumOpened::class);
});

it('rejects amendments with numbers out of range, and a second amendment while one is open', function () {
    $this->actingAs($this->proposer->user)->post(route('constitution.propose'), amendment(['referendum_pass_percent' => 40]))
        ->assertSessionHasErrors('rules.referendum_pass_percent');
    expect(Referendum::count())->toBe(0);

    $this->actingAs($this->proposer->user)->post(route('constitution.propose'), amendment());
    $this->actingAs($this->member->user)->post(route('constitution.propose'), amendment())
        ->assertSessionHasErrors(['title' => 'There\'s already a referendum on the constitution. Wait until it has been decided.']);
    expect(Referendum::count())->toBe(1);
});

it('adopts the amendment when enough members vote for it, and the platform follows the new numbers', function () {
    $this->actingAs($this->proposer->user)->post(route('constitution.propose'), amendment(['vouches_required' => 3]));

    $referendum = decide(Referendum::sole(), [ReferendumAnswer::For, ReferendumAnswer::For, ReferendumAnswer::Against]);

    expect($referendum->outcome)->toBe(ReferendumOutcome::Passed)
        ->and($referendum->member_count)->toBe(4)
        ->and($this->building->fresh()->current_constitution_id)->toBe($referendum->referendable_id)
        ->and($referendum->referendable->published_at)->not->toBeNull()
        ->and($this->building->fresh()->rules()->vouchesRequired)->toBe(3);
});

it('decides by quorum and the pass threshold, with abstentions counting only towards quorum', function (array $answers, ReferendumOutcome $outcome) {
    $referendum = Referendum::factory()->create(['building_id' => $this->building->id, 'referendable_id' => Policy::factory()->create(['building_id' => $this->building->id, 'published_at' => null])]);

    expect(decide($referendum, $answers)->outcome)->toBe($outcome)
        ->and($referendum->referendable->fresh()->published_at !== null)->toBe($outcome === ReferendumOutcome::Passed);
})->with([
    'one vote of four misses the 50% quorum' => [[ReferendumAnswer::For], ReferendumOutcome::NoQuorum],
    'abstentions make the quorum, one for passes' => [[ReferendumAnswer::For, ReferendumAnswer::Abstain], ReferendumOutcome::Passed],
    'a tie fails' => [[ReferendumAnswer::For, ReferendumAnswer::Against], ReferendumOutcome::Rejected],
    'all abstain fails' => [[ReferendumAnswer::Abstain, ReferendumAnswer::Abstain], ReferendumOutcome::Rejected],
]);

it('records that a member voted separately from how, and only once', function () {
    $referendum = Referendum::factory()->create(['building_id' => $this->building->id, 'referendable_id' => Policy::factory()->create(['building_id' => $this->building->id, 'published_at' => null])]);
    $this->travel(5)->minutes();

    $this->actingAs($this->member->user)->post(route('referendums.votes.store', $referendum), ['answer' => 'against'])->assertSessionHasNoErrors();
    $this->actingAs($this->member->user)->post(route('referendums.votes.store', $referendum), ['answer' => 'for'])
        ->assertSessionHasErrors(['answer' => 'You\'ve already voted in this referendum.']);

    $fresh = $referendum->fresh();
    expect([$fresh->for_count, $fresh->against_count, $fresh->ballot_count])->toBe([0, 1, 1])
        ->and($fresh->updated_at->equalTo($referendum->updated_at))->toBeTrue()
        ->and(Schema::getColumnListing('referendum_voters'))->toEqualCanonicalizing(['id', 'referendum_id', 'contact_id']);

    $this->actingAs($this->member->user)->get(route('referendums.show', $referendum))
        ->assertSee('Results will appear here when voting closes.')
        ->assertSee('1 of 4 members have voted.');
});

it('keeps other unions, tenants and late votes out', function () {
    $referendum = Referendum::factory()->create(['building_id' => $this->building->id, 'referendable_id' => Policy::factory()->create(['building_id' => $this->building->id, 'published_at' => null])]);
    $tenant = Contact::factory()->tenantOf($this->building)->create();
    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();

    $this->actingAs($tenant->user)->post(route('referendums.votes.store', $referendum), ['answer' => 'for'])->assertRedirect(route('dashboard'));
    $this->actingAs($outsider->user)->post(route('referendums.votes.store', $referendum), ['answer' => 'for'])->assertNotFound();
    $this->actingAs($outsider->user)->get(route('referendums.show', $referendum))->assertNotFound();

    $this->travelTo($referendum->closes_at->addSecond());
    $this->actingAs($this->member->user)->post(route('referendums.votes.store', $referendum), ['answer' => 'for'])
        ->assertSessionHasErrors(['answer' => 'Voting in this referendum has closed.']);

    expect($referendum->fresh()->ballot_count)->toBe(0);
});

it('lets only the proposer withdraw, and nothing changes', function () {
    $this->actingAs($this->proposer->user)->post(route('policies.store'), ['title' => 'Rent strike fund', 'body' => '<p>£5 a month.</p>']);
    $referendum = Referendum::sole();

    $this->actingAs($this->member->user)->post(route('referendums.withdraw', $referendum))
        ->assertSessionHasErrors(['answer' => 'Only the member who proposed this can withdraw it.']);
    $this->actingAs($this->proposer->user)->post(route('referendums.withdraw', $referendum))->assertSessionHasNoErrors();

    $this->travelTo($referendum->closes_at->addMinute());
    $this->artisan('referendums:close');

    expect($referendum->fresh()->outcome)->toBe(ReferendumOutcome::Withdrawn)
        ->and(Policy::sole()->published_at)->toBeNull();
});

it('replaces a policy with its revision when the revision passes', function () {
    $policy = Policy::factory()->create(['building_id' => $this->building->id, 'title' => 'Repairs', 'rich_text' => '<p>Within a week.</p>']);

    $this->actingAs($this->proposer->user)->post(route('policies.store'), [
        'title' => 'Repairs', 'body' => '<p>Within 48 hours.</p>', 'replaces_policy_id' => $policy->id,
    ]);
    $referendum = Referendum::sole();
    expect($referendum->title)->toBe('Revise policy: Repairs');

    $this->actingAs($this->member->user)->get(route('referendums.show', $referendum))
        ->assertSeeInOrder(['What changes', 'Within a week.', 'Within 48 hours.']);

    $this->actingAs($this->member->user)->post(route('policies.repeal', $policy))
        ->assertSessionHasErrors(['reason' => 'There\'s already a referendum to change this policy. Wait until it has been decided.']);

    decide($referendum, [ReferendumAnswer::For, ReferendumAnswer::For]);

    expect($policy->fresh()->superseded_at)->not->toBeNull()
        ->and($referendum->referendable->fresh()->isInForce())->toBeTrue();
});

it('repeals a policy', function () {
    $policy = Policy::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->proposer->user)->post(route('policies.repeal', $policy), ['reason' => 'Out of date.'])->assertRedirect();
    decide(Referendum::sole(), [ReferendumAnswer::For, ReferendumAnswer::For]);

    expect($policy->fresh()->repealed_at)->not->toBeNull();
    $this->actingAs($this->member->user)->get(route('policies.index'))->assertSeeInOrder(['No longer in force', $policy->title, 'Repealed']);
});

it('only revises or repeals policies in force in the member\'s union', function () {
    $repealed = Policy::factory()->create(['building_id' => $this->building->id, 'repealed_at' => now()]);
    $elsewhere = Policy::factory()->create();

    $this->actingAs($this->proposer->user)->post(route('policies.repeal', $repealed))
        ->assertSessionHasErrors(['reason' => 'Only policies in force can be revised or repealed.']);
    $this->actingAs($this->proposer->user)->post(route('policies.repeal', $elsewhere))->assertNotFound();
    $this->actingAs($this->proposer->user)->post(route('policies.store'), ['title' => 'X', 'body' => '<p>Y</p>', 'replaces_policy_id' => $elsewhere->id])
        ->assertSessionHasErrors('replaces_policy_id');

    expect(Referendum::count())->toBe(0);
});

it('shows the result and what changed once a referendum closes', function () {
    $this->actingAs($this->proposer->user)->post(route('constitution.propose'), amendment(['vouches_required' => 3]));
    $referendum = decide(Referendum::sole(), [ReferendumAnswer::For, ReferendumAnswer::Against, ReferendumAnswer::Against]);

    $this->actingAs($this->member->user)->get(route('referendums.show', $referendum))
        ->assertSee('Result: Rejected')
        ->assertSeeInOrder(['For', '1', 'Against', '2', 'Abstain', '0'])
        ->assertSeeInOrder(['Vouches needed to become a member', '2', '3 vouches']);
});

it('lets tenants waiting to be vouched for read the constitution, but not other unions', function () {
    $tenant = Contact::factory()->tenantOf($this->building)->create();

    $this->actingAs($tenant->user)->get(route('constitution.show'))
        ->assertOk()
        ->assertSee("Constitution of {$this->building->name}")
        ->assertDontSee('Propose an amendment');

    $this->actingAs($this->member->user)->get(route('constitution.show'))->assertSee('Propose an amendment');
});

it('lists referendums waiting for a member\'s vote on the dashboard', function () {
    $waiting = Referendum::factory()->create(['building_id' => $this->building->id, 'title' => 'Join the federation?', 'referendable_id' => Policy::factory()->create(['building_id' => $this->building->id, 'published_at' => null])]);
    $voted = Referendum::factory()->create(['building_id' => $this->building->id, 'title' => 'Adopt a logo?', 'referendable_id' => Policy::factory()->create(['building_id' => $this->building->id, 'published_at' => null])]);
    app(Referendums::class)->vote($this->member, $voted, ReferendumAnswer::For);

    $this->actingAs($this->member->user)->get(route('dashboard'))
        ->assertSee('Referendums waiting for your vote')
        ->assertSee('Join the federation?')
        ->assertDontSee('Adopt a logo?');
});
