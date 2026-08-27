<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_command_sets_the_flag_after_confirmation()
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->artisan('superadmin:grant', ['email' => $user->email])
            ->expectsConfirmation("Grant superadmin (read-only, all organizations) to {$user->name} <{$user->email}>?", 'yes')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->is_superadmin);
    }

    public function test_grant_command_does_nothing_if_not_confirmed()
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->artisan('superadmin:grant', ['email' => $user->email])
            ->expectsConfirmation("Grant superadmin (read-only, all organizations) to {$user->name} <{$user->email}>?", 'no')
            ->assertFailed();

        $this->assertFalse($user->fresh()->is_superadmin);
    }

    public function test_grant_command_fails_for_an_unknown_email()
    {
        $this->artisan('superadmin:grant', ['email' => 'nobody@example.com'])->assertFailed();
    }

    public function test_revoke_command_clears_the_flag()
    {
        $user = User::factory()->create(['is_superadmin' => true]);

        $this->artisan('superadmin:revoke', ['email' => $user->email])->assertSuccessful();

        $this->assertFalse($user->fresh()->is_superadmin);
    }
}
