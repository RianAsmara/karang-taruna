<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\BorrowInventoryItemAction;
use App\Actions\Inventory\ReturnInventoryLoanAction;
use App\Enums\InventoryCondition;
use App\Http\Requests\InventoryLoan\ReturnInventoryLoanRequest;
use App\Http\Requests\InventoryLoan\StoreInventoryLoanRequest;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InventoryLoanController extends Controller
{
    public function store(StoreInventoryLoanRequest $request, InventoryItem $inventoryItem, BorrowInventoryItemAction $borrow): RedirectResponse
    {
        $borrower = Auth::user()->membershipIn($inventoryItem->organization);
        $event = $request->filled('event_id') ? Event::findOrFail($request->string('event_id')->value()) : null;

        $borrow->handle(
            $inventoryItem,
            $borrower,
            (int) $request->integer('quantity'),
            Carbon::parse($request->string('due_date')->value()),
            $request->string('purpose')->value() ?: null,
            $event,
        );

        return to_route('inventory.show', $inventoryItem);
    }

    public function return(ReturnInventoryLoanRequest $request, InventoryLoan $loan, ReturnInventoryLoanAction $returnLoan): RedirectResponse
    {
        $returnLoan->handle(
            $loan,
            (int) $request->integer('quantity'),
            InventoryCondition::from($request->string('condition')->value()),
            $request->string('note')->value() ?: null,
        );

        return to_route('inventory.show', $loan->inventory_item_id);
    }
}
