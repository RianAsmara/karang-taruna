<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialTransactionAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_treasurer_can_upload_evidence_to_a_transaction()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/transactions/{$transaction->id}/attachments", [
                'file' => UploadedFile::fake()->create('kwitansi.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('financial_transaction_attachments', [
            'financial_transaction_id' => $transaction->id,
            'original_name' => 'kwitansi.pdf',
            'uploaded_by' => $treasurer->id,
        ]);

        $attachment = FinancialTransactionAttachment::firstWhere('financial_transaction_id', $transaction->id);
        Storage::disk($attachment->disk)->assertExists($attachment->path);
    }

    public function test_a_plain_member_cannot_upload_evidence()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($member)
            ->post("/finance/transactions/{$transaction->id}/attachments", [
                'file' => UploadedFile::fake()->create('kwitansi.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_evidence_upload_is_allowed_even_after_the_transaction_is_approved()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/transactions/{$transaction->id}/attachments", [
                'file' => UploadedFile::fake()->create('kwitansi.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('financial_transaction_attachments', 1);
    }

    public function test_an_oversized_file_is_rejected()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->post("/finance/transactions/{$transaction->id}/attachments", [
                'file' => UploadedFile::fake()->create('kwitansi.pdf', 6000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_treasurer_can_delete_an_attachment_and_the_file_is_removed()
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $attachment = FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $transaction->id,
            'uploaded_by' => $treasurer->id,
            'disk' => 'public',
        ]);
        Storage::disk('public')->put($attachment->path, 'fake-content');

        $this->actingAs($treasurer)
            ->delete("/finance/transactions/{$transaction->id}/attachments/{$attachment->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('financial_transaction_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->path);
    }

    public function test_a_plain_member_cannot_delete_an_attachment()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $attachment = FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $transaction->id,
            'uploaded_by' => $treasurer->id,
        ]);

        $this->actingAs($member)
            ->delete("/finance/transactions/{$transaction->id}/attachments/{$attachment->id}")
            ->assertForbidden();
    }

    public function test_an_attachment_from_another_transaction_returns_not_found()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $otherTransaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $attachment = FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $otherTransaction->id,
            'uploaded_by' => $treasurer->id,
        ]);

        $this->actingAs($treasurer)
            ->delete("/finance/transactions/{$transaction->id}/attachments/{$attachment->id}")
            ->assertNotFound();
    }

    public function test_a_member_can_download_evidence_of_an_approved_transaction()
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $attachment = FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $transaction->id,
            'uploaded_by' => $treasurer->id,
            'disk' => 'public',
            'path' => 'financial-transaction-attachments/test/kwitansi.pdf',
        ]);
        Storage::disk('public')->put($attachment->path, 'fake-content');

        $this->actingAs($member)
            ->get("/finance/transactions/{$transaction->id}/attachments/{$attachment->id}/download")
            ->assertOk();
    }
}
