<?php

use App\Models\Building;
use App\Models\Contact;
use App\Models\Reply;
use App\Models\Thread;

beforeEach(function () {
    $this->building = Building::factory()->create();
    [$this->member, $this->other] = Contact::factory()->count(2)->tenantOf($this->building)->enrolled()->create()->all();
});

it('lets a member start a discussion with cleaned rich text', function () {
    $this->actingAs($this->member->user)->post(route('threads.store'), [
        'title' => 'Should we join the renters\' union?',
        'body' => '<p>Thoughts <strong>welcome</strong>.</p><script>x()</script>',
    ])->assertRedirect();

    expect(Thread::sole())
        ->building_id->toBe($this->building->id)
        ->author_id->toBe($this->member->id)
        ->rich_text->toBe('<p>Thoughts <strong>welcome</strong>.</p>');
});

it('lists discussions with the most recent activity first', function () {
    $quiet = Thread::factory()->create(['building_id' => $this->building->id, 'title' => 'Quiet one', 'published_at' => now()->subDay()]);
    Thread::factory()->create(['building_id' => $this->building->id, 'title' => 'Newest', 'published_at' => now()->subHour()]);
    Reply::factory()->create(['replyable_type' => 'thread', 'replyable_id' => $quiet->id, 'author_id' => $this->other->id, 'published_at' => now()]);
    Thread::factory()->create(['title' => 'Another union\'s']);

    $this->actingAs($this->member->user)->get(route('threads.index'))
        ->assertSeeInOrder(['Quiet one', '1 reply', 'Newest'])
        ->assertDontSee('Another union');
});

it('lets members reply, and keeps other unions and tenants out', function () {
    $thread = Thread::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->other->user)->post(route('threads.replies.store', $thread), ['reply' => '<p>Agreed.</p>'])
        ->assertRedirect(route('threads.show', $thread).'#reply-'.Reply::sole()->id);
    $this->actingAs($this->member->user)->get(route('threads.show', $thread))->assertSeeInOrder([$this->other->name, 'Agreed.']);

    $tenant = Contact::factory()->tenantOf($this->building)->create();
    $this->actingAs($tenant->user)->get(route('threads.show', $thread))->assertRedirect(route('dashboard'));

    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();
    $this->actingAs($outsider->user)->get(route('threads.show', $thread))->assertNotFound();
    $this->actingAs($outsider->user)->post(route('threads.replies.store', $thread), ['reply' => '<p>Hi</p>'])->assertNotFound();

    expect(Reply::count())->toBe(1);
});
