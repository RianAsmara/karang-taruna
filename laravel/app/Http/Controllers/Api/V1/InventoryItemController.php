<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryItem\StoreInventoryItemRequest;
use App\Http\Requests\InventoryItem\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class InventoryItemController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [InventoryItem::class, $organization]);

        $items = $organization->inventoryItems()
            ->with('responsible.user:id,name')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return InventoryItemResource::collection($items);
    }

    public function store(StoreInventoryItemRequest $request, Organization $organization): JsonResponse
    {
        $item = $organization->inventoryItems()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return (new InventoryItemResource($item->load('responsible.user:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(InventoryItem $inventoryItem): JsonResource
    {
        $this->authorize('view', $inventoryItem);

        return new InventoryItemResource($inventoryItem->load([
            'responsible.user:id,name',
            'loans' => fn ($query) => $query->latest('borrowed_at')->limit(10),
            'loans.borrower.user:id,name',
            'loans.event:id,title',
        ]));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResource
    {
        $inventoryItem->update($request->validated());

        return new InventoryItemResource($inventoryItem->load('responsible.user:id,name'));
    }

    /**
     * Blocked while any loan is still BORROWED (screen 19: "Deleting an
     * item with active loans is blocked").
     */
    public function destroy(InventoryItem $inventoryItem): Response|JsonResponse
    {
        $this->authorize('delete', $inventoryItem);

        if ($inventoryItem->borrowedQuantity() > 0) {
            return response()->json([
                'message' => 'Barang tidak bisa dihapus karena masih ada yang dipinjam.',
            ], 422);
        }

        $inventoryItem->delete();

        return response()->noContent();
    }
}
