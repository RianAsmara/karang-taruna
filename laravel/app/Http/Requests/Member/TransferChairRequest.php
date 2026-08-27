<?php

namespace App\Http\Requests\Member;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransferChairRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var OrganizationMembership $member */
        $member = $this->route('member');

        return $this->user()->isChairOf($member->organization);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var OrganizationMembership $member */
            $member = $this->route('member');

            if ($member->role === OrganizationRole::Ketua) {
                $validator->errors()->add('member', 'Anggota ini sudah menjadi ketua.');
            }

            if ($member->user_id === $this->user()->id) {
                $validator->errors()->add('member', 'Tidak bisa memindahkan peran ketua ke diri sendiri.');
            }
        });
    }
}
