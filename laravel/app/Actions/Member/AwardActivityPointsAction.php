<?php

namespace App\Actions\Member;

use App\Enums\ActivityPointSource;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;

class AwardActivityPointsAction
{
    /**
     * Idempotent by (source, source_id) — a task toggled DONE -> TODO ->
     * DONE, or a check-in retried, never earns points twice for the same
     * underlying action.
     */
    public function handle(Organization $organization, OrganizationMembership $membership, ActivityPointSource $source, string $sourceId, ?string $description = null): ActivityLog
    {
        return ActivityLog::firstOrCreate(
            ['source' => $source, 'source_id' => $sourceId],
            [
                'organization_id' => $organization->id,
                'membership_id' => $membership->id,
                'points' => $source->points(),
                'description' => $description,
            ],
        );
    }
}
