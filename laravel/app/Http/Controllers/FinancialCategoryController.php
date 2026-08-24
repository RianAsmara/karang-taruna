<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialCategory\StoreFinancialCategoryRequest;
use App\Http\Requests\FinancialCategory\UpdateFinancialCategoryRequest;
use App\Models\FinancialCategory;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FinancialCategoryController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [FinancialCategory::class, $organization]);

        $categories = $organization->financialCategories()
            ->orderBy('transaction_type')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancialCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'transactionType' => $category->transaction_type->value,
                'transactionTypeLabel' => $category->transaction_type->label(),
            ]);

        return Inertia::render('finance/categories/index', [
            'categories' => $categories,
            'canManage' => Auth::user()->can('create', [FinancialCategory::class, $organization]),
        ]);
    }

    public function store(StoreFinancialCategoryRequest $request, Organization $organization): RedirectResponse
    {
        $organization->financialCategories()->create($request->validated());

        return back();
    }

    public function update(UpdateFinancialCategoryRequest $request, FinancialCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return back();
    }

    public function destroy(FinancialCategory $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return back();
    }
}
