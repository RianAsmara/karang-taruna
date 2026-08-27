<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_any_member_can_upload_a_jpg()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $response = $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertCreated();

        $response->assertJsonPath('data.originalName', 'foto.jpg');
        $response->assertJsonPath('data.mimeType', 'image/jpeg');
        $this->assertStringContainsString('/api/v1/uploads/', $response->json('data.url'));

        $upload = Upload::first();
        Storage::disk($upload->disk)->assertExists($upload->path);
    }

    public function test_a_pdf_can_be_uploaded()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.mimeType', 'application/pdf');
    }

    public function test_an_unsupported_file_type_is_rejected()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        Sanctum::actingAs($member);

        $this->postJson('/api/v1/uploads', [
            'file' => UploadedFile::fake()->create('video.mp4', 100, 'video/mp4'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_a_member_can_retrieve_and_preview_their_organizations_upload()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $upload = Upload::factory()->for($organization)->create(['disk' => 'local', 'mime_type' => 'image/jpeg']);
        Storage::disk('local')->put($upload->path, 'fake image bytes');

        Sanctum::actingAs($member);

        $response = $this->get("/api/v1/uploads/{$upload->id}");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringNotContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    }

    /**
     * Upload-time validation already constrains mime_type to the
     * previewable allowlist, so this row shouldn't occur in practice —
     * but the retrieval endpoint must never trust a stored mime_type
     * blindly. Simulates a row somehow carrying an unexpected type.
     */
    public function test_an_unexpected_stored_mime_type_is_never_served_inline()
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);
        $upload = Upload::factory()->for($organization)->create(['disk' => 'local', 'mime_type' => 'text/html']);
        Storage::disk('local')->put($upload->path, '<script>alert(1)</script>');

        Sanctum::actingAs($member);

        $response = $this->get("/api/v1/uploads/{$upload->id}");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_a_member_from_another_organization_cannot_retrieve_the_upload()
    {
        $organization = Organization::factory()->create();
        $upload = Upload::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        $outsider = $this->memberWithRole($otherOrganization, OrganizationRole::Ketua);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/uploads/{$upload->id}")->assertForbidden();
    }
}
