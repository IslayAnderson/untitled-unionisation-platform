<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contact;
use App\Models\Reply;
use App\Models\UnionMailAttachment;
use App\Models\UploadedImage;
use App\Models\User;
use App\Notifications\CleanupWarning;
use App\Notifications\UnionDataExported;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Admin clean-up: accounts that never finished signing up, and unions nobody
 * uses. Account holders are warned by email first, and are only deleted if
 * they don't log in within GRACE_DAYS. See docs/architecture/admin.md.
 */
class Cleanup
{
    public const GRACE_DAYS = 7;

    public function __construct(private UnionExport $exports) {}

    public const UNVERIFIED_DAYS = 7;

    public const UNPLACED_DAYS = 30;

    public const UNVOUCHED_DAYS = 90;

    public const ABANDONED_DAYS = 90;

    /**
     * The accounts an account clean-up tool covers.
     *
     * @return Builder<User>
     */
    public function accounts(string $tool): Builder
    {
        return match ($tool) {
            'unverified' => $this->unverifiedAccounts(),
            'unplaced' => $this->unplacedAccounts(),
            'unvouched' => $this->unvouchedAccounts(),
        };
    }

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
     * Why an account is being cleaned up, as told to its holder.
     */
    public function reason(string $tool, User $user): string
    {
        return match ($tool) {
            'unverified' => 'You started signing up to '.config('app.name').' but never confirmed your email address.',
            'unplaced' => 'You signed up to '.config('app.name').' but never told us which building and flat you live in.',
            'unvouched' => 'You signed up to join '.($user->contact?->building?->name ?? 'a tenants\' union').', but no members have vouched for you since.',
        };
    }

    /**
     * Email each account holder who hasn't been warned yet.
     *
     * @param  Builder<User>  $users
     */
    public function warnAccounts(Builder $users, string $tool, User $admin): int
    {
        $warned = 0;

        $users->clone()->whereNull('cleanup_warned_at')->with('contact.building')->chunkById(200, function ($chunk) use ($tool, &$warned) {
            foreach ($chunk as $user) {
                $user->forceFill(['cleanup_warned_at' => now()])->save();
                $user->notify(new CleanupWarning($this->reason($tool, $user), $this->deadline(now())));
                $warned++;
            }
        });

        Log::info('Admin warned accounts', ['admin_id' => $admin->id, 'reason' => $tool, 'count' => $warned]);

        return $warned;
    }

    /**
     * Narrow accounts to those warned at least GRACE_DAYS ago that haven't been
     * used since.
     *
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    public function dueForDeletion(Builder $users): Builder
    {
        return $users->clone()
            ->where('cleanup_warned_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->where(fn (Builder $query) => $query->whereNull('last_seen_at')->orWhereColumn('last_seen_at', '<', 'cleanup_warned_at'));
    }

    /**
     * Narrow accounts to those warned that are still within their week.
     *
     * @param  Builder<User>  $users
     * @return Builder<User>
     */
    public function awaitingDeadline(Builder $users): Builder
    {
        return $users->clone()->where('cleanup_warned_at', '>', now()->subDays(self::GRACE_DAYS));
    }

    /**
     * Email everyone with an account in the union. Any of them logging in within
     * GRACE_DAYS keeps it.
     */
    public function warnUnion(Building $building, string $reason, User $admin): void
    {
        $building->forceFill(['cleanup_warned_at' => now()])->save();
        $users = User::whereIn('contact_id', $building->contacts()->select('id'))->get();

        // Their own warning lets the login listener cancel the union's.
        User::whereKey($users->modelKeys())->whereNull('cleanup_warned_at')->update(['cleanup_warned_at' => now()]);
        Notification::send($users, new CleanupWarning($reason, $this->deadline($building->cleanup_warned_at), $building->name));

        Log::info('Admin warned a union', ['admin_id' => $admin->id, 'building_id' => $building->id, 'reason' => $reason]);
    }

    public function cancelUnionWarning(Building $building): void
    {
        $building->forceFill(['cleanup_warned_at' => null])->save();
        User::whereIn('contact_id', $building->contacts()->select('id'))->update(['cleanup_warned_at' => null]);
    }

    /**
     * Narrow unions to those warned at least GRACE_DAYS ago where nobody has
     * used their account since.
     *
     * @param  Builder<Building>  $buildings
     * @return Builder<Building>
     */
    public function unionsDueForDeletion(Builder $buildings): Builder
    {
        return $buildings->clone()
            ->where('cleanup_warned_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->whereDoesntHave('contacts.user', fn (Builder $user) => $user->whereColumn('users.last_seen_at', '>=', 'buildings.cleanup_warned_at'));
    }

    public function deadline(CarbonInterface $warnedAt): CarbonInterface
    {
        return $warnedAt->copy()->addDays(self::GRACE_DAYS);
    }

    /**
     * Delete accounts and the people behind them. Anything they wrote stays,
     * shown as by a former member. Only pass accounts from dueForDeletion().
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
     * landlords, posts, mail, votes and stored files. Only pass unions from
     * unionsDueForDeletion().
     *
     * Its members are emailed an export of its records first. If the export
     * can't be made, nothing is deleted.
     */
    public function deleteUnion(Building $building, User $admin): void
    {
        $recipients = $this->exports->recipients($building);
        $export = $recipients ? $this->exports->create($building) : null;

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

        if ($export) {
            $disk = Storage::disk(UnionExport::DISK);
            $notification = new UnionDataExported(
                $building->name,
                $this->exports->downloadUrl($export),
                now()->addDays(UnionExport::KEEP_DAYS),
                $disk->size($export) <= UnionExport::ATTACH_MAX_BYTES ? $disk->path($export) : null,
            );

            // Separately, so members don't see each other's addresses.
            foreach ($recipients as $email) {
                Notification::route('mail', $email)->notify($notification);
            }
        }

        Log::info('Admin deleted a union', ['admin_id' => $admin->id, 'building_id' => $building->id, 'name' => $building->name, 'export_sent_to' => count($recipients)]);
    }
}
