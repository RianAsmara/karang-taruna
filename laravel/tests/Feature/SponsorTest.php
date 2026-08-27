<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\SponsorContributionStatus;
use App\Enums\SponsorType;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\Sponsor;
use App\Models\SponsorContribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_any_member_can_list_sponsors_but_only_treasurer_sees_contact_details()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $sponsor = Sponsor::factory()->for($organization)->create(['contact_name' => 'Pak Budi', 'contact_phone' => '0812']);
        SponsorContribution::factory()->for($organization)->for($sponsor)->create();

        $this->actingAs($member)
            ->get('/sponsors')
            ->assertInertia(fn ($page) => $page
                ->has('contributions', 1)
                ->where('contributions.0.sponsor.contactName', null)
                ->where('canCreate', false)
            );

        $this->actingAs($treasurer)
            ->get('/sponsors')
            ->assertInertia(fn ($page) => $page
                ->where('contributions.0.sponsor.contactName', 'Pak Budi')
                ->where('canCreate', true)
            );
    }

    public function test_treasurer_can_add_a_cash_sponsorship_and_a_plain_member_cannot()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($treasurer)->get('/sponsors/create')->assertOk();
        $this->actingAs($member)->get('/sponsors/create')->assertForbidden();

        $this->actingAs($treasurer)
            ->post('/sponsors', [
                'name' => 'Toko Sejahtera',
                'type' => SponsorType::Uang->value,
                'amount' => 2_000_000,
            ])
            ->assertRedirect();

        $contribution = SponsorContribution::first();
        $this->assertSame(SponsorType::Uang, $contribution->type);

        $this->actingAs($member)
            ->post('/sponsors', [
                'name' => 'Toko Lain',
                'type' => SponsorType::Uang->value,
                'amount' => 1_000_000,
            ])
            ->assertForbidden();
    }

    public function test_adding_a_second_contribution_reuses_the_existing_sponsor_by_name()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($treasurer)->post('/sponsors', [
            'name' => 'Toko Sejahtera',
            'type' => SponsorType::Uang->value,
            'amount' => 2_000_000,
        ]);

        $this->actingAs($treasurer)->post('/sponsors', [
            'name' => 'Toko Sejahtera',
            'type' => SponsorType::Barang->value,
            'description' => '2 tenda',
        ]);

        $this->assertSame(1, Sponsor::where('name', 'Toko Sejahtera')->count());
        $this->assertSame(2, SponsorContribution::count());
    }

    public function test_an_in_kind_sponsorship_requires_a_description_not_an_amount()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($treasurer)
            ->post('/sponsors', [
                'name' => 'Toko Barang',
                'type' => SponsorType::Barang->value,
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_status_advances_one_step_at_a_time()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->for($organization)->for($sponsor)->create([
            'status' => SponsorContributionStatus::Diajukan,
        ]);

        $this->actingAs($treasurer)
            ->patch("/sponsors/{$contribution->id}/status", ['status' => SponsorContributionStatus::Diterima->value])
            ->assertSessionHasErrors('status');

        $this->actingAs($treasurer)
            ->patch("/sponsors/{$contribution->id}/status", ['status' => SponsorContributionStatus::Setuju->value])
            ->assertRedirect();

        $this->assertSame(SponsorContributionStatus::Setuju, $contribution->fresh()->status);
    }

    public function test_cancel_is_available_from_any_state()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->for($organization)->for($sponsor)->create([
            'status' => SponsorContributionStatus::Setuju,
        ]);

        $this->actingAs($treasurer)
            ->patch("/sponsors/{$contribution->id}/status", ['status' => SponsorContributionStatus::Batal->value])
            ->assertRedirect();

        $this->assertSame(SponsorContributionStatus::Batal, $contribution->fresh()->status);
    }

    public function test_marking_diterima_can_record_a_linked_income_transaction()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->for($organization)->for($sponsor)->create([
            'status' => SponsorContributionStatus::Setuju,
            'type' => SponsorType::Uang,
            'amount' => 500_000,
        ]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);

        $this->actingAs($treasurer)
            ->patch("/sponsors/{$contribution->id}/status", [
                'status' => SponsorContributionStatus::Diterima->value,
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertNotNull($contribution->fresh()->financial_transaction_id);
        $this->assertDatabaseHas('financial_transactions', [
            'organization_id' => $organization->id,
            'amount' => 500_000,
        ]);
    }

    public function test_a_transaction_cannot_be_attached_to_an_in_kind_sponsorship()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->inKind()->for($organization)->for($sponsor)->create([
            'status' => SponsorContributionStatus::Setuju,
        ]);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);

        $this->actingAs($treasurer)
            ->patch("/sponsors/{$contribution->id}/status", [
                'status' => SponsorContributionStatus::Diterima->value,
                'financial_account_id' => $account->id,
                'category_id' => $category->id,
            ])
            ->assertSessionHasErrors('financial_account_id');
    }

    public function test_show_page_renders_for_treasurer_and_a_plain_member()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->for($organization)->for($sponsor)->create();

        $this->actingAs($treasurer)
            ->get("/sponsors/{$contribution->id}")
            ->assertInertia(fn ($page) => $page->where('canManage', true));

        $this->actingAs($member)
            ->get("/sponsors/{$contribution->id}")
            ->assertInertia(fn ($page) => $page->where('canManage', false));
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_sponsor()
    {
        $organization = Organization::factory()->create();
        $sponsor = Sponsor::factory()->for($organization)->create();
        $contribution = SponsorContribution::factory()->for($organization)->for($sponsor)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $this->actingAs($outsider)
            ->get("/sponsors/{$contribution->id}")
            ->assertForbidden();
    }
}
