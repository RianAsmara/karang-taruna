<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
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
            'financial_account_id' => FinancialAccount::factory(),
            'amount' => fake()->numberBetween(10_000, 2_000_000),
            'transaction_type' => fake()->randomElement([TransactionType::Income, TransactionType::Expense]),
            'status' => TransactionStatus::Approved,
            'description' => fake()->sentence(6),
            'transaction_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => TransactionStatus::Draft]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TransactionStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => TransactionStatus::Rejected]);
    }
}
