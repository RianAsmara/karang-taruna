<?php

namespace App\Http\Requests\Membership;

use App\Models\MembershipExitRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreMembershipExitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->user()->currentMembership();

        if ($membership === null) {
            return false;
        }

        return $this->user()->can('create', [MembershipExitRequest::class, $membership]);
    }

    /**
     * The chair is refused by the policy, but "403 Forbidden" is the wrong
     * answer for something the member can fix themselves — surface it as
     * the actionable reason instead (hand the role over first).
     */
    protected function failedAuthorization(): void
    {
        throw ValidationException::withMessages([
            'membership' => 'Ketua tidak bisa keluar sebelum memindahkan peran ketua ke anggota lain.',
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
