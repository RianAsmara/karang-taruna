<?php

namespace App\Actions\Vote;

use App\Models\OrganizationMembership;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class SubmitVoteResponseAction
{
    /**
     * Replaces the member's existing selection(s) with the new ones —
     * "Ubah pilihan" isn't a diff, it's a resubmission. Safe to call even
     * on a first vote (nothing to delete).
     *
     * @param  string[]  $optionIds
     */
    public function handle(Vote $vote, OrganizationMembership $membership, array $optionIds): void
    {
        DB::transaction(function () use ($vote, $membership, $optionIds) {
            $vote->responses()->where('membership_id', $membership->id)->delete();

            foreach ($optionIds as $optionId) {
                $vote->responses()->create([
                    'vote_option_id' => $optionId,
                    'membership_id' => $membership->id,
                ]);
            }
        });
    }
}
