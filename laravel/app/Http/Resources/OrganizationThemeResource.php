<?php

namespace App\Http\Resources;

use App\Models\OrganizationTheme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * The exact shape from docs/design/docs/theme-builder.md § Output contract
 * — ONLY the six changeable color tokens (per mode) plus logo URLs and
 * identity fields ever appear here. This is deliberate: mobile trusts this
 * resource completely and merges it straight over its binary defaults, so
 * nothing that isn't in this list may ever be serialized, however the
 * model or its relations grow later.
 *
 * @property OrganizationTheme $resource
 */
class OrganizationThemeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $disk = Storage::disk($this->resource->disk);

        return [
            'version' => 1,
            'updatedAt' => $this->resource->updated_at->toIso8601String(),
            'name' => $this->resource->organization->name,
            'primary' => $this->resource->primary_hex,
            'logo' => [
                'mark' => $disk->url($this->resource->logo_mark_3x_path),
                'icon' => $disk->url($this->resource->logo_icon_path),
                'mono' => $disk->url($this->resource->logo_mono_path),
            ],
            'color' => [
                'light' => $this->onlyChangeableTokens($this->resource->color_light),
                'dark' => $this->onlyChangeableTokens($this->resource->color_dark),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $colors
     * @return array<string, mixed>
     */
    private function onlyChangeableTokens(array $colors): array
    {
        return array_intersect_key($colors, array_flip(['accent', 'accent200', 'accent700', 'accent800', 'onAccent', 'onInk']));
    }
}
