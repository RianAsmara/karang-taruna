<?php

namespace App\Http\Controllers\Api\V1\Superadmin;

use App\Actions\Superadmin\ViewOrganizationOverviewAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialReportResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\Superadmin\OrganizationSummaryResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only, cross-organization — see EnsureSuperadmin. Deliberately a
 * separate route/controller surface rather than a bypass bolted onto
 * the existing per-org controllers: those all resolve `Organization`
 * from the *viewer's own* membership via the `current-org` middleware,
 * which a superadmin (by definition, no membership anywhere) can never
 * satisfy. This surface resolves `Organization` from the URL directly.
 *
 * Every view here is written to AuditLog (via ViewOrganizationOverviewAction,
 * shared with the web superadmin surface) — this bypasses the normal
 * tenant-isolation invariant (master prompt §9), so every use of it
 * must be traceable to who looked at what, when.
 */
class OrganizationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $organizations = Organization::withCount('memberships')->orderBy('name')->get();

        return OrganizationSummaryResource::collection($organizations);
    }

    public function show(Organization $organization, ViewOrganizationOverviewAction $action): JsonResponse
    {
        $overview = $action->handle($organization, Auth::user());

        // MemberResource::collection()/FinancialReportResource::collection()
        // only auto-wrap in {"data": [...]} when returned as the response
        // directly — nested inside this array they'd otherwise serialize
        // as bare arrays, breaking the {"data": [...]} convention every
        // other list endpoint in this API follows (docs/api.md).
        return response()->json([
            'organization' => new OrganizationSummaryResource($overview['organization']),
            'summary' => $organization->transparencySummary(),
            'members' => ['data' => MemberResource::collection($overview['members'])],
            'reports' => ['data' => FinancialReportResource::collection($overview['reports'])],
        ]);
    }
}
