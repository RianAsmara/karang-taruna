<?php

namespace App\Actions\Finance;

use App\Enums\OrganizationRole;
use App\Models\MemberDue;
use App\Notifications\MemberDuePaymentNotified;
use Illuminate\Support\Facades\Notification;

class NotifyDuePaymentAction
{
    /**
     * Sets notified_at — never marks the due paid. Only a treasurer's
     * own record does that (mobile-ux.md § Dues are recorded, not
     * collected: "That asymmetry is what keeps the ledger trustworthy").
     */
    public function handle(MemberDue $memberDue): MemberDue
    {
        $memberDue->update(['notified_at' => now()]);

        $treasurers = $memberDue->organization->memberships()
            ->whereIn('role', [OrganizationRole::Ketua, OrganizationRole::Bendahara])
            ->with('user')
            ->get()
            ->pluck('user');

        Notification::send($treasurers, new MemberDuePaymentNotified($memberDue));

        return $memberDue;
    }
}
