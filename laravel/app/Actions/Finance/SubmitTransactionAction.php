<?php

namespace App\Actions\Finance;

use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;

class SubmitTransactionAction
{
    /**
     * Move a DRAFT transaction forward: PENDING if the organization
     * requires approval, otherwise straight to APPROVED (no reviewer —
     * nothing was reviewed, it was simply not required).
     */
    public function handle(FinancialTransaction $transaction): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => $transaction->organization->require_transaction_approval
                    ? TransactionStatus::Pending
                    : TransactionStatus::Approved,
            ]);

            return $transaction;
        });
    }
}
