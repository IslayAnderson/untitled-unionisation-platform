<?php

use App\Http\Controllers\CookieBannerController;
use App\Models\Building;
use App\Models\Contact;
use App\Notifications\FeedbackReceived;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

it('explains vouching with the default numbers', function () {
    $this->get(route('vouching.guide'))
        ->assertOk()
        ->assertSee('Vouching opens when 3')
        ->assertSee('Repeat until 2 members')
        ->assertSee('fewer than 3 members');
});

it('explains vouching with a tenant\'s own union\'s numbers', function () {
    $building = Building::factory()->create();
    $building->currentConstitution->update(['rules' => ['min_signups' => 5, 'vouches_required' => 3]]);
    $tenant = Contact::factory()->tenantOf($building)->create();

    $this->actingAs($tenant->user)->get(route('vouching.guide'))
        ->assertOk()
        ->assertSee('Vouching opens when 5')
        ->assertSee('Repeat until 3 members')
        ->assertSeeInOrder([$building->name, 'numbers, from its']);
});

it('shows the privacy policy with the controller\'s contact details', function () {
    config(['privacy.controller' => 'Pat Example', 'privacy.email' => 'privacy@example.test']);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Pat Example')
        ->assertSee('privacy@example.test')
        ->assertSee('ico.org.uk/make-a-complaint');
});

it('shows a cookie message until it is hidden', function () {
    $this->get(route('home'))->assertSee('Hide cookie message');

    $this->from(route('privacy'))->post(route('cookies.hide-banner'))
        ->assertRedirect(route('privacy'))
        ->assertCookie(CookieBannerController::COOKIE, '1');

    $this->withCookie(CookieBannerController::COOKIE, '1')->get(route('home'))->assertDontSee('Hide cookie message');
});

it('emails feedback to the privacy contact', function () {
    Notification::fake();
    config(['privacy.email' => 'privacy@example.test']);

    $this->get(route('home'))->assertSee('id="feedback-dialog"', escape: false);

    $this->from(route('home'))->post(route('feedback.store'), [
        'feedback_name' => 'Sam',
        'feedback_email' => 'sam@example.test',
        'feedback_details' => 'The vouching page helped.',
    ])->assertRedirect(route('home'))->assertSessionHas('status');

    Notification::assertSentTo(
        new AnonymousNotifiable,
        FeedbackReceived::class,
        fn (FeedbackReceived $feedback, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'privacy@example.test'
            && $feedback->details === 'The vouching page helped.'
            && $feedback->toMail($notifiable)->replyTo === [['sam@example.test', 'Sam']],
    );
});

it('accepts anonymous feedback, and shows errors on the feedback page', function () {
    Notification::fake();

    $this->from(route('home'))->post(route('feedback.store'), ['feedback_details' => 'Anonymous note'])->assertSessionHasNoErrors();
    Notification::assertSentTimes(FeedbackReceived::class, 1);

    $this->from(route('home'))->post(route('feedback.store'), ['feedback_details' => ''])
        ->assertRedirect(route('feedback.create'))
        ->assertSessionHasErrors(['feedback_details' => 'Enter your feedback']);
});
