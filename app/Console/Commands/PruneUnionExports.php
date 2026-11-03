<?php

namespace App\Console\Commands;

use App\Services\UnionExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('union-exports:prune')]
#[Description('Delete deleted unions\' exports once their download links have expired')]
class PruneUnionExports extends Command
{
    public function handle(UnionExport $exports): int
    {
        $pruned = $exports->prune();

        $this->info("Deleted {$pruned} ".str('export')->plural($pruned).'.');

        return self::SUCCESS;
    }
}
