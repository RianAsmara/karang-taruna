<?php

namespace App\Http\Requests\FinancialCategory;

use App\Models\FinancialCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialCategory $category */
        $category = $this->route('category');

        return $this->user()->can('update', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
