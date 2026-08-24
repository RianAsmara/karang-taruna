<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\FinancialCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialCategory>
 */
class FinancialCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());

        return [
            'organization_id' => Organization::factory(),
            'name' => $type === TransactionType::Income
                ? fake()->randomElement(['Iuran Anggota', 'Sponsor', 'Donasi'])
                : fake()->randomElement(['Konsumsi', 'Sewa Tempat', 'Dokumentasi']),
            'transaction_type' => $type,
        ];
    }
}
