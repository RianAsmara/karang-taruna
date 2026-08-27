<?php

namespace App\Http\Requests\Vote;

use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVoteResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Vote $vote */
        $vote = $this->route('vote');

        return $this->user()->can('respond', $vote);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Vote $vote */
        $vote = $this->route('vote');

        return [
            'option_ids' => ['required', 'array', 'min:1', 'max:'.$vote->max_selections],
            'option_ids.*' => [
                'string',
                'distinct',
                Rule::exists('vote_options', 'id')->where('vote_id', $vote->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Vote $vote */
            $vote = $this->route('vote');
            $membership = $this->user()->membershipIn($vote->organization);

            if (! $vote->editable && $membership && $vote->hasResponded($membership)) {
                $validator->errors()->add('option_ids', 'Pilihan tidak bisa diubah setelah dikirim.');
            }
        });
    }
}
