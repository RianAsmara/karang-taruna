<?php

namespace App\Enums;

enum EventCategory: string
{
    case KerjaBakti = 'KERJA_BAKTI';
    case Olahraga = 'OLAHRAGA';
    case Sosial = 'SOSIAL';
    case Lainnya = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::KerjaBakti => 'Kerja bakti',
            self::Olahraga => 'Olahraga',
            self::Sosial => 'Sosial',
            self::Lainnya => 'Lain-lain',
        };
    }
}
