<?php

namespace App\Notifications;

use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionReviewed extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FinancialTransaction $transaction,
        private readonly User $reviewer,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'organization_id' => $this->transaction->organization_id,
            'status' => $this->transaction->status->value,
            'amount' => $this->transaction->amount,
            'reviewer_name' => $this->reviewer->name,
        ];
    }
}
