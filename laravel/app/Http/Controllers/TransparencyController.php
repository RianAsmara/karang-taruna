<?php

namespace App\Http\Controllers;

use App\Enums\FinancialReportStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialReport;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransparencyController extends Controller
{
    /**
     * "Transparansi" — the answer to "kas sekarang berapa?" without
     * having to ask. Every member can see this; it only ever reflects
     * APPROVED transactions.
     */
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialTransaction::class, $organization]);

        $approved = $organization->financialTransactions()->where('status', TransactionStatus::Approved);

        $totalIncome = (int) (clone $approved)->where('transaction_type', TransactionType::Income)->sum('amount');
        $totalExpense = (int) (clone $approved)->where('transaction_type', TransactionType::Expense)->sum('amount');
        $balance = $totalIncome - $totalExpense;

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

        $publishedReports = $organization->financialReports()
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

        return Inertia::render('transparency/index', [
            'balance' => $balance,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'monthSurplus' => $monthIncome - $monthExpense,
            'recentTransactions' => $recentTransactions,
            'publishedReports' => $publishedReports,
            'canManageTransparency' => Auth::user()->isOrganizerOf($organization),
            'publicTransparencyEnabled' => $organization->public_transparency_enabled,
            'publicUrl' => route('organizations.transparency', $organization->slug),
        ]);
    }

    public function toggle(Organization $organization): RedirectResponse
    {
        $this->authorize('manageOrganization', $organization);

        $organization->update([
            'public_transparency_enabled' => ! $organization->public_transparency_enabled,
        ]);

        return back();
    }
}
