<?php

use App\Models\Building;
use App\Models\Contact;
use App\Models\User;

it('shows tenants printable flyers and posters with their building\'s join link', function () {
    $building = Building::factory()->create(['name' => 'Example Court Tenants\' Union']);
    $tenant = Contact::factory()->tenantOf($building)->create();

    $this->actingAs($tenant->user)->get(route('invite.print'))
        ->assertOk()
        ->assertSeeText('Example Court Tenants\' Union')
        ->assertSee(route('join', $building->signup_token))
        ->assertSee('data:image/svg+xml;base64,', false)
        ->assertDontSee($tenant->name);
});

it('links to the print page from the dashboard', function () {
    $tenant = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();

    $this->actingAs($tenant->user)->get(route('dashboard'))
        ->assertSee(route('invite.print'));
});

it('hides the print page from users without a building', function () {
    $this->actingAs(User::factory()->create())->get(route('invite.print'))->assertNotFound();
});
