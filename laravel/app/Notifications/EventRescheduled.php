<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventRescheduled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Event $event) {}

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
            'event_id' => $this->event->id,
            'organization_id' => $this->event->organization_id,
            'title' => $this->event->title,
            'start_at' => $this->event->start_at->toIso8601String(),
            'location' => $this->event->location,
        ];
    }
}
