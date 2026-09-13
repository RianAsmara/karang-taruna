<?php

namespace App\Models;

use App\Enums\MembershipExitRequestStatus;
use Database\Factories\MembershipExitRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A member's request to leave an organization, awaiting the chair's
 * decision. See the migration docblock for why leaving is a request.
 *
 * @property MembershipExitRequestStatus $status
 * @property Carbon|null $decided_at
 */
class MembershipExitRequest extends Model
{
    /** @use HasFactory<MembershipExitRequestFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'membership_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipExitRequestStatus::class,
            'decided_at' => 'datetime',
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
     * The requesting membership — `withTrashed()` because an approved
     * request soft-deletes the very membership it points at, and the
     * chair's decided-list still has to render the member's name.
     *
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'membership_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePending($query): void
    {
        $query->where('status', MembershipExitRequestStatus::Pending);
    }
}
