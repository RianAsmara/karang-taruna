<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberDueResource;
use App\Models\MemberDue;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
}
