<?php

namespace App\Http\Resources;

use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vote */
class VoteResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $participationCount = $this->responses()->distinct('membership_id')->count('membership_id');
        // Screen 28 edge case: fewer than 3 voters on an anonymous vote
        // suppresses percentages entirely, to avoid narrowing a ballot
        // down to a near-identifiable individual.
        $suppressPercent = $this->anonymous && $participationCount < 3;

        $canViewBreakdown = $request->user() !== null
            && ! $this->anonymous
            && $request->user()->isPengurusOf($this->organization);

        $optionCounts = $this->options->map(function ($option) use ($participationCount, $suppressPercent) {
            $count = $option->responses()->count();

            return [
                'id' => $option->id,
                'label' => $option->label,
                'count' => $count,
                'percent' => $suppressPercent || $participationCount === 0
                    ? null
                    : (int) round(($count / $participationCount) * 100),
            ];
        });

        $maxCount = $optionCounts->max('count');
        $winners = $optionCounts->where('count', $maxCount)->pluck('id')->values();

        return [
            'id' => $this->id,
            'question' => $this->question,
            'anonymous' => $this->anonymous,
            'closedAt' => $this->end_at->toIso8601String(),
            'participationCount' => $participationCount,
            'eligibleCount' => $this->eligibleCount(),
            'options' => $optionCounts,
            'winningOptionIds' => $maxCount > 0 ? $winners : [],
            'isTie' => $winners->count() > 1,
            'breakdown' => $canViewBreakdown
                ? $this->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'members' => $option->responses()
                        ->with('membership.user:id,name')
                        ->get()
                        ->map(fn ($response) => [
                            'id' => $response->membership->id,
                            'name' => $response->membership->user->name,
                        ]),
                ])
                : null,
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
