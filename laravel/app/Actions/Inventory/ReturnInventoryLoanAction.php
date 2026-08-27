<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryCondition;
use App\Enums\InventoryLoanStatus;
use App\Models\InventoryLoan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReturnInventoryLoanAction
{
    public function handle(InventoryLoan $loan, int $quantity, InventoryCondition $condition, ?string $note): InventoryLoan
    {
        return DB::transaction(function () use ($loan, $quantity, $condition, $note) {
            $loan->update([
                'status' => InventoryLoanStatus::Returned,
                'returned_at' => Carbon::today(),
                'returned_quantity' => $quantity,
                'returned_condition' => $condition,
                'return_note' => $note,
            ]);

            // The item's own condition reflects the worst reported state
            // across its returns — a damage report on one loan shouldn't
            // be silently overwritten by an unrelated, unaffected unit's
            // later return at a better condition.
            $item = $loan->inventoryItem;
            if ($this->severity($condition) > $this->severity($item->condition)) {
                $item->update(['condition' => $condition, 'last_checked_at' => Carbon::today()]);
            } else {
                $item->update(['last_checked_at' => Carbon::today()]);
            }

            return $loan;
        });
    }

    private function severity(InventoryCondition $condition): int
    {
        return match ($condition) {
            InventoryCondition::Baik => 0,
            InventoryCondition::PerluPerbaikan => 1,
            InventoryCondition::Rusak => 2,
        };
    }
}
