<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case SoundSystem = 'SOUND_SYSTEM';
    case KursiMeja = 'KURSI_MEJA';
    case Tenda = 'TENDA';
    case Olahraga = 'OLAHRAGA';
    case Lain = 'LAIN_LAIN';

    public function label(): string
    {
        return match ($this) {
            self::SoundSystem => 'Sound system',
            self::KursiMeja => 'Kursi & meja',
            self::Tenda => 'Tenda',
            self::Olahraga => 'Olahraga',
            self::Lain => 'Lain-lain',
        };
    }
}
