<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialTransaction\StoreFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class FinancialTransactionController extends Controller
{
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [FinancialTransaction::class, $organization]);

        $query = $organization->financialTransactions()
            ->with(['financialAccount:id,name', 'category:id,name', 'event:id,title'])
            ->orderByDesc('transaction_date');

        if (! Auth::user()->isTreasurerOf($organization)) {
            $query->approved();
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->string('event_id')->value());
        }

        return FinancialTransactionResource::collection($query->get());
    }

    public function store(StoreFinancialTransactionRequest $request, Organization $organization): JsonResponse
    {
        $transaction = $organization->financialTransactions()->create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        $transaction->refresh()->load(['financialAccount:id,name', 'category:id,name', 'event:id,title']);

        return (new FinancialTransactionResource($transaction))->response()->setStatusCode(201);
    }
}
