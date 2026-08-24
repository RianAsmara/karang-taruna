<?php

namespace App\Http\Requests\FinancialCategory;

use App\Enums\TransactionType;
use App\Models\FinancialCategory;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [FinancialCategory::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'transaction_type' => [
                'required',
                Rule::enum(TransactionType::class)->except(TransactionType::Transfer),
            ],
        ];
    }
}
