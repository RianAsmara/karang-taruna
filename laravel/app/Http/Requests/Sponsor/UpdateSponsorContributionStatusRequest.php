<?php

namespace App\Http\Requests\Sponsor;

use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Models\SponsorContribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSponsorContributionStatusRequest extends FormRequest
{
    /**
     * "Status advances one step at a time... Batal is available from any
     * state" (screen 24).
     */
    private const ALLOWED_FROM = [
        SponsorContributionStatus::Diajukan->value => [SponsorContributionStatus::Setuju, SponsorContributionStatus::Batal],
        SponsorContributionStatus::Setuju->value => [SponsorContributionStatus::Diterima, SponsorContributionStatus::Batal],
        SponsorContributionStatus::Diterima->value => [SponsorContributionStatus::Batal],
        SponsorContributionStatus::Batal->value => [],
    ];

    public function authorize(): bool
    {
        /** @var SponsorContribution $contribution */
        $contribution = $this->route('sponsor');

        return $this->user()->can('update', $contribution);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SponsorContribution $contribution */
        $contribution = $this->route('sponsor');
        $organizationId = $contribution->organization_id;

        return [
            'status' => ['required', Rule::enum(SponsorContributionStatus::class)],
            // Marking Diterima for a cash (UANG) sponsorship optionally
            // records the matching income transaction — "one tap, not
            // automatic" (screen 23): both fields together, or neither.
            'financial_account_id' => [
                'nullable',
                'string',
                'required_with:category_id',
                Rule::exists('financial_accounts', 'id')->where('organization_id', $organizationId),
            ],
            'category_id' => [
                'nullable',
                'string',
                'required_with:financial_account_id',
                Rule::exists('financial_categories', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('transaction_type', 'INCOME'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var SponsorContribution $contribution */
            $contribution = $this->route('sponsor');
            $requested = SponsorContributionStatus::tryFrom((string) $this->input('status'));

            if ($requested === null) {
                return;
            }

            $allowed = self::ALLOWED_FROM[$contribution->status->value];

            if (! in_array($requested, $allowed, true)) {
                $validator->errors()->add(
                    'status',
                    "Tidak bisa mengubah status dari {$contribution->status->label()} ke {$requested->label()}.",
                );
            }

            $wantsTransaction = $this->filled('financial_account_id') || $this->filled('category_id');
            $isCashDiterima = $requested === SponsorContributionStatus::Diterima
                && $contribution->type === SponsorType::Uang;

            if ($wantsTransaction && ! $isCashDiterima) {
                $validator->errors()->add(
                    'financial_account_id',
                    'Transaksi hanya bisa dicatat saat menandai sponsor uang sebagai Diterima.',
                );
            }
        });
    }
}
