<?php

namespace Tests\Feature;

use App\Enums\FinancialReportStatus;
use App\Enums\FinancialReportVisibility;
use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialReport;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function approvedTransaction(Organization $organization, User $creator, TransactionType $type, int $amount, string $date): FinancialTransaction
    {
        return FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => FinancialAccount::factory()->create(['organization_id' => $organization->id]),
            'transaction_type' => $type,
            'amount' => $amount,
            'transaction_date' => $date,
            'created_by' => $creator->id,
        ]);
    }

    public function test_treasurer_can_generate_a_draft_report_with_computed_figures()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $this->approvedTransaction($organization, $treasurer, TransactionType::Income, 1_000_000, '2026-08-05');
        $this->approvedTransaction($organization, $treasurer, TransactionType::Expense, 300_000, '2026-08-10');

        $this->actingAs($treasurer)
            ->post('/finance/reports', [
                'title' => 'Laporan Agustus',
                'report_type' => 'MONTHLY',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'visibility' => 'MEMBERS',
            ])
            ->assertRedirect();

        $report = FinancialReport::firstWhere('title', 'Laporan Agustus');

        $this->assertNotNull($report);
        $this->assertSame(FinancialReportStatus::Draft, $report->status);
        $this->assertSame(0, $report->opening_balance);
        $this->assertSame(1_000_000, $report->total_income);
        $this->assertSame(300_000, $report->total_expense);
        $this->assertSame(700_000, $report->closing_balance);
    }

    public function test_a_plain_member_cannot_generate_a_report()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        $this->actingAs($member)
            ->post('/finance/reports', [
                'title' => 'Laporan Agustus',
                'report_type' => 'MONTHLY',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'visibility' => 'MEMBERS',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_publish_but_treasurer_cannot()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/reports/{$report->id}/publish")
            ->assertForbidden();

        $this->actingAs($owner)
            ->post("/reports/{$report->id}/publish")
            ->assertRedirect();

        $fresh = $report->fresh();
        $this->assertSame(FinancialReportStatus::Published, $fresh->status);
        $this->assertSame($owner->id, $fresh->published_by);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_publishing_recomputes_figures_from_transactions_approved_after_the_draft_was_made()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $report = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'total_income' => 0,
            'closing_balance' => 0,
        ]);

        $this->approvedTransaction($organization, $treasurer, TransactionType::Income, 500_000, '2026-08-15');

        $this->actingAs($owner)->post("/reports/{$report->id}/publish");

        $this->assertSame(500_000, $report->fresh()->total_income);
    }

    public function test_a_published_report_cannot_be_published_again()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $report = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->post("/reports/{$report->id}/publish")
            ->assertForbidden();
    }

    public function test_owner_can_archive_a_published_report()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $report = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)->post("/reports/{$report->id}/archive")->assertRedirect();

        $this->assertSame(FinancialReportStatus::Archived, $report->fresh()->status);
    }

    public function test_revising_a_published_report_snapshots_history_and_recomputes_figures()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $report = FinancialReport::factory()->published()->create([
            'organization_id' => $organization->id,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'total_income' => 100_000,
            'closing_balance' => 100_000,
        ]);

        $this->approvedTransaction($organization, $treasurer, TransactionType::Income, 250_000, '2026-08-20');

        $this->actingAs($owner)->post("/reports/{$report->id}/revise")->assertRedirect();

        $fresh = $report->fresh();
        $this->assertSame(FinancialReportStatus::Published, $fresh->status);
        $this->assertSame(250_000, $fresh->total_income);
        $this->assertSame(1, $fresh->revisions()->count());
        $this->assertSame(100_000, $fresh->revisions()->first()->snapshot['total_income']);

        // Revising again increments the revision number rather than overwriting.
        $this->actingAs($owner)->post("/reports/{$report->id}/revise");
        $this->assertSame(2, $fresh->revisions()->count());
        $this->assertSame(2, $fresh->revisions()->orderByDesc('revision_number')->first()->revision_number);
    }

    public function test_a_draft_report_can_be_deleted_but_a_published_one_cannot()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $draft = FinancialReport::factory()->create(['organization_id' => $organization->id, 'created_by' => $treasurer->id]);
        $published = FinancialReport::factory()->published()->create(['organization_id' => $organization->id]);

        $this->actingAs($treasurer)->delete("/reports/{$draft->id}")->assertRedirect();
        $this->assertDatabaseMissing('financial_reports', ['id' => $draft->id]);

        $this->actingAs($treasurer)->delete("/reports/{$published->id}")->assertForbidden();
    }

    public function test_a_draft_report_is_only_visible_to_the_treasury_team()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        $draft = FinancialReport::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
            'visibility' => FinancialReportVisibility::Members,
        ]);

        $this->actingAs($treasurer)->get("/reports/{$draft->id}")->assertOk();
        $this->actingAs($member)->get("/reports/{$draft->id}")->assertForbidden();
    }

    public function test_a_private_report_is_only_visible_to_owner_and_admin()
    {
        $organization = Organization::factory()->create();
        $admin = $this->memberWithRole($organization, OrganizationRole::Admin);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);

        $report = FinancialReport::factory()->published()->privateVisibility()->create(['organization_id' => $organization->id]);

        $this->actingAs($admin)->get("/reports/{$report->id}")->assertOk();
        $this->actingAs($member)->get("/reports/{$report->id}")->assertForbidden();
    }

    public function test_a_public_published_report_is_visible_to_a_guest()
    {
        $organization = Organization::factory()->create();
        $report = FinancialReport::factory()->published()->publicVisibility()->create(['organization_id' => $organization->id]);

        $this->get("/reports/{$report->id}")->assertOk();
    }

    public function test_a_members_report_is_not_visible_to_a_guest()
    {
        $organization = Organization::factory()->create();
        $report = FinancialReport::factory()->published()->create([
            'organization_id' => $organization->id,
            'visibility' => FinancialReportVisibility::Members,
        ]);

        $this->get("/reports/{$report->id}")->assertNotFound();
    }

    public function test_a_member_from_another_organization_cannot_view_a_members_report()
    {
        $report = FinancialReport::factory()->published()->create();
        $outsider = User::factory()->create();
        $outsider->memberships()->create([
            'organization_id' => Organization::factory()->create()->id,
            'role' => OrganizationRole::Owner,
        ]);

        $this->actingAs($outsider)->get("/reports/{$report->id}")->assertForbidden();
    }

    public function test_generating_publishing_and_revising_a_report_writes_an_audit_trail()
    {
        $organization = Organization::factory()->create();
        $owner = $this->memberWithRole($organization, OrganizationRole::Owner);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);

        $this->actingAs($treasurer)->post('/finance/reports', [
            'title' => 'Laporan Agustus',
            'report_type' => 'MONTHLY',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'visibility' => 'MEMBERS',
        ]);
        $report = FinancialReport::firstWhere('title', 'Laporan Agustus');

        $this->assertDatabaseHas('audit_logs', [
            'model_id' => $report->id,
            'action' => 'report.created',
        ]);

        $this->actingAs($owner)->post("/reports/{$report->id}/publish");
        $this->assertDatabaseHas('audit_logs', [
            'model_id' => $report->id,
            'action' => 'report.published',
        ]);

        $this->approvedTransaction($organization, $treasurer, TransactionType::Income, 100_000, '2026-08-15');
        $this->actingAs($owner)->post("/reports/{$report->id}/revise");
        $this->assertDatabaseHas('audit_logs', [
            'model_id' => $report->id,
            'action' => 'report.revised',
        ]);

        $this->actingAs($owner)->post("/reports/{$report->id}/archive");
        $this->assertDatabaseHas('audit_logs', [
            'model_id' => $report->id,
            'action' => 'report.archived',
        ]);
    }
}
