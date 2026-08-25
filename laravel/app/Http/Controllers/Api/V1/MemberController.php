<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\AddMemberAction;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [OrganizationMembership::class, $organization]);

        $members = $organization->memberships()
            ->with('user:id,name,email')
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

        return (new MemberResource($membership->load('user:id,name,email')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(OrganizationMembership $member): JsonResource
    {
        $this->authorize('view', $member);

        return new MemberResource($member->load('user:id,name,email'));
    }
}
