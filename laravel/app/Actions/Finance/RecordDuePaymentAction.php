<?php

namespace App\Actions\Finance;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MemberDue;
use App\Models\MemberPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordDuePaymentAction
{
    public function __construct(
        private readonly SubmitTransactionAction $submitTransaction,
    ) {}

    /**
     * Record a payment against a due, and mirror it into the ledger as an
     * INCOME transaction so the kas balance and the dues ledger never
     * drift apart. Rejects overpayment beyond what's still outstanding.
     *
     * @throws ValidationException
     */
    public function handle(
        MemberDue $due,
        User $recordedBy,
        int $amount,
        Carbon $paidAt,
        FinancialAccount $account,
        FinancialCategory $category,
    ): MemberPayment {
        if ($amount > $due->amountOutstanding()) {
            throw ValidationException::withMessages([
                'amount' => 'Jumlah pembayaran melebihi sisa tagihan.',
            ]);
        }

        return DB::transaction(function () use ($due, $recordedBy, $amount, $paidAt, $account, $category) {
            $transaction = $due->organization->financialTransactions()->create([
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => $amount,
                'transaction_type' => TransactionType::Income,
                'status' => TransactionStatus::Draft,
                'description' => sprintf('%s — %s (%s)', $due->type->label(), $due->membership->user->name, $due->period->translatedFormat('F Y')),
                'transaction_date' => $paidAt,
                'created_by' => $recordedBy->id,
            ]);

            $this->submitTransaction->handle($transaction);

            return $due->payments()->create([
                'financial_transaction_id' => $transaction->id,
                'amount' => $amount,
                'paid_at' => $paidAt,
            ]);
        });
    }
}
