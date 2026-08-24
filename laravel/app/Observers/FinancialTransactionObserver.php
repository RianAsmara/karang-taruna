<?php

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Models\AuditLog;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\Auth;

class FinancialTransactionObserver
{
    public function created(FinancialTransaction $transaction): void
    {
        AuditLog::create([
            'organization_id' => $transaction->organization_id,
            'actor_id' => Auth::id(),
            'action' => 'transaction.created',
            'model_type' => FinancialTransaction::class,
            'model_id' => $transaction->id,
            'previous_values' => null,
            'new_values' => $this->snapshot($transaction),
        ]);
    }

    public function updated(FinancialTransaction $transaction): void
    {
        $changes = $transaction->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $previous = array_intersect_key($transaction->getOriginal(), $changes);

        AuditLog::create([
            'organization_id' => $transaction->organization_id,
            'actor_id' => Auth::id(),
            'action' => $this->actionFor($transaction, $changes),
            'model_type' => FinancialTransaction::class,
            'model_id' => $transaction->id,
            'previous_values' => $previous,
            'new_values' => $changes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function actionFor(FinancialTransaction $transaction, array $changes): string
    {
        if (! array_key_exists('status', $changes)) {
            return 'transaction.updated';
        }

        return match ($transaction->status) {
            TransactionStatus::Pending => 'transaction.submitted',
            TransactionStatus::Approved => $transaction->reviewed_by
                ? 'transaction.approved'
                : 'transaction.auto_approved',
            TransactionStatus::Rejected => 'transaction.rejected',
            default => 'transaction.updated',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(FinancialTransaction $transaction): array
    {
        return $transaction->only([
            'financial_account_id',
            'related_account_id',
            'category_id',
            'event_id',
            'amount',
            'transaction_type',
            'status',
            'description',
            'transaction_date',
        ]);
    }
}
