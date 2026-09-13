<?php

namespace App\Http\Resources;

use App\Models\MembershipExitRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MembershipExitRequest
 */
class MembershipExitRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'reason' => $this->reason,
            'memberName' => $this->membership->user?->name,
            'memberRoleLabel' => $this->membership->role->label(),
            'decisionNote' => $this->decision_note,
            'decidedAt' => $this->decided_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
