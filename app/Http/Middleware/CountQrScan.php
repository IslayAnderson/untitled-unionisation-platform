<?php

namespace App\Http\Middleware;

use App\Enums\QrScanKind;
use App\Models\Building;
use App\Models\Contact;
use App\Models\QrScan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts a page being opened from one of our QR codes (App\Support\QrCode::scannedUrl),
 * once per code in each browser session, so reloading doesn't count again.
 * Records which code and when, never who scanned it.
 */
class CountQrScan
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $kind): Response
    {
        $sessionKey = 'qr_scanned.'.sha1($request->path());

        if ($request->isMethod('GET') && $request->query('via') === 'qr' && ! $request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, true);

            QrScan::create([
                'kind' => QrScanKind::from($kind),
                'building_id' => match (true) {
                    $request->route('building') instanceof Building => $request->route('building')->id,
                    $request->route('contact') instanceof Contact => $request->route('contact')->building_id,
                    default => null,
                },
            ]);
        }

        return $next($request);
    }
}
