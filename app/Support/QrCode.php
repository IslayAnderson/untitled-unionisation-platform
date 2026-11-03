<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

final class QrCode
{
    /**
     * Render a URL as an SVG data URI, ready for an <img src>. The code opens the
     * URL marked as scanned, so App\Http\Middleware\CountQrScan can count it.
     */
    public static function dataUri(string $url): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
        ]);

        return (new Generator($options))->render(self::scannedUrl($url));
    }

    /**
     * The URL a QR code opens: the given one, marked as scanned.
     */
    public static function scannedUrl(string $url): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').'via=qr';
    }
}
