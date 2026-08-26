<?php

namespace App\Notifications;

use App\Models\FinancialTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionSubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(private readonly FinancialTransaction $transaction) {}

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
            'amount' => $this->transaction->amount,
            'transaction_type' => $this->transaction->transaction_type->value,
            'description' => $this->transaction->description,
        ];
    }
}
