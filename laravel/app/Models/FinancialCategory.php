<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\FinancialCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TransactionType $transaction_type
 */
class FinancialCategory extends Model
{
    /** @use HasFactory<FinancialCategoryFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'name',
        'transaction_type',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
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
     * @return HasMany<FinancialTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'category_id');
    }
}
