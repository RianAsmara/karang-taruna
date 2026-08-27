<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Enums\InventoryLoanStatus;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property InventoryCategory $category
 * @property InventoryCondition $condition
 * @property Carbon|null $last_checked_at
 */
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'name',
        'category',
        'quantity',
        'condition',
        'location',
        'notes',
        'last_checked_at',
        'responsible_membership_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => InventoryCategory::class,
            'quantity' => 'integer',
            'condition' => InventoryCondition::class,
            'last_checked_at' => 'date',
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
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'responsible_membership_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InventoryLoan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(InventoryLoan::class);
    }

    /**
     * Quantity currently out on active loans.
     */
    public function borrowedQuantity(): int
    {
        return (int) $this->loans()->where('status', InventoryLoanStatus::Borrowed)->sum('quantity');
    }

    public function availableQuantity(): int
    {
        if ($this->condition === InventoryCondition::Rusak) {
            return 0;
        }

        return max(0, $this->quantity - $this->borrowedQuantity());
    }

    public function isAvailable(): bool
    {
        return $this->availableQuantity() > 0;
    }
}
