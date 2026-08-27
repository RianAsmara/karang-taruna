<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the entire superadmin route group. Deliberately a flat
 * boolean check, not a Policy — this is a platform-level bypass of the
 * tenant model, not a tenant-scoped authorization decision, so it
 * doesn't belong in the same Policy/Gate vocabulary as
 * isChairOf()/isTreasurerOf() etc.
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_superadmin, 403);

        return $next($request);
    }
}
