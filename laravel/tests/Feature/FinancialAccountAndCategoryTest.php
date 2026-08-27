<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAccountAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_treasurer_can_create_an_account_and_a_category()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($treasurer)
            ->post('/finance/accounts', ['name' => 'Kas Pemuda'])
            ->assertRedirect();

        $this->actingAs($treasurer)
            ->post('/finance/categories', ['name' => 'Iuran Anggota', 'transaction_type' => 'INCOME'])
            ->assertRedirect();

        $this->assertDatabaseHas('financial_accounts', ['organization_id' => $organization->id, 'name' => 'Kas Pemuda']);
        $this->assertDatabaseHas('financial_categories', ['organization_id' => $organization->id, 'name' => 'Iuran Anggota']);
    }

    public function test_a_plain_member_cannot_manage_accounts_or_categories()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)
            ->post('/finance/accounts', ['name' => 'Kas Pemuda'])
            ->assertForbidden();

        $this->actingAs($member)
            ->post('/finance/categories', ['name' => 'Iuran Anggota', 'transaction_type' => 'INCOME'])
            ->assertForbidden();
    }

    public function test_an_account_with_transactions_cannot_be_deleted()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->delete("/finance/accounts/{$account->id}")
            ->assertForbidden();
    }

    public function test_an_unused_account_can_be_deleted()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($treasurer)
            ->delete("/finance/accounts/{$account->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('financial_accounts', ['id' => $account->id]);
    }

    public function test_duplicate_account_names_within_an_organization_are_rejected()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        FinancialAccount::factory()->create(['organization_id' => $organization->id, 'name' => 'Kas Pemuda']);

        $this->actingAs($treasurer)
            ->post('/finance/accounts', ['name' => 'Kas Pemuda'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_transaction_type_cannot_be_transfer()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($treasurer)
            ->post('/finance/categories', ['name' => 'Antar Kas', 'transaction_type' => TransactionType::Transfer->value])
            ->assertSessionHasErrors('transaction_type');
    }
}
