<?php

namespace App\Http\Resources;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationMembership */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $viewer ? $this->user->phoneVisibleTo($viewer, $this->organization) : null,
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'joinedAt' => $this->created_at->toIso8601String(),
            'isChair' => $this->role === OrganizationRole::Ketua,
            // Present only for a member within the 30-day "Keluar"
            // retention window (see MemberController::index) — a live
            // membership never has this set.
            'leftAt' => $this->deleted_at?->toIso8601String(),
            // Prefers the eager-loaded withSum() aggregate from index()
            // to avoid an N+1 query per member in a list; falls back to
            // a live query for single-record fetches (show/store/etc.),
            // which only ever cost one query anyway.
            'activityPoints' => $this->activity_points !== null ? (int) $this->activity_points : $this->activityPoints(),
        ];
    }
}
