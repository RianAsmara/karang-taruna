<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrganizationInviteTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $chair;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->chair = User::factory()->create();
        $this->organization->memberships()->create(['user_id' => $this->chair->id, 'role' => OrganizationRole::Ketua]);
    }

    public function test_the_chair_can_create_an_invite_link()
    {
        $this->actingAs($this->chair)
            ->post('/organizations/invites', [])
            ->assertRedirect();

        $invite = OrganizationInvite::firstWhere('organization_id', $this->organization->id);

        $this->assertNotNull($invite);
        $this->assertNotEmpty($invite->token);
        $this->assertTrue($invite->isActive());
    }

    public function test_an_ordinary_member_cannot_create_an_invite_link()
    {
        $member = User::factory()->create();
        $this->organization->memberships()->create(['user_id' => $member->id, 'role' => OrganizationRole::Anggota]);

        $this->actingAs($member)->post('/organizations/invites', [])->assertForbidden();

        $this->assertSame(0, OrganizationInvite::count());
    }

    public function test_accepting_an_invite_joins_the_organization_as_anggota()
    {
        $invite = $this->makeInvite();
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)
            ->get("/join/{$invite->token}")
            ->assertRedirect('/dashboard');

        $this->assertSame(OrganizationRole::Anggota, $newcomer->fresh()->roleIn($this->organization));
        $this->assertSame(1, $invite->fresh()->uses);
        // Joining makes it the active organization — landing anywhere else
        // right after accepting an invite would be a strange result.
        $this->assertSame($this->organization->id, $newcomer->fresh()->active_organization_id);
    }

    public function test_an_invite_never_grants_a_privileged_role()
    {
        $invite = $this->makeInvite();
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get("/join/{$invite->token}");

        $this->assertSame(OrganizationRole::Anggota, $newcomer->fresh()->roleIn($this->organization));
    }

    public function test_an_expired_invite_is_refused()
    {
        $invite = $this->makeInvite(['expires_at' => Carbon::now()->subDay()]);
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get("/join/{$invite->token}")->assertRedirect('/dashboard');

        $this->assertNull($newcomer->fresh()->roleIn($this->organization));
    }

    public function test_a_revoked_invite_is_refused()
    {
        $invite = $this->makeInvite(['revoked_at' => Carbon::now()]);
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get("/join/{$invite->token}")->assertRedirect('/dashboard');

        $this->assertNull($newcomer->fresh()->roleIn($this->organization));
    }

    public function test_an_invite_past_its_use_limit_is_refused()
    {
        $invite = $this->makeInvite(['max_uses' => 1, 'uses' => 1]);
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get("/join/{$invite->token}")->assertRedirect('/dashboard');

        $this->assertNull($newcomer->fresh()->roleIn($this->organization));
    }

    public function test_an_unknown_token_is_refused()
    {
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get('/join/tidak-ada-token-seperti-ini')->assertNotFound();
    }

    public function test_an_existing_member_accepting_again_is_not_duplicated()
    {
        $invite = $this->makeInvite();

        $this->actingAs($this->chair)->get("/join/{$invite->token}")->assertRedirect();

        // Still exactly one membership, and the chair was not demoted to ANGGOTA.
        $this->assertSame(1, $this->organization->memberships()->where('user_id', $this->chair->id)->count());
        $this->assertSame(OrganizationRole::Ketua, $this->chair->fresh()->roleIn($this->organization));
    }

    public function test_a_guest_is_sent_to_login_and_joins_after_authenticating()
    {
        $invite = $this->makeInvite();

        $this->get("/join/{$invite->token}")->assertRedirect('/login');
    }

    public function test_the_chair_can_revoke_an_invite()
    {
        $invite = $this->makeInvite();

        $this->actingAs($this->chair)
            ->delete("/organizations/invites/{$invite->id}")
            ->assertRedirect();

        $this->assertNotNull($invite->fresh()->revoked_at);
        $this->assertFalse($invite->fresh()->isActive());
    }

    public function test_a_chair_of_another_organization_cannot_revoke_this_ones_invite()
    {
        $invite = $this->makeInvite();

        $otherOrganization = Organization::factory()->create();
        $outsideChair = User::factory()->create();
        $otherOrganization->memberships()->create(['user_id' => $outsideChair->id, 'role' => OrganizationRole::Ketua]);

        $this->actingAs($outsideChair)
            ->delete("/organizations/invites/{$invite->id}")
            ->assertForbidden();

        $this->assertNull($invite->fresh()->revoked_at);
    }

    public function test_a_brand_new_user_who_registers_from_an_invite_link_lands_back_on_it()
    {
        // The commonest invite path by far: someone with no account taps the
        // link, is bounced to login, taps "Daftar", and registers. If
        // registration ignores the intended URL they land on the dashboard
        // having never joined — the whole feature silently fails for exactly
        // the people it exists to onboard.
        $invite = $this->makeInvite();

        $this->get("/join/{$invite->token}")->assertRedirect('/login');

        $this->post('/register', [
            'name' => 'Anggota Baru',
            'email' => 'baru@contoh.test',
            'password' => 'kata-sandi-rahasia',
            'password_confirmation' => 'kata-sandi-rahasia',
        ])->assertRedirect("/join/{$invite->token}");
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeInvite(array $attributes = []): OrganizationInvite
    {
        return OrganizationInvite::create(array_merge([
            'organization_id' => $this->organization->id,
            'token' => OrganizationInvite::generateToken(),
            'created_by' => $this->chair->id,
            'expires_at' => Carbon::now()->addDays(7),
        ], $attributes));
    }
}
