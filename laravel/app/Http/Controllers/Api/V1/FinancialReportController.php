<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Finance\ApproveFinancialReportAction;
use App\Actions\Finance\GenerateFinancialReportAction;
use App\Actions\Finance\PublishFinancialReportAction;
use App\Actions\Finance\RequestReportRevisionAction;
use App\Actions\Finance\SaveReportNoteAction;
use App\Actions\Finance\SubmitReportForReviewAction;
use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialReport\RequestReportRevisionRequest;
use App\Http\Requests\FinancialReport\SaveReportNoteRequest;
use App\Http\Requests\FinancialReport\StoreFinancialReportRequest;
use App\Http\Requests\FinancialReport\SubmitReportForReviewRequest;
use App\Http\Resources\FinancialReportResource;
use App\Models\FinancialReport;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class FinancialReportController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [FinancialReport::class, $organization]);

        $query = $organization->financialReports()->orderByDesc('period_start');

        if (! Auth::user()->isTreasurerOf($organization)) {
            $query->where('status', FinancialReportStatus::Published)
                ->whereIn('visibility', [FinancialReportVisibility::Members, FinancialReportVisibility::Public]);
        }

        return FinancialReportResource::collection($query->get());
    }

    public function store(StoreFinancialReportRequest $request, Organization $organization, GenerateFinancialReportAction $generateReport): JsonResponse
    {
        $report = $generateReport->handle($organization, Auth::user(), $request->validated());

        return (new FinancialReportResource($report))->response()->setStatusCode(201);
    }

    public function updateNote(SaveReportNoteRequest $request, FinancialReport $report, SaveReportNoteAction $saveNote): JsonResource
    {
        $saveNote->handle($report, $request->string('note')->value() ?: null);

        return new FinancialReportResource($report);
    }

    public function submit(SubmitReportForReviewRequest $request, FinancialReport $report, SubmitReportForReviewAction $submitReport): JsonResource
    {
        $submitReport->handle($report, Auth::user(), $request->string('note')->value() ?: null);

        return new FinancialReportResource($report);
    }

    public function approve(FinancialReport $report, ApproveFinancialReportAction $approveReport): JsonResource
    {
        $this->authorize('approve', $report);

        $approveReport->handle($report, Auth::user());

        return new FinancialReportResource($report);
    }

    public function requestRevision(RequestReportRevisionRequest $request, FinancialReport $report, RequestReportRevisionAction $requestRevision): JsonResource
    {
        $requestRevision->handle($report, Auth::user(), $request->string('reason')->value());

        return new FinancialReportResource($report);
    }

    public function publish(FinancialReport $report, PublishFinancialReportAction $publishReport): JsonResource
    {
        $this->authorize('publish', $report);

        $publishReport->handle($report, Auth::user());

        return new FinancialReportResource($report);
    }

    public function archive(FinancialReport $report): JsonResource
    {
        $this->authorize('archive', $report);

        $report->update(['status' => FinancialReportStatus::Archived]);

        return new FinancialReportResource($report);
    }
}
