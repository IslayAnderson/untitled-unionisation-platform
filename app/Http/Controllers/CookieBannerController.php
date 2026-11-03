<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Hides the cookie banner. It only tells people about essential cookies, so
 * there's nothing to accept or reject: this just remembers it was seen.
 */
class CookieBannerController extends Controller
{
    public const COOKIE = 'cookie_banner_seen';

    public function __invoke(Request $request): RedirectResponse
    {
        return back(fallback: route('home'))->withCookie(cookie(self::COOKIE, '1', 60 * 24 * 365));
    }
}
