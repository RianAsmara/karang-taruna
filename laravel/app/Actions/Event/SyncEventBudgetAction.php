<?php

namespace App\Actions\Event;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Event;
use App\Models\User;

class SyncEventBudgetAction
{
    /**
     * Update the event's linked DRAFT expense transaction if one already
     * exists, or create it — so re-saving the wizard's "anggaran" field on
     * autosave never creates duplicate planned-expense rows. Financial
     * account/category are auto-selected (no picker in this screen), the
     * same simplification already used for the dues-recording sheet.
     */
    public function handle(Event $event, int $amount, User $actor): void
    {
        if ($event->budget_financial_transaction_id !== null) {
            $event->budgetTransaction()->update(['amount' => $amount]);

            return;
        }

        $organization = $event->organization;

        $transaction = $organization->financialTransactions()->create([
            'financial_account_id' => $organization->financialAccounts()->first()->id,
            'category_id' => $organization->financialCategories()
                ->where('transaction_type', TransactionType::Expense)
                ->first()?->id,
            'event_id' => $event->id,
            'amount' => $amount,
            'transaction_type' => TransactionType::Expense,
            'status' => TransactionStatus::Draft,
            'description' => "Anggaran kegiatan: {$event->title}",
            'transaction_date' => $event->start_at->toDateString(),
            'created_by' => $actor->id,
        ]);

        $event->update(['budget_financial_transaction_id' => $transaction->id]);
    }
}
