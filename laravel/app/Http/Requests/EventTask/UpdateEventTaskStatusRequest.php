<?php

namespace App\Http\Requests\EventTask;

use App\Enums\EventTaskStatus;
use App\Models\EventTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EventTask $eventTask */
        $eventTask = $this->route('task');

        return $this->user()->can('updateStatus', $eventTask);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EventTaskStatus::class)],
        ];
    }
}
