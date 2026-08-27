<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_sees_their_mobile_tokens_listed()
    {
        $user = User::factory()->create();
        $user->createToken('iPhone Budi');
        $user->createToken('Pixel Rina');

        $response = $this->actingAs($user)->get('/settings/sessions');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('sessions', 2));
    }

    public function test_none_of_the_listed_tokens_are_marked_current_from_a_web_session()
    {
        $user = User::factory()->create();
        $user->createToken('iPhone Budi');

        $response = $this->actingAs($user)->get('/settings/sessions');

        $response->assertInertia(fn ($page) => $page->where('sessions.0.isCurrent', false));
    }

    public function test_a_user_can_revoke_their_own_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('iPhone Budi');

        $this->actingAs($user)
            ->delete("/settings/sessions/{$token->accessToken->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_a_user_cannot_revoke_another_users_token()
    {
        $owner = User::factory()->create();
        $token = $owner->createToken('iPhone Budi');
        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->delete("/settings/sessions/{$token->accessToken->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }
}
