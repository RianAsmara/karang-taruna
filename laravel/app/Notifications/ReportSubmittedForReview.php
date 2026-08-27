<?php

namespace App\Notifications;

use App\Models\FinancialReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FinancialReport $report) {}

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
            'period_start' => $this->report->period_start->toDateString(),
            'period_end' => $this->report->period_end->toDateString(),
        ];
    }
}
