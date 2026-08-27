<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\FinancialCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinancialCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_member_can_list_financial_categories()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);
        FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'INCOME']);
        FinancialCategory::factory()->create(['organization_id' => $organization->id, 'transaction_type' => 'EXPENSE']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/finance/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
