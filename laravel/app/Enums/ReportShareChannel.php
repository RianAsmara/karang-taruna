<?php

namespace App\Enums;

enum ReportShareChannel: string
{
    case WhatsApp = 'WHATSAPP';
    case Web = 'WEB';
    case Pdf = 'PDF';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Web => 'Tautan web',
            self::Pdf => 'PDF',
        };
    }
}
