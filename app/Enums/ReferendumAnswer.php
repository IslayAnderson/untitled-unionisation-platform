<?php

namespace App\Enums;

enum ReferendumAnswer: string
{
    case For = 'for';
    case Against = 'against';
    case Abstain = 'abstain';

    public function label(): string
    {
        return match ($this) {
            self::For => 'For',
            self::Against => 'Against',
            self::Abstain => 'Abstain',
        };
    }

    /**
     * The referendums column holding this answer's running total.
     */
    public function countColumn(): string
    {
        return "{$this->value}_count";
    }
}
