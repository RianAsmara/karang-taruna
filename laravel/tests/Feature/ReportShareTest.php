<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\FinancialReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_sharing_a_public_report_logs_the_channel_without_requiring_login()
    {
        $report = FinancialReport::factory()->published()->publicVisibility()->create();

        $this->post("/reports/{$report->id}/share", ['channel' => 'WHATSAPP'])->assertRedirect();

        $this->assertDatabaseHas('report_share_logs', [
            'financial_report_id' => $report->id,
            'channel' => 'WHATSAPP',
            'shared_by' => null,
        ]);
    }

    public function test_sharing_a_report_a_member_can_view_records_who_shared_it()
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create();
        $organization->memberships()->create(['user_id' => $member->id, 'role' => OrganizationRole::Anggota]);

        $report = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)->post("/reports/{$report->id}/share", ['channel' => 'WEB']);

        $this->assertDatabaseHas('report_share_logs', [
            'financial_report_id' => $report->id,
            'channel' => 'WEB',
            'shared_by' => $member->id,
        ]);
    }

    public function test_sharing_a_report_a_guest_cannot_view_is_rejected()
    {
        $report = FinancialReport::factory()->published()->create();

        $this->post("/reports/{$report->id}/share", ['channel' => 'WEB'])->assertNotFound();
        $this->assertDatabaseCount('report_share_logs', 0);
    }

    public function test_the_qr_endpoint_returns_a_png_for_a_publicly_viewable_report()
    {
        $report = FinancialReport::factory()->published()->publicVisibility()->create();

        $response = $this->get("/reports/{$report->id}/qr");

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_the_qr_endpoint_404s_for_a_report_a_guest_cannot_view()
    {
        $report = FinancialReport::factory()->published()->create();

        $this->get("/reports/{$report->id}/qr")->assertNotFound();
    }
}
