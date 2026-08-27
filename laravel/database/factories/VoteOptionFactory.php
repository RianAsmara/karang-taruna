<?php

namespace Database\Factories;

use App\Models\Vote;
use App\Models\VoteOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoteOption>
 */
class VoteOptionFactory extends Factory
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
            'label' => fake()->randomElement(['Ya', 'Tidak', 'Abstain']),
            'position' => 0,
        ];
    }
}
