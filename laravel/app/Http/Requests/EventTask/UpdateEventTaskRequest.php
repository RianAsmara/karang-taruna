<?php

namespace App\Http\Requests\EventTask;

use App\Enums\EventTaskPriority;
use App\Enums\EventTaskStatus;
use App\Models\EventTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EventTask $eventTask */
        $eventTask = $this->route('task');

        return $this->user()->can('update', $eventTask);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var EventTask $eventTask */
        $eventTask = $this->route('task');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_membership_id' => [
                'nullable',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $eventTask->event->organization_id),
            ],
            'status' => ['required', Rule::enum(EventTaskStatus::class)],
            'priority' => ['required', Rule::enum(EventTaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
