<?php

namespace App\Models;

use App\Enums\FinancialReportStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'require_transaction_approval',
        'public_transparency_enabled',
    ];

    protected function casts(): array
    {
        return [
            'require_transaction_approval' => 'boolean',
            'public_transparency_enabled' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @return HasMany<OrganizationMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * @return HasMany<FinancialAccount, $this>
     */
    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    /**
     * @return HasMany<FinancialCategory, $this>
     */
    public function financialCategories(): HasMany
    {
        return $this->hasMany(FinancialCategory::class);
    }

    /**
     * @return HasMany<FinancialTransaction, $this>
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * @return HasMany<MemberDue, $this>
     */
    public function memberDues(): HasMany
    {
        return $this->hasMany(MemberDue::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @return HasMany<FinancialReport, $this>
     */
    public function financialReports(): HasMany
    {
        return $this->hasMany(FinancialReport::class);
    }

    /**
     * The "Transparansi" figures — always derived from APPROVED
     * transactions, never a stored/cached value. Shared by the web
     * dashboard and its API/mobile counterpart so both read the exact
     * same numbers the exact same way.
     *
     * @return array<string, mixed>
     */
    public function transparencySummary(): array
    {
        $approved = $this->financialTransactions()->where('status', TransactionStatus::Approved);

        $totalIncome = (int) (clone $approved)->where('transaction_type', TransactionType::Income)->sum('amount');
        $totalExpense = (int) (clone $approved)->where('transaction_type', TransactionType::Expense)->sum('amount');

        $monthIncome = (int) (clone $approved)
            ->where('transaction_type', TransactionType::Income)
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $monthExpense = (int) (clone $approved)
            ->where('transaction_type', TransactionType::Expense)
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $recentTransactions = (clone $approved)
            ->with(['category:id,name'])
            ->orderByDesc('transaction_date')
            ->limit(5)
            ->get(['id', 'amount', 'transaction_type', 'description', 'transaction_date', 'category_id'])
            ->map(fn (FinancialTransaction $t) => [
                'amount' => $t->amount,
                'transactionType' => $t->transaction_type->value,
                'description' => $t->description ?? $t->category?->name,
                'transactionDate' => $t->transaction_date->toDateString(),
            ]);

        $publishedReports = $this->financialReports()
            ->where('status', FinancialReportStatus::Published)
            ->orderByDesc('period_start')
            ->limit(5)
            ->get(['id', 'title', 'period_start', 'closing_balance'])
            ->map(fn (FinancialReport $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'periodStart' => $r->period_start->toDateString(),
                'closingBalance' => $r->closing_balance,
            ]);

        return [
            'balance' => $totalIncome - $totalExpense,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'monthSurplus' => $monthIncome - $monthExpense,
            'recentTransactions' => $recentTransactions,
            'publishedReports' => $publishedReports,
        ];
    }
}
