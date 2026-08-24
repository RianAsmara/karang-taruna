<?php

namespace Database\Factories;

use App\Models\MemberDue;
use App\Models\MemberPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberPayment>
 */
class MemberPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_due_id' => MemberDue::factory(),
            'amount' => 25_000,
            'paid_at' => now(),
        ];
    }
}
