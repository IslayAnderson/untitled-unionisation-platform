<?php

namespace App\Jobs;

use App\Enums\ExportReason;
use App\Models\Building;
use App\Notifications\UnionDataExported;
use App\Services\UnionExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Emails a union's records to someone who has left it or been removed, in the
 * background, since rendering a big union's records can take minutes.
 */
class SendUnionExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $buildingId, public string $email, public ExportReason $reason)
    {
        $this->onConnection(config('queue.long_running_connection'));
        $this->afterCommit();
    }

    public function handle(UnionExport $exports): void
    {
        // The union may have been deleted since, in which case its members were already sent its records.
        $building = Building::find($this->buildingId);

        if (! $building) {
            return;
        }

        $export = $exports->create($building);
        $disk = Storage::disk(UnionExport::DISK);

        Notification::route('mail', $this->email)->notify(new UnionDataExported(
            $building->name,
            $exports->downloadUrl($export),
            now()->addDays(UnionExport::KEEP_DAYS),
            $disk->size($export) <= UnionExport::ATTACH_MAX_BYTES ? $disk->path($export) : null,
            $this->reason,
        ));
    }
}
