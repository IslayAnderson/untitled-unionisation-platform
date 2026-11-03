<?php

namespace App\Enums;

/**
 * Why someone is being sent a union's records (App\Notifications\UnionDataExported).
 */
enum ExportReason: string
{
    case UnionDeleted = 'union_deleted';
    case Left = 'left';
    case Removed = 'removed';
    case AccountDeleted = 'account_deleted';

    public function subject(string $union): string
    {
        return match ($this) {
            self::UnionDeleted => "{$union} has been deleted: your union's records",
            self::Left, self::AccountDeleted => "You've left {$union}: your union's records",
            self::Removed => "You've been removed from {$union}: your union's records",
        };
    }

    public function intro(string $union): string
    {
        return match ($this) {
            self::UnionDeleted => "{$union} has been deleted from ".config('app.name').', along with your account.',
            self::Left => "You've left {$union}, so you can no longer see its records on ".config('app.name').'.',
            self::Removed => "Members of {$union} voted to remove you from the union, so you can no longer see its records on ".config('app.name').'.',
            self::AccountDeleted => "You've deleted your account and left {$union}.",
        };
    }
}
