<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = DocumentCategory::tryFrom((string) $this->input('category'));

        if ($category === null) {
            // Let validation (not authorization) produce the "invalid
            // category" error. Default to a category only chair/secretary
            // can create (anything but Laporan) — never the one category
            // a treasurer also has access to — so a garbage value can't
            // accidentally let a treasurer-only user pass authorization.
            $category = DocumentCategory::Proposal;
        }

        return $this->user()->can('create', [Document::class, app(Organization::class), $category]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = app(Organization::class);

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
            'event_id' => [
                'nullable',
                'string',
                Rule::exists('events', 'id')->where('organization_id', $organization->id),
            ],
        ];
    }
}
