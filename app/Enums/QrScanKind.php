<?php

namespace App\Enums;

/**
 * Which QR code was scanned (App\Models\QrScan).
 */
enum QrScanKind: string
{
    /** A building's join code, on its dashboard or printed flyers. */
    case Join = 'join';

    /** A tenant's personal code, scanned by a neighbour to vouch for them. */
    case Vouch = 'vouch';

    /** An admin's campaign leaflet, which leads to the home page. */
    case Campaign = 'campaign';

    /** A demo flyer, or the demo union's own printables. */
    case Demo = 'demo';

    public function label(): string
    {
        return match ($this) {
            self::Join => 'Join codes',
            self::Vouch => 'Vouching codes',
            self::Campaign => 'Campaign leaflets',
            self::Demo => 'Demo codes',
        };
    }
}
