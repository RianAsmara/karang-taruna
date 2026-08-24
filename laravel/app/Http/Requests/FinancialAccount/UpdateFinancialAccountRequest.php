<?php

namespace App\Http\Requests\FinancialAccount;

use App\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialAccount $account */
        $account = $this->route('account');

        return $this->user()->can('update', $account);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var FinancialAccount $account */
        $account = $this->route('account');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_accounts', 'name')
                    ->where('organization_id', $account->organization_id)
                    ->ignore($account->id),
            ],
        ];
    }
}
