<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialCategoryResource;
use App\Models\FinancialCategory;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinancialCategoryController extends Controller
{
    /**
     * Read-only via API, same as accounts — no create/update/delete yet.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [FinancialCategory::class, $organization]);

        $categories = $organization->financialCategories()
            ->orderBy('transaction_type')
            ->orderBy('name')
            ->get();

        return FinancialCategoryResource::collection($categories);
    }
}
