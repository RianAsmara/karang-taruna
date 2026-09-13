<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Membership\DecideMembershipExitAction;
use App\Actions\Membership\RequestMembershipExitAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\DecideMembershipExitRequest;
use App\Http\Requests\Membership\StoreMembershipExitRequest;
use App\Http\Resources\MembershipExitRequestResource;
use App\Models\MembershipExitRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Mobile counterpart of the web flow — same Actions, same Policies, same
 * Form Requests. See the membership_exit_requests migration for why
 * leaving is a request rather than a unilateral act.
 */
class MembershipExitRequestController extends Controller
{
    /**
     * The chair's pending queue.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [MembershipExitRequest::class, $organization]);

        return MembershipExitRequestResource::collection(
            MembershipExitRequest::query()
                ->where('organization_id', $organization->id)
                ->pending()
                ->with(['membership.user'])
                ->orderBy('created_at')
                ->get()
        );
    }

    /**
     * The requester's own current request, so the app can show "menunggu
     * persetujuan" instead of offering the button again.
     */
    public function mine(Request $request, OrganizationMembership $membership): JsonResponse
    {
        $exitRequest = MembershipExitRequest::query()
            ->where('membership_id', $membership->id)
            ->with(['membership.user'])
            ->latest('created_at')
            ->first();

        return response()->json([
            'data' => $exitRequest === null ? null : new MembershipExitRequestResource($exitRequest),
            'canRequest' => $request->user()->can('create', [MembershipExitRequest::class, $membership]),
        ]);
    }

    public function store(StoreMembershipExitRequest $request, RequestMembershipExitAction $requestExit): JsonResponse
    {
        $exitRequest = $requestExit->handle(
            $request->user()->currentMembership(),
            $request->string('reason')->value() ?: null,
        );

        return (new MembershipExitRequestResource($exitRequest->load('membership.user')))
            ->response()
            ->setStatusCode(201);
    }

    public function decide(
        DecideMembershipExitRequest $request,
        MembershipExitRequest $exitRequest,
        DecideMembershipExitAction $decide,
    ): JsonResponse {
        $decided = $decide->handle(
            $exitRequest,
            $request->user(),
            $request->boolean('approve'),
            $request->string('note')->value() ?: null,
        );

        return (new MembershipExitRequestResource($decided->load('membership.user')))->response();
    }
}
