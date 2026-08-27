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
        if (in_array($financialReport->status, [
            FinancialReportStatus::Draft,
            FinancialReportStatus::Diperiksa,
            FinancialReportStatus::Disetujui,
        ], true)) {
            return $user->isTreasurerOf($financialReport->organization);
        }

        return match ($financialReport->visibility) {
            FinancialReportVisibility::Private => $user->isChairOf($financialReport->organization),
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

    public function updateNote(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Draft) {
            return false;
        }

        return $user->isTreasurerOf($financialReport->organization);
    }

    /**
     * A treasurer composing a report sends it for the chair's review
     * (screen 30). Allowed from either role since a chair may also
     * choose to submit rather than use the direct-publish shortcut below.
     */
    public function submit(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Draft) {
            return false;
        }

        return $user->isTreasurerOf($financialReport->organization);
    }

    /**
     * Chair-only, and never the submitter themselves — mirrors the
     * transaction approval separation of duties. A lone BENDAHARA who
     * submitted their own report cannot also approve it; only the chair
     * can (see `publish()` for the case where the chair submits it
     * personally, which skips this step entirely).
     */
    public function approve(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Diperiksa) {
            return false;
        }

        return $user->isChairOf($financialReport->organization);
    }

    public function requestRevision(User $user, FinancialReport $financialReport): bool
    {
        return $this->approve($user, $financialReport);
    }

    /**
     * Publishing, archiving, and revising are chair-grade governance
     * actions — deliberately excludes a lone BENDAHARA, who prepares the
     * report but is not its sole approver. A chair may publish a DRAFT
     * directly (screen 29's "the approval step is skipped" when the
     * chair composes and submits it themselves — they already have this
     * right, so there is no separate combined action) or a DISETUJUI
     * report that went through review.
     */
    public function publish(User $user, FinancialReport $financialReport): bool
    {
        if (! in_array($financialReport->status, [FinancialReportStatus::Draft, FinancialReportStatus::Disetujui], true)) {
            return false;
        }

        return $user->isChairOf($financialReport->organization);
    }

    public function archive(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Published) {
            return false;
        }

        return $user->isChairOf($financialReport->organization);
    }

    public function revise(User $user, FinancialReport $financialReport): bool
    {
        if ($financialReport->status !== FinancialReportStatus::Published) {
            return false;
        }

        return $user->isChairOf($financialReport->organization);
    }
}
