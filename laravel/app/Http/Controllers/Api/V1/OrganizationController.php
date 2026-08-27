<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organization\CreateOrganizationAction;
use App\Enums\EventParticipantStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationController extends Controller
{
    /**
     * Deliberately outside the 'current-org' middleware group (routes/api.php)
     * — a user with no organization yet must be able to reach this, same as
     * the web route. Reuses CreateOrganizationAction so a new org always
     * becomes usable through the same path as the web onboarding flow.
     */
    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $createOrganization): JsonResponse
    {
        $organization = $createOrganization->handle($request->user(), $request->string('name')->value());

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }

    public function current(Organization $organization, OrganizationMembership $membership): JsonResource
    {
        $this->authorize('view', $organization);

        return (new OrganizationResource($organization))->additional([
            'membership' => [
                'id' => $membership->id,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
                'participatedEventsCount' => EventParticipant::query()
                    ->where('membership_id', $membership->id)
                    ->where('status', EventParticipantStatus::Registered)
                    ->whereHas('event', fn ($query) => $query->where('status', EventStatus::Completed))
                    ->count(),
            ],
        ]);
    }
}
