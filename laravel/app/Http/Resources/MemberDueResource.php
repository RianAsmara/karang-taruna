<?php

namespace App\Http\Resources;

use App\Models\MemberDue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MemberDue */
class MemberDueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestPayment = $this->latestPayment();

        return [
            'id' => $this->id,
            'membershipId' => $this->membership_id,
            'memberName' => $this->whenLoaded('membership', fn () => $this->membership->user->name),
            'userId' => $this->whenLoaded('membership', fn () => $this->membership->user_id),
            'period' => $this->period->toDateString(),
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'amountDue' => $this->amount_due,
            'amountPaid' => $this->amountPaid(),
            'amountOutstanding' => $this->amountOutstanding(),
            'isPaid' => $this->isPaid(),
            'isExempt' => $this->is_exempt,
            'notifiedAt' => $this->notified_at?->toIso8601String(),
            'isAwaitingConfirmation' => $this->isAwaitingConfirmation(),
            'isPartiallyPaid' => $this->isPartiallyPaid(),
            'lastPaymentAt' => $latestPayment?->paid_at->toDateString(),
            'recordedByName' => $latestPayment?->recorder?->name,
            'method' => $latestPayment?->method->value,
            'methodLabel' => $latestPayment?->method->label(),
        ];
    }
}
