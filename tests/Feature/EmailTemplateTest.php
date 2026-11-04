<?php

use App\Mail\ForwardedReply;
use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\UnionMail;
use App\Notifications\CleanupWarning;
use App\Services\InboundMail\InboundEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

it('renders notifications in the platform\'s template', function () {
    $html = (string) (new CleanupWarning('Testing.', now()->addWeek()))->toMail(new AnonymousNotifiable)->render();

    expect($html)
        ->toContain(config('app.name').' is run by tenants, for tenants')
        ->toContain(route('privacy'))
        ->toContain("If the button doesn't work")
        ->not->toContain('Regards')
        ->not->toContain('Hello!')
        ->not->toContain('laravel.com');
});

it('heads and signs union mail with the union, keeping its rich text as HTML', function () {
    $building = Building::factory()->create(['name' => 'Example Court Tenants\' Union']);

    $html = (new UnionMessage($building, 'Lift', '<p>The lift is <strong>broken</strong>.</p><script>alert(1)</script>', 'replies@example.test'))->render();

    expect($html)
        ->toContain("Example Court Tenants' Union")
        ->toContain('broken</strong>')
        ->not->toContain('href="'.config('app.url').'"')
        ->toContain('Reply to this email to contact the union.')
        ->not->toContain('<script>')
        ->not->toContain('is run by tenants, for tenants');
});

it('shows forwarded replies as escaped text', function () {
    $mail = (new UnionMail)->forceFill(['bulletin_id' => 1, 'subject' => 'Lift'])->setRelation('building', Building::factory()->create());
    $reply = new InboundEmail('1', null, 'agent@example.test', 'Agent', [], 'Re: Lift', "<b>Fixed</b> [link](https://evil.test)\n\nThanks", null);

    $html = (new ForwardedReply($mail, $reply, toMembers: true))->render();

    expect($html)
        ->toContain('&lt;b&gt;Fixed&lt;/b&gt; [link](https://evil.test)')
        ->not->toContain('href="https://evil.test"')
        ->toContain("You're getting this copy as a member");
});

it('sends a sample of every email to one address', function () {
    Mail::fake();
    Notification::fake();

    $this->artisan('mail:samples', ['email' => 'me@example.test'])->assertSuccessful();

    Mail::assertSent(UnionMessage::class, 1);
    Mail::assertSent(ForwardedReply::class, 2);
    Notification::assertSentOnDemandTimes(CleanupWarning::class, 2);
    Notification::assertCount(12);
    Notification::assertSentTimes(VerifyEmail::class, 1);

    $this->artisan('mail:samples', ['email' => 'not an email'])->assertFailed();
});
