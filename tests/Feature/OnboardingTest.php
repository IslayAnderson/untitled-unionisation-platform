<?php

use App\Models\Building;
use App\Models\Contact;
use App\Models\User;
use App\Services\AddressLookup\AddressLookup;
use Illuminate\Support\Facades\URL;

/**
 * A verified user who signed up for a new building at the given fake address.
 */
function pendingTenant(string $addressId = 'fake-1', string $unitName = 'Flat 4'): User
{
    $address = app(AddressLookup::class)->find($addressId);

    $contact = Contact::factory()->create([
        'pending_signup' => ['route' => 'new', 'address' => $address->toArray(), 'unit_name' => $unitName],
    ]);

    return User::factory()->create(['contact_id' => $contact->id]);
}

it('confirms the email and moves on to onboarding', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('onboarding'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('shows the building detail form when the address is new', function () {
    $this->actingAs(pendingTenant())
        ->get('/onboarding')
        ->assertOk()
        ->assertSee("You're the first from your building", false)
        ->assertSee('Example Court, 1 Example Street');
});

it('creates the building, boilerplate constitution and unit', function () {
    $user = pendingTenant();

    $this->actingAs($user)
        ->post('/onboarding/building', ['name' => 'Example Court Tenants\' Union', 'unit_name' => 'Flat 4'])
        ->assertRedirect(route('dashboard'));

    $building = Building::sole();
    $contact = $user->contact->fresh();

    expect($building->address_provider_id)->toBe('fake-1')
        ->and($building->postcode)->toBe('EC1A 1AA')
        ->and($building->currentConstitution->rules)->toMatchArray(['min_signups' => 3, 'vouches_required' => 2])
        ->and($building->currentConstitution->published_at)->not->toBeNull()
        ->and($contact->building_id)->toBe($building->id)
        ->and($contact->unit->name)->toBe('Flat 4')
        ->and($contact->pending_signup)->toBeNull();

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSee('Waiting for neighbours')
        ->assertSee(route('join', $building->signup_token));
});

it('sends a tenant whose address is already registered to the unit form', function () {
    $building = Building::factory()->create(['address_provider_id' => 'fake-1']);
    $user = pendingTenant('fake-1', 'flat  4');

    $this->actingAs($user)->get('/onboarding')
        ->assertOk()
        ->assertSee("Joining {$building->name}");

    $this->actingAs($user)->post('/onboarding/building', ['name' => 'Duplicate', 'unit_name' => 'Flat 4'])
        ->assertStatus(302);

    expect(Building::count())->toBe(1)
        ->and($user->contact->fresh()->building_id)->toBe($building->id);
});

it('dedupes unit names within a building', function () {
    $building = Building::factory()->create();
    $first = Contact::factory()->tenantOf($building, 'Flat 4')->create();

    $contact = Contact::factory()->create(['pending_signup' => ['route' => 'join', 'building_id' => $building->id, 'unit_name' => 'FLAT 4.']]);
    $user = User::factory()->create(['contact_id' => $contact->id]);

    $this->actingAs($user)->post('/onboarding/unit', ['unit_name' => 'FLAT 4.'])->assertRedirect(route('dashboard'));

    expect($contact->fresh()->unit_id)->toBe($first->unit_id)
        ->and($building->units()->count())->toBe(1);
});

it('sends placed tenants straight to the dashboard', function () {
    $building = Building::factory()->create();
    $contact = Contact::factory()->tenantOf($building)->create();

    $this->actingAs($contact->user)->get('/onboarding')->assertRedirect(route('dashboard'));
});
