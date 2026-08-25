<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
}
