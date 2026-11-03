<?php

namespace App\Console\Commands;

use App\Services\Referendums;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('referendums:close')]
#[Description('Count referendums whose voting period has ended and put passed ones into effect')]
class CloseReferendums extends Command
{
    public function handle(Referendums $referendums): int
    {
        $closed = $referendums->closeDue();

        $this->info("Counted {$closed} ".str('referendum')->plural($closed).'.');

        return self::SUCCESS;
    }
}
