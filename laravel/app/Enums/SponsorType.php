<?php

namespace App\Enums;

enum SponsorType: string
{
    case Uang = 'UANG';
    case Barang = 'BARANG';
    case Jasa = 'JASA';

    public function label(): string
    {
        return match ($this) {
            self::Uang => 'Uang',
            self::Barang => 'Barang',
            self::Jasa => 'Jasa',
        };
    }
}
