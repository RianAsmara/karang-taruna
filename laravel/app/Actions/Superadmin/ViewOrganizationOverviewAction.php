<?php

namespace App\Actions\Superadmin;

use App\Models\AuditLog;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared between the web and API superadmin surfaces (ADR-0018) so the
 * "view everything, always audit" invariant can't drift between the two
 * entry points — each caller applies its own Resource/serialization on
 * top of what this returns.
 */
class ViewOrganizationOverviewAction
{
    /**
     * @return array{organization: Organization, members: Collection<int, OrganizationMembership>, reports: Collection<int, FinancialReport>}
     */
    public function handle(Organization $organization, User $actor): array
    {
        AuditLog::create([
            'organization_id' => $organization->id,
            'actor_id' => $actor->id,
            'action' => 'superadmin.viewed_organization',
            'model_type' => Organization::class,
            'model_id' => $organization->id,
            'previous_values' => null,
            'new_values' => null,
        ]);

        return [
            'organization' => $organization->loadCount('memberships'),
            'members' => $organization->memberships()->with('user:id,name,email')->orderBy('created_at')->get(),
            // Full detail regardless of visibility/status (including
            // DRAFT and PRIVATE reports) — deliberate, per the "view
            // everything, for support/moderation" scope.
            'reports' => $organization->financialReports()->with(['publisher:id,name'])->withCount('revisions')->orderByDesc('period_start')->get(),
        ];
    }
}
