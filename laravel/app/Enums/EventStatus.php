<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'DRAFT';
    case Planned = 'PLANNED';
    case Ongoing = 'ONGOING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Planned => 'Direncanakan',
            self::Ongoing => 'Berlangsung',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
