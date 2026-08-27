<?php

namespace App\Models;

use App\Enums\VoteEligibleScope;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property VoteEligibleScope $eligible_scope
 * @property Carbon $start_at
 * @property Carbon $end_at
 */
class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'question',
        'description',
        'anonymous',
        'editable',
        'max_selections',
        'eligible_scope',
        'event_id',
        'start_at',
        'end_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'anonymous' => 'boolean',
            'editable' => 'boolean',
            'max_selections' => 'integer',
            'eligible_scope' => VoteEligibleScope::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
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
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<VoteOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(VoteOption::class)->orderBy('position');
    }

    /**
     * @return HasMany<VoteResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(VoteResponse::class);
    }

    public function isOpen(): bool
    {
        $now = now();

        return $now->betweenIncluded($this->start_at, $this->end_at);
    }

    public function isEligible(User $user): bool
    {
        $role = $user->roleIn($this->organization);

        if ($role === null) {
            return false;
        }

        return $this->eligible_scope === VoteEligibleScope::All || $user->isPengurusOf($this->organization);
    }

    public function hasResponded(OrganizationMembership $membership): bool
    {
        return $this->responses()->where('membership_id', $membership->id)->exists();
    }
}
