<?php

namespace App\Http\Requests\FinancialAccount;

use App\Models\FinancialAccount;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [FinancialAccount::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_accounts', 'name')->where('organization_id', $organization->id),
            ],
        ];
    }
}
