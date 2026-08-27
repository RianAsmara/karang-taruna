<?php

namespace App\Models;

use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'name',
        'contact_name',
        'contact_phone',
        'notes',
        'created_by',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<SponsorContribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(SponsorContribution::class);
    }
}
