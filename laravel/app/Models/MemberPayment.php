<?php

namespace App\Models;

use Database\Factories\MemberPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $paid_at
 */
class MemberPayment extends Model
{
    /** @use HasFactory<MemberPaymentFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'member_due_id',
        'financial_transaction_id',
        'amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<MemberDue, $this>
     */
    public function memberDue(): BelongsTo
    {
        return $this->belongsTo(MemberDue::class);
    }

    /**
     * @return BelongsTo<FinancialTransaction, $this>
     */
    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
