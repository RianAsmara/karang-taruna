<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\FinancialReportReviewed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApproveFinancialReportAction
{
    public function handle(FinancialReport $report, User $chair): FinancialReport
    {
        DB::transaction(function () use ($report, $chair) {
            $figures = FinancialReport::calculateFigures($report->organization, $report->period_start, $report->period_end);

            $report->update([
                ...$figures,
                'status' => FinancialReportStatus::Disetujui,
                'approved_at' => now(),
                'approved_by' => $chair->id,
            ]);
        });

        if ($report->submitter !== null) {
            Notification::send($report->submitter, new FinancialReportReviewed($report, approved: true));
        }

        return $report;
    }
}
