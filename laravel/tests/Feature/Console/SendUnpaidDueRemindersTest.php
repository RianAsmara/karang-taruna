<?php

namespace Tests\Feature\Console;

use App\Models\MemberDue;
use App\Models\MemberPayment;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Notifications\MemberDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendUnpaidDueRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reminds_members_with_outstanding_dues_whose_period_has_arrived()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $membership = OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $unpaidDue = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'period' => now()->subMonth()->startOfMonth(),
            'amount_due' => 25_000,
        ]);

        $this->artisan('dues:remind-unpaid')->assertSuccessful();

        Notification::assertSentTo($user, MemberDueReminder::class, function (MemberDueReminder $notification) use ($unpaidDue) {
            return $notification->toArray($unpaidDue)['member_due_id'] === $unpaidDue->id;
        });
    }

    public function test_it_does_not_remind_members_whose_dues_are_fully_paid()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $membership = OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $paidDue = MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'period' => now()->subMonth()->startOfMonth(),
            'amount_due' => 25_000,
        ]);
        MemberPayment::factory()->create([
            'member_due_id' => $paidDue->id,
            'amount' => 25_000,
        ]);

        $this->artisan('dues:remind-unpaid')->assertSuccessful();

        Notification::assertNotSentTo($user, MemberDueReminder::class);
    }

    public function test_it_does_not_remind_members_whose_due_period_has_not_arrived_yet()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $membership = OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        MemberDue::factory()->create([
            'organization_id' => $organization->id,
            'membership_id' => $membership->id,
            'period' => now()->addMonth()->startOfMonth(),
            'amount_due' => 25_000,
        ]);

        $this->artisan('dues:remind-unpaid')->assertSuccessful();

        Notification::assertNotSentTo($user, MemberDueReminder::class);
    }
}
