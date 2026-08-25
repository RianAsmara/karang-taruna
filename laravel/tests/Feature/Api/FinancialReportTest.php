<?php

namespace Tests\Feature\Api;

use App\Enums\FinancialReportStatus;
use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_plain_member_only_sees_published_members_or_public_reports()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        FinancialReport::factory()->create(['organization_id' => $organization->id]); // draft
        FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/finance/reports')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_an_owner_can_publish_a_draft_report_but_a_treasurer_cannot()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);
        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/finance/reports/{$report->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'PUBLISHED');
    }

    public function test_an_owner_can_archive_a_published_report()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $report = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/finance/reports/{$report->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'ARCHIVED');

        $this->assertSame(FinancialReportStatus::Archived, $report->fresh()->status);
    }

    public function test_a_guest_can_view_a_public_published_report_without_a_token()
    {
        $report = FinancialReport::factory()->published()->publicVisibility()->create();

        $this->getJson("/api/v1/finance/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);

        $this->getJson("/api/v1/finance/reports/{$report->id}/share")
            ->assertOk()
            ->assertJsonStructure(['shareUrl', 'qrUrl']);

        $this->get("/api/v1/finance/reports/{$report->id}/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_a_guest_cannot_view_a_members_only_report()
    {
        $report = FinancialReport::factory()->published()->create(); // default visibility is MEMBERS

        $this->getJson("/api/v1/finance/reports/{$report->id}")->assertNotFound();
    }

    public function test_a_member_from_another_organization_cannot_view_a_private_report()
    {
        $report = FinancialReport::factory()->published()->privateVisibility()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Owner,
        ]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/finance/reports/{$report->id}")->assertForbidden();
    }
}
