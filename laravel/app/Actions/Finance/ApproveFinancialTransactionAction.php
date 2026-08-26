<?php

namespace App\Actions\Finance;

use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Notifications\TransactionReviewed;
use Illuminate\Support\Facades\DB;

class ApproveFinancialTransactionAction
{
    public function handle(FinancialTransaction $transaction, User $reviewer): FinancialTransaction
    {
        DB::transaction(function () use ($transaction, $reviewer) {
            $transaction->update([
                'status' => TransactionStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });

        $transaction->creator->notify(new TransactionReviewed($transaction, $reviewer));

        return $transaction;
    }
}
