<?php

namespace App\Http\Controllers;

use App\Actions\Finance\ApproveFinancialReportAction;
use App\Actions\Finance\GenerateFinancialReportAction;
use App\Actions\Finance\PublishFinancialReportAction;
use App\Actions\Finance\RequestReportRevisionAction;
use App\Actions\Finance\RevisePublishedReportAction;
use App\Actions\Finance\SubmitReportForReviewAction;
use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportType;
use App\Enums\FinancialReportVisibility;
use App\Http\Requests\FinancialReport\RequestReportRevisionRequest;
use App\Http\Requests\FinancialReport\StoreFinancialReportRequest;
use App\Http\Requests\FinancialReport\SubmitReportForReviewRequest;
use App\Models\FinancialReport;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialReport::class, $organization]);

        $user = Auth::user();
        $isTreasurer = $user->isTreasurerOf($organization);

        $query = $organization->financialReports()->orderByDesc('period_start');

        if (! $isTreasurer) {
            $query->where('status', FinancialReportStatus::Published)
                ->whereIn('visibility', [FinancialReportVisibility::Members, FinancialReportVisibility::Public]);
        }

        $reports = $query->get()->map(fn (FinancialReport $report) => [
            'id' => $report->id,
            'title' => $report->title,
            'reportType' => $report->report_type->label(),
            'periodStart' => $report->period_start->toDateString(),
            'periodEnd' => $report->period_end->toDateString(),
            'status' => $report->status->value,
            'statusLabel' => $report->status->label(),
            'visibilityLabel' => $report->visibility->label(),
            'closingBalance' => $report->closing_balance,
        ]);

        return Inertia::render('finance/reports/index', [
            'reports' => $reports,
            'canCreate' => Auth::user()->can('create', [FinancialReport::class, $organization]),
        ]);
    }

    public function create(Organization $organization): Response
    {
        $this->authorize('create', [FinancialReport::class, $organization]);

        return Inertia::render('finance/reports/create', [
            'reportTypes' => array_map(
                fn (FinancialReportType $t) => ['value' => $t->value, 'label' => $t->label()],
                FinancialReportType::cases(),
            ),
            'visibilities' => array_map(
                fn (FinancialReportVisibility $v) => ['value' => $v->value, 'label' => $v->label()],
                FinancialReportVisibility::cases(),
            ),
        ]);
    }

    public function store(
        StoreFinancialReportRequest $request,
        Organization $organization,
        GenerateFinancialReportAction $generateReport,
    ): RedirectResponse {
        $report = $generateReport->handle($organization, Auth::user(), $request->validated());

        return to_route('reports.show', $report);
    }

    public function destroy(FinancialReport $report): RedirectResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return to_route('finance.reports.index');
    }

    public function submit(SubmitReportForReviewRequest $request, FinancialReport $report, SubmitReportForReviewAction $submitReport): RedirectResponse
    {
        $submitReport->handle($report, Auth::user(), $request->string('note')->value() ?: null);

        return to_route('reports.show', $report);
    }

    public function approve(FinancialReport $report, ApproveFinancialReportAction $approveReport): RedirectResponse
    {
        $this->authorize('approve', $report);

        $approveReport->handle($report, Auth::user());

        return to_route('reports.show', $report);
    }

    public function requestRevision(RequestReportRevisionRequest $request, FinancialReport $report, RequestReportRevisionAction $requestRevision): RedirectResponse
    {
        $requestRevision->handle($report, Auth::user(), $request->string('reason')->value());

        return to_route('reports.show', $report);
    }

    public function publish(FinancialReport $report, PublishFinancialReportAction $publishReport): RedirectResponse
    {
        $this->authorize('publish', $report);

        $publishReport->handle($report, Auth::user());

        return to_route('reports.show', $report);
    }

    public function archive(FinancialReport $report): RedirectResponse
    {
        $this->authorize('archive', $report);

        $report->update(['status' => FinancialReportStatus::Archived]);

        return to_route('reports.show', $report);
    }

    public function revise(FinancialReport $report, RevisePublishedReportAction $reviseReport): RedirectResponse
    {
        $this->authorize('revise', $report);

        $reviseReport->handle($report, Auth::user());

        return to_route('reports.show', $report);
    }
}
