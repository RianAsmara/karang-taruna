<?php

namespace Database\Factories;

use App\Enums\MemberDueType;
use App\Models\MemberDue;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberDue>
 */
class MemberDueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'membership_id' => OrganizationMembership::factory(),
            'period' => now()->startOfMonth(),
            'amount_due' => 25_000,
            'type' => MemberDueType::Monthly,
        ];
    }
}
