<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Ketua = 'KETUA';
    case Bendahara = 'BENDAHARA';
    case Sekretaris = 'SEKRETARIS';
    case Anggota = 'ANGGOTA';

    public function label(): string
    {
        return match ($this) {
            self::Ketua => 'Ketua',
            self::Bendahara => 'Bendahara',
            self::Sekretaris => 'Sekretaris',
            self::Anggota => 'Anggota',
        };
    }
}
