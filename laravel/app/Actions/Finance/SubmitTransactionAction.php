<?php

namespace App\Actions\Finance;

use App\Enums\OrganizationRole;
use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use App\Notifications\TransactionSubmittedForReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubmitTransactionAction
{
    /**
     * Move a DRAFT transaction forward: PENDING if the organization
     * requires approval, otherwise straight to APPROVED (no reviewer —
     * nothing was reviewed, it was simply not required).
     */
    public function handle(FinancialTransaction $transaction): FinancialTransaction
    {
        $requiresApproval = $transaction->organization->require_transaction_approval;

        DB::transaction(function () use ($transaction, $requiresApproval) {
            $transaction->update([
                'status' => $requiresApproval
                    ? TransactionStatus::Pending
                    : TransactionStatus::Approved,
            ]);
        });

        if ($requiresApproval) {
            $organizers = $transaction->organization->memberships()
                ->where('role', OrganizationRole::Ketua)
                ->where('user_id', '!=', $transaction->created_by)
                ->with('user')
                ->get()
                ->pluck('user');

            Notification::send($organizers, new TransactionSubmittedForReview($transaction));
        }

        return $transaction;
    }
}
