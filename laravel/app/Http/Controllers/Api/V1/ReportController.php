<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Finance\RevertDriftedReportAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialReportResource;
use App\Models\FinancialReport;
use App\Support\ReportQrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * The API counterpart of the web's canonical /reports/{report} page —
     * same mixed-audience rule (PUBLIC + PUBLISHED is viewable without a
     * token), same Policy, different response shape (JSON, not Inertia).
     */
    public function show(FinancialReport $report, RevertDriftedReportAction $revertDrifted): JsonResource
    {
        $this->authorizeView($report);

        $report = $revertDrifted->handle($report);

        $report->loadCount('revisions');
        $report->load(['organization:id,name', 'publisher:id,name', 'submitter:id,name', 'approver:id,name']);

        // This route sits outside the `auth:sanctum` middleware group
        // (deliberately, so a guest can reach a PUBLIC report) — so
        // `Auth::user()` (the default guard) is never populated from a
        // Bearer token here. The `sanctum` guard resolves it directly
        // from the request regardless of route middleware.
        $user = Auth::guard('sanctum')->user();

        return (new FinancialReportResource($report))->additional([
            'meta' => [
                'canSubmit' => $user?->can('submit', $report) ?? false,
                'canApprove' => $user?->can('approve', $report) ?? false,
                'canRequestRevision' => $user?->can('requestRevision', $report) ?? false,
                'canPublish' => $user?->can('publish', $report) ?? false,
                'canArchive' => $user?->can('archive', $report) ?? false,
                'canRevise' => $user?->can('revise', $report) ?? false,
                'canDelete' => $user?->can('delete', $report) ?? false,
                'shareUrl' => route('reports.show', $report),
                'qrUrl' => route('api.v1.finance.reports.qr', $report),
                'pdfUrl' => route('reports.pdf', $report),
            ],
            'categoryBreakdown' => $report->categoryBreakdown(),
        ]);
    }

    /**
     * Read-only: hands a mobile client the data it needs to build its own
     * share action (a wa.me link, the canonical URL). Logging a share
     * event stays a web-only, POST-based concern (ReportController::share)
     * — a GET endpoint should not have that side effect.
     */
    public function share(FinancialReport $report): JsonResponse
    {
        $this->authorizeView($report);

        return response()->json([
            'shareUrl' => route('reports.show', $report),
            'qrUrl' => route('api.v1.finance.reports.qr', $report),
            'pdfUrl' => route('reports.pdf', $report),
        ]);
    }

    public function qr(FinancialReport $report): HttpResponse
    {
        $this->authorizeView($report);

        return ReportQrCode::png(route('reports.show', $report));
    }

    private function authorizeView(FinancialReport $report): void
    {
        // This route sits outside the `auth:sanctum` middleware group
        // (deliberately, so a guest can reach a PUBLIC report) — so
        // `Auth::user()` (the default guard) is never populated from a
        // Bearer token here. The `sanctum` guard resolves it directly
        // from the request regardless of route middleware.
        $user = Auth::guard('sanctum')->user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }
    }
}
