<?php

namespace App\Enums;

enum ReferendumAnswer: string
{
    case For = 'for';
    case Against = 'against';
    case Abstain = 'abstain';
}
