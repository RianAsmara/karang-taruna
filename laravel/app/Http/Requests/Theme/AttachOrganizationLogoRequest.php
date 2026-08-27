<?php

namespace App\Http\Requests\Theme;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Two files: `logo` is always the PNG the server-side pipeline actually
 * processes (the browser rasterizes SVG to PNG client-side before upload —
 * see the theme-builder plan's SVG decision, this class never sees SVG
 * pixels); `source` is the original file as the user picked it (PNG or
 * SVG), kept only for provenance/download, never processed.
 *
 * Each spec-named upload error (size/format/dimension) gets its own
 * message rather than Laravel's generic per-rule text, per
 * docs/design/docs/theme-builder.md § States.
 */
class AttachOrganizationLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageTheme', app(Organization::class));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'mimes:png',
                'mimetypes:image/png',
                'max:2048',
                'dimensions:min_width=512,min_height=512',
            ],
            'source' => [
                'required',
                'file',
                'mimes:png,svg',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'source.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.mimes' => 'Format tidak didukung. Gunakan PNG atau SVG — JPEG tidak mendukung transparansi.',
            'logo.mimetypes' => 'Format tidak didukung. Gunakan PNG atau SVG — JPEG tidak mendukung transparansi.',
            'source.mimes' => 'Format tidak didukung. Gunakan PNG atau SVG — JPEG tidak mendukung transparansi.',
            'logo.dimensions' => 'Logo terlalu kecil. Ukuran minimal 512×512 piksel.',
        ];
    }
}
