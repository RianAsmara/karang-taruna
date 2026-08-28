<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class SwitchOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Real membership is verified inside SwitchOrganizationAction, not
     * here — this only checks the shape of the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'string'],
        ];
    }
}
