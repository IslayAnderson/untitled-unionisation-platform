<?php

namespace App\Console\Commands;

use App\Services\MailApprovals;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('union-mail:expire')]
#[Description('Mark union mail that was not approved in time as expired')]
class ExpireUnionMail extends Command
{
    public function handle(MailApprovals $approvals): int
    {
        $expired = $approvals->expireOverdue();

        $this->info("Expired {$expired} ".str('mail')->plural($expired).'.');

        return self::SUCCESS;
    }
}
