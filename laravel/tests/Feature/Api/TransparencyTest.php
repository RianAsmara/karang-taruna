<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransparencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_fetch_the_transparency_summary()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Member]);

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 1_000_000,
            'transaction_date' => now(),
        ]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Expense,
            'amount' => 400_000,
            'transaction_date' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/transparency')
            ->assertOk()
            ->assertJsonPath('balance', 600_000)
            ->assertJsonPath('monthSurplus', 600_000)
            ->assertJsonCount(2, 'recentTransactions');
    }

    public function test_this_matches_the_web_transparency_dashboards_figures()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Owner]);

        $account = FinancialAccount::factory()->create(['organization_id' => $organization->id]);
        FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'financial_account_id' => $account->id,
            'transaction_type' => TransactionType::Income,
            'amount' => 750_000,
            'transaction_date' => now(),
        ]);

        Sanctum::actingAs($user);

        $api = $this->getJson('/api/v1/transparency')->assertOk()->json();
        $web = $this->actingAs($user)->get('transparansi');

        $web->assertOk();
        $web->assertInertia(fn ($page) => $page
            ->where('balance', $api['balance'])
            ->where('monthIncome', $api['monthIncome'])
        );
    }
}
