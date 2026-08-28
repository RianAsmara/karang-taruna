<?php

namespace App\Enums;

/**
 * "Participation scoring, derived from event/task/attendance activity"
 * (domain-model.md § Engagement & audit) — the two concrete, currently
 * measurable actions that fit that description. Not an exhaustive or
 * final list; extend here as new participation signals become worth
 * scoring.
 */
enum ActivityPointSource: string
{
    case EventAttendance = 'EVENT_ATTENDANCE';
    case TaskCompleted = 'TASK_COMPLETED';

    public function points(): int
    {
        return match ($this) {
            self::EventAttendance => 5,
            self::TaskCompleted => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EventAttendance => 'Hadir di kegiatan',
            self::TaskCompleted => 'Tugas selesai',
        };
    }
}
