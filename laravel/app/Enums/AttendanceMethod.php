<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Manual = 'MANUAL';
    case Qr = 'QR';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Qr => 'QR',
        };
    }
}
