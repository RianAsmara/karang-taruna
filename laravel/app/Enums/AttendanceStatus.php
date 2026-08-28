<?php

namespace App\Enums;

/**
 * Only HADIR exists today — the master prompt's "record ... status"
 * requirement is honored with a real column rather than assumed away,
 * leaving room for an organizer-marked IZIN/ALPHA later without a
 * migration, but nothing in the current design asks for those states yet.
 */
enum AttendanceStatus: string
{
    case Hadir = 'HADIR';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
        };
    }
}
