<?php

namespace App\Http\Requests\Theme;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageTheme', app(Organization::class));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'primary' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }
}
