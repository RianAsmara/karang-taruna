<?php

namespace App\Models;

use App\Enums\InventoryCondition;
use App\Enums\InventoryLoanStatus;
use Database\Factories\InventoryLoanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property InventoryLoanStatus $status
 * @property InventoryCondition|null $returned_condition
 * @property Carbon $borrowed_at
 * @property Carbon $due_date
 * @property Carbon|null $returned_at
 */
class InventoryLoan extends Model
{
    /** @use HasFactory<InventoryLoanFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'inventory_item_id',
        'borrower_membership_id',
        'event_id',
        'quantity',
        'status',
        'purpose',
        'borrowed_at',
        'due_date',
        'returned_at',
        'returned_quantity',
        'returned_condition',
        'return_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => InventoryLoanStatus::class,
            'borrowed_at' => 'date',
            'due_date' => 'date',
            'returned_at' => 'date',
            'returned_quantity' => 'integer',
            'returned_condition' => InventoryCondition::class,
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
     * @return BelongsTo<InventoryItem, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * @return BelongsTo<OrganizationMembership, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'borrower_membership_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === InventoryLoanStatus::Borrowed && $this->due_date->isPast();
    }
}
