<?php

namespace App\Http\Requests\InventoryItem;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Models\InventoryItem;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [InventoryItem::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(InventoryCategory::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', Rule::enum(InventoryCondition::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'responsible_membership_id' => [
                'nullable',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $organization->id),
            ],
        ];
    }
}
