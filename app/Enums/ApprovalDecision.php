<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
