<?php

namespace App\Actions\Vote;

use App\Enums\VoteEligibleScope;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateVoteAction
{
    /**
     * @param  string[]  $optionLabels
     */
    public function handle(
        Organization $organization,
        User $creator,
        string $question,
        ?string $description,
        bool $anonymous,
        bool $editable,
        int $maxSelections,
        VoteEligibleScope $eligibleScope,
        ?string $eventId,
        Carbon $startAt,
        Carbon $endAt,
        array $optionLabels,
    ): Vote {
        return DB::transaction(function () use (
            $organization, $creator, $question, $description, $anonymous, $editable,
            $maxSelections, $eligibleScope, $eventId, $startAt, $endAt, $optionLabels,
        ) {
            $vote = $organization->votes()->create([
                'question' => $question,
                'description' => $description,
                'anonymous' => $anonymous,
                'editable' => $editable,
                'max_selections' => $maxSelections,
                'eligible_scope' => $eligibleScope,
                'event_id' => $eventId,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'created_by' => $creator->id,
            ]);

            foreach ($optionLabels as $position => $label) {
                $vote->options()->create(['label' => $label, 'position' => $position]);
            }

            return $vote;
        });
    }
}
