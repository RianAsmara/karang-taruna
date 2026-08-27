<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Enums\OrganizationRole;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($member)
            ->get('/documents')
            ->assertInertia(fn ($page) => $page->has('documents', 1)->where('canCreate', false));
    }

    public function test_secretary_can_upload_a_notulen_document()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $secretary = $this->memberWithRole($organization, OrganizationRole::Sekretaris);

        $this->actingAs($secretary)->get('/documents/create')->assertOk();

        $this->actingAs($secretary)
            ->post('/documents', [
                'title' => 'Notulen Rapat 12 Agustus',
                'category' => DocumentCategory::Notulen->value,
                'file' => UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $document = Document::first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentCategory::Notulen, $document->category);
        Storage::disk($document->disk)->assertExists($document->path);
    }

    public function test_a_plain_member_cannot_upload_a_document()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)->get('/documents/create')->assertForbidden();

        $this->actingAs($member)
            ->post('/documents', [
                'title' => 'Proposal Kegiatan',
                'category' => DocumentCategory::Proposal->value,
                'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_treasurer_can_upload_a_financial_document_but_not_other_categories()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $treasurer = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($treasurer)->get('/documents/create')->assertOk();

        $this->actingAs($treasurer)
            ->post('/documents', [
                'title' => 'Laporan Kas Agustus',
                'category' => DocumentCategory::Laporan->value,
                'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($treasurer)
            ->post('/documents', [
                'title' => 'Proposal Kegiatan',
                'category' => DocumentCategory::Proposal->value,
                'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
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

        $this->actingAs($secretary)
            ->delete("/documents/{$document->id}")
            ->assertRedirect();

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

        $this->actingAs($other)
            ->delete("/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_a_pengurus_can_delete_someone_elses_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $uploader = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $chair = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $document = Document::factory()->for($organization)->create(['disk' => 'local', 'uploaded_by' => $uploader->id]);

        $this->actingAs($chair)
            ->delete("/documents/{$document->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_the_index_shows_delete_permission_per_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $uploader = $this->memberWithRole($organization, OrganizationRole::Sekretaris);
        $other = $this->memberWithRole($organization, OrganizationRole::Anggota);
        Document::factory()->for($organization)->create(['disk' => 'local', 'uploaded_by' => $uploader->id]);

        $this->actingAs($other)
            ->get('/documents')
            ->assertInertia(fn ($page) => $page->where('documents.0.canDelete', false));

        $this->actingAs($uploader)
            ->get('/documents')
            ->assertInertia(fn ($page) => $page->where('documents.0.canDelete', true));
    }

    public function test_a_member_from_another_organization_cannot_download_this_organizations_document()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $document = Document::factory()->for($organization)->create(['disk' => 'local']);
        Storage::disk('local')->put($document->path, 'fake contents');

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        $this->actingAs($outsider)
            ->get("/documents/{$document->id}/download")
            ->assertForbidden();
    }
}
