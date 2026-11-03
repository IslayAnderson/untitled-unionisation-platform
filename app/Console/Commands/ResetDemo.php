<?php

namespace App\Console\Commands;

use App\Services\DemoUnion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset {--remove : Remove the demo union without rebuilding it}')]
#[Description('Rebuild the public demo union, Larkspur House, dated from now')]
class ResetDemo extends Command
{
    public function handle(DemoUnion $demo): int
    {
        if ($this->option('remove')) {
            $removed = $demo->remove();
            $this->info($removed ? 'Removed the demo union.' : 'There was no demo union.');

            return self::SUCCESS;
        }

        $building = $demo->build();

        $this->info(sprintf(
            'Built %s: %d members, %d waiting, %d bulletins, %d referendums.',
            $building->name,
            $building->enrolledCount(),
            $building->confirmedTenantCount() - $building->enrolledCount(),
            $building->bulletins()->count(),
            $building->referendums()->count(),
        ));

        if (! config('demo.enabled')) {
            $this->warn('Set DEMO_ENABLED=true to let people look around it at /demo.');
        }

        return self::SUCCESS;
    }
}
