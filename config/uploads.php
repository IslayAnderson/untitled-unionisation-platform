<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Disk
    |--------------------------------------------------------------------------
    |
    | Where images uploaded into rich text are stored. In production this is
    | "r2": a Cloudflare R2 bucket served from a custom domain with the CSAM
    | Scanning Tool enabled. See docs/architecture/uploads.md.
    |
    */

    'disk' => env('UPLOADS_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Warm Cloudflare's Cache
    |--------------------------------------------------------------------------
    |
    | Request each image once after upload, so it enters Cloudflare's cache and
    | is scanned straight away rather than when a member first views it.
    |
    */

    'warm_cache' => (bool) env('UPLOADS_WARM_CACHE', false),

    'images' => [
        'max_upload_kb' => 10240,
        'max_dimension' => 2000,
        'jpeg_quality' => 82,
    ],

];
