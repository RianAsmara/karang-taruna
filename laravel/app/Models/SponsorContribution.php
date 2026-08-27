<?php

namespace App\Models;

use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use Database\Factories\SponsorContributionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property SponsorType $type
 * @property SponsorContributionStatus $status
 */
class SponsorContribution extends Model
{
    /** @use HasFactory<SponsorContributionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'sponsor_id',
        'event_id',
        'type',
        'status',
        'amount',
        'description',
        'financial_transaction_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => SponsorType::class,
            'status' => SponsorContributionStatus::class,
            'amount' => 'integer',
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
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<FinancialTransaction, $this>
     */
    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
