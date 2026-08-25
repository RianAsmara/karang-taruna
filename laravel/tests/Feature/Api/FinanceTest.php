<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_member_can_list_accounts_with_their_computed_balance()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 500_000,
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/finance/accounts')
            ->assertOk()
            ->assertJsonPath('data.0.balance', 500_000);
    }

    public function test_a_plain_member_only_sees_approved_transactions_while_the_treasurer_sees_everything()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
        ]);
        FinancialTransaction::factory()->draft()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
        ]);

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/finance/transactions')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($treasurer);
        $this->getJson('/api/v1/finance/transactions')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_the_treasurer_can_record_a_transaction_which_starts_as_a_draft()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        $category = FinancialCategory::factory()->create([
            'organization_id' => $organization->id,
            'transaction_type' => TransactionType::Income,
        ]);

        Sanctum::actingAs($treasurer);

        $this->postJson('/api/v1/finance/transactions', [
            'financial_account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 250_000,
            'transaction_type' => 'INCOME',
            'transaction_date' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.status', 'DRAFT');
    }

    public function test_a_plain_member_cannot_record_a_transaction()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/finance/transactions', [
            'financial_account_id' => $account->id,
            'amount' => 250_000,
            'transaction_type' => 'INCOME',
            'transaction_date' => now()->toDateString(),
        ])->assertForbidden();
    }
}
