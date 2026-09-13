<?php

namespace App\Actions\Membership;

use App\Enums\MembershipExitRequestStatus;
use App\Enums\OrganizationRole;
use App\Models\MembershipExitRequest;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\MembershipExitRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestMembershipExitAction
{
    /**
     * Submit a request to leave, for the chair to decide on. The chair
     * itself can't submit one — an organization is never left without a
     * chair, so the chair must hand the role over first (TransferChairAction),
     * which is the same rule that already blocks removing a chair.
     */
    public function handle(OrganizationMembership $membership, ?string $reason = null): MembershipExitRequest
    {
        if ($membership->role === OrganizationRole::Ketua) {
            throw ValidationException::withMessages([
                'membership' => 'Ketua tidak bisa keluar sebelum memindahkan peran ketua ke anggota lain.',
            ]);
        }

        return DB::transaction(function () use ($membership, $reason) {
            $alreadyPending = MembershipExitRequest::query()
                ->where('membership_id', $membership->id)
                ->pending()
                ->exists();

            if ($alreadyPending) {
                throw ValidationException::withMessages([
                    'membership' => 'Permintaan keluar Anda masih menunggu persetujuan ketua.',
                ]);
            }

            $exitRequest = MembershipExitRequest::create([
                'organization_id' => $membership->organization_id,
                'membership_id' => $membership->id,
                'reason' => $reason,
                'status' => MembershipExitRequestStatus::Pending,
            ]);

            $this->notifyChair($exitRequest);

            return $exitRequest;
        });
    }

    private function notifyChair(MembershipExitRequest $exitRequest): void
    {
        $chair = User::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('organization_id', $exitRequest->organization_id)
                ->where('role', OrganizationRole::Ketua))
            ->first();

        $chair?->notify(new MembershipExitRequested($exitRequest));
    }
}
