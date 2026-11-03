<?php

namespace App\Enums;

enum UnionMailKind: string
{
    case Instant = 'instant';
    case Bulletin = 'bulletin';

    public function label(): string
    {
        return match ($this) {
            self::Instant => 'Instant mail',
            self::Bulletin => 'Bulletin mail',
        };
    }
}
