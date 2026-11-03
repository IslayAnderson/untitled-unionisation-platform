<?php

namespace App\Enums;

enum UnionMailStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for approval',
            self::Sent => 'Sent',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
