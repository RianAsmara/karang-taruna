<?php

namespace Tests\Feature;

use App\Actions\Theme\ApplyOrganizationThemeAction;
use App\Actions\Theme\AttachOrganizationLogoAction;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_a_bendahara_cannot_open_the_theme_builder_page()
    {
        $organization = Organization::factory()->create();
        $bendahara = $this->memberWithRole($organization, OrganizationRole::Bendahara);

        $this->actingAs($bendahara)->get('organisasi/tema')->assertForbidden();
    }

    public function test_a_ketua_sees_the_empty_state_when_no_theme_is_set()
    {
        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $response = $this->actingAs($ketua)->get('organisasi/tema');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('organization/theme')
            ->where('theme', null)
            ->where('organizationName', $organization->name),
        );
    }

    public function test_logo_upload_rejects_a_jpeg_with_the_transparency_reason()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $response = $this->actingAs($ketua)->postJson('organisasi/tema/logo', [
            'logo' => UploadedFile::fake()->image('logo.jpg', 600, 600),
            'source' => UploadedFile::fake()->image('logo.jpg', 600, 600),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['logo' => ['Format tidak didukung. Gunakan PNG atau SVG — JPEG tidak mendukung transparansi.']]);
    }

    public function test_logo_upload_rejects_an_undersized_image()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        $response = $this->actingAs($ketua)->postJson('organisasi/tema/logo', [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 100),
            'source' => UploadedFile::fake()->image('logo.png', 100, 100),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['logo' => ['Logo terlalu kecil. Ukuran minimal 512×512 piksel.']]);
    }

    public function test_a_ketua_can_apply_a_color_and_the_page_reflects_it_afterward()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);

        app(AttachOrganizationLogoAction::class)->handle(
            $organization,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );

        $this->actingAs($ketua)
            ->patch('organisasi/tema', ['primary' => '#1E5B3B'])
            ->assertRedirect();

        $response = $this->actingAs($ketua)->get('organisasi/tema');

        $response->assertInertia(fn ($page) => $page
            ->component('organization/theme')
            ->where('theme.primary', '#1E5B3B')
            ->has('history', 1),
        );
    }

    public function test_a_ketua_who_is_also_superadmin_still_cannot_open_the_theme_builder()
    {
        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        $ketua->forceFill(['is_superadmin' => true])->save();

        $this->actingAs($ketua)->get('organisasi/tema')->assertForbidden();
    }

    public function test_a_ketua_who_is_also_superadmin_cannot_apply_a_color()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        app(AttachOrganizationLogoAction::class)->handle(
            $organization,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );
        $ketua->forceFill(['is_superadmin' => true])->save();

        $this->actingAs($ketua)
            ->patch('organisasi/tema', ['primary' => '#1E5B3B'])
            ->assertForbidden();
    }

    public function test_a_ketua_of_one_organization_can_never_reach_another_organizations_theme()
    {
        Storage::fake(config('filesystems.default'));

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $ketuaA = $this->memberWithRole($orgA, OrganizationRole::Ketua);
        $this->memberWithRole($orgB, OrganizationRole::Ketua);

        app(AttachOrganizationLogoAction::class)->handle(
            $orgB,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );
        // Re-fetch: $orgB's `theme` relation was already cached as null by
        // the attach call above, and directly chaining two Actions on the
        // same in-memory instance (unlike two real HTTP requests, which
        // each resolve a fresh Organization) would otherwise see that stale
        // cache instead of the row that now exists.
        app(ApplyOrganizationThemeAction::class)->handle($orgB->fresh(), $orgB->memberships()->first()->user, '#1E5B3B');

        // Every route resolves the org from ketuaA's own membership only
        // (current-org middleware) — there is no org id in the URL for
        // ketuaA to even attempt to target org B with.
        $response = $this->actingAs($ketuaA)->get('organisasi/tema');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('organization/theme')
            ->where('organizationName', $orgA->name)
            ->where('theme', null),
        );
    }
}
