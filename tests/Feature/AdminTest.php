<?php

use App\Enums\ApprovalDecision;
use App\Models\Building;
use App\Models\Bulletin;
use App\Models\Contact;
use App\Models\Landlord;
use App\Models\MailingList;
use App\Models\Poll;
use App\Models\Referendum;
use App\Models\Reply;
use App\Models\Thread;
use App\Models\UploadedImage;
use App\Models\User;
use App\Services\MailApprovals;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

it('deletes accounts that never finished signing up', function () {
    $building = Building::factory()->create();
    Contact::factory()->count(2)->tenantOf($building)->enrolled()->create();

    $unverified = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
    $recentUnverified = User::factory()->unverified()->create();
    $unplaced = User::factory()->create(['created_at' => now()->subDays(31)]);
    $unvouched = Contact::factory()->tenantOf($building)->create();
    $unvouched->user->forceFill(['created_at' => now()->subDays(91)])->save();
    $reply = Reply::factory()->create(['replyable_id' => $building->vouchingBulletin->id, 'author_id' => $unvouched->id]);

    $this->actingAs($this->admin)->get(route('admin.cleanup.index'))
        ->assertSeeInOrder(['Unconfirmed accounts', 'Delete 1 account', 'Unfinished sign-ups', 'Delete 1 account', 'Never vouched for', 'Delete 1 account']);

    foreach (['unverified', 'unplaced', 'unvouched'] as $tool) {
        $this->actingAs($this->admin)->delete(route('admin.cleanup.accounts.destroy', $tool))
            ->assertSessionHas('status', 'Deleted 1 account.');
    }

    expect(User::find($unverified->id))->toBeNull()
        ->and(Contact::find($unverified->contact_id))->toBeNull()
        ->and(User::find($unplaced->id))->toBeNull()
        ->and(Contact::find($unvouched->id))->toBeNull()
        ->and(User::find($recentUnverified->id))->not->toBeNull()
        ->and($reply->fresh()->author_id)->toBeNull()
        ->and($building->contacts()->enrolled()->count())->toBe(2)
        ->and($this->admin->fresh())->not->toBeNull();
});

it('lists and deletes abandoned unions, keeping ones in use', function () {
    $abandoned = Building::factory()->create(['created_at' => now()->subDays(100)]);
    $signup = Contact::factory()->tenantOf($abandoned)->create();
    $signup->user->forceFill(['created_at' => now()->subDays(95)])->save();
    $recentSignups = Building::factory()->create(['created_at' => now()->subDays(100)]);
    Contact::factory()->tenantOf($recentSignups)->create();
    $working = Building::factory()->create(['created_at' => now()->subDays(100)]);
    Contact::factory()->tenantOf($working)->enrolled()->create(['created_at' => now()->subDays(100)]);

    $this->actingAs($this->admin)->get(route('admin.cleanup.index'))
        ->assertSeeText($abandoned->name)
        ->assertDontSeeText($recentSignups->name)
        ->assertDontSeeText($working->name);

    $this->actingAs($this->admin)->delete(route('admin.cleanup.abandoned.destroy'))
        ->assertSessionHas('status', 'Deleted 1 abandoned union.');

    expect(Building::find($abandoned->id))->toBeNull()
        ->and(User::find($signup->user->id))->toBeNull()
        ->and(Building::count())->toBe(2);
});

it('deletes a union and everything in it, after typing its name', function () {
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

    $this->actingAs($this->admin)->delete(route('admin.unions.destroy', $building), ['confirm_name' => 'Doomed'])
        ->assertSessionHasErrors('confirm_name');
    expect(Building::find($building->id))->not->toBeNull();

    $this->actingAs($this->admin)->delete(route('admin.unions.destroy', $building), ['confirm_name' => 'Doomed Court'])
        ->assertRedirect(route('admin.unions.index'));

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
