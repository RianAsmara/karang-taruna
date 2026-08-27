<?php

namespace App\Notifications;

use App\Models\MemberDue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * "Ada pembayaran iuran menunggu konfirmasi." (mobile-ux.md § Notifications).
 * Sent synchronously to the treasury team, same pattern as
 * TransactionSubmittedForReview — the doc's "batched daily" nuance is a
 * scheduling concern (would need its own aggregation job) intentionally
 * not built here; this fires once per notify() call.
 */
class MemberDuePaymentNotified extends Notification
{
    use Queueable;

    public function __construct(private readonly MemberDue $memberDue) {}

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
            'member_due_id' => $this->memberDue->id,
            'organization_id' => $this->memberDue->organization_id,
            'membership_id' => $this->memberDue->membership_id,
            'period' => $this->memberDue->period->toDateString(),
            'member_name' => $this->memberDue->membership->user->name,
        ];
    }
}
