<?php

namespace App\Http\Requests\FinancialTransaction;

use App\Enums\TransactionType;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [FinancialTransaction::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'financial_account_id' => [
                'required',
                'string',
                Rule::exists('financial_accounts', 'id')->where('organization_id', $organization->id),
            ],
            'related_account_id' => [
                Rule::requiredIf($this->input('transaction_type') === TransactionType::Transfer->value),
                'nullable',
                'string',
                'different:financial_account_id',
                Rule::exists('financial_accounts', 'id')->where('organization_id', $organization->id),
            ],
            'category_id' => [
                Rule::requiredIf($this->input('transaction_type') !== TransactionType::Transfer->value),
                'nullable',
                'string',
                Rule::exists('financial_categories', 'id')->where('organization_id', $organization->id),
            ],
            'event_id' => [
                'nullable',
                'string',
                Rule::exists('events', 'id')->where('organization_id', $organization->id),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_type' => ['required', Rule::enum(TransactionType::class)],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->input('category_id');
            $transactionType = $this->input('transaction_type');

            if (! $categoryId || ! $transactionType) {
                return;
            }

            $category = FinancialCategory::whereKey($categoryId)->first();

            if ($category && $category->transaction_type->value !== $transactionType) {
                $validator->errors()->add('category_id', 'Kategori tidak sesuai dengan jenis transaksi.');
            }
        });
    }
}
