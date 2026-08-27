<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\FinancialReportReviewed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RequestReportRevisionAction
{
    /**
     * Send a DIPERIKSA report back to DRAFT with a required reason,
     * shown at the top of Susun Laporan on the treasurer's next visit
     * (screen 30/31).
     */
    public function handle(FinancialReport $report, User $chair, string $reason): FinancialReport
    {
        DB::transaction(function () use ($report, $reason) {
            $report->update([
                'status' => FinancialReportStatus::Draft,
                'revision_reason' => $reason,
            ]);
        });

        if ($report->submitter !== null) {
            Notification::send($report->submitter, new FinancialReportReviewed($report, approved: false));
        }

        return $report;
    }
}
