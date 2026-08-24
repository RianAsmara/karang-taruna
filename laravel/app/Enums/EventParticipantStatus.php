<?php

namespace App\Enums;

enum EventParticipantStatus: string
{
    case Registered = 'REGISTERED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Terdaftar',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
