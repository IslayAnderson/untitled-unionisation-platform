<?php

use App\Exceptions\VouchRefused;
use App\Models\Building;
use App\Models\Contact;
use App\Services\Vouching;

beforeEach(function () {
    $this->building = Building::factory()->create();
    $this->vouching = app(Vouching::class);
});

function tenants(Building $building, int $count): array
{
    return Contact::factory()->count($count)->tenantOf($building)->create()->all();
}

it('keeps vouching closed until enough tenants have signed up', function () {
    [$a, $b] = tenants($this->building, 2);

    expect($this->building->vouchingOpen())->toBeFalse()
        ->and($this->vouching->refusal($a, $b))->toBe('Vouching opens once 3 tenants have signed up.');
});

it('does not count tenants who have not confirmed their email', function () {
    tenants($this->building, 2);
    $unconfirmed = Contact::factory()->tenantOf($this->building)->create();
    $unconfirmed->user->forceFill(['email_verified_at' => null])->save();

    expect($this->building->confirmedTenantCount())->toBe(2)
        ->and($this->building->vouchingOpen())->toBeFalse();
});

it('lets the first tenants vouch for each other to bootstrap the union', function () {
    [$a, $b, $c] = tenants($this->building, 3);

    $this->vouching->vouch($a, $b);
    $this->vouching->vouch($c, $b);
    $this->vouching->vouch($b, $a);
    $this->vouching->vouch($c, $a);
    $this->vouching->vouch($a, $c);
    $this->vouching->vouch($b, $c);

    expect([$a->fresh(), $b->fresh(), $c->fresh()])->each(fn ($contact) => $contact->isEnrolled()->toBeTrue())
        ->and($this->building->enrolledCount())->toBe(3);
});

it('enrols a tenant only once they reach the vouches required', function () {
    [$a, $b, $c] = tenants($this->building, 3);

    $this->vouching->vouch($a, $c);
    expect($c->fresh()->isEnrolled())->toBeFalse();

    $this->vouching->vouch($b, $c);
    expect($c->fresh()->isEnrolled())->toBeTrue();
});

it('reads the vouches required from the current constitution', function () {
    $this->building->currentConstitution->update(['rules' => ['min_signups' => 2, 'vouches_required' => 1]]);
    [$a, $b] = tenants($this->building, 2);

    $this->vouching->vouch($a, $b);

    expect($b->fresh()->isEnrolled())->toBeTrue();
});

it('only lets members vouch once the union is established', function () {
    tenants($this->building, 1);
    Contact::factory()->count(3)->tenantOf($this->building)->enrolled()->create();
    [$newcomer, $other] = tenants($this->building, 2);

    expect($this->building->isBootstrapping())->toBeFalse()
        ->and($this->vouching->refusal($newcomer, $other))->toBe('Only members can vouch for new tenants.');
});

it('refuses invalid vouches', function () {
    [$a, $b, $c] = tenants($this->building, 3);
    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->create();
    $member = Contact::factory()->tenantOf($this->building)->enrolled()->create();

    expect($this->vouching->refusal($a, $a))->toBe("You can't vouch for yourself.")
        ->and($this->vouching->refusal($a, $outsider))->toBe('You can only vouch for tenants of your own building.')
        ->and($this->vouching->refusal($a, $member))->toBe("{$member->name} is already a member.");

    $this->vouching->vouch($a, $b);

    expect(fn () => $this->vouching->vouch($a, $b))->toThrow(VouchRefused::class, "You've already vouched for {$b->name}.");
});

it('vouches through the QR code page', function () {
    [$a, $b] = tenants($this->building, 3);

    $this->actingAs($a->user)
        ->get("/vouch/{$b->vouch_token}")
        ->assertOk()
        ->assertSee("I confirm {$b->name} lives here", false);

    $this->actingAs($a->user)
        ->post("/vouch/{$b->vouch_token}")
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', "You vouched for {$b->name}.");

    expect($b->vouchesReceived()->count())->toBe(1);
});

it('shows the refusal reason instead of the confirm button', function () {
    [$a, $b] = tenants($this->building, 2);

    $this->actingAs($a->user)
        ->get("/vouch/{$b->vouch_token}")
        ->assertOk()
        ->assertSee('Vouching opens once 3 tenants have signed up.')
        ->assertDontSee('lives here');

    $this->actingAs($a->user)->post("/vouch/{$b->vouch_token}")->assertSessionHasErrors('vouch');
});

it('sends guests to log in before vouching', function () {
    [$a] = tenants($this->building, 1);

    $this->get("/vouch/{$a->vouch_token}")->assertRedirect(route('login'));
});

it('shows a personal QR code on the dashboard once vouching opens', function () {
    [$a] = tenants($this->building, 3);

    $this->actingAs($a->user)->get('/dashboard')
        ->assertOk()
        ->assertSee('Get vouched for')
        ->assertSee(route('vouch.show', $a->vouch_token));
});
