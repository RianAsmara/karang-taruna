<?php

namespace App\Http\Requests\MemberDue;

use App\Enums\MemberPaymentMethod;
use App\Models\MemberDue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MemberDue $due */
        $due = $this->route('due');

        return $this->user()->can('recordPayment', $due);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var MemberDue $due */
        $due = $this->route('due');
        $organizationId = $due->organization_id;

        return [
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'financial_account_id' => [
                'required',
                'string',
                Rule::exists('financial_accounts', 'id')->where('organization_id', $organizationId),
            ],
            'category_id' => [
                'required',
                'string',
                Rule::exists('financial_categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('transaction_type', 'INCOME'),
            ],
            'method' => ['nullable', Rule::enum(MemberPaymentMethod::class)],
            'note' => ['nullable', 'string'],
        ];
    }
}
