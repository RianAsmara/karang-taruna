<?php

namespace App\Http\Controllers\Superadmin;

use App\Actions\Superadmin\ViewOrganizationOverviewAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialReportResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\Superadmin\OrganizationSummaryResource;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Web counterpart to Api\V1\Superadmin\OrganizationController — same
 * scope (read-only, cross-organization, gated by the 'superadmin'
 * middleware, see ADR-0018), sharing ViewOrganizationOverviewAction so
 * the audit-log invariant can't drift between the two entry points.
 */
class OrganizationController extends Controller
{
    public function index(): Response
    {
        $organizations = Organization::withCount('memberships')->orderBy('name')->get();

        return Inertia::render('superadmin/organizations/index', [
            'organizations' => OrganizationSummaryResource::collection($organizations)->resolve(),
        ]);
    }

    public function show(Organization $organization, ViewOrganizationOverviewAction $action): Response
    {
        $overview = $action->handle($organization, Auth::user());

        return Inertia::render('superadmin/organizations/show', [
            'organization' => (new OrganizationSummaryResource($overview['organization']))->resolve(),
            'summary' => $organization->transparencySummary(),
            'members' => MemberResource::collection($overview['members'])->resolve(),
            'reports' => FinancialReportResource::collection($overview['reports'])->resolve(),
        ]);
    }
}
