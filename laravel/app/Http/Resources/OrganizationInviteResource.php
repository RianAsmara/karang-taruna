<?php

namespace App\Http\Resources;

use App\Models\OrganizationInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrganizationInvite
 */
class OrganizationInviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // The full link, built server-side — the client should never have
            // to know how to assemble it.
            'url' => route('organizations.invites.accept', $this->token),
            'expiresAt' => $this->expires_at->toIso8601String(),
            'maxUses' => $this->max_uses,
            'uses' => $this->uses,
            'isActive' => $this->isActive(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
