<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'DRAFT';
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Pending => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * DRAFT/PENDING may still be edited or deleted; APPROVED/REJECTED are
     * historical record — never silently modified.
     */
    public function isFinal(): bool
    {
        return $this === self::Approved || $this === self::Rejected;
    }
}
