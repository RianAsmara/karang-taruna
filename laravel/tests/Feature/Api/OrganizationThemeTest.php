<?php

namespace Tests\Feature\Api;

use App\Actions\Theme\AttachOrganizationLogoAction;
use App\Enums\OrganizationRole;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationThemeTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    /**
     * Direct model-layer setup for tests that need an existing theme but
     * aren't exercising the upload endpoint itself — deliberately bypasses
     * HTTP/session auth so it never interferes with a *different* user's
     * auth (session vs bearer token) later in the same test.
     */
    private function seedTheme(Organization $organization): void
    {
        app(AttachOrganizationLogoAction::class)->handle(
            $organization,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );
    }

    public function test_a_ketua_can_attach_a_logo_and_it_creates_a_theme_with_the_default_color()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $this->actingAs($ketua)->post('organisasi/tema/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 600, 600),
            'source' => UploadedFile::fake()->image('logo.png', 600, 600),
        ])->assertOk();

        $this->assertNotNull($organization->fresh()->theme);
        $this->assertSame('#ec3013', $organization->fresh()->theme->primary_hex);
    }

    public function test_a_bendahara_cannot_attach_a_logo()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $bendahara = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($bendahara)->post('organisasi/tema/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 600, 600),
            'source' => UploadedFile::fake()->image('logo.png', 600, 600),
        ])->assertForbidden();
    }

    public function test_applying_a_color_before_any_logo_is_attached_fails_with_a_clear_reason()
    {
        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $token = $ketua->createToken('test')->plainTextToken;
        $response = $this->withToken($token)->putJson('/api/v1/organizations/current/theme', ['primary' => '#1E5B3B']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('primary');
    }

    public function test_a_ketua_can_apply_a_passing_color_via_the_api_and_it_writes_an_audit_log()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->seedTheme($organization);

        $token = $ketua->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->putJson('/api/v1/organizations/current/theme', [
            'primary' => '#1E5B3B',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.primary', '#1E5B3B');
        $response->assertJsonPath('data.color.light.accent', '#1e5b3b');

        $log = AuditLog::where('organization_id', $organization->id)
            ->where('action', 'organization.theme_applied')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($ketua->id, $log->actor_id);
        $this->assertSame('#ec3013', $log->previous_values['primary']);
        $this->assertSame('#1E5B3B', $log->new_values['primary']);
    }

    public function test_a_bendahara_with_a_real_bearer_token_cannot_apply_a_color()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->seedTheme($organization);

        $bendahara = $this->memberWithRole($organization, OrganizationRole::Bendahara);
        $token = $bendahara->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/organizations/current/theme', ['primary' => '#1E5B3B'])
            ->assertForbidden();
    }

    public function test_a_color_that_fails_a_guard_is_rejected_with_the_failing_guard_and_a_suggestion()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->seedTheme($organization);

        $token = $ketua->createToken('test')->plainTextToken;
        $response = $this->withToken($token)->putJson('/api/v1/organizations/current/theme', ['primary' => '#888888']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['primary', 'primary_failing_guards', 'primary_nearest_passing']);
    }

    public function test_the_theme_response_carries_only_the_six_tokens_and_logo_keys_never_anything_else()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->seedTheme($organization);

        $token = $ketua->createToken('test')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/v1/organizations/current/theme')->assertOk();

        $response->assertJsonStructure([
            'data' => ['version', 'updatedAt', 'name', 'primary', 'logo' => ['mark', 'icon', 'mono'], 'color' => ['light', 'dark']],
        ]);
        $this->assertSame(
            ['accent', 'accent200', 'accent700', 'accent800', 'onAccent', 'onInk'],
            array_keys($response->json('data.color.light')),
        );
        $this->assertSame(
            ['accent', 'accent200', 'accent700', 'accent800', 'onAccent', 'onInk'],
            array_keys($response->json('data.color.dark')),
        );
    }

    public function test_an_organization_with_no_theme_returns_404()
    {
        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $token = $ketua->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/organizations/current/theme')->assertNotFound();
    }

    public function test_a_ketua_who_is_also_superadmin_cannot_apply_a_color_via_the_api()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $this->seedTheme($organization);
        $ketua->forceFill(['is_superadmin' => true])->save();

        $token = $ketua->createToken('test')->plainTextToken;
        $this->withToken($token)
            ->putJson('/api/v1/organizations/current/theme', ['primary' => '#1E5B3B'])
            ->assertForbidden();
    }
}
