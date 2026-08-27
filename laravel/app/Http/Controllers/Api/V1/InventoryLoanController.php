<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Inventory\BorrowInventoryItemAction;
use App\Actions\Inventory\ReturnInventoryLoanAction;
use App\Enums\InventoryCondition;
use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryLoan\ReturnInventoryLoanRequest;
use App\Http\Requests\InventoryLoan\StoreInventoryLoanRequest;
use App\Http\Resources\InventoryLoanResource;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class InventoryLoanController extends Controller
{
    public function store(StoreInventoryLoanRequest $request, InventoryItem $inventoryItem, BorrowInventoryItemAction $borrow): JsonResponse
    {
        $borrower = $request->user()->membershipIn($inventoryItem->organization);
        $event = $request->filled('event_id') ? Event::findOrFail($request->string('event_id')->value()) : null;

        $loan = $borrow->handle(
            $inventoryItem,
            $borrower,
            (int) $request->integer('quantity'),
            Carbon::parse($request->string('due_date')->value()),
            $request->string('purpose')->value() ?: null,
            $event,
        );

        return (new InventoryLoanResource($loan->load('borrower.user:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function return(ReturnInventoryLoanRequest $request, InventoryLoan $loan, ReturnInventoryLoanAction $returnLoan): JsonResource
    {
        $returnLoan->handle(
            $loan,
            (int) $request->integer('quantity'),
            InventoryCondition::from($request->string('condition')->value()),
            $request->string('note')->value() ?: null,
        );

        return new InventoryLoanResource($loan->fresh()->load('borrower.user:id,name'));
    }
}
