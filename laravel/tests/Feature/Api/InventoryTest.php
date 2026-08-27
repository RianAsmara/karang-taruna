<?php

namespace Tests\Feature\Api;

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use App\Enums\InventoryLoanStatus;
use App\Enums\OrganizationRole;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    private function membershipOf(Organization $organization, User $user): OrganizationMembership
    {
        return $organization->memberships()->where('user_id', $user->id)->firstOrFail();
    }

    public function test_any_member_can_list_inventory()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        InventoryItem::factory()->for($organization)->create();

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/inventory')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pengurus_can_add_an_item_but_a_plain_member_cannot()
    {
        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($secretary);
        $this->postJson('/api/v1/inventory', [
            'name' => 'Tenda Pleton',
            'category' => InventoryCategory::Tenda->value,
            'quantity' => 3,
            'condition' => InventoryCondition::Baik->value,
        ])->assertCreated()->assertJsonPath('data.availableQuantity', 3);

        Sanctum::actingAs($member);
        $this->postJson('/api/v1/inventory', [
            'name' => 'Tenda Lain',
            'category' => InventoryCategory::Tenda->value,
            'quantity' => 1,
            'condition' => InventoryCondition::Baik->value,
        ])->assertForbidden();
    }

    public function test_reducing_quantity_below_what_is_on_loan_is_rejected()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $borrower = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 5]);

        InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $borrower)->id,
            'quantity' => 3,
            'status' => InventoryLoanStatus::Borrowed,
        ]);

        Sanctum::actingAs($chair);

        $this->patchJson("/api/v1/inventory/{$item->id}", [
            'name' => $item->name,
            'category' => $item->category->value,
            'quantity' => 2,
            'condition' => $item->condition->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_deleting_an_item_with_an_active_loan_is_blocked()
    {
        $organization = Organization::factory()->create();
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $borrower = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 5]);

        InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $borrower)->id,
            'quantity' => 1,
            'status' => InventoryLoanStatus::Borrowed,
        ]);

        Sanctum::actingAs($chair);

        $this->deleteJson("/api/v1/inventory/{$item->id}")->assertStatus(422);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
    }

    public function test_any_member_can_borrow_within_availability()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/inventory/{$item->id}/loans", [
            'quantity' => 2,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertCreated()->assertJsonPath('data.quantity', 2);

        $this->assertSame(1, $item->fresh()->availableQuantity());
    }

    public function test_borrowing_more_than_available_is_rejected()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 2]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/inventory/{$item->id}/loans", [
            'quantity' => 3,
            'due_date' => now()->addWeek()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_the_borrower_can_return_their_own_loan()
    {
        $organization = Organization::factory()->create();
        $borrower = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        $loan = InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $borrower)->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($borrower);

        $this->postJson("/api/v1/inventory/loans/{$loan->id}/return", [
            'quantity' => 1,
            'condition' => InventoryCondition::Baik->value,
        ])->assertOk()->assertJsonPath('data.status', InventoryLoanStatus::Returned->value);

        $this->assertSame(3, $item->fresh()->availableQuantity());
    }

    public function test_a_different_member_cannot_return_someone_elses_loan()
    {
        $organization = Organization::factory()->create();
        $borrower = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        $loan = InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $borrower)->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($other);

        $this->postJson("/api/v1/inventory/loans/{$loan->id}/return", [
            'quantity' => 1,
            'condition' => InventoryCondition::Baik->value,
        ])->assertForbidden();
    }

    public function test_returning_with_a_dropped_condition_requires_a_note()
    {
        $organization = Organization::factory()->create();
        $borrower = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3, 'condition' => InventoryCondition::Baik]);

        $loan = InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $borrower)->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($borrower);

        $this->postJson("/api/v1/inventory/loans/{$loan->id}/return", [
            'quantity' => 1,
            'condition' => InventoryCondition::Rusak->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('note');

        $this->postJson("/api/v1/inventory/loans/{$loan->id}/return", [
            'quantity' => 1,
            'condition' => InventoryCondition::Rusak->value,
            'note' => 'Kaki tenda patah saat dipakai.',
        ])->assertOk();

        $this->assertSame(InventoryCondition::Rusak, $item->fresh()->condition);
    }

    public function test_item_detail_carries_its_loans()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $member)->id,
            'quantity' => 1,
            'status' => InventoryLoanStatus::Borrowed,
        ]);

        Sanctum::actingAs($member);

        $this->getJson("/api/v1/inventory/{$item->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.loans')
            ->assertJsonPath('data.loans.0.borrower.name', $member->name);
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_item()
    {
        $organization = Organization::factory()->create();
        $item = InventoryItem::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/inventory/{$item->id}")->assertForbidden();
    }
}
