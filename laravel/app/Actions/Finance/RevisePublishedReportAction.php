<?php

namespace App\Actions\Finance;

use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevisePublishedReportAction
{
    /**
     * Correct a PUBLISHED report without silently rewriting history: the
     * current state is snapshotted into a new FinancialReportRevision
     * first, then the report's own figures are recomputed fresh. The
     * report stays PUBLISHED throughout — only its numbers move forward,
     * and every past state remains readable via its revisions.
     */
    public function handle(FinancialReport $report, User $reviser): FinancialReport
    {
        return DB::transaction(function () use ($report, $reviser) {
            $nextRevisionNumber = ((int) $report->revisions()->max('revision_number')) + 1;

            $report->revisions()->create([
                'revision_number' => $nextRevisionNumber,
                'snapshot' => [
                    'title' => $report->title,
                    'opening_balance' => $report->opening_balance,
                    'total_income' => $report->total_income,
                    'total_expense' => $report->total_expense,
                    'closing_balance' => $report->closing_balance,
                    'published_at' => $report->published_at?->toIso8601String(),
                    'published_by' => $report->published_by,
                ],
                'created_by' => $reviser->id,
            ]);

            $figures = FinancialReport::calculateFigures($report->organization, $report->period_start, $report->period_end);

            $report->update($figures);

            return $report;
        });
    }
}
