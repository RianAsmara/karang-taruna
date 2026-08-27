<?php

namespace Database\Factories;

use App\Models\OrganizationMembership;
use App\Models\Vote;
use App\Models\VoteOption;
use App\Models\VoteResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoteResponse>
 */
class VoteResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vote_id' => Vote::factory(),
            'vote_option_id' => VoteOption::factory(),
            'membership_id' => OrganizationMembership::factory(),
        ];
    }
}
