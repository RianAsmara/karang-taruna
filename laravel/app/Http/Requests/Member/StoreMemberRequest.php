<?php

namespace App\Http\Requests\Member;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [OrganizationMembership::class, app(Organization::class)]);
    }

    /**
     * KETUA is excluded — the chair is unique and set only by
     * CreateOrganizationAction or a dedicated transfer, never a direct
     * invite. See UpdateMemberRoleRequest for the same rule on changes.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(OrganizationRole::class)->except(OrganizationRole::Ketua)],
        ];
    }
}
