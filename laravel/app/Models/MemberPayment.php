<?php

namespace App\Models;

use App\Enums\MemberPaymentMethod;
use Database\Factories\MemberPaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $paid_at
 * @property MemberPaymentMethod $method
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
        'method',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
            'method' => MemberPaymentMethod::class,
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
