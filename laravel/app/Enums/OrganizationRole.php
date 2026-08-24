<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Treasurer = 'TREASURER';
    case Committee = 'COMMITTEE';
    case Member = 'MEMBER';
    case Resident = 'RESIDENT';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Treasurer => 'Bendahara',
            self::Committee => 'Panitia',
            self::Member => 'Anggota',
            self::Resident => 'Warga',
        };
    }
}
