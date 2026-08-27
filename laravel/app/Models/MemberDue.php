<?php

namespace App\Models;

use App\Enums\MemberDueType;
use Database\Factories\MemberDueFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property MemberDueType $type
 * @property Carbon $period
 * @property Carbon|null $notified_at
 * @property bool $is_exempt
 */
class MemberDue extends Model
{
    /** @use HasFactory<MemberDueFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'membership_id',
        'period',
        'amount_due',
        'type',
        'notified_at',
        'is_exempt',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount_due' => 'integer',
            'type' => MemberDueType::class,
            'notified_at' => 'datetime',
            'is_exempt' => 'boolean',
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

    /**
     * @return HasMany<MemberPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(MemberPayment::class);
    }

    /**
     * The payment that most recently moved this due forward — screen
     * 17's "Riwayat" shows one row per period ("amount, date recorded,
     * and who recorded it"), not one row per individual installment, so
     * a due with several partial payments is summarized by its latest.
     */
    public function latestPayment(): ?MemberPayment
    {
        return $this->payments()->latest('paid_at')->first();
    }

    public function amountPaid(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    public function amountOutstanding(): int
    {
        return max(0, $this->amount_due - $this->amountPaid());
    }

    public function isPaid(): bool
    {
        return $this->amountOutstanding() === 0;
    }

    /**
     * The member said "sudah bayar" but the treasurer hasn't recorded a
     * matching payment yet — distinct from a partial payment, which
     * means the treasurer already recorded *something*.
     */
    public function isAwaitingConfirmation(): bool
    {
        return $this->notified_at !== null && $this->amountPaid() === 0;
    }

    public function isPartiallyPaid(): bool
    {
        return ! $this->isPaid() && $this->amountPaid() > 0;
    }
}
