<?php

namespace App\Actions\Membership;

use App\Enums\MembershipExitRequestStatus;
use App\Models\MembershipExitRequest;
use App\Models\User;
use App\Notifications\MembershipExitDecided;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecideMembershipExitAction
{
    /**
     * The chair's decision. Approving soft-deletes the membership — the
     * same mechanism as the chair's own "Keluarkan", so the departed
     * member keeps the 30-day "Keluar" tag and every dues/transaction
     * record stays attached to the organization. Rejecting leaves the
     * membership untouched and lets the member ask again later.
     */
    public function handle(MembershipExitRequest $exitRequest, User $decidedBy, bool $approve, ?string $note = null): MembershipExitRequest
    {
        if ($exitRequest->status->isDecided()) {
            throw ValidationException::withMessages([
                'status' => 'Permintaan ini sudah diputuskan.',
            ]);
        }

        return DB::transaction(function () use ($exitRequest, $decidedBy, $approve, $note) {
            $exitRequest->update([
                'status' => $approve ? MembershipExitRequestStatus::Approved : MembershipExitRequestStatus::Rejected,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $membership = $exitRequest->membership;

            if ($approve) {
                // Clear the departed organization from the member's active
                // pointer so they don't land on an organization they no
                // longer belong to; currentMembership() already falls back
                // to their first remaining membership.
                $user = $membership->user;

                if ($user !== null && $user->active_organization_id === $exitRequest->organization_id) {
                    $user->forceFill(['active_organization_id' => null])->save();
                }

                $membership->delete();
            }

            $membership->user?->notify(new MembershipExitDecided($exitRequest->fresh()));

            return $exitRequest->refresh();
        });
    }
}
