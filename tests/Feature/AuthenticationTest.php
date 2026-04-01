<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can register with valid data
     * - Validates form input (username, email, password with special requirements)
     * - Creates user with 'user' role by default
     * - Logs user in after registration
     * - Redirects to home page
     */
    public function test_user_can_register_with_valid_credentials(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);

        $this->assertAuthenticated();

        echo "✓ test_user_can_register_with_valid_credentials PASSED\n";
    }

    /**
     * Test: Registration fails with duplicate username
     */
    public function test_registration_fails_with_duplicate_username(): void
    {
        User::factory()->create(['username' => 'existinguser']);

        $response = $this->post(route('register.perform'), [
            'username' => 'existinguser',
            'email' => 'different@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertSessionHasErrors('username');

        echo "✓ test_registration_fails_with_duplicate_username PASSED\n";
    }

    /**
     * Test: Registration fails with duplicate email
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertSessionHasErrors('email');

        echo "✓ test_registration_fails_with_duplicate_email PASSED\n";
    }

    /**
     * Test: Registration fails with weak password (no uppercase)
     */
    public function test_registration_fails_with_weak_password_no_uppercase(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_registration_fails_with_weak_password_no_uppercase PASSED\n";
    }

    /**
     * Test: Registration fails with weak password (no lowercase)
     */
    public function test_registration_fails_with_weak_password_no_lowercase(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SECUREPASS123',
            'password_confirmation' => 'SECUREPASS123',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_registration_fails_with_weak_password_no_lowercase PASSED\n";
    }

    /**
     * Test: Registration fails with weak password (no numbers)
     */
    public function test_registration_fails_with_weak_password_no_numbers(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass',
            'password_confirmation' => 'SecurePass',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_registration_fails_with_weak_password_no_numbers PASSED\n";
    }

    /**
     * Test: Registration fails with password too short
     */
    public function test_registration_fails_with_password_too_short(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Short1',
            'password_confirmation' => 'Short1',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_registration_fails_with_password_too_short PASSED\n";
    }

    /**
     * Test: Registration fails when passwords don't match
     */
    public function test_registration_fails_when_passwords_dont_match(): void
    {
        $response = $this->post(route('register.perform'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
            'password_confirmation' => 'DifferentPass123',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_registration_fails_when_passwords_dont_match PASSED\n";
    }

    /**
     * Test: User can login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('SecurePass123'),
        ]);

        $response = $this->post(route('login.perform'), [
            'email' => 'user@example.com',
            'password' => 'SecurePass123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        echo "✓ test_user_can_login_with_valid_credentials PASSED\n";
    }

    /**
     * Test: Login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('SecurePass123'),
        ]);

        $response = $this->post(route('login.perform'), [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        echo "✓ test_login_fails_with_invalid_credentials PASSED\n";
    }

    /**
     * Test: User can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();

        echo "✓ test_user_can_logout PASSED\n";
    }
}

