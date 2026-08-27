<?php

namespace App\Http\Requests\Sponsor;

use App\Enums\SponsorType;
use App\Models\Organization;
use App\Models\SponsorContribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [SponsorContribution::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SponsorType::class)],
            'amount' => ['required_if:type,'.SponsorType::Uang->value, 'nullable', 'integer', 'min:1'],
            'description' => ['required_unless:type,'.SponsorType::Uang->value, 'nullable', 'string'],
            'event_id' => [
                'nullable',
                'string',
                Rule::exists('events', 'id')->where('organization_id', $organization->id),
            ],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
