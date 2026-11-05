<?php

use App\Enums\ApprovalDecision;
use App\Enums\QrScanKind;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\DemoSession;
use App\Models\Landlord;
use App\Models\MailingList;
use App\Models\Poll;
use App\Models\QrScan;
use App\Models\Referendum;
use App\Models\Reply;
use App\Models\Thread;
use App\Models\UploadedImage;
use App\Models\User;
use App\Notifications\CleanupWarning;
use App\Notifications\UnionDataExported;
use App\Services\Cleanup;
use App\Services\MailApprovals;
use App\Services\UnionExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->create(['email' => 'admin@example.test'])->forceFill(['is_admin' => true]);
    $this->admin->save();
});

it('grants and revokes admin access from the command line', function () {
    $user = User::factory()->create(['email' => 'sam@example.test']);

    $this->artisan('admin:grant', ['email' => 'sam@example.test'])->expectsOutput('sam@example.test is now an admin.')->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeTrue();

    $this->artisan('admin:grant', ['email' => 'sam@example.test', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeFalse();

    $this->artisan('admin:grant', ['email' => 'nobody@example.test'])->assertFailed();
});

it('creates an admin account that isn\'t a tenant, asking for the password', function () {
    $this->artisan('admin:create', ['email' => 'Sam@Example.test'])
        ->expectsQuestion('Password', 'a long enough password')
        ->expectsQuestion('Confirm the password', 'a long enough password')
        ->assertSuccessful();

    $user = User::where('email', 'sam@example.test')->sole();
    expect($user->is_admin)->toBeTrue()
        ->and($user->contact_id)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('a long enough password', $user->password))->toBeTrue();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('admin.unions.index'));
    $this->actingAs($user)->get(route('admin.unions.index'))->assertOk()->assertDontSee(route('membership.show'));
});

it('refuses to create an admin with an email already in use or a mismatched password', function () {
    $this->artisan('admin:create', ['email' => 'admin@example.test'])->assertFailed();

    $this->artisan('admin:create', ['email' => 'new@example.test'])
        ->expectsQuestion('Password', 'a long enough password')
        ->expectsQuestion('Confirm the password', 'something else entirely')
        ->expectsOutput('The passwords do not match')
        ->assertFailed();

    expect(User::where('email', 'new@example.test')->exists())->toBeFalse();
});

it('hides the admin area from everyone else', function () {
    $member = Contact::factory()->tenantOf(Building::factory()->create())->enrolled()->create();

    $this->actingAs($member->user)->get(route('admin.unions.index'))->assertNotFound();
    $this->actingAs($member->user)->get(route('admin.cleanup.index'))->assertNotFound();
    $this->actingAs($member->user)->get(route('dashboard'))->assertDontSee(route('admin.unions.index'));
});

it('shows usage numbers for each union without names or content', function () {
    $building = Building::factory()->create(['name' => 'Example Court Tenants\' Union']);
    $members = Contact::factory()->count(3)->tenantOf($building)->enrolled()->create();
    Contact::factory()->tenantOf($building)->create();
    Bulletin::factory()->count(2)->create(['building_id' => $building->id, 'author_id' => $members[0]->id, 'title' => 'Secret plans']);
    Thread::factory()->create(['building_id' => $building->id]);

    $this->actingAs($this->admin)->get(route('admin.unions.index'))
        ->assertOk()
        ->assertSeeText('Example Court Tenants\' Union')
        ->assertSeeInOrder(['Example Court', '3', '1', '2', '1'])
        ->assertDontSeeText($members[0]->name)
        ->assertDontSeeText('Secret plans');

    $this->actingAs($this->admin)->get(route('admin.unions.show', $building))
        ->assertOk()
        ->assertSeeInOrder(['Members', '3', 'Waiting to be vouched for', '1'])
        ->assertDontSeeText($members[0]->name);
});

it('leaves the demo union out of the totals but still lists it', function () {
    $real = Building::factory()->create();
    Contact::factory()->count(2)->tenantOf($real)->enrolled()->create();
    Bulletin::factory()->create(['building_id' => $real->id]);
    $demo = Building::factory()->create(['name' => 'Larkspur House', 'is_demo' => true]);
    Contact::factory()->count(5)->tenantOf($demo)->enrolled()->create();
    Bulletin::factory()->create(['building_id' => $demo->id]);
    QrScan::create(['kind' => QrScanKind::Join, 'building_id' => $demo->id]);
    QrScan::create(['kind' => QrScanKind::Join, 'building_id' => $real->id]);
    QrScan::create(['kind' => QrScanKind::Demo, 'building_id' => null]);

    $this->actingAs($this->admin)->get(route('admin.unions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Unions', '1', 'Members', '2', 'Waiting to be vouched for', '0', 'Accounts', '3', 'Active unions (last 30 days)', '1', 'Inactive unions (nothing new in 30 days)', '0'])
        ->assertSeeInOrder(['Join codes scanned', '1', 'Vouching codes scanned', '0', 'Campaign leaflets scanned', '0', 'Demo codes scanned', '1', 'All QR code scans', '2'])
        ->assertSeeInOrder(['Larkspur House', 'Demo', '5']);
});

it('counts demo sessions, not who started them', function () {
    config(['demo.enabled' => true]);
    $this->travel(-40)->days();
    DemoSession::create();
    $this->travelBack();
    DemoSession::create();
    DemoSession::create();

    $this->actingAs($this->admin)->get(route('admin.unions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Demo sessions (last 30 days)', '2', 'All demo sessions', '3']);

    config(['demo.enabled' => false]);
    DemoSession::query()->delete();
    $this->actingAs($this->admin)->get(route('admin.unions.index'))->assertDontSeeText('Demo sessions');
});

it('counts unions with nothing new in 30 days as inactive', function () {
    $active = Building::factory()->create();
    Bulletin::factory()->create(['building_id' => $active->id]);
    $this->travel(-40)->days();
    $quiet = Building::factory()->create();
    Bulletin::factory()->create(['building_id' => $quiet->id]);
    Building::factory()->create();
    $this->travelBack();

    $this->actingAs($this->admin)->get(route('admin.unions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Active unions (last 30 days)', '1', 'Inactive unions (nothing new in 30 days)', '2']);
});

it('emails account holders first, and deletes only those who do not log in within 7 days', function () {
    Notification::fake();
    $building = Building::factory()->create(['name' => 'Example Court']);
    Contact::factory()->count(2)->tenantOf($building)->enrolled()->create();

    $unverified = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
    $recentUnverified = User::factory()->unverified()->create();
    $unplaced = User::factory()->create(['created_at' => now()->subDays(31)]);
    $returning = User::factory()->create(['created_at' => now()->subDays(31)]);
    $unvouched = Contact::factory()->tenantOf($building)->create();
    $unvouched->user->forceFill(['created_at' => now()->subDays(91)])->save();
    $reply = Reply::factory()->create(['replyable_id' => $building->vouchingBulletin->id, 'author_id' => $unvouched->id]);

    $this->actingAs($this->admin)->get(route('admin.cleanup.index'))
        ->assertSeeInOrder(['Unconfirmed accounts', 'Email 1 account holder', 'Unfinished sign-ups', 'Email 2 account holders', 'Never vouched for', 'Email 1 account holder'])
        ->assertDontSee('that didn&#039;t log in', false);

    foreach (['unverified', 'unplaced', 'unvouched'] as $tool) {
        $this->actingAs($this->admin)->post(route('admin.cleanup.accounts.warn', $tool))->assertSessionHas('status');
    }

    Notification::assertSentTo($unverified, CleanupWarning::class, fn (CleanupWarning $warning) => str_contains($warning->reason, 'never confirmed your email')
        && $warning->deadline->isSameDay(now()->addDays(7))
        && $warning->union === null);
    Notification::assertSentTo($unvouched->user, CleanupWarning::class, fn (CleanupWarning $warning) => str_contains($warning->reason, 'join Example Court'));
    Notification::assertNotSentTo($recentUnverified, CleanupWarning::class);
    expect((new CleanupWarning('Reason.', now()->addDays(7)))->toMail($unplaced)->actionUrl)->toBe(route('login'));

    // Warning twice doesn't email again.
    $this->actingAs($this->admin)->post(route('admin.cleanup.accounts.warn', 'unplaced'));
    Notification::assertSentToTimes($unplaced, CleanupWarning::class, 1);

    // Nothing can be deleted during the week.
    $this->travel(6)->days();
    $this->actingAs($this->admin)->delete(route('admin.cleanup.accounts.destroy', 'unplaced'))->assertSessionHas('status', 'Deleted 0 accounts.');

    // Logging in cancels the warning.
    Auth::login($returning);
    Auth::logout();
    expect($returning->fresh()->cleanup_warned_at)->toBeNull();

    $this->travel(1)->days();
    foreach (['unverified', 'unplaced', 'unvouched'] as $tool) {
        $this->actingAs($this->admin)->delete(route('admin.cleanup.accounts.destroy', $tool))
            ->assertSessionHas('status', 'Deleted 1 account.');
    }

    expect(User::find($unverified->id))->toBeNull()
        ->and(Contact::find($unverified->contact_id))->toBeNull()
        ->and(User::find($unplaced->id))->toBeNull()
        ->and(Contact::find($unvouched->id))->toBeNull()
        ->and(User::find($returning->id))->not->toBeNull()
        ->and(User::find($recentUnverified->id))->not->toBeNull()
        ->and($reply->fresh()->author_id)->toBeNull()
        ->and($building->contacts()->enrolled()->count())->toBe(2)
        ->and($this->admin->fresh())->not->toBeNull();
});

it('does not delete an account used without logging in again', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(31)]);
    app(Cleanup::class)->warnAccounts(app(Cleanup::class)->accounts('unplaced'), 'unplaced', $this->admin);

    $this->travel(3)->days();
    $this->actingAs($user->fresh())->get(route('dashboard'));
    $this->travel(5)->days();

    expect($user->fresh()->cleanup_warned_at)->toBeNull()
        ->and(app(Cleanup::class)->dueForDeletion(app(Cleanup::class)->accounts('unplaced'))->count())->toBe(0);
});

it('emails abandoned unions and deletes those nobody came back to', function () {
    Notification::fake();
    $abandoned = Building::factory()->create(['created_at' => now()->subDays(100)]);
    $signup = Contact::factory()->tenantOf($abandoned)->create();
    $signup->user->forceFill(['created_at' => now()->subDays(95)])->save();
    $revived = Building::factory()->create(['created_at' => now()->subDays(100)]);
    $revivedSignup = Contact::factory()->tenantOf($revived)->create();
    $revivedSignup->user->forceFill(['created_at' => now()->subDays(95)])->save();
    $recentSignups = Building::factory()->create(['created_at' => now()->subDays(100)]);
    Contact::factory()->tenantOf($recentSignups)->create();
    $working = Building::factory()->create(['created_at' => now()->subDays(100)]);
    Contact::factory()->tenantOf($working)->enrolled()->create(['created_at' => now()->subDays(100)]);

    $this->actingAs($this->admin)->get(route('admin.cleanup.index'))
        ->assertSeeText($abandoned->name)
        ->assertSeeText('Not emailed yet')
        ->assertDontSeeText($recentSignups->name)
        ->assertDontSeeText($working->name);

    $this->actingAs($this->admin)->post(route('admin.cleanup.abandoned.warn'))
        ->assertSessionHas('status', 'Emailed everyone in 2 abandoned unions.');
    Notification::assertSentTo($signup->user, CleanupWarning::class, fn (CleanupWarning $warning) => $warning->union === $abandoned->name);

    $this->actingAs($this->admin)->delete(route('admin.cleanup.abandoned.destroy'))->assertSessionHas('status', 'Deleting 0 abandoned unions in the background.');

    $this->travel(2)->days();
    $this->actingAs($revivedSignup->user->fresh())->get(route('dashboard'));
    $this->travel(5)->days();

    $this->actingAs($this->admin)->delete(route('admin.cleanup.abandoned.destroy'))
        ->assertSessionHas('status', 'Deleting 1 abandoned union in the background.');

    expect(Building::find($abandoned->id))->toBeNull()
        ->and(User::find($signup->user->id))->toBeNull()
        ->and($revived->fresh()->cleanup_warned_at)->toBeNull()
        ->and(Building::count())->toBe(3);

    // It had no members, so there was nobody to send its records to.
    Notification::assertNothingSentTo(new AnonymousNotifiable);
    Notification::assertSentOnDemandTimes(UnionDataExported::class, 0);
});

it('splits a long section into PDFs of no more than the page limit', function () {
    Storage::fake('local');
    $building = Building::factory()->create();
    Bulletin::factory()->count(75)->create([
        'building_id' => $building->id,
        'rich_text' => '<p>'.str_repeat('The lift is broken again. ', 60).'</p>',
    ]);

    $path = (new UnionExport(maxPages: 12))->create($building);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($path));
    $parts = collect(range(0, $zip->numFiles - 1))
        ->map(fn (int $index) => $zip->getNameIndex($index))
        ->filter(fn (string $name) => str_starts_with($name, '5-bulletins'))
        ->values();
    $pageCounts = $parts->map(fn (string $name) => preg_match_all('#/Type\s*/Page\b(?!s)#', $zip->getFromName($name)));

    expect($parts->count())->toBeGreaterThan(1)
        ->and($parts->first())->toBe('5-bulletins-part-1.pdf')
        ->and($pageCounts->max())->toBeLessThanOrEqual(12)
        ->and($pageCounts->sum())->toBeGreaterThan(12)
        ->and($zip->getFromName('README.txt'))->toContain('5-bulletins-part-2.pdf')->not->toContain('files/mail');
});

it('deletes exports once their links have expired', function () {
    Storage::fake('local');
    $exports = app(UnionExport::class);
    $old = $exports->create(Building::factory()->create());
    $new = $exports->create(Building::factory()->create());
    touch(Storage::disk('local')->path($old), now()->subDays(UnionExport::KEEP_DAYS + 1)->getTimestamp());

    $this->artisan('union-exports:prune')->expectsOutput('Deleted 1 export.')->assertSuccessful();

    expect(Storage::disk('local')->exists($old))->toBeFalse()
        ->and(Storage::disk('local')->exists($new))->toBeTrue();
});

it('lets a member logging in stop a union being deleted', function () {
    Notification::fake();
    $building = Building::factory()->create(['name' => 'Saved Court']);
    $member = Contact::factory()->tenantOf($building)->enrolled()->create();

    $this->actingAs($this->admin)->post(route('admin.unions.warn', $building), ['confirm_name' => 'Saved Court']);
    $this->travel(1)->days();
    Auth::login($member->user->fresh());
    Auth::logout();
    $this->travel(7)->days();

    expect($building->fresh()->cleanup_warned_at)->toBeNull();
    $this->actingAs($this->admin)->delete(route('admin.unions.destroy', $building), ['confirm_name' => 'Saved Court'])->assertStatus(409);
    expect(Building::find($building->id))->not->toBeNull();
});

it('lets the admin call off a union deletion', function () {
    Notification::fake();
    $building = Building::factory()->create(['name' => 'Spared Court']);
    $member = Contact::factory()->tenantOf($building)->enrolled()->create();

    $this->actingAs($this->admin)->post(route('admin.unions.warn', $building), ['confirm_name' => 'Spared Court']);
    $this->actingAs($this->admin)->delete(route('admin.unions.warn.cancel', $building))->assertSessionHas('status');

    expect($building->fresh()->cleanup_warned_at)->toBeNull()
        ->and($member->user->fresh()->cleanup_warned_at)->toBeNull();
});

it('deletes a union and everything in it, after emailing its members and waiting a week', function () {
    Mail::fake();
    Notification::fake();
    Storage::fake('public');
    Storage::fake('local');

    $building = Building::factory()->create(['name' => 'Doomed Court']);
    [$author, $approver] = Contact::factory()->count(2)->tenantOf($building)->enrolled()->create()->all();
    $landlord = Landlord::factory()->create(['building_id' => $building->id]);
    $agent = Contact::factory()->atLandlord($landlord)->create();
    $list = MailingList::factory()->create(['building_id' => $building->id]);
    $list->contacts()->attach($agent);
    $bulletin = Bulletin::factory()->create(['building_id' => $building->id, 'author_id' => $author->id]);
    Reply::factory()->create(['replyable_id' => $bulletin->id, 'author_id' => $approver->id]);
    $thread = Thread::factory()->create(['building_id' => $building->id]);
    Reply::factory()->create(['replyable_type' => 'thread', 'replyable_id' => $thread->id]);
    Poll::factory()->create(['bulletin_id' => $bulletin->id]);
    Referendum::factory()->create(['building_id' => $building->id]);
    $image = UploadedImage::factory()->create(['building_id' => $building->id]);
    Storage::disk('public')->put($image->path, 'jpeg');
    $mail = app(MailApprovals::class)->createInstant($author, $list, 'Hello', '<p>Hi</p>', [UploadedFile::fake()->createWithContent('notes.txt', 'notes')]);
    app(MailApprovals::class)->decide($mail->approvers->first()->contact, $mail, ApprovalDecision::Approve);
    $attachment = $mail->attachments()->sole();

    $other = Building::factory()->create();
    $otherMember = Contact::factory()->tenantOf($other)->enrolled()->create();
    Bulletin::factory()->create(['building_id' => $other->id]);

    $this->actingAs($this->admin)->post(route('admin.unions.warn', $building), ['confirm_name' => 'Doomed'])
        ->assertSessionHasErrors('confirm_name');
    $this->actingAs($this->admin)->post(route('admin.unions.warn', $building), ['confirm_name' => 'Doomed Court'])
        ->assertSessionHas('status');
    Notification::assertSentTo([$author->user, $approver->user], CleanupWarning::class, fn (CleanupWarning $warning) => $warning->union === 'Doomed Court');
    Notification::assertNotSentTo($otherMember->user, CleanupWarning::class);

    // Not before the week is up.
    $this->actingAs($this->admin)->delete(route('admin.unions.destroy', $building), ['confirm_name' => 'Doomed Court'])->assertStatus(409);

    $this->travel(7)->days();
    $this->actingAs($this->admin)->get(route('admin.unions.show', $building))->assertSeeText('You can delete it now.');
    $this->actingAs($this->admin)->delete(route('admin.unions.destroy', $building), ['confirm_name' => 'Doomed Court'])
        ->assertRedirect(route('admin.unions.index'));

    // Members, not the landlord's staff or other unions, get the union's records.
    $sent = [];
    Notification::assertSentOnDemand(UnionDataExported::class, function (UnionDataExported $notification, array $channels, AnonymousNotifiable $notifiable) use (&$sent) {
        $sent[] = $notifiable->routes['mail'];

        return $notification->union === 'Doomed Court' && $notification->attachPath !== null;
    });
    expect($sent)->toEqualCanonicalizing([$author->user->email, $approver->user->email]);

    $notification = null;
    Notification::assertSentOnDemand(UnionDataExported::class, function (UnionDataExported $sentNotification) use (&$notification) {
        return (bool) ($notification = $sentNotification);
    });
    $zip = new ZipArchive;
    $zip->open($notification->attachPath);
    $files = collect(range(0, $zip->numFiles - 1))->map(fn (int $index) => $zip->getNameIndex($index));
    expect($files)->toContain(
        '1-constitution.pdf', '2-policies.pdf', '3-roles.pdf', '4-referendums.pdf', '5-bulletins.pdf',
        '6-discussions.pdf', '7-mail.pdf', '8-mailing-lists.pdf', 'README.txt', 'landlords.csv', 'contacts.csv',
        "files/mail/{$mail->id}/{$attachment->id}-notes.txt",
    )
        ->and($zip->getFromName('5-bulletins.pdf'))->toStartWith('%PDF')
        ->and($zip->getFromName('README.txt'))->toContain('Doomed Court')->toContain('5-bulletins.pdf')->toContain('files/mail:')->not->toContain('deleted')
        ->and($zip->getFromName("files/mail/{$mail->id}/{$attachment->id}-notes.txt"))->toBe('notes')
        ->and($zip->getFromName('contacts.csv'))->toContain($agent->name)->not->toContain($author->user->email)
        ->and($zip->getFromName('landlords.csv'))->toContain($landlord->name);
    $zip->close();

    $this->get($notification->downloadUrl)->assertOk()->assertDownload('doomed-court-export.zip');
    $this->get(route('union-exports.download', basename(dirname(str_replace(Storage::disk('local')->path(''), '', $notification->attachPath)))))->assertForbidden();

    expect(Building::find($building->id))->toBeNull()
        ->and(User::whereIn('contact_id', [$author->id, $approver->id])->exists())->toBeFalse()
        ->and(Contact::whereKey([$author->id, $approver->id, $agent->id])->exists())->toBeFalse()
        ->and(Reply::count())->toBe(0)
        ->and(Storage::disk('public')->exists($image->path))->toBeFalse()
        ->and(Storage::disk($attachment->disk)->exists($attachment->path))->toBeFalse()
        ->and($otherMember->fresh())->not->toBeNull()
        ->and($other->bulletins()->count())->toBe(2);

    // Nothing that belonged to the union is left in any table.
    foreach (Schema::getTableListing() as $table) {
        if (Schema::hasColumn($table, 'building_id')) {
            expect(DB::table($table)->where('building_id', $building->id)->count())->toBe(0, $table);
        }
    }
    expect(Contact::whereNull('building_id')->whereNull('landlord_id')->whereDoesntHave('user')->count())->toBe(0);
});
