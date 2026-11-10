<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Public registration must stay closed: any account on this panel is an admin.
 * No RefreshDatabase — these routes must 404 before touching the database.
 */
class RegistrationTest extends TestCase
{
    public function test_registration_screen_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_users_cannot_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
    }
}
