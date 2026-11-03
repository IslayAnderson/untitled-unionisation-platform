<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only enrolled union members may use the union's tools.
 */
class EnsureMember
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->contact?->isEnrolled()) {
            return redirect()->route('dashboard')
                ->with('status', 'Only union members can use that. Get vouched for by your neighbours first.');
        }

        return $next($request);
    }
}
