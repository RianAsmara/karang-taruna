<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialAccountResource;
use App\Models\FinancialAccount;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinancialAccountController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [FinancialAccount::class, $organization]);

        $accounts = $organization->financialAccounts()->orderBy('name')->get();

        return FinancialAccountResource::collection($accounts);
    }
}
