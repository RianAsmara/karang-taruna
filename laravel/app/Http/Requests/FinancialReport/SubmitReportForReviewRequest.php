<?php

namespace App\Http\Requests\FinancialReport;

use App\Models\FinancialReport;
use Illuminate\Foundation\Http\FormRequest;

class SubmitReportForReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FinancialReport $report */
        $report = $this->route('report');

        return $this->user()->can('submit', $report);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
        ];
    }
}
