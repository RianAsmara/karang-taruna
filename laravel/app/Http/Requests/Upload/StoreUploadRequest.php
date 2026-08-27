<?php

namespace App\Http\Requests\Upload;

use App\Models\Organization;
use App\Models\Upload;
use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Upload::class, app(Organization::class)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Both rules deliberately: `mimes` maps the allowed
            // extensions to their expected MIME types; `mimetypes` checks
            // the upload's actual sniffed content type against the same
            // list, so a file renamed to spoof one of these four
            // extensions still can't slip a different real type through.
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:10240',
            ],
        ];
    }
}
