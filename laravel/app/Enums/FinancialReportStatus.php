<?php

namespace App\Enums;

enum FinancialReportStatus: string
{
    case Draft = 'DRAFT';
    case Diperiksa = 'DIPERIKSA';
    case Disetujui = 'DISETUJUI';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Diperiksa => 'Diperiksa',
            self::Disetujui => 'Disetujui',
            self::Published => 'Diterbitkan',
            self::Archived => 'Diarsipkan',
        };
    }
}
