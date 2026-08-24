<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialAccount\StoreFinancialAccountRequest;
use App\Http\Requests\FinancialAccount\UpdateFinancialAccountRequest;
use App\Models\FinancialAccount;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialAccount::class, $organization]);

        $accounts = $organization->financialAccounts()
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => $account->balance(),
            ]);

        return Inertia::render('finance/accounts/index', [
            'accounts' => $accounts,
            'totalBalance' => $accounts->sum('balance'),
            'canManage' => Auth::user()->can('create', [FinancialAccount::class, $organization]),
        ]);
    }

    public function store(StoreFinancialAccountRequest $request, Organization $organization): RedirectResponse
    {
        $organization->financialAccounts()->create($request->validated());

        return back();
    }

    public function update(UpdateFinancialAccountRequest $request, FinancialAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return back();
    }

    public function destroy(FinancialAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return back();
    }
}
