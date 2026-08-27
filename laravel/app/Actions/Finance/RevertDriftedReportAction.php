<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Models\FinancialReport;
use Illuminate\Support\Facades\DB;

class RevertDriftedReportAction
{
    /**
     * A DISETUJUI report whose transactions changed since approval (a
     * new one approved, an existing one edited/rejected) is never
     * silently stale — viewing it catches the drift and sends it back to
     * DRAFT with a system-authored reason (screen 31 edge case).
     */
    public function handle(FinancialReport $report): FinancialReport
    {
        if ($report->status !== FinancialReportStatus::Disetujui || ! $report->hasDriftedSinceApproval()) {
            return $report;
        }

        DB::transaction(function () use ($report) {
            $figures = FinancialReport::calculateFigures($report->organization, $report->period_start, $report->period_end);

            $report->update([
                ...$figures,
                'status' => FinancialReportStatus::Draft,
                'revision_reason' => 'Ada transaksi yang berubah setelah disetujui.',
                'approved_at' => null,
                'approved_by' => null,
            ]);
        });

        return $report;
    }
}
