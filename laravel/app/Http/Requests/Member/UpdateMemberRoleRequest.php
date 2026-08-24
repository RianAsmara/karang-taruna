<?php

namespace App\Http\Requests\Member;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var OrganizationMembership $membership */
        $membership = $this->route('member');

        if ($membership->role === OrganizationRole::Owner) {
            return false;
        }

        return $this->user()->can('update', $membership);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::enum(OrganizationRole::class)->except(OrganizationRole::Owner),
            ],
        ];
    }
}
