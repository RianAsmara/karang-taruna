<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
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

    public function test_treasurer_can_upload_evidence_via_the_api()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);

        $this->postJson("/api/v1/finance/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('kwitansi.pdf', 100, 'application/pdf'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.originalName', 'kwitansi.pdf');

        $this->assertDatabaseHas('financial_transaction_attachments', [
            'financial_transaction_id' => $transaction->id,
            'original_name' => 'kwitansi.pdf',
        ]);
    }

    public function test_a_plain_member_cannot_upload_evidence_via_the_api()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/v1/finance/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('kwitansi.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_treasurer_can_delete_an_attachment_via_the_api()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        $attachment = FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $transaction->id,
            'uploaded_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);

        $this->deleteJson("/api/v1/finance/transactions/{$transaction->id}/attachments/{$attachment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('financial_transaction_attachments', ['id' => $attachment->id]);
    }

    public function test_show_includes_attachments_and_has_evidence_flag()
    {
        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
        $transaction = FinancialTransaction::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $treasurer->id,
        ]);
        FinancialTransactionAttachment::factory()->create([
            'financial_transaction_id' => $transaction->id,
            'uploaded_by' => $treasurer->id,
        ]);

        Sanctum::actingAs($treasurer);

        $this->getJson("/api/v1/finance/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.attachments');

        $this->getJson('/api/v1/finance/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.hasEvidence', true);
    }

    public function test_a_member_can_download_evidence_via_the_api()
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Member);
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Treasurer);
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

        Sanctum::actingAs($member);

        $this->get("/api/v1/finance/transactions/{$transaction->id}/attachments/{$attachment->id}/download")
            ->assertOk();
    }
}
