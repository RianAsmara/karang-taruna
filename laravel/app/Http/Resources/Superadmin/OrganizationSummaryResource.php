<?php

namespace App\Http\Resources\Superadmin;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'memberCount' => $this->whenCounted('memberships'),
            'requireTransactionApproval' => $this->require_transaction_approval,
            'publicTransparencyEnabled' => $this->public_transparency_enabled,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
