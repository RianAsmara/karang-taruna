<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Both pages must be reachable without an account: the register page
     * links to them, and agreeing to terms you cannot read is not consent.
     */
    public function test_a_guest_can_read_the_privacy_policy_and_terms()
    {
        $this->get('/privasi')->assertOk();
        $this->get('/syarat')->assertOk();
    }
}
