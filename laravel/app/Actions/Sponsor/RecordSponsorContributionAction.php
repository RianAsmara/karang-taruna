<?php

namespace App\Actions\Sponsor;

use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Models\Organization;
use App\Models\Sponsor;
use App\Models\SponsorContribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordSponsorContributionAction
{
    /**
     * Finds the sponsor by name within the organization, or creates one —
     * the "duplicate name" prompt (screen 25) is a soft mobile-side
     * confirmation before submitting, not a backend rule, so this never
     * refuses a matching name; it just reuses the existing Sponsor.
     */
    public function handle(
        Organization $organization,
        User $user,
        string $name,
        SponsorType $type,
        ?int $amount,
        ?string $description,
        ?string $eventId,
        ?string $contactName,
        ?string $contactPhone,
        ?string $notes,
    ): SponsorContribution {
        return DB::transaction(function () use ($organization, $user, $name, $type, $amount, $description, $eventId, $contactName, $contactPhone, $notes) {
            $sponsor = $organization->sponsors()->firstOrCreate(
                ['name' => $name],
                [
                    'contact_name' => $contactName,
                    'contact_phone' => $contactPhone,
                    'notes' => $notes,
                    'created_by' => $user->id,
                ],
            );

            return $sponsor->contributions()->create([
                'organization_id' => $organization->id,
                'event_id' => $eventId,
                'type' => $type,
                'status' => SponsorContributionStatus::Diajukan,
                'amount' => $amount,
                'description' => $description,
                'created_by' => $user->id,
            ]);
        });
    }
}
