<?php

namespace App\Http\Resources;

use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FinancialReport */
class FinancialReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reportType' => $this->report_type->value,
            'reportTypeLabel' => $this->report_type->label(),
            'periodStart' => $this->period_start->toDateString(),
            'periodEnd' => $this->period_end->toDateString(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'visibility' => $this->visibility->value,
            'visibilityLabel' => $this->visibility->label(),
            'openingBalance' => $this->opening_balance,
            'totalIncome' => $this->total_income,
            'totalExpense' => $this->total_expense,
            'closingBalance' => $this->closing_balance,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'publisherName' => $this->whenLoaded('publisher', fn () => $this->publisher?->name),
            'treasurerNote' => $this->treasurer_note,
            'revisionReason' => $this->revision_reason,
            'submittedAt' => $this->submitted_at?->toIso8601String(),
            'submitterName' => $this->whenLoaded('submitter', fn () => $this->submitter?->name),
            'approvedAt' => $this->approved_at?->toIso8601String(),
            'approverName' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'revisionCount' => $this->whenCounted('revisions'),
            'organizationName' => $this->whenLoaded('organization', fn () => $this->organization->name),
        ];
    }
}
