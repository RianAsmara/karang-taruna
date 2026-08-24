<?php

namespace App\Enums;

enum EventLifecycleStage: string
{
    case Idea = 'IDEA';
    case Approval = 'APPROVAL';
    case Preparation = 'PREPARATION';
    case Event = 'EVENT';
    case Closing = 'CLOSING';
    case Report = 'REPORT';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Ide',
            self::Approval => 'Persetujuan',
            self::Preparation => 'Persiapan',
            self::Event => 'Pelaksanaan',
            self::Closing => 'Penutupan',
            self::Report => 'Laporan',
        };
    }
}
