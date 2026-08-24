<?php

namespace Database\Factories;

use App\Enums\ReportShareChannel;
use App\Models\FinancialReport;
use App\Models\ReportShareLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportShareLog>
 */
class ReportShareLogFactory extends Factory
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
            'channel' => fake()->randomElement(ReportShareChannel::cases()),
            'shared_at' => now(),
        ];
    }
}
