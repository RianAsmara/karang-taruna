<?php

namespace App\Http\Resources;

use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vote */
class VoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $membership = $user?->membershipIn($this->organization);
        $isEligible = $user !== null && $this->isEligible($user);

        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'anonymous' => $this->anonymous,
            'editable' => $this->editable,
            'maxSelections' => $this->max_selections,
            'startAt' => $this->start_at->toIso8601String(),
            'endAt' => $this->end_at->toIso8601String(),
            'isOpen' => $this->isOpen(),
            'isEligible' => $isEligible,
            'hasResponded' => $membership ? $this->hasResponded($membership) : false,
            'participationCount' => $this->responses()->distinct('membership_id')->count('membership_id'),
            'eligibleCount' => $this->eligibleCount(),
            'options' => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
            ]),
            'myOptionIds' => $membership
                ? $this->responses()->where('membership_id', $membership->id)->pluck('vote_option_id')
                : [],
            'event' => $this->whenLoaded('event', fn () => $this->event ? ['id' => $this->event->id, 'title' => $this->event->title] : null),
        ];
    }

    private function eligibleCount(): int
    {
        $query = $this->organization->memberships();

        if ($this->eligible_scope->value === 'PENGURUS') {
            $query->where('role', '!=', 'ANGGOTA');
        }

        return $query->count();
    }
}
