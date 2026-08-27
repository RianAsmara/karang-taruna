<?php

namespace App\Http\Requests\Event;

use App\Enums\EventCategory;
use App\Enums\EventLifecycleStage;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()->can('update', $event);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

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
                Rule::exists('organization_memberships', 'id')->where('organization_id', $event->organization_id),
            ],
            'sponsor_id' => [
                'nullable',
                'string',
                Rule::exists('sponsors', 'id')->where('organization_id', $event->organization_id),
            ],
            'budget_amount' => ['nullable', 'integer', 'min:1'],
            'committees' => ['nullable', 'array'],
            'committees.*.membership_id' => [
                'required',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $event->organization_id),
            ],
            'committees.*.role_title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'lifecycle_stage' => ['required', Rule::enum(EventLifecycleStage::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('budget_amount')) {
                return;
            }

            /** @var Event $event */
            $event = $this->route('event');
            $organization = $event->organization;

            if (! $organization->financialAccounts()->exists()) {
                $validator->errors()->add('budget_amount', 'Atur akun kas terlebih dahulu sebelum menetapkan anggaran.');
            }
        });
    }
}
