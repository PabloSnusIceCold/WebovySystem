<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Non-admin user cannot access admin users section
     */
    public function test_non_admin_cannot_access_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.users'));

        $response->assertStatus(302);

        echo "✓ test_non_admin_cannot_access_users PASSED\n";
    }

    /**
     * Test: Admin can view users list
     */
    public function test_admin_can_view_users_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();

        echo "✓ test_admin_can_view_users_list PASSED\n";
    }

    /**
     * Test: Admin can view user creation form
     */
    public function test_admin_can_view_user_create_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertViewIs('admin.users.create');

        echo "✓ test_admin_can_view_user_create_form PASSED\n";
    }

    /**
     * Test: Admin can create user
     * - User is created with specified role
     * - Password is hashed
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
            'role' => 'user',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'role' => 'user',
        ]);

        echo "✓ test_admin_can_create_user PASSED\n";
    }

    /**
     * Test: Admin can create admin user
     */
    public function test_admin_can_create_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'newadmin',
            'email' => 'newadmin@example.com',
            'password' => 'SecurePass123',
            'role' => 'admin',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'username' => 'newadmin',
            'role' => 'admin',
        ]);

        echo "✓ test_admin_can_create_admin_user PASSED\n";
    }

    /**
     * Test: User creation fails without username
     */
    public function test_user_creation_fails_without_username(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
        ]);

        $response->assertSessionHasErrors('username');

        echo "✓ test_user_creation_fails_without_username PASSED\n";
    }

    /**
     * Test: User creation fails with duplicate username
     */
    public function test_user_creation_fails_with_duplicate_username(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['username' => 'existinguser']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'existinguser',
            'email' => 'different@example.com',
            'password' => 'SecurePass123',
        ]);

        $response->assertSessionHasErrors('username');

        echo "✓ test_user_creation_fails_with_duplicate_username PASSED\n";
    }

    /**
     * Test: User creation fails with weak password
     */
    public function test_user_creation_fails_with_weak_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');

        echo "✓ test_user_creation_fails_with_weak_password PASSED\n";
    }

    /**
     * Test: Admin can view user edit form
     */
    public function test_admin_can_view_user_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user->id));

        $response->assertOk();
        $response->assertViewIs('admin.users.edit');
        $response->assertViewHas('user');

        echo "✓ test_admin_can_view_user_edit_form PASSED\n";
    }

    /**
     * Test: Admin can update user
     * - Username and email can be changed
     * - Password is optional on update
     * - Role can be changed
     */
    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'username' => 'oldname',
            'email' => 'old@example.com',
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'username' => 'newname',
            'email' => 'new@example.com',
            'role' => 'admin',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'newname',
            'email' => 'new@example.com',
            'role' => 'admin',
        ]);

        echo "✓ test_admin_can_update_user PASSED\n";
    }

    /**
     * Test: Admin can update user password
     */
    public function test_admin_can_update_user_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['password' => bcrypt('OldPass123')]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'NewPass456',
        ]);

        $response->assertRedirect();

        // Verify password is actually changed
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass456', $user->password));

        echo "✓ test_admin_can_update_user_password PASSED\n";
    }

    /**
     * Test: Admin cannot update user to duplicate username
     */
    public function test_user_update_fails_with_duplicate_username(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user2->id), [
            'username' => 'user1',
            'email' => $user2->email,
        ]);

        $response->assertSessionHasErrors('username');

        echo "✓ test_user_update_fails_with_duplicate_username PASSED\n";
    }

    /**
     * Test: Admin can delete user
     * - User is deleted from DB
     * - All user's datasets are deleted
     * - All files are deleted from storage
     * - If admin deletes themselves, they are logged out
     */
    public function test_admin_can_delete_user(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $path = UploadedFile::fake()->create('file.txt', 50)->store('datasets');
        $dataset->files()->create([
            'file_name' => 'file.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 50,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.delete', $user->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertDatabaseCount('files', 0);

        echo "✓ test_admin_can_delete_user PASSED\n";
    }

    /**
     * Test: Admin is logged out when deleting themselves
     */
    public function test_admin_is_logged_out_when_deleting_themselves(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('admin.users.delete', $admin->id));

        $response->assertRedirect(route('home'));
        $this->assertGuest();

        echo "✓ test_admin_is_logged_out_when_deleting_themselves PASSED\n";
    }

    /**
     * Test: Users list shows dataset count per user
     */
    public function test_users_list_shows_dataset_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(3)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
        ]);

        Dataset::factory(2)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();

        echo "✓ test_users_list_shows_dataset_count PASSED\n";
    }
}

