<?php

namespace App\Http\Resources;

use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialCategory */
class FinancialCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'transactionType' => $this->transaction_type->value,
            'transactionTypeLabel' => $this->transaction_type->label(),
        ];
    }
}
