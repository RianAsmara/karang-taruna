<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class TransparencyController extends Controller
{
    /**
     * Mobile counterpart of the web "Transparansi" dashboard — same
     * figures, computed the same way, via Organization::transparencySummary().
     */
    public function index(Organization $organization): JsonResponse
    {
        $this->authorize('viewAny', [FinancialTransaction::class, $organization]);

        return response()->json($organization->transparencySummary());
    }
}
