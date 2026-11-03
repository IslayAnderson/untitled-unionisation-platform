<?php

use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Referendum;
use App\Models\User;
use App\Services\Cleanup;
use App\Services\DemoUnion;

beforeEach(function () {
    config(['demo.enabled' => true]);
});

function demoVisitor(): User
{
    return User::where('email', config('demo.email'))->sole();
}

it('builds a lived-in union with no real email addresses', function () {
    $this->artisan('demo:reset')->assertSuccessful();

    $demo = Building::where('is_demo', true)->sole();

    expect($demo->units()->count())->toBe(252)
        ->and($demo->units()->pluck('name'))->toContain('A101', 'C503', 'D709')->not->toContain('A110', 'B801')
        ->and($demo->enrolledCount())->toBeGreaterThan(60)
        ->and($demo->confirmedTenantCount() - $demo->enrolledCount())->toBe(5)
        ->and($demo->referendums()->whereNull('closed_at')->count())->toBe(3)
        ->and($demo->referendums()->pluck('outcome')->filter()->map->value->unique()->sort()->values()->all())->toBe(['no_quorum', 'passed', 'rejected', 'withdrawn'])
        ->and($demo->unionMails()->pluck('status')->map->value->unique()->sort()->values()->all())->toBe(['expired', 'pending', 'rejected', 'sent', 'withdrawn'])
        ->and($demo->currentConstitution->rules['referendum_days'])->toBe(10)
        ->and($demo->constitutions()->count())->toBe(3);

    $emails = Contact::where('building_id', $demo->id)->whereNotNull('email')->pluck('email')
        ->concat(User::whereIn('contact_id', $demo->contacts()->select('id'))->pluck('email'));
    expect($emails->reject(fn (string $email) => str_ends_with($email, '.invalid') || str_ends_with($email, '.example')))->toBeEmpty();
});

it('replaces the previous demo when rebuilt, and can remove it', function () {
    $this->artisan('demo:reset')->assertSuccessful();
    $this->artisan('demo:reset')->assertSuccessful();

    expect(Building::where('is_demo', true)->count())->toBe(1);

    $this->artisan('demo:reset', ['--remove' => true])->expectsOutput('Removed the demo union.');

    expect(Building::where('is_demo', true)->count())->toBe(0)
        ->and(User::where('email', 'like', '%@demo.invalid')->count())->toBe(0);
});

it('lets anyone look around as the tenant of C503', function () {
    app(DemoUnion::class)->build();

    $this->get(route('demo.show'))->assertOk()->assertSeeText('Enter the demo');
    $this->post(route('demo.enter'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs(demoVisitor());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('You\'re looking around a demo union')
        ->assertSeeText('Damp and mould: what we expect')
        ->assertSeeText('Major works: our observations');
    $this->get(route('referendums.index'))->assertOk()->assertSeeText('Lower the referendum quorum to 40%');
    $this->get(route('bulletins.index'))->assertOk()->assertSeeText('Welcome to the Larkspur House Tenants\' Union');
    $this->get(route('invite.print'))->assertOk()->assertSee(route('demo.show'))->assertDontSee('/join/');
});

it('shows anyone the demo union\'s printables, with codes that lead to the demo', function () {
    app(DemoUnion::class)->build();
    $demo = Building::where('is_demo', true)->sole();

    $this->get(route('demo.show'))->assertSee(route('demo.printables'));
    $this->get(route('demo.printables'))
        ->assertOk()
        ->assertSeeText('These are the demo union\'s flyers and posters')
        ->assertSee($demo->name)
        ->assertSee(route('demo.show'))
        ->assertDontSee(route('join', $demo->signup_token));
    $this->assertGuest();
});

it('saves nothing a demo visitor does', function () {
    app(DemoUnion::class)->build();
    $visitor = demoVisitor();
    $referendum = Referendum::whereNull('closed_at')->where('title', 'like', '%Damp%')->sole();
    $bulletins = Bulletin::count();

    $this->actingAs($visitor)->from(route('bulletins.create'))
        ->post(route('bulletins.store'), ['title' => 'Hello', 'body' => '<p>Hi</p>'])
        ->assertRedirect(route('bulletins.create'))
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'This is a demo'));
    $this->actingAs($visitor)->post(route('referendums.votes.store', $referendum), ['answer' => 'for']);
    $this->actingAs($visitor)->postJson(route('uploads.images.store'))->assertForbidden();

    expect(Bulletin::count())->toBe($bulletins)
        ->and($referendum->fresh()->ballot_count)->toBe($referendum->ballot_count);

    $this->actingAs($visitor)->post(route('logout'))->assertRedirect();
    $this->assertGuest();
});

it('hides the demo when it is turned off, and nobody can join it', function () {
    app(DemoUnion::class)->build();
    $demo = Building::where('is_demo', true)->sole();

    $this->get(route('join', $demo->signup_token))->assertNotFound();

    config(['demo.enabled' => false]);
    $this->get(route('demo.show'))->assertNotFound();
    $this->get(route('demo.printables'))->assertNotFound();
    $this->post(route('demo.enter'))->assertNotFound();
});

it('leaves the demo out of clean-up', function () {
    app(DemoUnion::class)->build();
    $this->travel(200)->days();

    $cleanup = app(Cleanup::class);

    expect($cleanup->unvouchedAccounts()->count())->toBe(0)
        ->and($cleanup->abandonedUnions()->count())->toBe(0);

    $demo = Building::where('is_demo', true)->sole();
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->post(route('admin.unions.warn', $demo), ['confirm_name' => $demo->name])->assertConflict();
    $this->actingAs($admin)->delete(route('admin.unions.destroy', $demo), ['confirm_name' => $demo->name])->assertConflict();

    expect($demo->fresh()->cleanup_warned_at)->toBeNull();
});
