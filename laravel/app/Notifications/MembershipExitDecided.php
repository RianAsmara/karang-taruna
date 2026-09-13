<?php

namespace App\Notifications;

use App\Models\MembershipExitRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MembershipExitDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly MembershipExitRequest $exitRequest) {}

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
            'exit_request_id' => $this->exitRequest->id,
            'organization_id' => $this->exitRequest->organization_id,
            'organization_name' => $this->exitRequest->organization->name,
            'status' => $this->exitRequest->status->value,
            'status_label' => $this->exitRequest->status->label(),
            'decision_note' => $this->exitRequest->decision_note,
        ];
    }
}
