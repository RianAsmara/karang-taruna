<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryLoanStatus;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\OrganizationMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowInventoryItemAction
{
    public function handle(
        InventoryItem $item,
        OrganizationMembership $borrower,
        int $quantity,
        Carbon $dueDate,
        ?string $purpose,
        ?Event $event,
    ): InventoryLoan {
        return DB::transaction(fn () => $item->loans()->create([
            'organization_id' => $item->organization_id,
            'borrower_membership_id' => $borrower->id,
            'event_id' => $event?->id,
            'quantity' => $quantity,
            'status' => InventoryLoanStatus::Borrowed,
            'purpose' => $purpose,
            'borrowed_at' => Carbon::today(),
            'due_date' => $dueDate,
        ]));
    }
}
