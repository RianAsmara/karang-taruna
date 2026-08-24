<?php

namespace App\Enums;

enum FinancialReportStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Diterbitkan',
            self::Archived => 'Diarsipkan',
        };
    }
}
