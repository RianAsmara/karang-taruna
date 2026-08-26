<?php

namespace App\Actions\Finance;

use App\Enums\TransactionStatus;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Notifications\TransactionReviewed;
use Illuminate\Support\Facades\DB;

class RejectFinancialTransactionAction
{
    public function handle(FinancialTransaction $transaction, User $reviewer): FinancialTransaction
    {
        DB::transaction(function () use ($transaction, $reviewer) {
            $transaction->update([
                'status' => TransactionStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });

        $transaction->creator->notify(new TransactionReviewed($transaction, $reviewer));

        return $transaction;
    }
}
