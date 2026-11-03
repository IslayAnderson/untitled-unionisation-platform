<?php

use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;

beforeEach(function () {
    $this->building = Building::factory()->create(['name' => "Example Court's Union", 'contact_email' => 'union@example.test']);
    $this->memberContact = Contact::factory()->tenantOf($this->building)->enrolled()->create(['name' => 'Morgan Member']);
    $this->member = $this->memberContact->user;
    $this->landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
});

it('creates a mailing list of the union\'s contacts', function () {
    $pat = Contact::factory()->atLandlord($this->landlord)->create();
    $sam = Contact::factory()->atLandlord($this->landlord)->create();

    $this->actingAs($this->member)->post('/mailing-lists', [
        'name' => 'Repairs',
        'landlord_id' => $this->landlord->id,
        'contacts' => [$pat->id, $sam->id],
    ]);

    $list = MailingList::sole();

    expect($list->building_id)->toBe($this->building->id)
        ->and($list->contacts->pluck('id')->sort()->values()->all())->toBe([$pat->id, $sam->id]);
});

it('rejects tenants and other unions\' contacts on a list', function (Closure $contact) {
    $this->actingAs($this->member)->post('/mailing-lists', ['name' => 'Repairs', 'contacts' => [$contact($this)->id]])
        ->assertSessionHasErrors(['contacts.0' => 'The selected contacts.0 is invalid.']);

    expect(MailingList::count())->toBe(0);
})->with([
    'a tenant' => [fn ($test) => Contact::factory()->tenantOf($test->building)->create()],
    'another union\'s contact' => [fn () => Contact::factory()->atLandlord(Landlord::factory()->create())->create()],
]);

it('never names the sending member in the email', function () {
    $mail = new UnionMessage($this->building, 'Broken lift', 'The lift has been broken for three weeks.');

    $mail->assertSeeInHtml('The lift has been broken for three weeks.')
        ->assertSeeInHtml("Example Court's Union")
        ->assertDontSeeInHtml('Morgan Member')
        ->assertSeeInText("Example Court's Union")
        ->assertDontSeeInText('Morgan Member');
});

it('strips unsafe HTML from the message body but keeps formatting', function () {
    (new UnionMessage($this->building, 'Hi', '<p onclick="steal()"><strong>Fix the lift</strong></p><script>alert(1)</script>'))
        ->assertSeeInHtml('<p><strong>Fix the lift</strong></p>', false)
        ->assertDontSeeInHtml('<script', false)
        ->assertDontSeeInHtml('onclick', false)
        ->assertSeeInText('Fix the lift')
        ->assertDontSeeInText('<strong>', false);
});

it('hides other unions\' mailing lists', function () {
    $otherList = MailingList::factory()->create();

    $this->actingAs($this->member)->get("/mailing-lists/{$otherList->id}")->assertNotFound();
});

it('saves the union\'s reply-to address', function () {
    $this->actingAs($this->member)->patch('/mailing-lists/reply-to', ['contact_email' => 'new@example.test'])
        ->assertRedirect(route('mailing-lists.index'));

    expect($this->building->fresh()->contact_email)->toBe('new@example.test');
});

it('does not HTML-escape the plain-text version', function () {
    (new UnionMessage($this->building->fill(['name' => "Example Court Tenants' Union"]), 'Hi', "We're waiting"))
        ->assertSeeInText("We're waiting")
        ->assertSeeInText("Example Court Tenants' Union")
        ->assertDontSeeInText('&#039;');
});
