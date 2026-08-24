<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Observers\FinancialTransactionObserver;
use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property TransactionType $transaction_type
 * @property TransactionStatus $status
 * @property Carbon $transaction_date
 * @property Carbon|null $reviewed_at
 */
#[ObservedBy(FinancialTransactionObserver::class)]
class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'financial_account_id',
        'related_account_id',
        'category_id',
        'event_id',
        'amount',
        'transaction_type',
        'status',
        'description',
        'transaction_date',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'transaction_type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'transaction_date' => 'date',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function relatedAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'related_account_id');
    }

    /**
     * @return BelongsTo<FinancialCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  Builder<FinancialTransaction>  $query
     * @return Builder<FinancialTransaction>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::Approved);
    }
}
