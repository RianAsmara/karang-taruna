<?php

namespace Tests\Feature;

use App\Enums\FinancialReportVisibility;
use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_download_a_visible_reports_pdf()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $report = FinancialReport::factory()->published()->create([
            'organization_id' => $organization->id,
            'visibility' => FinancialReportVisibility::Members,
        ]);

        $response = $this->actingAs($member)->get("/reports/{$report->id}/pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_a_guest_can_download_a_public_published_reports_pdf()
    {
        $report = FinancialReport::factory()->published()->publicVisibility()->create();

        $this->get("/reports/{$report->id}/pdf")->assertOk();
    }

    public function test_a_guest_cannot_download_a_members_reports_pdf()
    {
        $report = FinancialReport::factory()->published()->create([
            'visibility' => FinancialReportVisibility::Members,
        ]);

        $this->get("/reports/{$report->id}/pdf")->assertNotFound();
    }

    public function test_a_member_from_another_organization_cannot_download_the_pdf()
    {
        $report = FinancialReport::factory()->published()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Ketua,
        ]);

        $this->actingAs($outsider)->get("/reports/{$report->id}/pdf")->assertForbidden();
    }
}
