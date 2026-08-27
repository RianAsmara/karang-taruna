<?php

namespace App\Notifications;

use App\Models\FinancialReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FinancialReportReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FinancialReport $report, private readonly bool $approved) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'organization_id' => $this->report->organization_id,
            'title' => $this->report->title,
            'approved' => $this->approved,
            'revision_reason' => $this->approved ? null : $this->report->revision_reason,
        ];
    }
}
