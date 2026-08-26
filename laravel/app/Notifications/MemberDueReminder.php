<?php

namespace App\Notifications;

use App\Models\MemberDue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberDueReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly MemberDue $due) {}

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
            'member_due_id' => $this->due->id,
            'organization_id' => $this->due->organization_id,
            'type' => $this->due->type->value,
            'period' => $this->due->period->toDateString(),
            'amount_outstanding' => $this->due->amountOutstanding(),
        ];
    }
}
