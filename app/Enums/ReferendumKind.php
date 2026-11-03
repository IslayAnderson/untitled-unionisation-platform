<?php

namespace App\Enums;

enum ReferendumKind: string
{
    /** Adopt the referendable: a new policy, a revised policy, or a new version of the constitution. */
    case Adopt = 'adopt';

    /** Repeal the referendable policy. */
    case Repeal = 'repeal';
}
