<?php

namespace App\Http\Requests\MemberDue;

use App\Models\MemberDue;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class GenerateMonthlyDuesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [MemberDue::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', 'date'],
            'amount_due' => ['required', 'integer', 'min:1'],
        ];
    }
}
