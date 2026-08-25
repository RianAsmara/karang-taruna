<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Finance\PublishFinancialReportAction;
use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialReportResource;
use App\Models\FinancialReport;
use App\Models\Organization;
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
