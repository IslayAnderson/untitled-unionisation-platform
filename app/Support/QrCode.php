<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

final class QrCode
{
    /**
     * Render a URL as an SVG data URI, ready for an <img src>.
     */
    public static function dataUri(string $url): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
        ]);

        return (new Generator($options))->render($url);
    }
}
