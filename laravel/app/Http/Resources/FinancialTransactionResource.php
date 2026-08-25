<?php

namespace App\Http\Resources;

use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialTransaction */
class FinancialTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'transactionType' => $this->transaction_type->value,
            'transactionTypeLabel' => $this->transaction_type->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'description' => $this->description,
            'transactionDate' => $this->transaction_date->toDateString(),
            'accountName' => $this->whenLoaded('financialAccount', fn () => $this->financialAccount->name),
            'relatedAccountName' => $this->whenLoaded('relatedAccount', fn () => $this->relatedAccount?->name),
            'categoryName' => $this->whenLoaded('category', fn () => $this->category?->name),
            'eventTitle' => $this->whenLoaded('event', fn () => $this->event?->title),
            'creatorName' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'reviewerName' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
