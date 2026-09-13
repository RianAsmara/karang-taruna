<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invite\CreateOrganizationInviteAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationInviteResource;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Mobile counterpart of the web flow — same Action, same Policy. Mobile only
 * creates and revokes links; accepting one happens by opening the link, which
 * is the web route.
 */
class OrganizationInviteController extends Controller
{
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [OrganizationInvite::class, $organization]);

        return OrganizationInviteResource::collection(
            $organization->invites()->whereNull('revoked_at')->latest()->get()
        );
    }

    public function store(Request $request, Organization $organization, CreateOrganizationInviteAction $createInvite): JsonResponse
    {
        $this->authorize('create', [OrganizationInvite::class, $organization]);

        $validated = $request->validate([
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $invite = $createInvite->handle($organization, $request->user(), $validated['max_uses'] ?? null);

        return (new OrganizationInviteResource($invite))->response()->setStatusCode(201);
    }

    public function destroy(OrganizationInvite $invite): JsonResponse
    {
        $this->authorize('revoke', $invite);

        $invite->update(['revoked_at' => now()]);

        return response()->json(null, 204);
    }
}
