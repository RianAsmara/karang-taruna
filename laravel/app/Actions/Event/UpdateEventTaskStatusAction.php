<?php

namespace App\Actions\Event;

use App\Actions\Member\AwardActivityPointsAction;
use App\Enums\ActivityPointSource;
use App\Enums\EventTaskStatus;
use App\Models\EventTask;
use Illuminate\Support\Facades\DB;

class UpdateEventTaskStatusAction
{
    public function __construct(
        private readonly AwardActivityPointsAction $awardActivityPoints,
    ) {}

    /**
     * Awards points to the assignee the moment a task first becomes DONE
     * — not on every save while it stays DONE, and not if it's later
     * toggled back to another status and marked DONE again (the
     * (source, source_id) unique constraint in ActivityLog is what
     * actually guarantees that, this check just avoids the redundant
     * query on the common case).
     */
    public function handle(EventTask $task, EventTaskStatus $status): EventTask
    {
        return DB::transaction(function () use ($task, $status) {
            $becameDone = $status === EventTaskStatus::Done && $task->status !== EventTaskStatus::Done;

            $task->update(['status' => $status]);

            if ($becameDone && $task->assignee !== null) {
                $this->awardActivityPoints->handle(
                    $task->event->organization,
                    $task->assignee,
                    ActivityPointSource::TaskCompleted,
                    $task->id,
                    $task->title,
                );
            }

            return $task;
        });
    }
}
