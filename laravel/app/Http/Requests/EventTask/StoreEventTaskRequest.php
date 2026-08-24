<?php

namespace App\Http\Requests\EventTask;

use App\Enums\EventTaskPriority;
use App\Models\Event;
use App\Models\EventTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()->can('create', [EventTask::class, $event]);
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
            'assignee_membership_id' => [
                'nullable',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $event->organization_id),
            ],
            'priority' => ['required', Rule::enum(EventTaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
