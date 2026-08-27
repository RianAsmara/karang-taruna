<?php

namespace App\Enums;

enum SponsorContributionStatus: string
{
    case Diajukan = 'DIAJUKAN';
    case Setuju = 'SETUJU';
    case Diterima = 'DITERIMA';
    case Batal = 'BATAL';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Setuju => 'Setuju',
            self::Diterima => 'Diterima',
            self::Batal => 'Batal',
        };
    }
}
