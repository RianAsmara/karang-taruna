<?php

namespace App\Enums;

enum MemberPaymentMethod: string
{
    case Tunai = 'TUNAI';
    case Transfer = 'TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Transfer => 'Transfer',
        };
    }
}
