<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Finance\GenerateMonthlyDuesAction;
use App\Actions\Finance\NotifyDuePaymentAction;
use App\Actions\Finance\RecordDuePaymentAction;
use App\Enums\MemberPaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\MemberDue\GenerateMonthlyDuesRequest;
use App\Http\Requests\MemberDue\RecordPaymentRequest;
use App\Http\Resources\MemberDueResource;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\MemberDue;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MemberDueController extends Controller
{
    /**
     * The treasury team sees every due; a plain member sees only their
     * own — same scoping as the web index (MemberDuePolicy::view).
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [MemberDue::class, $organization]);

        $user = Auth::user();
        $membership = $user->membershipIn($organization);

        $query = $organization->memberDues()
            ->with('membership.user:id,name')
            ->orderByDesc('period');

        if (! $user->isTreasurerOf($organization) && $membership) {
            $query->where('membership_id', $membership->id);
        }

        return MemberDueResource::collection($query->get());
    }

    public function generateMonthly(GenerateMonthlyDuesRequest $request, Organization $organization, GenerateMonthlyDuesAction $generateMonthlyDues): AnonymousResourceCollection
    {
        $generateMonthlyDues->handle(
            $organization,
            Carbon::parse($request->string('period')->value()),
            (int) $request->integer('amount_due'),
        );

        $dues = $organization->memberDues()
            ->with('membership.user:id,name')
            ->where('period', Carbon::parse($request->string('period')->value())->startOfMonth())
            ->get();

        return MemberDueResource::collection($dues);
    }

    public function recordPayment(RecordPaymentRequest $request, MemberDue $due, RecordDuePaymentAction $recordPayment): JsonResource
    {
        $recordPayment->handle(
            $due,
            Auth::user(),
            (int) $request->integer('amount'),
            Carbon::parse($request->string('paid_at')->value()),
            FinancialAccount::findOrFail($request->string('financial_account_id')->value()),
            FinancialCategory::findOrFail($request->string('category_id')->value()),
            $request->filled('method') ? MemberPaymentMethod::from($request->string('method')->value()) : MemberPaymentMethod::Tunai,
            $request->string('note')->value() ?: null,
        );

        return new MemberDueResource($due->fresh('membership.user'));
    }

    /**
     * "Beri tahu bendahara" — screen 17.
     */
    public function notify(MemberDue $due, NotifyDuePaymentAction $notifyPayment): JsonResource
    {
        $this->authorize('notify', $due);

        $notifyPayment->handle($due);

        return new MemberDueResource($due->fresh('membership.user'));
    }
}
