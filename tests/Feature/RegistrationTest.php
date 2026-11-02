<?php

use App\Models\Building;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function tenantDetails(array $overrides = []): array
{
    return [
        'name' => 'Sam Tenant',
        'email' => 'sam@example.test',
        'telephone' => '07700 900123',
        'unit_name' => 'Flat 4',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        ...$overrides,
    ];
}

it('registers a tenant for a new building and sends a confirmation email', function () {
    Notification::fake();

    $this->post('/register', tenantDetails(['address_id' => 'fake-1']))
        ->assertRedirect(route('verification.notice'));

    $user = User::firstWhere('email', 'sam@example.test');

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->contact->name)->toBe('Sam Tenant')
        ->and($user->contact->building_id)->toBeNull()
        ->and($user->contact->pending_signup['route'])->toBe('new')
        ->and($user->contact->pending_signup['address']['provider_id'])->toBe('fake-1')
        ->and($user->contact->pending_signup['unit_name'])->toBe('Flat 4');

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects an address ID the lookup provider does not know', function () {
    $this->post('/register', tenantDetails(['address_id' => 'forged']))
        ->assertSessionHasErrors('address_id');

    expect(User::count())->toBe(0);
});

it('registers a tenant through a building join link', function () {
    Notification::fake();
    $building = Building::factory()->create();

    $this->get("/join/{$building->signup_token}")->assertOk()->assertSee($building->name);

    $this->post("/join/{$building->signup_token}", tenantDetails())
        ->assertRedirect(route('verification.notice'));

    $contact = User::firstWhere('email', 'sam@example.test')->contact;

    expect($contact->pending_signup)->toMatchArray(['route' => 'join', 'building_id' => $building->id]);
});

it('returns 404 for an unknown join link', function () {
    $this->get('/join/not-a-real-token')->assertNotFound();
});

it('keeps unconfirmed tenants out of onboarding', function () {
    $this->post('/register', tenantDetails(['address_id' => 'fake-1']));

    $this->get('/onboarding')->assertRedirect(route('verification.notice'));
});

it('logs a tenant in and out', function () {
    $user = User::factory()->create(['email' => 'sam@example.test']);

    $this->post('/login', ['email' => 'sam@example.test', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/login', ['email' => 'sam@example.test', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
