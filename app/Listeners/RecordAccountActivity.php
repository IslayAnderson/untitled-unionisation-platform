<?php

namespace App\Listeners;

use App\Models\Building;
use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;

/**
 * Notes when an account holder logs in or uses the platform, at most hourly.
 * Coming back cancels any clean-up warning for their account and their union.
 */
class RecordAccountActivity
{
    public function handle(Login|Authenticated $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $warned = $user->cleanup_warned_at !== null;

        if ($warned || ! $user->last_seen_at || $user->last_seen_at->lt(now()->subHour())) {
            $user->forceFill(['last_seen_at' => now(), 'cleanup_warned_at' => null])->saveQuietly();
        }

        // Warning a union also warns each of its account holders, so this only runs after a warning.
        if ($warned && $user->contact?->building_id) {
            Building::whereKey($user->contact->building_id)->update(['cleanup_warned_at' => null]);
        }
    }
}
