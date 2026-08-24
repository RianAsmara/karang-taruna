<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateFinancialReportAction
{
    /**
     * Create a DRAFT report computed from approved transactions for the
     * given period — never a manually-typed balance.
     *
     * @param  array<string, mixed>  $data  title, report_type, period_start, period_end, visibility
     */
    public function handle(Organization $organization, User $creator, array $data): FinancialReport
    {
        return DB::transaction(function () use ($organization, $creator, $data) {
            $figures = FinancialReport::calculateFigures(
                $organization,
                Carbon::parse($data['period_start']),
                Carbon::parse($data['period_end']),
            );

            return $organization->financialReports()->create([
                ...$data,
                ...$figures,
                'status' => FinancialReportStatus::Draft,
                'created_by' => $creator->id,
            ]);
        });
    }
}
