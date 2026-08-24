<?php

namespace Database\Factories;

use App\Models\FinancialReport;
use App\Models\FinancialReportRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialReportRevision>
 */
class FinancialReportRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financial_report_id' => FinancialReport::factory(),
            'revision_number' => 1,
            'snapshot' => ['closing_balance' => 0],
            'created_by' => User::factory(),
        ];
    }
}
