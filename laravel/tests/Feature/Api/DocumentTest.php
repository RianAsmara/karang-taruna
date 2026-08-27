<?php

namespace Tests\Feature\Api;

use App\Enums\DocumentCategory;
use App\Enums\OrganizationRole;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_any_member_can_list_documents()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        Document::factory()->for($organization)->create();

        Sanctum::actingAs($member);

        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_secretary_can_upload_a_notulen_document()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);

        Sanctum::actingAs($secretary);

        $this->postJson('/api/v1/documents', [
            'title' => 'Notulen Rapat 12 Agustus',
            'category' => DocumentCategory::Notulen->value,
            'file' => UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.category', 'NOTULEN');

        $document = Document::first();
        Storage::disk($document->disk)->assertExists($document->path);
    }

    public function test_a_plain_member_cannot_upload_a_document()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/documents', [
            'title' => 'Proposal Kegiatan',
            'category' => DocumentCategory::Proposal->value,
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_treasurer_can_upload_a_financial_document_but_not_other_categories()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        Sanctum::actingAs($treasurer);

        $this->postJson('/api/v1/documents', [
            'title' => 'Laporan Kas Agustus',
            'category' => DocumentCategory::Laporan->value,
            'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $this->postJson('/api/v1/documents', [
            'title' => 'Proposal Kegiatan',
            'category' => DocumentCategory::Proposal->value,
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_the_uploader_can_delete_their_own_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $document = Document::factory()->for($organization)->create([
            'disk' => 'local',
            'uploaded_by' => $secretary->id,
        ]);
        Storage::disk('local')->put($document->path, 'fake contents');

        Sanctum::actingAs($secretary);

        $this->deleteJson("/api/v1/documents/{$document->id}")->assertNoContent();
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_a_different_plain_member_cannot_delete_someone_elses_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $uploader = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $document = Document::factory()->for($organization)->create(['disk' => 'local', 'uploaded_by' => $uploader->id]);

        Sanctum::actingAs($other);

        $this->deleteJson("/api/v1/documents/{$document->id}")->assertForbidden();
    }

    public function test_a_pengurus_can_delete_someone_elses_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $uploader = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $document = Document::factory()->for($organization)->create(['disk' => 'local', 'uploaded_by' => $uploader->id]);

        Sanctum::actingAs($chair);

        $this->deleteJson("/api/v1/documents/{$document->id}")->assertNoContent();
    }

    public function test_a_member_from_another_organization_cannot_view_this_organizations_document()
    {
        $organization = Organization::factory()->create();
        $document = Document::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/documents/{$document->id}")->assertForbidden();
    }
}
