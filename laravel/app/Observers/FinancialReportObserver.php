<?php

namespace App\Observers;

use App\Enums\FinancialReportStatus;
use App\Models\AuditLog;
use App\Models\FinancialReport;
use Illuminate\Support\Facades\Auth;

class FinancialReportObserver
{
    public function created(FinancialReport $report): void
    {
        AuditLog::create([
            'organization_id' => $report->organization_id,
            'actor_id' => Auth::id(),
            'action' => 'report.created',
            'model_type' => FinancialReport::class,
            'model_id' => $report->id,
            'previous_values' => null,
            'new_values' => $this->snapshot($report),
        ]);
    }

    public function updated(FinancialReport $report): void
    {
        $changes = $report->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $previous = array_intersect_key($report->getOriginal(), $changes);

        AuditLog::create([
            'organization_id' => $report->organization_id,
            'actor_id' => Auth::id(),
            'action' => $this->actionFor($report, $changes),
            'model_type' => FinancialReport::class,
            'model_id' => $report->id,
            'previous_values' => $previous,
            'new_values' => $changes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function actionFor(FinancialReport $report, array $changes): string
    {
        if (array_key_exists('status', $changes)) {
            return match ($report->status) {
                FinancialReportStatus::Published => 'report.published',
                FinancialReportStatus::Archived => 'report.archived',
                default => 'report.updated',
            };
        }

        if (array_key_exists('closing_balance', $changes) && $report->status === FinancialReportStatus::Published) {
            return 'report.revised';
        }

        return 'report.updated';
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(FinancialReport $report): array
    {
        return $report->only([
            'title',
            'report_type',
            'period_start',
            'period_end',
            'status',
            'visibility',
            'opening_balance',
            'total_income',
            'total_expense',
            'closing_balance',
        ]);
    }
}
