<?php

namespace App\Http\Controllers;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\FinancialReport;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class PublicTransparencyController extends Controller
{
    /**
     * §30 — an organization may opt in to a fully public transparency
     * page. Only the current balance and PUBLIC+PUBLISHED reports are
     * shown; never member data, donor details, or internal audit notes.
     */
    public function show(Organization $organization): Response
    {
        abort_unless($organization->public_transparency_enabled, 404);

        $approved = $organization->financialTransactions()->where('status', TransactionStatus::Approved);

        $totalIncome = (int) (clone $approved)->where('transaction_type', TransactionType::Income)->sum('amount');
        $totalExpense = (int) (clone $approved)->where('transaction_type', TransactionType::Expense)->sum('amount');

        $publicReports = $organization->financialReports()
            ->where('status', FinancialReportStatus::Published)
            ->where('visibility', FinancialReportVisibility::Public)
            ->orderByDesc('period_start')
            ->limit(10)
            ->get(['id', 'title', 'period_start', 'period_end', 'closing_balance'])
            ->map(fn (FinancialReport $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'periodStart' => $r->period_start->toDateString(),
                'periodEnd' => $r->period_end->toDateString(),
                'closingBalance' => $r->closing_balance,
            ]);

        return Inertia::render('organizations/transparency', [
            'organization' => [
                'name' => $organization->name,
            ],
            'balance' => $totalIncome - $totalExpense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'publicReports' => $publicReports,
        ]);
    }
}
