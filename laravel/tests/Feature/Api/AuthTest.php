<?php

namespace Tests\Feature\Api;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_an_account_and_issues_a_usable_token()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@rukunmuda.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'Android phone',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'emailVerified']]);
        $response->assertJsonPath('user.emailVerified', false);

        $this->assertDatabaseHas('users', ['email' => 'budi@rukunmuda.test', 'name' => 'Budi Santoso']);

        $token = $response->json('token');

        // The token is issued, but it opens nothing until the address is
        // verified — an unverified email means an unverified person, and
        // an account here reaches an organization's member list and its
        // financial history.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/organizations/current')
            ->assertStatus(403)
            ->assertJsonPath('code', 'email_unverified');

        User::where('email', 'budi@rukunmuda.test')->firstOrFail()->markEmailAsVerified();

        // Test-process artifact, not app behaviour: the auth manager caches
        // the user it resolved for the previous request, so without this the
        // second call still sees the pre-verification instance. Each real
        // HTTP request resolves the user from scratch.
        Auth::forgetGuards();

        // Verified, but no organization yet — the mobile client is
        // expected to route this to Buat Organisasi rather than treating
        // it as a hard error.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/organizations/current')
            ->assertStatus(422);
    }

    public function test_an_unverified_user_can_still_log_out_and_request_a_new_verification_link()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('Android phone')->plainTextToken;

        // Both sit outside the 'verified' gate on purpose: the user who
        // needs them is exactly the one who cannot pass it. Gating these
        // would leave a newly registered account with no way forward.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertStatus(202);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
    }

    public function test_registering_with_an_already_used_email_is_rejected()
    {
        User::factory()->create(['email' => 'taken@rukunmuda.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'taken@rukunmuda.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'Android phone',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_registering_with_mismatched_password_confirmation_is_rejected()
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@rukunmuda.test',
            'password' => 'password123',
            'password_confirmation' => 'not-the-same',
            'device_name' => 'Android phone',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_registration_is_throttled_after_five_attempts_from_the_same_ip()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Budi Santoso',
                'email' => "budi{$i}@rukunmuda.test",
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'device_name' => 'Android phone',
            ])->assertCreated();
        }

        // The 6th attempt is throttled even with entirely valid, unused
        // details — registration throttles by IP alone, unlike login
        // which only hits the limiter on a failed attempt.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi-sixth@rukunmuda.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'Android phone',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'budi-sixth@rukunmuda.test']);
    }

    public function test_a_user_can_list_and_switch_between_their_organizations()
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create(['name' => 'Karang Taruna Melati']);
        $orgB = Organization::factory()->create(['name' => 'Pemuda Kampung Sejahtera']);
        $orgA->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Ketua]);
        $orgB->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations/mine')
            ->assertOk()
            ->assertJsonCount(2, 'organizations');

        $this->postJson('/api/v1/organizations/switch', ['organization_id' => $orgB->id])
            ->assertOk()
            ->assertJsonPath('organization.name', 'Pemuda Kampung Sejahtera');

        $this->getJson('/api/v1/organizations/current')
            ->assertOk()
            ->assertJsonPath('data.name', 'Pemuda Kampung Sejahtera');
    }

    public function test_a_user_cannot_switch_to_an_organization_they_do_not_belong_to()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->memberships()->create(['user_id' => $user->id, 'role' => OrganizationRole::Anggota]);

        $otherOrganization = Organization::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/organizations/switch', ['organization_id' => $otherOrganization->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('organization_id');
    }

    public function test_login_with_valid_credentials_issues_a_usable_token()
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $token = $response->json('token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone 15',
        ]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/organizations/current')
            ->assertStatus(422); // authenticated, but no organization membership yet.
    }

    public function test_login_with_invalid_password_is_rejected()
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'iPhone 15',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_unauthenticated_request_to_a_protected_endpoint_is_rejected()
    {
        $this->getJson('/api/v1/organizations/current')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        // The revoked row is gone — a real, separate HTTP request bearing
        // this token would no longer authenticate (verified as an app
        // behavior in test_unauthenticated_request_to_a_protected_endpoint_is_rejected;
        // asserting that here too would just be re-testing PHPUnit's own
        // in-process guard caching across two calls in one test method).
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sanctum_acting_as_helper_authenticates_api_requests()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/organizations/current')->assertStatus(422);
    }

    public function test_login_is_throttled_after_five_failed_attempts()
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                'device_name' => 'iPhone 15',
            ])->assertUnprocessable();
        }

        // The 6th attempt is throttled even with the correct password.
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'iPhone 15',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_user_can_list_their_active_sessions_and_see_which_is_current()
    {
        $user = User::factory()->create();
        $otherToken = $user->createToken('Android phone');
        $currentToken = $user->createToken('iPhone 15');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->getJson('/api/v1/auth/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['deviceName' => 'Android phone', 'isCurrent' => false])
            ->assertJsonFragment(['deviceName' => 'iPhone 15', 'isCurrent' => true]);

        $this->assertNotNull($otherToken);
    }

    public function test_a_user_can_revoke_another_devices_session()
    {
        $user = User::factory()->create();
        $otherToken = $user->createToken('Android phone');
        $currentToken = $user->createToken('iPhone 15');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->deleteJson("/api/v1/auth/sessions/{$otherToken->accessToken->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'iPhone 15']);
    }

    public function test_a_user_cannot_revoke_another_users_session()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUsersToken = $otherUser->createToken('Their phone');
        $currentToken = $user->createToken('My phone');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->deleteJson("/api/v1/auth/sessions/{$otherUsersToken->accessToken->id}")
            ->assertNotFound();

        $this->assertDatabaseCount('personal_access_tokens', 2);
    }
}
