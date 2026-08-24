<?php

namespace App\Http\Controllers;

use App\Actions\Finance\GenerateMonthlyDuesAction;
use App\Actions\Finance\RecordDuePaymentAction;
use App\Http\Requests\MemberDue\GenerateMonthlyDuesRequest;
use App\Http\Requests\MemberDue\RecordPaymentRequest;
use App\Http\Requests\MemberDue\StoreMemberDueRequest;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MemberDue;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MemberDueController extends Controller
{
    public function index(Organization $organization): Response
    {
        $this->authorize('viewAny', [MemberDue::class, $organization]);

        $user = Auth::user();
        $isTreasurer = $user->isTreasurerOf($organization);
        $membership = $user->membershipIn($organization);

        $query = $organization->memberDues()
            ->with('membership.user:id,name')
            ->orderByDesc('period');

        if (! $isTreasurer && $membership) {
            $query->where('membership_id', $membership->id);
        }

        $dues = $query->get()->map(fn (MemberDue $due) => [
            'id' => $due->id,
            'memberName' => $due->membership->user->name,
            'period' => $due->period->toDateString(),
            'type' => $due->type->value,
            'typeLabel' => $due->type->label(),
            'amountDue' => $due->amount_due,
            'amountPaid' => $due->amountPaid(),
            'amountOutstanding' => $due->amountOutstanding(),
            'isPaid' => $due->isPaid(),
        ]);

        return Inertia::render('finance/dues/index', [
            'dues' => $dues,
            'isTreasurer' => $isTreasurer,
            'members' => $isTreasurer
                ? $organization->memberships()->with('user:id,name')->get()->map(fn ($m) => ['id' => $m->id, 'name' => $m->user->name])
                : [],
            'accounts' => $isTreasurer ? $organization->financialAccounts()->orderBy('name')->get(['id', 'name']) : [],
            'incomeCategories' => $isTreasurer
                ? $organization->financialCategories()->where('transaction_type', 'INCOME')->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    public function store(StoreMemberDueRequest $request, Organization $organization): RedirectResponse
    {
        $organization->memberDues()->create($request->validated());

        return back();
    }

    public function generateMonthly(
        GenerateMonthlyDuesRequest $request,
        Organization $organization,
        GenerateMonthlyDuesAction $generateMonthlyDues,
    ): RedirectResponse {
        $generateMonthlyDues->handle(
            $organization,
            Carbon::parse($request->string('period')->value()),
            (int) $request->integer('amount_due'),
        );

        return back();
    }

    public function destroy(MemberDue $due): RedirectResponse
    {
        $this->authorize('delete', $due);

        $due->delete();

        return back();
    }

    public function recordPayment(RecordPaymentRequest $request, MemberDue $due, RecordDuePaymentAction $recordPayment): RedirectResponse
    {
        $recordPayment->handle(
            $due,
            Auth::user(),
            (int) $request->integer('amount'),
            Carbon::parse($request->string('paid_at')->value()),
            FinancialAccount::findOrFail($request->string('financial_account_id')->value()),
            FinancialCategory::findOrFail($request->string('category_id')->value()),
        );

        return back();
    }
}
