<?php

namespace App\Policies;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;

class FinancialReportPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    /**
     * DRAFT reports are internal prep — treasury-only. Once
     * PUBLISHED/ARCHIVED, visibility follows the report's own
     * PRIVATE/MEMBERS/PUBLIC setting. (PUBLIC unauthenticated access is
     * handled by a separate controller path, not this Policy, which
     * always requires a logged-in User.)
     */
    public function view(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status === FinancialReportStatus::Draft) {
            return $user->isTreasurerOf($financialReport->organization);
        }

        return match ($financialReport->visibility) {
            FinancialReportVisibility::Private => $user->isOrganizerOf($financialReport->organization),
            FinancialReportVisibility::Members, FinancialReportVisibility::Public => $user->roleIn($financialReport->organization) !== null,
        };
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    public function delete(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Draft) {
            return false;
        }

        return $user->isTreasurerOf($financialReport->organization);
    }

    /**
     * Publishing, archiving, and revising are organizer-grade governance
     * actions — deliberately excludes TREASURER, who prepares the report
     * but is not its sole approver, mirroring the transaction approval
     * separation of duties.
     */
    public function publish(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Draft) {
            return false;
        }

        return $user->isOrganizerOf($financialReport->organization);
    }

    public function archive(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Published) {
            return false;
        }

        return $user->isOrganizerOf($financialReport->organization);
    }

    public function revise(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Published) {
            return false;
        }

        return $user->isOrganizerOf($financialReport->organization);
    }
}
