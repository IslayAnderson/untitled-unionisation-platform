<?php

namespace App\Jobs;

use App\Models\UploadedImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Requests a new upload through Cloudflare once, so it enters the cache and
 * the CSAM Scanning Tool hashes it straight away.
 */
class WarmImageCache implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public UploadedImage $image)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $response = Http::timeout(15)->get($this->image->url());

        if ($response->failed()) {
            Log::warning('Uploaded image could not be fetched through the CDN.', [
                'uploaded_image_id' => $this->image->id,
                'status' => $response->status(),
            ]);

            $response->throwIf($response->serverError());

            return;
        }

        $this->image->update(['cache_warmed_at' => now()]);
    }
}
