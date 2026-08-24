<?php

namespace App\Http\Requests\EventCommittee;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventCommitteeRequest extends FormRequest
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
            'membership_id' => [
                'required',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $event->organization_id),
                Rule::unique('event_committees', 'membership_id')->where('event_id', $event->id),
            ],
            'role_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
