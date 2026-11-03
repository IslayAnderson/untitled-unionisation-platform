<?php

use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Poll;
use App\Models\Reply;
use App\Models\Thread;
use App\Support\VouchingBulletin;

beforeEach(function () {
    $this->building = Building::factory()->create();
    [$this->author, $this->other] = Contact::factory()->count(2)->tenantOf($this->building)->enrolled()->create()->all();
    $this->bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->author->id]);
    $this->thread = Thread::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->author->id]);
});

it('lets authors edit and delete bulletins and discussions with cleaned text', function (string $kind) {
    $record = $kind === 'bulletins' ? $this->bulletin : $this->thread;

    $this->actingAs($this->author->user)->get(route("{$kind}.show", $record))->assertSee('Until someone replies');
    $this->actingAs($this->author->user)->put(route("{$kind}.update", $record), ['title' => 'New title', 'body' => '<p>Fixed <em>typo</em>.</p><script>x()</script>'])
        ->assertRedirect(route("{$kind}.show", $record));

    expect($record->fresh())->title->toBe('New title')->rich_text->toBe('<p>Fixed <em>typo</em>.</p>');

    $this->actingAs($this->author->user)->delete(route("{$kind}.destroy", $record))->assertRedirect(route("{$kind}.index"));
    expect($record->fresh())->toBeNull();
})->with(['bulletins', 'threads']);

it('stops changes once a bulletin or discussion has replies, and by anyone but the author', function (string $kind) {
    $record = $kind === 'bulletins' ? $this->bulletin : $this->thread;

    $this->actingAs($this->other->user)->put(route("{$kind}.update", $record), ['title' => 'Hijacked', 'body' => '<p>x</p>'])
        ->assertSessionHas('status');
    $this->actingAs($this->other->user)->get(route("{$kind}.show", $record))->assertDontSee('Until someone replies');

    Reply::factory()->create(['replyable_type' => str($kind)->singular()->toString(), 'replyable_id' => $record->id, 'author_id' => $this->other->id]);

    $this->actingAs($this->author->user)->put(route("{$kind}.update", $record), ['title' => 'Too late', 'body' => '<p>x</p>']);
    $this->actingAs($this->author->user)->delete(route("{$kind}.destroy", $record));

    expect($record->fresh())->not->toBeNull()->title->not->toBeIn(['Hijacked', 'Too late']);
})->with(['bulletins', 'threads']);

it('stops changes to bulletins with polls or mail, the vouching bulletin and referendum discussions', function () {
    Poll::factory()->create(['bulletin_id' => $this->bulletin->id]);
    $vouching = VouchingBulletin::createFor($this->building);

    expect($this->bulletin->canBeChangedBy($this->author))->toBeFalse()
        ->and($vouching->canBeChangedBy($this->author))->toBeFalse();

    $this->actingAs($this->author->user)->post(route('policies.store'), ['title' => 'A policy', 'body' => '<p>Text</p>']);
    $discussion = Thread::where('title', 'Referendum: Adopt policy: A policy')->sole();
    expect($discussion->canBeChangedBy($this->author))->toBeFalse();
});

it('lets members change their own reply until someone replies after it', function () {
    $first = Reply::factory()->create(['replyable_id' => $this->bulletin->id, 'author_id' => $this->author->id, 'rich_text' => '<p>First</p>']);

    $this->actingAs($this->author->user)->put(route('replies.update', $first), ['reply' => '<p>First, edited</p>'])
        ->assertRedirect(route('bulletins.show', $this->bulletin).'#reply-'.$first->id);
    expect($first->fresh()->rich_text)->toBe('<p>First, edited</p>');

    $this->actingAs($this->other->user)->delete(route('replies.destroy', $first));
    expect($first->fresh())->not->toBeNull();

    $second = Reply::factory()->create(['replyable_id' => $this->bulletin->id, 'author_id' => $this->other->id]);
    $this->actingAs($this->author->user)->delete(route('replies.destroy', $first));
    expect($first->fresh())->not->toBeNull();

    $this->actingAs($this->other->user)->delete(route('replies.destroy', $second))->assertRedirect(route('bulletins.show', $this->bulletin));
    expect($second->fresh())->toBeNull();
});

it('keeps tenants waiting to be vouched for to plain text, and other unions out', function () {
    $vouching = VouchingBulletin::createFor($this->building);
    $tenant = Contact::factory()->tenantOf($this->building)->create();
    $reply = Reply::factory()->create(['replyable_id' => $vouching->id, 'author_id' => $tenant->id, 'rich_text' => '<p>Hi</p>']);

    $this->actingAs($tenant->user)->get(route('replies.edit', $reply))->assertOk()->assertSee('Text only');
    $this->actingAs($tenant->user)->put(route('replies.update', $reply), ['reply' => '<b>Can meet</b> Tuesday']);
    expect($reply->fresh()->rich_text)->toBe('<p>&lt;b&gt;Can meet&lt;/b&gt; Tuesday</p>');

    $threadReply = Reply::factory()->create(['replyable_type' => 'thread', 'replyable_id' => $this->thread->id, 'author_id' => $this->author->id]);
    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();
    $threadReply->update(['author_id' => $outsider->id]);
    $this->actingAs($outsider->user)->delete(route('replies.destroy', $threadReply))->assertNotFound();
    expect($threadReply->fresh())->not->toBeNull();
});
