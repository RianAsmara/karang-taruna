<?php

namespace App\Http\Controllers\Api\V1;

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
    public function show(FinancialReport $report): JsonResource
    {
        $this->authorizeView($report);

        $report->loadCount('revisions');
        $report->load(['organization:id,name', 'publisher:id,name']);

        $user = Auth::user();

        return (new FinancialReportResource($report))->additional([
            'meta' => [
                'canPublish' => $user?->can('publish', $report) ?? false,
                'canArchive' => $user?->can('archive', $report) ?? false,
                'canRevise' => $user?->can('revise', $report) ?? false,
                'canDelete' => $user?->can('delete', $report) ?? false,
                'shareUrl' => route('reports.show', $report),
                'qrUrl' => route('api.v1.finance.reports.qr', $report),
            ],
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
        ]);
    }

    public function qr(FinancialReport $report): HttpResponse
    {
        $this->authorizeView($report);

        return ReportQrCode::png(route('reports.show', $report));
    }

    private function authorizeView(FinancialReport $report): void
    {
        $user = Auth::user();

        if ($user) {
            abort_unless($user->can('view', $report), 403);
        } else {
            abort_unless($report->isPubliclyViewable(), 404);
        }
    }
}
