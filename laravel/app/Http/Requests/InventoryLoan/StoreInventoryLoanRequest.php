<?php

namespace App\Http\Requests\InventoryLoan;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var InventoryItem $item */
        $item = $this->route('inventoryItem');

        return $this->user()->can('borrow', $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var InventoryItem $item */
        $item = $this->route('inventoryItem');
        $available = $item->availableQuantity();

        return [
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($available) {
                    if ($value > $available) {
                        $fail("Hanya {$available} tersedia.");
                    }
                },
            ],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['nullable', 'string'],
            'event_id' => [
                'nullable',
                'string',
                Rule::exists('events', 'id')->where('organization_id', $item->organization_id),
            ],
        ];
    }
}
