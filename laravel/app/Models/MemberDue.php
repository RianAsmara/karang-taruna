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
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount_due' => 'integer',
            'type' => MemberDueType::class,
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
}
