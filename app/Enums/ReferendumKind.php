<?php

namespace App\Enums;

enum ReferendumKind: string
{
    /** Adopt the referendable: a new or revised policy, a new role, or a new version of the constitution. */
    case Adopt = 'adopt';

    /** Repeal the referendable policy, or abolish the referendable role. */
    case Repeal = 'repeal';

    /** Elect the candidate to the referendable role. */
    case Elect = 'elect';

    /** Remove the candidate from the referendable role. */
    case Remove = 'remove';
}
