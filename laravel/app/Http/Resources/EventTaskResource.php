<?php

namespace App\Http\Resources;

use App\Models\EventTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventTask */
class EventTaskResource extends JsonResource
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
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'priority' => $this->priority->value,
            'priorityLabel' => $this->priority->label(),
            'dueDate' => $this->due_date?->toIso8601String(),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->user->name,
            ] : null),
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ]),
        ];
    }
}
