<?php

namespace App\Enums;

enum MemberDueType: string
{
    case Monthly = 'MONTHLY';
    case Event = 'EVENT';
    case Special = 'SPECIAL';
    case Donation = 'DONATION';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Iuran bulanan',
            self::Event => 'Iuran kegiatan',
            self::Special => 'Kontribusi khusus',
            self::Donation => 'Donasi',
        };
    }
}
