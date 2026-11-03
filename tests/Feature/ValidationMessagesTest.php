<?php

use App\Models\Building;
use App\Models\Contact;

beforeEach(function () {
    $this->building = Building::factory()->create();
    $this->member = Contact::factory()->tenantOf($this->building)->enrolled()->create();
});

it('names the empty box when rich text has no visible text', function (string $route, string $message) {
    $this->actingAs($this->member->user)->post(route($route), ['title' => 'A title', 'body' => '<p>&nbsp;</p>'])
        ->assertSessionHasErrors(['body' => $message]);
})->with([
    'a bulletin' => ['bulletins.store', 'Enter the text'],
    'a policy' => ['policies.store', 'Enter the policy'],
]);

it('says what to do when a form is left empty', function () {
    $this->actingAs($this->member->user)->post(route('bulletins.store'), [])
        ->assertSessionHasErrors(['title' => 'Enter a title', 'body' => 'Enter the text']);
});

it('asks for a correct email address and password when logging in fails', function () {
    $this->post(route('login'), ['email' => $this->member->user->email, 'password' => 'not the password'])
        ->assertSessionHasErrors(['email' => 'Enter a correct email address and password']);
});
