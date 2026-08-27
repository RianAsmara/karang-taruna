<?php

namespace App\Http\Controllers;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Http\Requests\InventoryItem\StoreInventoryItemRequest;
use App\Http\Requests\InventoryItem\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    /**
     * Grouped by category — mobile-screens.md §18.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [InventoryItem::class, $organization]);

        $items = $organization->inventoryItems()
            ->with('responsible.user:id,name')
            ->orderBy('category')
            ->orderByRaw("condition = 'RUSAK'")
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category->value,
                'categoryLabel' => $item->category->label(),
                'quantity' => $item->quantity,
                'availableQuantity' => $item->availableQuantity(),
                'condition' => $item->condition->value,
                'conditionLabel' => $item->condition->label(),
            ]);

        return Inertia::render('inventory/index', [
            'items' => $items,
            'canCreate' => Auth::user()->can('create', [InventoryItem::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [InventoryItem::class, $organization]);

        return Inertia::render('inventory/create', $this->formOptions($organization));
    }

    public function store(StoreInventoryItemRequest $request, Organization $organization): RedirectResponse
    {
        $item = $organization->inventoryItems()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return to_route('inventory.show', $item);
    }

    public function show(InventoryItem $inventoryItem): Response
    {
        $this->authorize('view', $inventoryItem);

        $inventoryItem->load([
            'responsible.user:id,name',
            'loans' => fn ($query) => $query->latest('borrowed_at')->limit(10),
            'loans.borrower.user:id,name',
            'loans.event:id,title',
        ]);

        $membership = Auth::user()->membershipIn($inventoryItem->organization);
        $myActiveLoan = $membership
            ? $inventoryItem->loans->first(fn ($loan) => $loan->status->value === 'BORROWED' && $loan->borrower_membership_id === $membership->id)
            : null;

        return Inertia::render('inventory/show', [
            'item' => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'category' => $inventoryItem->category->value,
                'categoryLabel' => $inventoryItem->category->label(),
                'quantity' => $inventoryItem->quantity,
                'availableQuantity' => $inventoryItem->availableQuantity(),
                'condition' => $inventoryItem->condition->value,
                'conditionLabel' => $inventoryItem->condition->label(),
                'location' => $inventoryItem->location,
                'notes' => $inventoryItem->notes,
                'lastCheckedAt' => $inventoryItem->last_checked_at?->toDateString(),
                'responsible' => $inventoryItem->responsible ? [
                    'id' => $inventoryItem->responsible->id,
                    'name' => $inventoryItem->responsible->user->name,
                ] : null,
            ],
            'loans' => $inventoryItem->loans->map(fn ($loan) => [
                'id' => $loan->id,
                'borrowerName' => $loan->borrower->user->name,
                'quantity' => $loan->quantity,
                'status' => $loan->status->value,
                'statusLabel' => $loan->status->label(),
                'isOverdue' => $loan->isOverdue(),
                'purpose' => $loan->purpose,
                'borrowedAt' => $loan->borrowed_at->toDateString(),
                'dueDate' => $loan->due_date->toDateString(),
                'returnedAt' => $loan->returned_at?->toDateString(),
                'eventTitle' => $loan->event?->title,
            ]),
            'canManage' => Auth::user()->can('update', $inventoryItem),
            'canBorrow' => Auth::user()->can('borrow', $inventoryItem),
            'myActiveLoanId' => $myActiveLoan?->id,
            'conditions' => array_map(
                fn (InventoryCondition $c) => ['value' => $c->value, 'label' => $c->label()],
                InventoryCondition::cases(),
            ),
            'events' => $inventoryItem->organization->events()->orderByDesc('start_at')->get(['id', 'title']),
        ]);
    }

    public function edit(InventoryItem $inventoryItem): Response
    {
        $this->authorize('update', $inventoryItem);

        return Inertia::render('inventory/edit', [
            ...$this->formOptions($inventoryItem->organization),
            'item' => [
                'id' => $inventoryItem->id,
                'name' => $inventoryItem->name,
                'category' => $inventoryItem->category->value,
                'quantity' => $inventoryItem->quantity,
                'condition' => $inventoryItem->condition->value,
                'location' => $inventoryItem->location,
                'notes' => $inventoryItem->notes,
                'responsible_membership_id' => $inventoryItem->responsible_membership_id,
            ],
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->update($request->validated());

        return to_route('inventory.show', $inventoryItem);
    }

    /**
     * Blocked while any loan is still BORROWED — mobile-screens.md §19:
     * "Deleting an item with active loans is blocked".
     */
    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);

        if ($inventoryItem->borrowedQuantity() > 0) {
            throw ValidationException::withMessages([
                'inventoryItem' => 'Barang tidak bisa dihapus karena masih ada yang dipinjam.',
            ]);
        }

        $inventoryItem->delete();

        return to_route('inventory.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Organization $organization): array
    {
        return [
            'categories' => array_map(
                fn (InventoryCategory $c) => ['value' => $c->value, 'label' => $c->label()],
                InventoryCategory::cases(),
            ),
            'conditions' => array_map(
                fn (InventoryCondition $c) => ['value' => $c->value, 'label' => $c->label()],
                InventoryCondition::cases(),
            ),
            'members' => $organization->memberships()
                ->with('user:id,name')
                ->get()
                ->map(fn ($m) => ['id' => $m->id, 'name' => $m->user->name])
                ->all(),
        ];
    }
}
