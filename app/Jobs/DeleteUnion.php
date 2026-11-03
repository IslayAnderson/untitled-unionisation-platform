<?php

namespace App\Jobs;

use App\Models\Building;
use App\Models\User;
use App\Services\Cleanup;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Exports, emails and deletes a union in the background, since rendering a big
 * union's records can take minutes. Checks again that nobody has logged in
 * since the admin asked. If it fails, the union is left as it was.
 */
class DeleteUnion implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $buildingId, public int $adminId)
    {
        $this->onConnection(config('queue.long_running_connection'));
    }

    public function uniqueId(): string
    {
        return (string) $this->buildingId;
    }

    public function handle(Cleanup $cleanup): void
    {
        $building = $cleanup->unionsDueForDeletion(Building::whereKey($this->buildingId))->first();
        $admin = User::find($this->adminId);

        if ($building && $admin) {
            $cleanup->deleteUnion($building, $admin);
        }
    }
}
