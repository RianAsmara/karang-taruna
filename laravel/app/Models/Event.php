<?php

namespace App\Models;

use App\Enums\EventCategory;
use App\Enums\EventLifecycleStage;
use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property EventStatus $status
 * @property EventLifecycleStage $lifecycle_stage
 * @property EventCategory|null $category
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'category',
        'location',
        'start_at',
        'end_at',
        'pic_membership_id',
        'sponsor_id',
        'budget_financial_transaction_id',
        'status',
        'lifecycle_stage',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => EventStatus::class,
            'lifecycle_stage' => EventLifecycleStage::class,
            'category' => EventCategory::class,
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
    public function pic(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'pic_membership_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<EventCommittee, $this>
     */
    public function committees(): HasMany
    {
        return $this->hasMany(EventCommittee::class);
    }

    /**
     * @return HasMany<EventTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(EventTask::class);
    }

    /**
     * @return HasMany<EventParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * The single DRAFT expense transaction created from the creation
     * wizard's "anggaran" field — kept up to date rather than duplicated
     * when the planned amount changes (see UpdateEventAction).
     *
     * @return BelongsTo<FinancialTransaction, $this>
     */
    public function budgetTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'budget_financial_transaction_id');
    }

    /**
     * Organization OWNER/ADMIN, or this event's PIC, may manage it —
     * the event itself and everything under it (committee, tasks).
     */
    public function isManagedBy(User $user): bool
    {
        if ($user->isChairOf($this->organization)) {
            return true;
        }

        $membership = $user->membershipIn($this->organization);

        return $membership !== null && $this->pic_membership_id === $membership->id;
    }
}
