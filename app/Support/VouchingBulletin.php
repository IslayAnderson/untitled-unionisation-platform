<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Bulletin;

/**
 * The pinned bulletin every union starts with, where members and tenants
 * waiting to be vouched for arrange to meet. Tenants who aren't members yet
 * can read and reply to it, in plain text only. See docs/architecture/signup-process.md.
 */
final class VouchingBulletin
{
    public const KEY = 'vouching';

    public static function createFor(Building $building): Bulletin
    {
        return $building->bulletins()->firstOrCreate(['system_key' => self::KEY], [
            'title' => 'Arrange vouching',
            'rich_text' => view('bulletins.vouching')->render(),
            'published_at' => now(),
            'pinned_at' => now(),
        ]);
    }
}
