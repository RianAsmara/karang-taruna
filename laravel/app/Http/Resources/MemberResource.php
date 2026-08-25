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
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'joinedAt' => $this->created_at->toIso8601String(),
            'isOwner' => $this->role === OrganizationRole::Owner,
        ];
    }
}
