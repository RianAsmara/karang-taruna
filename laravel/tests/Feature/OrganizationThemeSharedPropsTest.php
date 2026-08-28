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

/**
 * The org theme (logo + brand color) is shared on every authenticated
 * page via HandleInertiaRequests, not just the Theme Builder page itself
 * — that's what lets the sidebar logo and primary-color CSS variables
 * apply across the whole app, not only inside the builder's own preview.
 */
class OrganizationThemeSharedPropsTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithRole(Organization $organization, OrganizationRole $role): User
    {
        $user = User::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_organization_theme_is_null_when_no_theme_is_set()
    {
        $organization = Organization::factory()->create();
        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('organizationTheme', null));
    }

    public function test_organization_theme_is_shared_once_set()
    {
        Storage::fake(config('filesystems.default'));

        $organization = Organization::factory()->create();
        $ketua = $this->memberWithRole($organization, OrganizationRole::Ketua);
        app(AttachOrganizationLogoAction::class)->handle(
            $organization,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );
        app(ApplyOrganizationThemeAction::class)->handle($organization->fresh(), $ketua, '#1E5B3B');

        $member = $this->memberWithRole($organization, OrganizationRole::Anggota);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('organizationTheme.primary', '#1E5B3B')
                ->has('organizationTheme.logo.mark')
                ->has('organizationTheme.color.light.accent')
                ->has('organizationTheme.color.dark.accent')
            );
    }

    public function test_a_members_own_organization_theme_never_leaks_from_another_organization()
    {
        Storage::fake(config('filesystems.default'));

        $themedOrg = Organization::factory()->create();
        $themedKetua = $this->memberWithRole($themedOrg, OrganizationRole::Ketua);
        app(AttachOrganizationLogoAction::class)->handle(
            $themedOrg,
            UploadedFile::fake()->image('logo.png', 600, 600),
            UploadedFile::fake()->image('logo.png', 600, 600),
        );
        app(ApplyOrganizationThemeAction::class)->handle($themedOrg->fresh(), $themedKetua, '#1E5B3B');

        $untouchedOrg = Organization::factory()->create();
        $member = $this->memberWithRole($untouchedOrg, OrganizationRole::Anggota);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('organizationTheme', null));
    }
}
