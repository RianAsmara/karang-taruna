<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationController extends Controller
{
    public function current(Organization $organization, OrganizationMembership $membership): JsonResource
    {
        $this->authorize('view', $organization);

        return (new OrganizationResource($organization))->additional([
            'membership' => [
                'id' => $membership->id,
                'role' => $membership->role->value,
                'roleLabel' => $membership->role->label(),
            ],
        ]);
    }
}
