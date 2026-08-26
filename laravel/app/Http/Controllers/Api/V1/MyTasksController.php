<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventTaskResource;
use App\Models\EventTask;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyTasksController extends Controller
{
    /**
     * Every task assigned to the current user, across every event in
     * the current organization — the query Home's "Tugas saya" needs
     * that no single-event endpoint can answer. Inherently self-scoped
     * (assignee_membership_id = the caller's own membership), so no
     * separate Policy check is needed beyond being a member at all
     * (already guaranteed by the 'current-org' middleware).
     */
    public function index(Organization $organization, OrganizationMembership $membership): AnonymousResourceCollection
    {
        $tasks = EventTask::query()
            ->whereHas('event', fn ($query) => $query->where('organization_id', $organization->id))
            ->where('assignee_membership_id', $membership->id)
            ->with('event:id,title')
            ->orderBy('due_date')
            ->get();

        return EventTaskResource::collection($tasks);
    }
}
