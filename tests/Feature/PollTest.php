<?php

use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Poll;
use App\Support\VouchingBulletin;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->building = Building::factory()->create();
    [$this->author, $this->member, $this->other] = Contact::factory()->count(3)->tenantOf($this->building)->enrolled()->create()->all();
    $this->bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->author->id]);
});

function pollOn(Bulletin $bulletin, array $answers = ['Yes', 'No', 'Not sure'], array $attributes = []): Poll
{
    return Poll::factory()->withAnswers($answers)->create(['bulletin_id' => $bulletin->id, ...$attributes]);
}

it('lets a bulletin\'s author add a poll, ignoring blank answers', function () {
    $closesAt = now()->addDays(3)->startOfMinute();

    $this->actingAs($this->author->user)->post(route('bulletins.polls.store', $this->bulletin), [
        'title' => 'Start a rent strike?',
        'answers' => ['Yes', '', 'No', null],
        'allows_multiple' => '1',
        'closes_at' => $closesAt->format('Y-m-d\TH:i'),
    ])->assertRedirect(route('bulletins.show', $this->bulletin));

    $poll = $this->bulletin->polls()->sole();
    expect($poll->title)->toBe('Start a rent strike?')
        ->and($poll->allows_multiple)->toBeTrue()
        ->and($poll->closes_at->equalTo($closesAt))->toBeTrue()
        ->and($poll->answers->pluck('title')->all())->toBe(['Yes', 'No']);
});

it('rejects polls with too few or repeated answers, or a closing date in the past', function (array $input, string $field) {
    $this->actingAs($this->author->user)->post(route('bulletins.polls.store', $this->bulletin), [
        'title' => 'Start a rent strike?',
        'answers' => ['Yes', 'No'],
        'closes_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ...$input,
    ])->assertSessionHasErrors($field);

    expect(Poll::count())->toBe(0);
})->with([
    'one answer' => [['answers' => ['Yes', '']], 'answers'],
    'repeated answers' => [['answers' => ['Yes', 'yes']], 'answers.1'],
    'closing in the past' => [['closes_at' => now()->subHour()->format('Y-m-d\TH:i')], 'closes_at'],
    'open for too long' => [['closes_at' => now()->addDays(91)->format('Y-m-d\TH:i')], 'closes_at'],
]);

it('only lets the author add polls, and never on the vouching bulletin', function () {
    $input = ['title' => 'Q?', 'answers' => ['A', 'B'], 'closes_at' => now()->addWeek()->format('Y-m-d\TH:i')];

    $this->actingAs($this->member->user)->post(route('bulletins.polls.store', $this->bulletin), $input)
        ->assertSessionHasErrors(['title' => 'Only the author of a bulletin can add a poll to it.']);

    $vouching = VouchingBulletin::createFor($this->building);
    $this->actingAs($this->author->user)->post(route('bulletins.polls.store', $vouching), $input)
        ->assertSessionHasErrors('title');

    $this->actingAs($this->author->user)->post(route('bulletins.polls.store', Bulletin::factory()->create()), $input)
        ->assertNotFound();

    expect(Poll::count())->toBe(0);
});

it('records that a member voted separately from what they chose, without timestamps', function () {
    $poll = pollOn($this->bulletin);
    [$yes, $no] = $poll->answers;
    $this->travel(5)->minutes();

    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => [$no->id]])
        ->assertRedirect(route('bulletins.show', $this->bulletin).'#poll-'.$poll->id);

    expect($poll->voters()->sole()->contact_id)->toBe($this->member->id)
        ->and(Schema::getColumnListing('poll_voters'))->toEqualCanonicalizing(['id', 'poll_id', 'contact_id'])
        ->and($no->fresh()->vote_count)->toBe(1)
        ->and($yes->fresh()->vote_count)->toBe(0)
        ->and($poll->fresh()->ballot_count)->toBe(1)
        ->and($poll->fresh()->updated_at->equalTo($poll->updated_at))->toBeTrue()
        ->and($no->fresh()->updated_at->equalTo($no->updated_at))->toBeTrue();
});

it('counts every answer ticked in a multiple-choice poll as one ballot', function () {
    $poll = pollOn($this->bulletin, ['Mon', 'Tue', 'Wed'], ['allows_multiple' => true]);
    [$mon, $tue, $wed] = $poll->answers;

    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => [$mon->id, $wed->id, $wed->id]]);

    expect([$mon->fresh()->vote_count, $tue->fresh()->vote_count, $wed->fresh()->vote_count])->toBe([1, 0, 1])
        ->and($poll->fresh()->ballot_count)->toBe(1);
});

it('refuses votes it shouldn\'t count', function (Closure $answers, string $message, array $attributes = []) {
    $poll = pollOn($this->bulletin, attributes: $attributes);

    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => $answers($poll)])
        ->assertSessionHasErrors(["poll_{$poll->id}" => $message]);

    expect($poll->voters()->count())->toBe(0)
        ->and($poll->answers()->sum('vote_count'))->toBe(0);
})->with([
    'two answers in a single-choice poll' => [fn (Poll $poll) => $poll->answers->take(2)->modelKeys(), 'Choose one answer.'],
    'another poll\'s answer' => [fn () => [pollOn(Bulletin::factory()->create())->answers[0]->id], 'That answer isn\'t part of this poll.'],
    'after the deadline' => [fn (Poll $poll) => [$poll->answers[0]->id], 'This poll has closed.', ['closes_at' => now()->subMinute()]],
    'after it was closed early' => [fn (Poll $poll) => [$poll->answers[0]->id], 'This poll has closed.', ['closed_at' => now()]],
]);

it('doesn\'t let a member vote twice or change their vote', function () {
    $poll = pollOn($this->bulletin);
    [$yes, $no] = $poll->answers;

    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => [$yes->id]]);
    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => [$no->id]])
        ->assertSessionHasErrors(["poll_{$poll->id}" => 'You\'ve already voted in this poll.']);

    expect([$yes->fresh()->vote_count, $no->fresh()->vote_count])->toBe([1, 0])
        ->and($poll->fresh()->ballot_count)->toBe(1);
});

it('only lets members of the poll\'s union vote', function () {
    $poll = pollOn($this->bulletin);
    $tenant = Contact::factory()->tenantOf($this->building)->create();
    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();

    $this->actingAs($tenant->user)->post(route('polls.votes.store', $poll), ['answers' => [$poll->answers[0]->id]])->assertRedirect(route('dashboard'));
    $this->actingAs($outsider->user)->post(route('polls.votes.store', $poll), ['answers' => [$poll->answers[0]->id]])->assertNotFound();

    expect($poll->voters()->count())->toBe(0);
});

it('hides the results from everyone, including the author, until the poll closes', function () {
    $poll = pollOn($this->bulletin, ['Strike now', 'Wait until spring']);
    [$now, $wait] = $poll->answers;
    $this->actingAs($this->member->user)->post(route('polls.votes.store', $poll), ['answers' => [$now->id]]);
    $this->actingAs($this->other->user)->post(route('polls.votes.store', $poll), ['answers' => [$now->id]]);

    $this->actingAs($this->member->user)->get(route('bulletins.show', $this->bulletin))
        ->assertSee('You\'ve voted. Results will appear here when the poll closes.', false)
        ->assertSee('2 of 3 members have voted.')
        ->assertDontSee('2 votes');

    $this->actingAs($this->author->user)->get(route('bulletins.show', $this->bulletin))
        ->assertSee('Your vote is secret')
        ->assertSee('Close now')
        ->assertDontSee('2 votes');

    $this->travelTo($poll->closes_at->addSecond());

    $this->actingAs($this->author->user)->get(route('bulletins.show', $this->bulletin))
        ->assertSeeInOrder(['Strike now', '2 votes · 100%', 'Wait until spring', '0 votes · 0%', '2 members voted.'])
        ->assertDontSee('Close now');
});

it('lets only the author close a poll early', function () {
    $poll = pollOn($this->bulletin);

    $this->actingAs($this->member->user)->post(route('polls.close', $poll))
        ->assertSessionHasErrors(["poll_{$poll->id}" => 'Only the author of the bulletin can close its poll.']);
    expect($poll->fresh()->isOpen())->toBeTrue();

    $this->actingAs($this->author->user)->post(route('polls.close', $poll))->assertSessionHasNoErrors();
    expect($poll->fresh()->isOpen())->toBeFalse();
});

it('lists open polls a member hasn\'t voted in on the dashboard', function () {
    $waiting = pollOn($this->bulletin, attributes: ['title' => 'Join the march?']);
    $voted = pollOn($this->bulletin, attributes: ['title' => 'Pick a meeting room?']);
    pollOn($this->bulletin, attributes: ['title' => 'Closed already?', 'closed_at' => now()]);
    pollOn(Bulletin::factory()->create(), attributes: ['title' => 'Another union?']);
    $this->actingAs($this->member->user)->post(route('polls.votes.store', $voted), ['answers' => [$voted->answers[0]->id]]);

    $this->actingAs($this->member->user)->get(route('dashboard'))
        ->assertSee('Polls waiting for your vote')
        ->assertSee('Join the march?')
        ->assertDontSee('Pick a meeting room?')
        ->assertDontSee('Closed already?')
        ->assertDontSee('Another union?');
});
