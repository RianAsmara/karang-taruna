<?php

namespace App\Http\Resources;

use App\Models\EventCommittee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventCommittee */
class MemberResponsibilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roleTitle' => $this->role_title,
            'event' => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'status' => $this->event->status->value,
                'statusLabel' => $this->event->status->label(),
            ],
        ];
    }
}
