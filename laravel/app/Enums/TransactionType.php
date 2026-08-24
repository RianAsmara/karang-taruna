<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'INCOME';
    case Expense = 'EXPENSE';
    case Transfer = 'TRANSFER';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
            self::Transfer => 'Transfer',
        };
    }
}
