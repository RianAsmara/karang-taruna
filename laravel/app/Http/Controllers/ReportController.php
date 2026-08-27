<?php

namespace App\Http\Controllers;

use App\Actions\Finance\RevertDriftedReportAction;
use App\Enums\ReportShareChannel;
use App\Models\FinancialReport;
use App\Support\ReportQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * The canonical, shareable report page — used both by org members
     * (via the reports list) and by public visitors following a shared
     * link or QR code. Not gated by the 'auth' middleware: a report may
     * be legitimately viewable while logged out (PUBLIC + PUBLISHED).
     */
    public function show(FinancialReport $report, RevertDriftedReportAction $revertDrifted): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }

        $report = $revertDrifted->handle($report);

        $report->load(['organization:id,name', 'publisher:id,name']);

        return Inertia::render('reports/show', [
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'reportTypeLabel' => $report->report_type->label(),
                'periodStart' => $report->period_start->toDateString(),
                'periodEnd' => $report->period_end->toDateString(),
                'status' => $report->status->value,
                'statusLabel' => $report->status->label(),
                'visibility' => $report->visibility->value,
                'visibilityLabel' => $report->visibility->label(),
                'openingBalance' => $report->opening_balance,
                'totalIncome' => $report->total_income,
                'totalExpense' => $report->total_expense,
                'closingBalance' => $report->closing_balance,
                'publishedAt' => $report->published_at?->toIso8601String(),
                'publisherName' => $report->publisher?->name,
                'revisionCount' => $report->revisions()->count(),
                'organizationName' => $report->organization->name,
            ],
            'canPublish' => $user?->can('publish', $report) ?? false,
            'canArchive' => $user?->can('archive', $report) ?? false,
            'canRevise' => $user?->can('revise', $report) ?? false,
            'canDelete' => $user?->can('delete', $report) ?? false,
            'shareUrl' => route('reports.show', $report),
        ]);
    }

    public function share(Request $request, FinancialReport $report): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }

        $data = $request->validate([
            'channel' => ['required', Rule::enum(ReportShareChannel::class)],
        ]);

        $report->shareLogs()->create([
            'channel' => $data['channel'],
            'shared_by' => $user?->id,
            'shared_at' => now(),
        ]);

        return back();
    }

    public function qr(FinancialReport $report): HttpResponse
    {
        $user = Auth::user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }

        return ReportQrCode::png(route('reports.show', $report));
    }

    /**
     * PDF is a distribution artifact of the published report, not a
     * source of truth (master prompt §75) — generated on demand from
     * the report's own stored figures, never a stand-in source.
     */
    public function pdf(FinancialReport $report): HttpResponse
    {
        $user = Auth::user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }

        $report->load(['organization:id,name', 'publisher:id,name']);

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'breakdown' => $report->categoryBreakdown(),
            'revisionCount' => $report->revisions()->count(),
        ]);

        return $pdf->download("laporan-{$report->id}.pdf");
    }
}
