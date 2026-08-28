<?php

namespace App\Models;

use App\Enums\ActivityPointSource;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ActivityPointSource $source
 */
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'membership_id',
        'points',
        'source',
        'source_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'source' => ActivityPointSource::class,
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class);
    }
}
