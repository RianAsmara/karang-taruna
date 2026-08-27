<?php

namespace Tests\Feature;

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

        $this->actingAs($member)
            ->get('/inventory')
            ->assertInertia(fn ($page) => $page->has('items', 1)->where('canCreate', false));
    }

    public function test_pengurus_can_add_an_item_but_a_plain_member_cannot()
    {
        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($secretary)->get('/inventory/create')->assertOk();
        $this->actingAs($member)->get('/inventory/create')->assertForbidden();

        $this->actingAs($secretary)
            ->post('/inventory', [
                'name' => 'Tenda Pleton',
                'category' => InventoryCategory::Tenda->value,
                'quantity' => 3,
                'condition' => InventoryCondition::Baik->value,
            ])
            ->assertRedirect();

        $item = InventoryItem::firstWhere('name', 'Tenda Pleton');
        $this->assertNotNull($item);
        $this->assertSame(3, $item->availableQuantity());

        $this->actingAs($member)
            ->post('/inventory', [
                'name' => 'Tenda Lain',
                'category' => InventoryCategory::Tenda->value,
                'quantity' => 1,
                'condition' => InventoryCondition::Baik->value,
            ])
            ->assertForbidden();
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

        $this->actingAs($chair)
            ->patch("/inventory/{$item->id}", [
                'name' => $item->name,
                'category' => $item->category->value,
                'quantity' => 2,
                'condition' => $item->condition->value,
            ])
            ->assertSessionHasErrors('quantity');

        $this->actingAs($chair)->get("/inventory/{$item->id}/edit")->assertOk();
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

        $this->actingAs($chair)
            ->delete("/inventory/{$item->id}")
            ->assertSessionHasErrors('inventoryItem');

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
    }

    public function test_a_plain_member_cannot_delete_an_item()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create();

        $this->actingAs($member)
            ->delete("/inventory/{$item->id}")
            ->assertForbidden();
    }

    public function test_any_member_can_borrow_within_availability()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        $this->actingAs($member)
            ->post("/inventory/{$item->id}/loans", [
                'quantity' => 2,
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(1, $item->fresh()->availableQuantity());
    }

    public function test_borrowing_more_than_available_is_rejected()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 2]);

        $this->actingAs($member)
            ->post("/inventory/{$item->id}/loans", [
                'quantity' => 3,
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertSessionHasErrors('quantity');
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

        $this->actingAs($borrower)
            ->post("/inventory/loans/{$loan->id}/return", [
                'quantity' => 1,
                'condition' => InventoryCondition::Baik->value,
            ])
            ->assertRedirect();

        $this->assertSame(InventoryLoanStatus::Returned, $loan->fresh()->status);
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

        $this->actingAs($other)
            ->post("/inventory/loans/{$loan->id}/return", [
                'quantity' => 1,
                'condition' => InventoryCondition::Baik->value,
            ])
            ->assertForbidden();
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

        $this->actingAs($borrower)
            ->post("/inventory/loans/{$loan->id}/return", [
                'quantity' => 1,
                'condition' => InventoryCondition::Rusak->value,
            ])
            ->assertSessionHasErrors('note');

        $this->actingAs($borrower)
            ->post("/inventory/loans/{$loan->id}/return", [
                'quantity' => 1,
                'condition' => InventoryCondition::Rusak->value,
                'note' => 'Kaki tenda patah saat dipakai.',
            ])
            ->assertRedirect();

        $this->assertSame(InventoryCondition::Rusak, $item->fresh()->condition);
    }

    public function test_item_detail_carries_its_loans_and_the_viewers_active_loan_id()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $item = InventoryItem::factory()->for($organization)->create(['quantity' => 3]);

        $loan = InventoryLoan::factory()->create([
            'organization_id' => $organization->id,
            'inventory_item_id' => $item->id,
            'borrower_membership_id' => $this->membershipOf($organization, $member)->id,
            'quantity' => 1,
            'status' => InventoryLoanStatus::Borrowed,
        ]);

        $this->actingAs($member)
            ->get("/inventory/{$item->id}")
            ->assertInertia(fn ($page) => $page
                ->has('loans', 1)
                ->where('loans.0.borrowerName', $member->name)
                ->where('myActiveLoanId', $loan->id)
            );
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_item()
    {
        $organization = Organization::factory()->create();
        $item = InventoryItem::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $this->actingAs($outsider)
            ->get("/inventory/{$item->id}")
            ->assertForbidden();
    }
}
