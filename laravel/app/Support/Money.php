<?php

namespace App\Support;

class Money
{
    public static function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
