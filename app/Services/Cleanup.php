<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Reply;
use App\Models\UnionMailAttachment;
use App\Models\UploadedImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Admin clean-up: accounts that never finished signing up, and unions nobody
 * uses. See docs/architecture/admin.md.
 */
class Cleanup
{
    public const UNVERIFIED_DAYS = 7;

    public const UNPLACED_DAYS = 30;

    public const UNVOUCHED_DAYS = 90;

    public const ABANDONED_DAYS = 90;

    /**
     * Accounts whose email address was never confirmed.
     *
     * @return Builder<User>
     */
    public function unverifiedAccounts(): Builder
    {
        return User::query()
            ->where('is_admin', false)
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(self::UNVERIFIED_DAYS));
    }

    /**
     * Confirmed accounts that never said which flat they live in.
     *
     * @return Builder<User>
     */
    public function unplacedAccounts(): Builder
    {
        return User::query()
            ->where('is_admin', false)
            ->whereNotNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(self::UNPLACED_DAYS))
            ->whereDoesntHave('contact', fn (Builder $contact) => $contact->whereNotNull('unit_id'));
    }

    /**
     * Tenants of a working union (one with members) who were never vouched for.
     * Unions without members are handled as abandoned unions instead.
     *
     * @return Builder<User>
     */
    public function unvouchedAccounts(): Builder
    {
        return User::query()
            ->where('is_admin', false)
            ->whereNotNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(self::UNVOUCHED_DAYS))
            ->whereHas('contact', fn (Builder $contact) => $contact
                ->whereNotNull('unit_id')
                ->whereNull('enrolled_at')
                ->whereHas('building.contacts', fn (Builder $member) => $member->whereNotNull('enrolled_at')));
    }

    /**
     * Unions with no members, where nobody has signed up for a while.
     *
     * @return Builder<Building>
     */
    public function abandonedUnions(): Builder
    {
        $since = now()->subDays(self::ABANDONED_DAYS);

        return Building::query()
            ->where('created_at', '<=', $since)
            ->whereDoesntHave('contacts', fn (Builder $contact) => $contact->whereNotNull('enrolled_at'))
            ->whereDoesntHave('contacts', fn (Builder $contact) => $contact->whereHas('user', fn (Builder $user) => $user->where('created_at', '>', $since)));
    }

    /**
     * Delete accounts and the people behind them. Anything they wrote stays,
     * shown as by a former member.
     *
     * @param  Builder<User>  $users
     */
    public function deleteAccounts(Builder $users, User $admin, string $reason): int
    {
        $deleted = 0;

        $users->with('contact')->chunkById(200, function ($chunk) use (&$deleted) {
            DB::transaction(function () use ($chunk, &$deleted) {
                foreach ($chunk as $user) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                    $user->delete();
                    $user->contact?->delete();
                    $deleted++;
                }
            });
        });

        Log::info('Admin deleted accounts', ['admin_id' => $admin->id, 'reason' => $reason, 'count' => $deleted]);

        return $deleted;
    }

    /**
     * Delete a union and everything in it: its tenants' accounts, contacts,
     * landlords, posts, mail, votes and stored files.
     */
    public function deleteUnion(Building $building, User $admin): void
    {
        $files = UploadedImage::where('building_id', $building->id)->get(['disk', 'path'])
            ->concat(UnionMailAttachment::whereIn('union_mail_id', $building->unionMails()->select('id'))->get(['disk', 'path']));

        DB::transaction(function () use ($building) {
            $contactIds = Contact::query()
                ->where('building_id', $building->id)
                ->orWhereIn('landlord_id', $building->landlords()->select('id'))
                ->pluck('id');
            $userIds = User::whereIn('contact_id', $contactIds)->pluck('id');

            // Replies point at bulletins and threads by morph, without a foreign key.
            Reply::query()
                ->where(fn (Builder $query) => $query->where('replyable_type', 'bulletin')->whereIn('replyable_id', $building->bulletins()->select('id')))
                ->orWhere(fn (Builder $query) => $query->where('replyable_type', 'thread')->whereIn('replyable_id', $building->threads()->select('id')))
                ->delete();

            DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            User::whereKey($userIds)->delete();
            $building->delete();
            Contact::whereKey($contactIds)->delete();
        });

        $files->each(fn ($file) => Storage::disk($file->disk)->delete($file->path));

        Log::info('Admin deleted a union', ['admin_id' => $admin->id, 'building_id' => $building->id, 'name' => $building->name]);
    }
}
