<?php

namespace App\Http\Requests\MemberDue;

use App\Enums\MemberDueType;
use App\Models\MemberDue;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberDueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [MemberDue::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'membership_id' => [
                'required',
                'string',
                Rule::exists('organization_memberships', 'id')->where('organization_id', $organization->id),
            ],
            'period' => ['required', 'date'],
            'amount_due' => ['required', 'integer', 'min:1'],
            'type' => [
                'required',
                Rule::enum(MemberDueType::class),
                Rule::unique('member_dues')
                    ->where('organization_id', $organization->id)
                    ->where('membership_id', $this->input('membership_id'))
                    ->where('period', $this->input('period'))
                    ->where('type', $this->input('type')),
            ],
        ];
    }
}
