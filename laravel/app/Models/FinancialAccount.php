<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\FinancialAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    /** @use HasFactory<FinancialAccountFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'name',
    ];

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
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Current balance: approved income in, approved expense out, approved
     * transfers moved between accounts. Always derived, never stored.
     */
    public function balance(): int
    {
        $incoming = $this->transactions()
            ->approved()
            ->where('transaction_type', TransactionType::Income)
            ->sum('amount');

        $outgoing = $this->transactions()
            ->approved()
            ->where('transaction_type', TransactionType::Expense)
            ->sum('amount');

        $transfersOut = $this->transactions()
            ->approved()
            ->where('transaction_type', TransactionType::Transfer)
            ->sum('amount');

        $transfersIn = FinancialTransaction::query()
            ->approved()
            ->where('related_account_id', $this->id)
            ->where('transaction_type', TransactionType::Transfer)
            ->sum('amount');

        return (int) ($incoming - $outgoing - $transfersOut + $transfersIn);
    }
}
