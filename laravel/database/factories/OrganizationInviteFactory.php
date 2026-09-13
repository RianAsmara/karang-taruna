<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OrganizationInvite>
 */
class OrganizationInviteFactory extends Factory
{
    protected $model = OrganizationInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'token' => OrganizationInvite::generateToken(),
            'created_by' => User::factory(),
            'expires_at' => Carbon::now()->addDays(7),
            'max_uses' => null,
            'uses' => 0,
        ];
    }
}
