<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\AddMemberAction;
use App\Actions\Organization\TransferChairAction;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\TransferChairRequest;
use App\Http\Requests\Member\UpdateMemberRoleRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\MemberResponsibilityResource;
use App\Models\AuditLog;
use App\Models\EventCommittee;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class MemberController extends Controller
{
    private const LEFT_RETENTION_DAYS = 30;

    /**
     * Includes members who left within the retention window (screen 12:
     * "show a 'Keluar' outline tag for 30 days, then drop off") alongside
     * current members — MemberResource exposes `leftAt` so mobile can
     * render the tag.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [OrganizationMembership::class, $organization]);

        $members = $organization->memberships()
            ->withTrashed()
            ->where(function ($query) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', Carbon::now()->subDays(self::LEFT_RETENTION_DAYS));
            })
            ->with(['user:id,name,email,phone,show_phone_to_members', 'organization'])
            ->orderBy('created_at')
            ->get();

        return MemberResource::collection($members);
    }

    public function store(StoreMemberRequest $request, Organization $organization, AddMemberAction $addMember): JsonResponse
    {
        $membership = $addMember->handle(
            $organization,
            $request->string('email')->value(),
            OrganizationRole::from($request->string('role')->value()),
        );

        return (new MemberResource($membership->load(['user:id,name,email,phone,show_phone_to_members', 'organization'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(OrganizationMembership $member): JsonResource
    {
        $this->authorize('view', $member);

        return new MemberResource($member->load(['user:id,name,email,phone,show_phone_to_members', 'organization']));
    }

    public function updateRole(UpdateMemberRoleRequest $request, OrganizationMembership $member): JsonResource
    {
        $member->update(['role' => $request->string('role')->value()]);

        return new MemberResource($member->load(['user:id,name,email,phone,show_phone_to_members', 'organization']));
    }

    /**
     * The chair role moves, it is never granted a second time or left
     * empty — see TransferChairAction.
     */
    public function transferChair(TransferChairRequest $request, OrganizationMembership $member, TransferChairAction $transferChair): JsonResource
    {
        $currentChair = $request->user()->membershipIn($member->organization);

        $transferChair->handle($currentChair, $member);

        return new MemberResource($member->fresh()->load(['user:id,name,email,phone,show_phone_to_members', 'organization']));
    }

    public function destroy(OrganizationMembership $member): Response
    {
        $this->authorize('delete', $member);

        $member->delete();

        return response()->noContent();
    }

    /**
     * Active committee assignments only (screen 13: "Tanggung jawab" —
     * "Belum ada tanggung jawab aktif" when there are none). A member's
     * completed/cancelled event history isn't repeated here — the
     * "Aktivitas" section below already surfaces past activity.
     */
    public function responsibilities(OrganizationMembership $member): AnonymousResourceCollection
    {
        $this->authorize('view', $member);

        $responsibilities = EventCommittee::where('membership_id', $member->id)
            ->whereHas('event', fn ($query) => $query->whereIn('status', ['PLANNED', 'ONGOING']))
            ->with('event:id,title,status')
            ->get();

        return MemberResponsibilityResource::collection($responsibilities);
    }

    /**
     * Last 5 actions (screen 13: "Aktivitas"). Scoped to what AuditLog
     * already covers today — financial transaction/report events via
     * FinancialTransactionObserver — not a general activity feed across
     * every domain; extending AuditLog's coverage is separate work.
     */
    public function activity(OrganizationMembership $member): AnonymousResourceCollection
    {
        $this->authorize('view', $member);

        $activity = AuditLog::where('organization_id', $member->organization_id)
            ->where('actor_id', $member->user_id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return AuditLogResource::collection($activity);
    }
}
