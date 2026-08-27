<?php

namespace App\Enums;

enum InventoryCondition: string
{
    case Baik = 'BAIK';
    case PerluPerbaikan = 'PERLU_PERBAIKAN';
    case Rusak = 'RUSAK';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::PerluPerbaikan => 'Perlu perbaikan',
            self::Rusak => 'Rusak',
        };
    }
}
