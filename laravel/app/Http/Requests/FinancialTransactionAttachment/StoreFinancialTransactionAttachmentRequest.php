<?php

namespace App\Http\Requests\FinancialTransactionAttachment;

use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialTransactionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialTransaction $transaction */
        $transaction = $this->route('transaction');

        return $this->user()->can('manageEvidence', $transaction);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Receipts/invoices/transfer proof/purchase photos — master
            // prompt §25. 5MB matches a typical phone-camera photo without
            // being an easy vector for someone to fill the bucket.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
