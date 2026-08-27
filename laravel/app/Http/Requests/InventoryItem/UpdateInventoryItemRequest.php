<?php

namespace App\Http\Requests\InventoryItem;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = $this->route('inventoryItem');

        return $this->user()->can('update', $inventoryItem);
    }

    /**
     * Reducing quantity below what's currently on loan is refused here
     * (screen 20 edge case) rather than left to silently produce a
     * negative available count.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = $this->route('inventoryItem');
        $borrowed = $inventoryItem->borrowedQuantity();

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(InventoryCategory::class)],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($borrowed) {
                    if ($value < $borrowed) {
                        $fail("Jumlah tidak boleh kurang dari {$borrowed} yang sedang dipinjam.");
                    }
                },
            ],
            'condition' => ['required', Rule::enum(InventoryCondition::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'responsible_membership_id' => [
                'nullable',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $inventoryItem->organization_id),
            ],
        ];
    }
}
