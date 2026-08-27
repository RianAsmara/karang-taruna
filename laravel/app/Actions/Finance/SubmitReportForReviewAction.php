<?php

namespace App\Actions\Finance;

use App\Enums\FinancialReportStatus;
use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\ReportSubmittedForReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubmitReportForReviewAction
{
    /**
     * Move a DRAFT report to DIPERIKSA for the chair's review. Figures
     * are recomputed one last time, same as every other status
     * transition on this model — the treasurer's own note travels with
     * it (screen 30: "Catatan bendahara").
     */
    public function handle(FinancialReport $report, User $treasurer, ?string $note): FinancialReport
    {
        DB::transaction(function () use ($report, $treasurer, $note) {
            $figures = FinancialReport::calculateFigures($report->organization, $report->period_start, $report->period_end);

            $report->update([
                ...$figures,
                'status' => FinancialReportStatus::Diperiksa,
                'treasurer_note' => $note,
                'submitted_at' => now(),
                'submitted_by' => $treasurer->id,
            ]);
        });

        $chairs = $report->organization->memberships()
            ->where('role', OrganizationRole::Ketua)
            ->with('user')
            ->get()
            ->pluck('user');

        Notification::send($chairs, new ReportSubmittedForReview($report));

        return $report;
    }
}
