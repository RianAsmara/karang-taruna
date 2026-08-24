<?php

namespace App\Enums;

enum FinancialReportType: string
{
    case Monthly = 'MONTHLY';
    case Event = 'EVENT';
    case Annual = 'ANNUAL';
    case CashFlow = 'CASH_FLOW';
    case MemberDues = 'MEMBER_DUES';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Event => 'Kegiatan',
            self::Annual => 'Tahunan',
            self::CashFlow => 'Arus Kas',
            self::MemberDues => 'Iuran Anggota',
        };
    }
}
