<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryItem */
class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'categoryLabel' => $this->category->label(),
            'quantity' => $this->quantity,
            'availableQuantity' => $this->availableQuantity(),
            'condition' => $this->condition->value,
            'conditionLabel' => $this->condition->label(),
            'location' => $this->location,
            'notes' => $this->notes,
            'lastCheckedAt' => $this->last_checked_at?->toDateString(),
            'responsible' => $this->whenLoaded('responsible', fn () => $this->responsible ? [
                'id' => $this->responsible->id,
                'name' => $this->responsible->user->name,
            ] : null),
            'loans' => InventoryLoanResource::collection($this->whenLoaded('loans')),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
