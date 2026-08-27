<?php

namespace App\Http\Requests\Event;

use App\Enums\EventCategory;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Event::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', Rule::enum(EventCategory::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'pic_membership_id' => [
                'nullable',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $organization->id),
            ],
            'sponsor_id' => [
                'nullable',
                'string',
                Rule::exists('sponsors', 'id')->where('organization_id', $organization->id),
            ],
            'budget_amount' => ['nullable', 'integer', 'min:1'],
            'committees' => ['nullable', 'array'],
            'committees.*.membership_id' => [
                'required',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $organization->id),
            ],
            'committees.*.role_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('budget_amount')) {
                return;
            }

            $organization = app(Organization::class);

            if (! $organization->financialAccounts()->exists()) {
                $validator->errors()->add('budget_amount', 'Atur akun kas terlebih dahulu sebelum menetapkan anggaran.');
            }
        });
    }
}
