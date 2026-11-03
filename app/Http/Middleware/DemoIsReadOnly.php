<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Visitors to the public demo can look at everything but change nothing.
 * Anything other than reading a page is turned away, except logging out and
 * hiding the cookie banner.
 */
class DemoIsReadOnly
{
    public const MESSAGE = 'This is a demo, so nothing you do here is saved. Start a union for your own building to do this for real.';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->routeIs('logout', 'demo.*', 'cookies.hide-banner') || ! $request->user()?->isDemo()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        return back()->with('notice', self::MESSAGE);
    }
}
