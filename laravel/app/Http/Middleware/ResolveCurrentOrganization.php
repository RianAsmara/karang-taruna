<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's organization from their membership —
 * never from a client-supplied organization_id — and binds it into the
 * container so controllers can simply type-hint Organization/
 * OrganizationMembership instead of re-resolving it themselves.
 */
class ResolveCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $membership = $request->user()->currentMembership();

        if (! $membership) {
            return redirect()->route('dashboard')
                ->with('error', 'Anda belum tergabung di organisasi manapun.');
        }

        app()->instance(OrganizationMembership::class, $membership);
        app()->instance(Organization::class, $membership->organization);

        return $next($request);
    }
}
