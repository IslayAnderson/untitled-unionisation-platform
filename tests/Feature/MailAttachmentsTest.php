<?php

use App\Enums\ApprovalDecision;
use App\Mail\UnionMessage;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;
use App\Models\UnionMail;
use App\Models\UnionMailAttachment;
use App\Services\MailApprovals;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Storage::fake('local');
    Storage::fake('public');

    $this->building = Building::factory()->create();
    [$this->author, $this->member] = Contact::factory()->count(3)->tenantOf($this->building)->enrolled()->create()->all();

    $landlord = Landlord::factory()->create(['building_id' => $this->building->id]);
    $this->list = MailingList::factory()->create(['building_id' => $this->building->id]);
    $this->list->contacts()->attach(Contact::factory()->atLandlord($landlord)->create(['email' => 'pat@acme.test']));
});

function instantMailWith(array $attachments, array $overrides = []): TestResponse
{
    return test()->actingAs(test()->author->user)->post(route('mail.store'), [
        'mailing_list_id' => test()->list->id,
        'subject' => 'Broken lift',
        'body' => '<p>See the attached schedule.</p>',
        'attachments' => $attachments,
        ...$overrides,
    ]);
}

it('stores documents privately as they are, and images compressed', function () {
    instantMailWith([
        UploadedFile::fake()->createWithContent('Repair schedule.pdf', '%PDF-1.4 schedule'),
        UploadedFile::fake()->image('lift photo.png', 3000, 1500),
    ])->assertRedirect();

    [$pdf, $photo] = UnionMail::sole()->attachments;

    expect($pdf)
        ->disk->toBe('local')
        ->name->toBe('Repair schedule.pdf')
        ->mime_type->toBe('application/pdf')
        ->uploaded_image_id->toBeNull();
    expect(Storage::disk('local')->get($pdf->path))->toBe('%PDF-1.4 schedule');

    expect($photo)
        ->disk->toBe('public')
        ->name->toBe('lift photo.jpg')
        ->mime_type->toBe('image/jpeg')
        ->uploaded_image_id->not->toBeNull();
    expect(getimagesizefromstring(Storage::disk('public')->get($photo->path))[0])->toBe(2000);
});

it('sends the attachments with the mail once it is approved', function () {
    instantMailWith([UploadedFile::fake()->createWithContent('schedule.pdf', '%PDF-1.4 schedule')]);
    $mail = UnionMail::sole();

    app(MailApprovals::class)->decide($mail->approvers->first()->contact, $mail, ApprovalDecision::Approve);

    Mail::assertQueued(UnionMessage::class, fn (UnionMessage $email) => $email->hasTo('pat@acme.test')
        && $email->hasAttachment(Attachment::fromData(fn () => '%PDF-1.4 schedule', 'schedule.pdf')->withMime('application/pdf')));
});

it('adds attachments to bulletin mail', function () {
    $bulletin = Bulletin::factory()->create(['building_id' => $this->building->id, 'author_id' => $this->author->id]);

    $this->actingAs($this->author->user)->post(route('bulletins.mail', $bulletin), [
        'mailing_list_id' => $this->list->id,
        'subject' => 'Our demands',
        'body' => '<p>Attached.</p>',
        'attachments' => [UploadedFile::fake()->createWithContent('demands.pdf', '%PDF-1.4 demands')],
    ])->assertRedirect();

    expect(UnionMail::sole()->attachments()->sole()->name)->toBe('demands.pdf');
});

it('refuses attachments it shouldn\'t send', function (Closure $attachments, string $field, string $message) {
    instantMailWith($attachments())->assertSessionHasErrors([$field => $message]);

    expect(UnionMail::count())->toBe(0)
        ->and(UnionMailAttachment::count())->toBe(0);
})->with([
    'a program' => [fn () => [UploadedFile::fake()->create('invoice.exe', 10, 'application/x-msdownload')], 'attachments.0', 'Each file must be a PDF, document, spreadsheet, text file or image'],
    'a web page' => [fn () => [UploadedFile::fake()->createWithContent('form.html', '<html><script>x()</script></html>')], 'attachments.0', 'Each file must be a PDF, document, spreadsheet, text file or image'],
    'too many' => [fn () => array_map(fn ($i) => UploadedFile::fake()->create("{$i}.pdf", 1, 'application/pdf'), range(1, 6)), 'attachments', 'Select no more than 5 files'],
    'one too big' => [fn () => [UploadedFile::fake()->create('big.pdf', 10_241, 'application/pdf')], 'attachments.0', 'Each file must be smaller than 10 MB'],
    'too big together' => [fn () => array_map(fn ($i) => UploadedFile::fake()->create("{$i}.pdf", 7_000, 'application/pdf'), range(1, 3)), 'attachments', 'The selected files must be smaller than 20 MB in total'],
]);

it('keeps no files when the mail can\'t be created', function () {
    instantMailWith([
        UploadedFile::fake()->createWithContent('schedule.pdf', '%PDF-1.4'),
        UploadedFile::fake()->createWithContent('photo.jpg', 'not really an image'),
    ])->assertSessionHasErrors(['attachments' => 'That file could not be read as an image.']);

    expect(UnionMail::count())->toBe(0)
        ->and(UnionMailAttachment::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('lets members of the union, and nobody else, download attachments', function () {
    instantMailWith([UploadedFile::fake()->createWithContent('schedule.pdf', '%PDF-1.4 schedule')]);
    $mail = UnionMail::sole();
    $attachment = $mail->attachments()->sole();

    $this->actingAs($this->member->user)->get(route('mail.show', $mail))
        ->assertSee('schedule.pdf')
        ->assertSee(route('mail.attachments.show', [$mail, $attachment]));

    $response = $this->actingAs($this->member->user)->get(route('mail.attachments.show', [$mail, $attachment]))->assertOk();
    expect($response->streamedContent())->toBe('%PDF-1.4 schedule')
        ->and($response->headers->get('content-disposition'))->toContain('schedule.pdf');

    $outsider = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();
    $this->actingAs($outsider->user)->get(route('mail.attachments.show', [$mail, $attachment]))->assertNotFound();

    instantMailWith([], ['subject' => 'Another mail']);
    $otherMail = UnionMail::where('subject', 'Another mail')->sole();
    $this->actingAs($this->member->user)->get(route('mail.attachments.show', [$otherMail, $attachment]))->assertNotFound();
});
