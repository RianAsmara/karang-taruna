<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'actor_id' => User::factory(),
            'action' => 'transaction.created',
            'model_type' => 'App\\Models\\FinancialTransaction',
            'model_id' => (string) Str::ulid(),
            'previous_values' => null,
            'new_values' => ['amount' => 100_000],
        ];
    }
}
