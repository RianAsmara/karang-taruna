<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    /**
     * Any member may view an APPROVED transaction (financial transparency
     * is a default, not a privilege); DRAFT/PENDING/REJECTED are internal
     * to the treasury team until they reach a final, public state.
     */
    public function view(User $user, FinancialTransaction $financialTransaction): bool
    {
        if ($user->isTreasurerOf($financialTransaction->organization)) {
            return true;
        }

        return $financialTransaction->status === TransactionStatus::Approved
            && $user->roleIn($financialTransaction->organization) !== null;
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->isTreasurerOf($organization);
    }

    /**
     * DRAFT/PENDING may still be edited; once APPROVED/REJECTED a
     * transaction is historical record — never silently modified.
     */
    public function update(User $user, FinancialTransaction $financialTransaction): bool
    {
        if ($financialTransaction->status->isFinal()) {
            return false;
        }

        return $user->isTreasurerOf($financialTransaction->organization);
    }

    public function delete(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $this->update($user, $financialTransaction);
    }

    public function submit(User $user, FinancialTransaction $financialTransaction): bool
    {
        if ($financialTransaction->status !== TransactionStatus::Draft) {
            return false;
        }

        return $user->isTreasurerOf($financialTransaction->organization);
    }

    /**
     * KETUA only — deliberately excludes BENDAHARA, who prepares
     * transactions but should not be the sole approver of their own
     * entries. A preparer also may not approve/reject their own
     * transaction even if they hold KETUA some other way.
     */
    public function review(User $user, FinancialTransaction $financialTransaction): bool
    {
        if ($financialTransaction->status !== TransactionStatus::Pending) {
            return false;
        }

        if ($financialTransaction->created_by === $user->id) {
            return false;
        }

        return $user->isChairOf($financialTransaction->organization);
    }

    /**
     * Attaching/removing evidence (receipts, invoices, transfer proof) is
     * deliberately not gated by status.isFinal() like update()/delete():
     * evidence is supplementary documentation, not the recorded amount or
     * type — finding and attaching a receipt after a transaction is
     * already APPROVED is a normal, legitimate treasury workflow.
     */
    public function manageEvidence(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->isTreasurerOf($financialTransaction->organization);
    }
}
