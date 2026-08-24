<?php

namespace App\Actions\Finance;

use App\Enums\MemberDueType;
use App\Models\Organization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyDuesAction
{
    /**
     * Create a MONTHLY due for every current member for the given period,
     * skipping anyone who already has one (safe to re-run).
     */
    public function handle(Organization $organization, Carbon $period, int $amountDue): int
    {
        return DB::transaction(function () use ($organization, $period, $amountDue) {
            $created = 0;

            foreach ($organization->memberships as $membership) {
                $due = $organization->memberDues()->firstOrCreate(
                    [
                        'membership_id' => $membership->id,
                        'period' => $period->startOfMonth()->toDateString(),
                        'type' => MemberDueType::Monthly,
                    ],
                    ['amount_due' => $amountDue],
                );

                if ($due->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        });
    }
}
