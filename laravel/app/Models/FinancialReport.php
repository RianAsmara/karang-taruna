<?php

namespace App\Models;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Observers\FinancialReportObserver;
use Carbon\CarbonInterface;
use Database\Factories\FinancialReportFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property FinancialReportStatus $status
 * @property FinancialReportVisibility $visibility
 * @property FinancialReportType $report_type
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property Carbon|null $published_at
 */
#[ObservedBy(FinancialReportObserver::class)]
class FinancialReport extends Model
{
    /** @use HasFactory<FinancialReportFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'title',
        'report_type',
        'period_start',
        'period_end',
        'status',
        'visibility',
        'opening_balance',
        'total_income',
        'total_expense',
        'closing_balance',
        'published_at',
        'published_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'report_type' => FinancialReportType::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => FinancialReportStatus::class,
            'visibility' => FinancialReportVisibility::class,
            'opening_balance' => 'integer',
            'total_income' => 'integer',
            'total_expense' => 'integer',
            'closing_balance' => 'integer',
            'published_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * @return HasMany<FinancialReportRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(FinancialReportRevision::class);
    }

    /**
     * @return HasMany<ReportShareLog, $this>
     */
    public function shareLogs(): HasMany
    {
        return $this->hasMany(ReportShareLog::class);
    }

    /**
     * Whether this report may be viewed without authentication — the
     * mixed-audience check shared by the web and API report controllers.
     */
    public function isPubliclyViewable(): bool
    {
        return $this->status === FinancialReportStatus::Published
            && $this->visibility === FinancialReportVisibility::Public;
    }

    /**
     * Compute a period's figures straight from approved transactions —
     * administrators never type a closing balance by hand. TRANSFER
     * transactions are excluded: they move money between the
     * organization's own accounts and net to zero at the organization
     * level, which is the scope of a report (not a single account).
     *
     * @return array{opening_balance: int, total_income: int, total_expense: int, closing_balance: int}
     */
    public static function calculateFigures(Organization $organization, CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        $approved = $organization->financialTransactions()->where('status', TransactionStatus::Approved);

        $openingIncome = (clone $approved)
            ->where('transaction_type', TransactionType::Income)
            ->where('transaction_date', '<', $periodStart)
            ->sum('amount');

        $openingExpense = (clone $approved)
            ->where('transaction_type', TransactionType::Expense)
            ->where('transaction_date', '<', $periodStart)
            ->sum('amount');

        $totalIncome = (int) (clone $approved)
            ->where('transaction_type', TransactionType::Income)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        $totalExpense = (int) (clone $approved)
            ->where('transaction_type', TransactionType::Expense)
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        $openingBalance = (int) $openingIncome - (int) $openingExpense;

        return [
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'closing_balance' => $openingBalance + $totalIncome - $totalExpense,
        ];
    }

    /**
     * Income/expense grouped by category name for this report's own
     * period — the bar-chart breakdown on Report Detail. Same
     * APPROVED-only, TRANSFER-excluded scope as calculateFigures(),
     * just grouped instead of summed. A transaction can't be missing a
     * category (StoreFinancialTransactionRequest requires one for
     * INCOME/EXPENSE), but 'Lainnya' is a safety-net label, not an
     * expected case.
     *
     * @return array{income: list<array{label: string, amount: int}>, expense: list<array{label: string, amount: int}>}
     */
    public function categoryBreakdown(): array
    {
        $byType = FinancialTransaction::query()
            ->where('organization_id', $this->organization_id)
            ->where('status', TransactionStatus::Approved)
            ->whereIn('transaction_type', [TransactionType::Income, TransactionType::Expense])
            ->whereBetween('transaction_date', [$this->period_start, $this->period_end])
            ->with('category:id,name')
            ->get()
            ->groupBy(fn (FinancialTransaction $t) => $t->transaction_type->value);

        $summarize = fn ($transactions) => $transactions
            ->groupBy(fn (FinancialTransaction $t) => $t->category?->name ?? 'Lainnya')
            ->map(fn ($group, $label) => ['label' => $label, 'amount' => (int) $group->sum('amount')])
            ->sortByDesc('amount')
            ->values()
            ->all();

        return [
            'income' => $summarize($byType->get(TransactionType::Income->value, collect())),
            'expense' => $summarize($byType->get(TransactionType::Expense->value, collect())),
        ];
    }
}
