<?php

namespace App\Actions\Sponsor;

use App\Actions\Finance\SubmitTransactionAction;
use App\Enums\SponsorContributionStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\SponsorContribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSponsorContributionStatusAction
{
    public function __construct(
        private readonly SubmitTransactionAction $submitTransaction,
    ) {}

    /**
     * Marking DITERIMA for a cash sponsorship optionally records the
     * matching income transaction. It's created as DRAFT and immediately
     * submitted through the same approval workflow as any other
     * transaction (respects `require_transaction_approval`) — a
     * sponsor's cash never bypasses approval just because it originated
     * here instead of the transaction form.
     */
    public function handle(
        SponsorContribution $contribution,
        SponsorContributionStatus $status,
        User $actor,
        ?FinancialAccount $account,
        ?FinancialCategory $category,
    ): SponsorContribution {
        return DB::transaction(function () use ($contribution, $status, $actor, $account, $category) {
            $contribution->update(['status' => $status]);

            if ($account !== null && $category !== null) {
                $transaction = $contribution->organization->financialTransactions()->create([
                    'financial_account_id' => $account->id,
                    'category_id' => $category->id,
                    'event_id' => $contribution->event_id,
                    'amount' => $contribution->amount,
                    'transaction_type' => TransactionType::Income,
                    'status' => TransactionStatus::Draft,
                    'description' => "Sponsor: {$contribution->sponsor->name}",
                    'transaction_date' => now(),
                    'created_by' => $actor->id,
                ]);

                $this->submitTransaction->handle($transaction);

                $contribution->update(['financial_transaction_id' => $transaction->id]);
            }

            return $contribution;
        });
    }
}
