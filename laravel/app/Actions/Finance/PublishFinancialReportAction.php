<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishFinancialReportAction
{
    /**
     * Publish a DRAFT report. Figures are recomputed one last time at
     * publish — any transactions approved since the draft was generated
     * are picked up — then the report becomes an immutable official
     * record (see RevisePublishedReportAction for corrections).
     */
    public function handle(FinancialReport $report, User $publisher): FinancialReport
    {
        return DB::transaction(function () use ($report, $publisher) {
            $figures = FinancialReport::calculateFigures($report->organization, $report->period_start, $report->period_end);

            $report->update([
                ...$figures,
                'status' => FinancialReportStatus::Published,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

            return $report;
        });
    }
}
