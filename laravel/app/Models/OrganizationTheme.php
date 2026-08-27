<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, string> $color_light
 * @property array<string, string> $color_dark
 */
class OrganizationTheme extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id',
        'primary_hex',
        'color_light',
        'color_dark',
        'disk',
        'logo_source_path',
        'logo_source_mime',
        'logo_mark_1x_path',
        'logo_mark_2x_path',
        'logo_mark_3x_path',
        'logo_icon_path',
        'logo_mono_path',
    ];

    protected function casts(): array
    {
        return [
            'color_light' => 'array',
            'color_dark' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
