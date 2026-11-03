<?php

use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Support\RichText;
use App\Support\VouchingBulletin;

beforeEach(function () {
    $this->building = Building::factory()->create();
    $this->bulletin = $this->building->vouchingBulletin;
    $this->member = Contact::factory()->tenantOf($this->building)->enrolled()->create();
    $this->waiting = Contact::factory()->tenantOf($this->building)->create();
});

it('gives every union one pinned vouching bulletin', function () {
    expect($this->bulletin)
        ->title->toBe('Arrange vouching')
        ->pinned_at->not->toBeNull()
        ->author_id->toBeNull()
        ->and(VouchingBulletin::createFor($this->building)->id)->toBe($this->bulletin->id)
        ->and($this->building->bulletins()->count())->toBe(1);
});

it('lists the pinned bulletin first', function () {
    Bulletin::factory()->create(['building_id' => $this->building->id, 'title' => 'Newer news', 'published_at' => now()->addDay()]);

    $this->actingAs($this->member->user)->get(route('bulletins.index'))
        ->assertSeeInOrder(['Arrange vouching', 'Pinned', 'Newer news']);
});

it('lets tenants waiting to be vouched for read it and reply in text only', function () {
    $this->actingAs($this->waiting->user)->get(route('bulletins.show', $this->bulletin))
        ->assertOk()
        ->assertSee('Text only until you&#039;ve been vouched for', false)
        ->assertDontSee('data-rich-text', false)
        ->assertDontSee('Send as mail');

    $this->actingAs($this->waiting->user)
        ->post(route('bulletins.replies.store', $this->bulletin), [
            'reply' => "Hi, I'm free Tuesday evening.\n<img src=\"".RichText::uploadsUrl().'1/a.jpg"><b>bold</b> https://example.org',
        ])
        ->assertRedirect();

    expect($this->bulletin->replies()->sole())
        ->author_id->toBe($this->waiting->id)
        ->rich_text->toBe('<p>Hi, I&#039;m free Tuesday evening.<br>&lt;img src=&quot;'.e(RichText::uploadsUrl()).'1/a.jpg&quot;&gt;&lt;b&gt;bold&lt;/b&gt; https://example.org</p>');
});

it('marks who has and hasn\'t been vouched for in the thread', function () {
    $this->actingAs($this->waiting->user)->post(route('bulletins.replies.store', $this->bulletin), ['reply' => 'Hello from upstairs']);
    $this->actingAs($this->member->user)->post(route('bulletins.replies.store', $this->bulletin), ['reply' => '<p>See you in the <strong>lobby</strong> at 7</p>']);

    $this->actingAs($this->waiting->user)->get(route('bulletins.show', $this->bulletin))
        ->assertSeeInOrder(['Not yet vouched for', $this->waiting->name, 'Hello from upstairs', 'Member', $this->member->name, '<strong>lobby</strong>'], false);
});

it('keeps tenants waiting to be vouched for out of every other bulletin', function () {
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id]);
    $otherUnions = Building::factory()->create()->vouchingBulletin;

    $this->actingAs($this->waiting->user)->get(route('bulletins.show', $bulletin))->assertNotFound();
    $this->actingAs($this->waiting->user)->post(route('bulletins.replies.store', $bulletin), ['reply' => 'Hi'])->assertNotFound();
    $this->actingAs($this->waiting->user)->get(route('bulletins.show', $otherUnions))->assertNotFound();
    $this->actingAs($this->waiting->user)->post(route('bulletins.replies.store', $otherUnions), ['reply' => 'Hi'])->assertNotFound();
    $this->actingAs($this->member->user)->get(route('bulletins.show', $otherUnions))->assertNotFound();

    expect($bulletin->replies()->count() + $otherUnions->replies()->count())->toBe(0);
});

it('refuses blank and overlong text replies', function () {
    $this->actingAs($this->waiting->user)->post(route('bulletins.replies.store', $this->bulletin), ['reply' => '   '])
        ->assertSessionHasErrors('reply');
    $this->actingAs($this->waiting->user)->post(route('bulletins.replies.store', $this->bulletin), ['reply' => str_repeat('a', 2001)])
        ->assertSessionHasErrors('reply');

    expect($this->bulletin->replies()->count())->toBe(0);
});

it('links to it from the dashboard', function (string $who) {
    $this->actingAs($this->{$who}->user)->get(route('dashboard'))
        ->assertSee(route('bulletins.show', $this->bulletin))
        ->assertSee('pinned vouching bulletin');
})->with(['waiting', 'member']);
