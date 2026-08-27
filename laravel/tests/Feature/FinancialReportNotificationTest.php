<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\FinancialReportPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FinancialReportNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_publishing_a_report_notifies_other_members_but_not_the_publisher()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->post("/reports/{$report->id}/publish");

        Notification::assertSentTo($member, FinancialReportPublished::class);
        Notification::assertNotSentTo($owner, FinancialReportPublished::class);
    }
}
