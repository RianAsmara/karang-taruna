<?php

namespace App\Enums;

enum FinancialReportVisibility: string
{
    case Private = 'PRIVATE';
    case Members = 'MEMBERS';
    case Public = 'PUBLIC';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Hanya pengurus',
            self::Members => 'Anggota',
            self::Public => 'Publik',
        };
    }
}
