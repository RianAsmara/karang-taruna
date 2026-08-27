<?php

namespace App\Http\Resources;

use App\Models\SponsorContribution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contact details only appear when the viewer can manage sponsors
 * (screen 24: pengurus-only — actually treasurer/chair per the
 * SponsorContributionPolicy). Determined from the authenticated
 * request user, so index/show/updateStatus all get this for free.
 *
 * @mixin SponsorContribution
 */
class SponsorContributionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewContact = $request->user()?->isTreasurerOf($this->organization) ?? false;

        return [
            'id' => $this->id,
            'sponsor' => [
                'id' => $this->sponsor->id,
                'name' => $this->sponsor->name,
                'contactName' => $canViewContact ? $this->sponsor->contact_name : null,
                'contactPhone' => $canViewContact ? $this->sponsor->contact_phone : null,
            ],
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'amount' => $this->amount,
            'description' => $this->description,
            'event' => $this->whenLoaded('event', fn () => $this->event ? [
                'id' => $this->event->id,
                'title' => $this->event->title,
            ] : null),
            'notes' => $canViewContact ? $this->sponsor->notes : null,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
