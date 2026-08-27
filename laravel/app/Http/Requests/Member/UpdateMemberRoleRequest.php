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

        if ($membership->role === OrganizationRole::Ketua) {
            return false;
        }

        return $this->user()->can('update', $membership);
    }

    /**
     * KETUA is excluded — the chair role changes only via a dedicated
     * transfer (not built yet), never this general-purpose endpoint, so
     * the organization can never end up with zero or more than one chair.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::enum(OrganizationRole::class)->except(OrganizationRole::Ketua),
            ],
        ];
    }
}
