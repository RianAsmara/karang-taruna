<?php

namespace Database\Factories;

use App\Enums\MembershipExitRequestStatus;
use App\Models\MembershipExitRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipExitRequest>
 */
class MembershipExitRequestFactory extends Factory
{
    protected $model = MembershipExitRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'membership_id' => OrganizationMembership::factory(),
            'reason' => $this->faker->sentence(),
            'status' => MembershipExitRequestStatus::Pending,
        ];
    }
}
