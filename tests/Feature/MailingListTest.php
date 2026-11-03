<?php

use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;
use Illuminate\Support\Facades\Mail;

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

it('emails each contact separately, signed by the union, and logs the send', function () {
    Mail::fake();
    $list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $pat = Contact::factory()->atLandlord($this->landlord)->create(['email' => 'pat@acme.test']);
    $sam = Contact::factory()->atLandlord($this->landlord)->create(['email' => 'sam@acme.test']);
    $noEmail = Contact::factory()->atLandlord($this->landlord)->create(['email' => null]);
    $list->contacts()->attach([$pat->id, $sam->id, $noEmail->id]);

    $this->actingAs($this->member)->post("/mailing-lists/{$list->id}/messages", [
        'subject' => 'Broken lift',
        'body' => 'The lift has been broken for three weeks.',
    ])->assertRedirect(route('mailing-lists.show', $list))
        ->assertSessionHas('status', 'Sending to 2 contacts.');

    Mail::assertQueuedCount(2);
    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $mail) => $mail->hasTo('pat@acme.test') && ! $mail->hasTo('sam@acme.test'));
    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $mail) => $mail->hasTo('sam@acme.test')
        && $mail->hasFrom(config('mail.from.address'), "Example Court's Union")
        && $mail->hasReplyTo('union@example.test')
        && $mail->hasSubject('Broken lift'));

    $message = $list->messages()->sole();

    expect($message->recipient_count)->toBe(2)
        ->and($message->sender_contact_id)->toBe($this->memberContact->id);
});

it('never names the sending member in the email', function () {
    $mail = new UnionMessage($this->building, 'Broken lift', 'The lift has been broken for three weeks.');

    $mail->assertSeeInHtml('The lift has been broken for three weeks.')
        ->assertSeeInHtml("Example Court's Union")
        ->assertDontSeeInHtml('Morgan Member')
        ->assertSeeInText("Example Court's Union")
        ->assertDontSeeInText('Morgan Member');
});

it('escapes HTML in the message body', function () {
    (new UnionMessage($this->building, 'Hi', '<script>alert(1)</script>'))
        ->assertDontSeeInHtml('<script>alert(1)</script>', false)
        ->assertSeeInHtml('&lt;script&gt;', false);
});

it('refuses to send when nobody on the list has an email', function () {
    Mail::fake();
    $list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $list->contacts()->attach(Contact::factory()->atLandlord($this->landlord)->create(['email' => null]));

    $this->actingAs($this->member)->post("/mailing-lists/{$list->id}/messages", ['subject' => 'Hi', 'body' => 'Hello'])
        ->assertSessionHasErrors(['body' => 'Nobody on this list has an email address.']);

    Mail::assertNothingQueued();
    expect($list->messages()->count())->toBe(0);
});

it('requires a subject and message', function () {
    $list = MailingList::factory()->create(['building_id' => $this->building->id]);

    $this->actingAs($this->member)->post("/mailing-lists/{$list->id}/messages", [])
        ->assertSessionHasErrors([
            'subject' => 'The subject field is required.',
            'body' => 'The body field is required.',
        ]);
});

it('hides other unions\' mailing lists', function () {
    Mail::fake();
    $otherList = MailingList::factory()->create();

    $this->actingAs($this->member)->get("/mailing-lists/{$otherList->id}")->assertNotFound();
    $this->actingAs($this->member)->post("/mailing-lists/{$otherList->id}/messages", ['subject' => 'Hi', 'body' => 'Hello'])->assertNotFound();

    Mail::assertNothingQueued();
});

it('shows the sent log with the sender to members', function () {
    $list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $list->messages()->create([
        'sender_contact_id' => $this->memberContact->id,
        'subject' => 'Broken lift',
        'body' => 'Please fix it.',
        'recipient_count' => 3,
        'sent_at' => now(),
    ]);

    $this->actingAs($this->member)->get("/mailing-lists/{$list->id}")
        ->assertOk()
        ->assertSee('Broken lift')
        ->assertSee('3 recipients')
        ->assertSee('sent by Morgan Member')
        ->assertDontSee('&amp;#039;', false);
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
