<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Database\Factories\OrganizationMembershipFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Soft-deleted, not hard-deleted, when a member leaves — see the
 * migration's docblock. `roleIn()`/`membershipIn()` on User never see a
 * departed member (the default global scope excludes trashed rows), so
 * every existing Policy check keeps working unmodified; only
 * MemberController's index deliberately reaches past that scope with
 * `withTrashed()` to show the 30-day "Keluar" tag.
 *
 * @property OrganizationRole $role
 * @property-read int|null $activity_points only present via withSum('activityLogs as activity_points', 'points')
 */
class OrganizationMembership extends Model
{
    /** @use HasFactory<OrganizationMembershipFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MemberDue, $this>
     */
    public function dues(): HasMany
    {
        return $this->hasMany(MemberDue::class, 'membership_id');
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'membership_id');
    }

    public function activityPoints(): int
    {
        return (int) $this->activityLogs()->sum('points');
    }
}
