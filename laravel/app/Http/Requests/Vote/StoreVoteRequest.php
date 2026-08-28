<?php

namespace App\Http\Requests\Vote;

use App\Enums\VoteEligibleScope;
use App\Models\Organization;
use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Vote::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'question' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'anonymous' => ['required', 'boolean'],
            'editable' => ['required', 'boolean'],
            'max_selections' => ['required', 'integer', 'min:1', 'max:'.count((array) $this->input('options', []))],
            'eligible_scope' => ['required', Rule::enum(VoteEligibleScope::class)],
            'event_id' => [
                'nullable',
                'string',
                Rule::exists('events', 'id')->where('organization_id', $organization->id),
            ],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }
}
