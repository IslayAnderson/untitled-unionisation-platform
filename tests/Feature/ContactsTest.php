<?php

use App\Models\Building;
use App\Models\Contact;
use App\Models\Landlord;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->building = Building::factory()->create();
    $this->member = Contact::factory()->tenantOf($this->building)->enrolled()->create()->user;
});

it('sends tenants who are not yet members back to the dashboard', function () {
    $tenant = Contact::factory()->tenantOf($this->building)->create()->user;

    $this->actingAs($tenant)->get('/contacts')
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('notice', 'Only union members can use that. Get vouched for by your neighbours first.');
});

it('sends guests to log in', function () {
    $this->get('/contacts')->assertRedirect(route('login'));
});

it('lists only this union\'s landlords and contacts, never tenants', function () {
    $landlord = Landlord::factory()->create(['building_id' => $this->building->id, 'name' => 'Acme Lettings']);
    Contact::factory()->atLandlord($landlord)->create(['name' => 'Pat Agent']);
    Contact::factory()->create(['building_id' => $this->building->id, 'name' => 'Councillor Jo']);
    $neighbour = Contact::factory()->tenantOf($this->building)->create();

    $otherLandlord = Landlord::factory()->create(['name' => 'Other Union Landlord']);
    Contact::factory()->atLandlord($otherLandlord)->create(['name' => 'Other Union Agent']);

    $this->actingAs($this->member)->get('/contacts')
        ->assertOk()
        ->assertSeeInOrder(['Acme Lettings', 'Pat Agent', 'Other contacts', 'Councillor Jo'])
        ->assertDontSee('Other Union Landlord')
        ->assertDontSee('Other Union Agent')
        ->assertDontSee($neighbour->name);
});

it('adds a landlord and marks it as the building owner', function () {
    $this->actingAs($this->member)->post('/landlords', [
        'name' => 'Big Property Ltd',
        'company_number' => '01234567',
        'email' => 'info@bigproperty.test',
        'is_building_owner' => '1',
    ])->assertRedirect(route('contacts.index'));

    $landlord = Landlord::sole();

    expect($landlord->building_id)->toBe($this->building->id)
        ->and($landlord->company_number)->toBe('01234567')
        ->and($this->building->fresh()->building_owner_id)->toBe($landlord->id);
});

it('unmarks the building owner', function () {
    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $this->building->update(['building_owner_id' => $landlord->id]);

    $this->actingAs($this->member)->put("/landlords/{$landlord->id}", [
        'name' => $landlord->name,
        'is_building_owner' => '0',
    ])->assertRedirect(route('contacts.index'));

    expect($this->building->fresh()->building_owner_id)->toBeNull();
});

it('adds a contact as a landlord\'s primary contact', function () {
    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->member)->post('/contacts', [
        'name' => 'Pat Agent',
        'landlord_id' => $landlord->id,
        'email' => 'pat@acme.test',
        'is_primary' => '1',
    ])->assertRedirect(route('contacts.index'));

    $contact = Contact::firstWhere('name', 'Pat Agent');

    expect($contact->building_id)->toBe($this->building->id)
        ->and($contact->landlord_id)->toBe($landlord->id)
        ->and($landlord->fresh()->primary_contact_id)->toBe($contact->id);
});

it('clears the primary contact when they move to another landlord', function () {
    $first = Landlord::factory()->create(['building_id' => $this->building->id]);
    $second = Landlord::factory()->create(['building_id' => $this->building->id]);
    $contact = Contact::factory()->atLandlord($first)->create();
    $first->update(['primary_contact_id' => $contact->id]);

    $this->actingAs($this->member)->put("/contacts/{$contact->id}", [
        'name' => $contact->name,
        'landlord_id' => $second->id,
        'is_primary' => '0',
    ])->assertRedirect(route('contacts.index'));

    expect($first->fresh()->primary_contact_id)->toBeNull()
        ->and($contact->fresh()->landlord_id)->toBe($second->id);
});

it('requires a contact name', function () {
    $this->actingAs($this->member)->post('/contacts', [])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);
});

it('rejects linking a contact to another union\'s landlord', function () {
    $otherLandlord = Landlord::factory()->create();

    $this->actingAs($this->member)->post('/contacts', ['name' => 'Pat', 'landlord_id' => $otherLandlord->id])
        ->assertSessionHasErrors(['landlord_id' => 'The selected landlord id is invalid.']);
});

it('keeps a deleted landlord\'s contacts', function () {
    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $contact = Contact::factory()->atLandlord($landlord)->create();

    $this->actingAs($this->member)->delete("/landlords/{$landlord->id}")->assertRedirect(route('contacts.index'));

    expect(Landlord::count())->toBe(0)
        ->and($contact->fresh()->landlord_id)->toBeNull();
});

it('hides other unions\' records and tenant contacts from editing', function (Closure $record, string $url) {
    $this->actingAs($this->member)->get(sprintf($url, $record($this->building)->id))->assertNotFound();
})->with([
    'another union\'s landlord' => [fn () => Landlord::factory()->create(), '/landlords/%d/edit'],
    'another union\'s contact' => [fn () => Contact::factory()->atLandlord(Landlord::factory()->create())->create(), '/contacts/%d/edit'],
    'a tenant of this union' => [fn (Building $building) => Contact::factory()->tenantOf($building)->create(), '/contacts/%d/edit'],
]);

it('only lets enrolled members of the same union manage a record', function () {
    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $tenant = Contact::factory()->tenantOf($this->building)->create()->user;
    $otherMember = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create()->user;

    expect(Gate::forUser($this->member)->allows('manage-union-record', $landlord))->toBeTrue()
        ->and(Gate::forUser($tenant)->allows('manage-union-record', $landlord))->toBeFalse()
        ->and(Gate::forUser($otherMember)->allows('manage-union-record', $landlord))->toBeFalse()
        ->and(Gate::forUser($this->member)->allows('manage-union-record', $tenant->contact))->toBeFalse();
});

it('shows members the contacts and mailing list links', function () {
    $this->actingAs($this->member)->get('/dashboard')
        ->assertSee(route('contacts.index'))
        ->assertSee(route('mailing-lists.index'));

    $tenant = Contact::factory()->tenantOf($this->building)->create()->user;

    $this->actingAs($tenant)->get('/dashboard')->assertDontSee(route('contacts.index'));
});
