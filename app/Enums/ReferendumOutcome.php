<?php

namespace App\Enums;

enum ReferendumOutcome: string
{
    case Passed = 'passed';
    case Rejected = 'rejected';
    case NoQuorum = 'no_quorum';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Passed',
            self::Rejected => 'Rejected',
            self::NoQuorum => 'Not enough votes',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
