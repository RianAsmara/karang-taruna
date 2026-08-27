<?php

namespace App\Http\Resources;

use App\Models\InventoryLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryLoan */
class InventoryLoanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'borrower' => $this->whenLoaded('borrower', fn () => [
                'id' => $this->borrower->id,
                'name' => $this->borrower->user->name,
            ]),
            'quantity' => $this->quantity,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isOverdue' => $this->isOverdue(),
            'purpose' => $this->purpose,
            'borrowedAt' => $this->borrowed_at->toDateString(),
            'dueDate' => $this->due_date->toDateString(),
            'returnedAt' => $this->returned_at?->toDateString(),
            'returnedQuantity' => $this->returned_quantity,
            'returnedCondition' => $this->returned_condition?->value,
            'returnNote' => $this->return_note,
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
        ];
    }
}
