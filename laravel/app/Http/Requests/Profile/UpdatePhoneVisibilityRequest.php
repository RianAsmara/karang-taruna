<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberately narrow — just the two fields the opt-in phone-visibility
 * feature needs (mobile-ux.md § Open product decisions). Not a general
 * profile-edit endpoint; name/email edits aren't part of any of the
 * screens this unblocks.
 */
class UpdatePhoneVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:32'],
            'show_phone_to_members' => ['required', 'boolean'],
        ];
    }
}
