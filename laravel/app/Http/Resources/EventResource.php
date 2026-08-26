<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'startAt' => $this->start_at->toIso8601String(),
            'endAt' => $this->end_at?->toIso8601String(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lifecycleStage' => $this->lifecycle_stage->value,
            'lifecycleStageLabel' => $this->lifecycle_stage->label(),
            'pic' => $this->whenLoaded('pic', fn () => $this->pic ? [
                'id' => $this->pic->id,
                'name' => $this->pic->user->name,
            ] : null),
            'tasks' => EventTaskResource::collection($this->whenLoaded('tasks')),
            'committees' => $this->whenLoaded('committees', fn () => $this->committees->map(fn ($c) => [
                'id' => $c->id,
                'membershipId' => $c->membership_id,
                'name' => $c->membership->user->name,
                'roleTitle' => $c->role_title,
            ])),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($p) => [
                'id' => $p->id,
                'membershipId' => $p->membership_id,
                'name' => $p->membership->user->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
            ])),
            // Cheap counts for list views (via withCount()) that don't
            // want the full member lists above.
            'committeeCount' => $this->whenCounted('committees'),
            'participantCount' => $this->whenCounted('participants'),
        ];
    }
}
