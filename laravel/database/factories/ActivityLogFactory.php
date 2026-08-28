<?php

namespace Database\Factories;

use App\Enums\ActivityPointSource;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $source = fake()->randomElement(ActivityPointSource::cases());

        return [
            'organization_id' => Organization::factory(),
            'membership_id' => OrganizationMembership::factory(),
            'points' => $source->points(),
            'source' => $source,
            'source_id' => (string) Str::ulid(),
        ];
    }
}
