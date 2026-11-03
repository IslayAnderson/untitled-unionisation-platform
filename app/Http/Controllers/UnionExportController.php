<?php

namespace App\Http\Controllers;

use App\Services\UnionExport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a deleted union's records from the signed link emailed to its
 * members. Their accounts are gone, so the signature is the only check.
 */
class UnionExportController extends Controller
{
    public function __invoke(string $export): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $export), 404);

        $disk = Storage::disk(UnionExport::DISK);
        $file = $disk->files(UnionExport::DIRECTORY.'/'.$export)[0] ?? abort(404);

        return $disk->download($file, basename($file));
    }
}
