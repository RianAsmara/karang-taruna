<?php

namespace App\Http\Requests\InventoryLoan;

use App\Enums\InventoryCondition;
use App\Models\InventoryLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReturnInventoryLoanRequest extends FormRequest
{
    /** Lower index = better condition. */
    private const SEVERITY = [
        InventoryCondition::Baik->value => 0,
        InventoryCondition::PerluPerbaikan->value => 1,
        InventoryCondition::Rusak->value => 2,
    ];

    private bool $conditionDropped = false;

    public function authorize(): bool
    {
        /** @var InventoryLoan $loan */
        $loan = $this->route('loan');

        return $this->user()->can('return', $loan);
    }

    /**
     * A required note when the returned condition is worse than the
     * item's current recorded condition (screen 19: "if condition drops,
     * a note becomes required") is enforced in withValidator() below,
     * since it depends on comparing against the current model state.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var InventoryLoan $loan */
        $loan = $this->route('loan');

        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$loan->quantity],
            'condition' => ['required', Rule::enum(InventoryCondition::class)],
            'note' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var InventoryLoan $loan */
        $loan = $this->route('loan');
        $currentSeverity = self::SEVERITY[$loan->inventoryItem->condition->value];
        $submittedSeverity = self::SEVERITY[$this->input('condition')] ?? null;

        $this->conditionDropped = $submittedSeverity !== null && $submittedSeverity > $currentSeverity;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->conditionDropped && ! $this->filled('note')) {
                $validator->errors()->add('note', 'Catatan wajib diisi karena kondisi barang menurun.');
            }
        });
    }
}
