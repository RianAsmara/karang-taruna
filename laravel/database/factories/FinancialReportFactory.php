<?php

namespace Database\Factories;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialReport>
 */
class FinancialReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', 'now')->modify('first day of this month');
        $income = fake()->numberBetween(500_000, 3_000_000);
        $expense = fake()->numberBetween(100_000, 1_500_000);
        $opening = fake()->numberBetween(0, 2_000_000);

        return [
            'organization_id' => Organization::factory(),
            'title' => 'Laporan Kas Bulanan',
            'report_type' => FinancialReportType::Monthly,
            'period_start' => $start,
            'period_end' => (clone $start)->modify('last day of this month'),
            'status' => FinancialReportStatus::Draft,
            'visibility' => FinancialReportVisibility::Members,
            'opening_balance' => $opening,
            'total_income' => $income,
            'total_expense' => $expense,
            'closing_balance' => $opening + $income - $expense,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => FinancialReportStatus::Published,
            'published_at' => now(),
            'published_by' => User::factory(),
        ]);
    }

    public function publicVisibility(): static
    {
        return $this->state(['visibility' => FinancialReportVisibility::Public]);
    }

    public function privateVisibility(): static
    {
        return $this->state(['visibility' => FinancialReportVisibility::Private]);
    }
}
