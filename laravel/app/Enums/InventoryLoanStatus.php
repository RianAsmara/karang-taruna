<?php

namespace App\Enums;

/**
 * OVERDUE is deliberately not a stored value — it's derived from
 * `status === Borrowed && due_date < today` (see InventoryLoan::isOverdue()).
 * Storing it would need a scheduled job to flip it at exactly the right
 * moment for no real benefit; the master prompt's three-state list
 * (BORROWED/RETURNED/OVERDUE, §33) is a UI-facing description, not a
 * mandate that all three live in the database column.
 */
enum InventoryLoanStatus: string
{
    case Borrowed = 'BORROWED';
    case Returned = 'RETURNED';

    public function label(): string
    {
        return match ($this) {
            self::Borrowed => 'Dipinjam',
            self::Returned => 'Dikembalikan',
        };
    }
}
