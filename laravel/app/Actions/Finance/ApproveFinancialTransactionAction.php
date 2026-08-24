<?php

namespace App\Actions\Finance;

use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveFinancialTransactionAction
{
    public function handle(FinancialTransaction $transaction, User $reviewer): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction, $reviewer) {
            $transaction->update([
                'status' => TransactionStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $transaction;
        });
    }
}
