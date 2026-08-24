<?php

namespace App\Http\Requests\FinancialReport;

use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Models\FinancialReport;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [FinancialReport::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'report_type' => ['required', Rule::enum(FinancialReportType::class)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'visibility' => ['required', Rule::enum(FinancialReportVisibility::class)],
        ];
    }
}
