<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Flashed messages were being set by five different places and shared with
 * none of them — the user was silently redirected with no explanation. These
 * pin the plumbing so a `->with('error'|'success')` always reaches the page.
 */
class FlashMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_flashed_error_reaches_the_page()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $chair = User::factory()->create();
        $organization->memberships()->create(['user_id' => $chair->id, 'role' => OrganizationRole::Ketua]);

        $invite = OrganizationInvite::create([
            'organization_id' => $organization->id,
            'token' => OrganizationInvite::generateToken(),
            'created_by' => $chair->id,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get("/join/{$invite->token}")
            ->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('flash.error', 'Tautan undangan ini sudah tidak berlaku. Minta tautan baru dari ketua.'));
    }

    public function test_a_flashed_success_reaches_the_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['success' => 'Kehadiran Anda tercatat.'])
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('flash.success', 'Kehadiran Anda tercatat.'));
    }

    public function test_flash_is_null_when_nothing_was_flashed()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.error', null)
                ->where('flash.success', null));
    }
}
