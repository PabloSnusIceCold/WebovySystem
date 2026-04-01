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

class AdminDatasetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Non-admin cannot access admin datasets
     */
    public function test_non_admin_cannot_access_admin_datasets(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.datasets.index'));

        $response->assertStatus(302);

        echo "✓ test_non_admin_cannot_access_admin_datasets PASSED\n";
    }

    /**
     * Test: Admin can view all datasets
     * - Lists both public and private datasets
     * - Shows owner information
     * - Shows file counts
     */
    public function test_admin_can_view_all_datasets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(3)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        Dataset::factory(2)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.datasets.index'));

        $response->assertOk();
        $response->assertViewIs('admin.datasets.index');
        $response->assertViewHas('datasets');

        // Admin should see all 5 datasets
        $this->assertEquals(5, $response->viewData('datasets')->total());

        echo "✓ test_admin_can_view_all_datasets PASSED\n";
    }

    /**
     * Test: Admin can view dataset edit form
     */
    public function test_admin_can_view_dataset_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.datasets.edit', $dataset->id));

        $response->assertOk();
        $response->assertViewIs('admin.datasets.edit');
        $response->assertViewHas('dataset');
        $response->assertViewHas('categories');

        echo "✓ test_admin_can_view_dataset_edit_form PASSED\n";
    }

    /**
     * Test: Admin can update dataset
     * - Can change name, description, category, and public status
     */
    public function test_admin_can_update_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Cat1']);
        $category2 = Category::factory()->create(['name' => 'Cat2']);

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'name' => 'Old Name',
            'description' => 'Old description',
            'is_public' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.datasets.update', $dataset->id), [
            'name' => 'New Name',
            'description' => 'New description',
            'category_id' => $category2->id,
            'is_public' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'name' => 'New Name',
            'description' => 'New description',
            'category_id' => $category2->id,
            'is_public' => true,
        ]);

        echo "✓ test_admin_can_update_dataset PASSED\n";
    }

    /**
     * Test: Admin can delete dataset
     * - Dataset is deleted from DB
     * - Associated files are deleted from storage
     * - File records are deleted from DB
     */
    public function test_admin_can_delete_dataset(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $path1 = UploadedFile::fake()->create('file1.txt', 50)->store('datasets');
        $path2 = UploadedFile::fake()->create('file2.txt', 60)->store('datasets');

        $dataset->files()->create([
            'file_name' => 'file1.txt',
            'file_type' => 'TXT',
            'file_path' => $path1,
            'file_size' => 50,
        ]);

        $dataset->files()->create([
            'file_name' => 'file2.txt',
            'file_type' => 'TXT',
            'file_path' => $path2,
            'file_size' => 60,
        ]);

        Storage::assertExists($path1);
        Storage::assertExists($path2);

        $response = $this->actingAs($admin)->delete(route('admin.datasets.destroy', $dataset->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertDatabaseCount('files', 0);
        Storage::assertMissing($path1);
        Storage::assertMissing($path2);

        echo "✓ test_admin_can_delete_dataset PASSED\n";
    }

    /**
     * Test: Admin dataset update validation - name is required
     */
    public function test_admin_dataset_update_fails_without_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.datasets.update', $dataset->id), [
            'category_id' => $category->id,
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_admin_dataset_update_fails_without_name PASSED\n";
    }

    /**
     * Test: Admin can change dataset owner indirectly by updating category
     */
    public function test_admin_can_reassign_dataset_to_different_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.datasets.update', $dataset->id), [
            'name' => $dataset->name,
            'category_id' => $category2->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'category_id' => $category2->id,
        ]);

        echo "✓ test_admin_can_reassign_dataset_to_different_category PASSED\n";
    }

    /**
     * Test: Admin can toggle dataset privacy
     */
    public function test_admin_can_toggle_dataset_privacy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.datasets.update', $dataset->id), [
            'name' => $dataset->name,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'is_public' => true,
        ]);

        echo "✓ test_admin_can_toggle_dataset_privacy PASSED\n";
    }

    /**
     * Test: Admin dataset list is paginated
     */
    public function test_admin_datasets_list_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(20)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.datasets.index'));

        $response->assertOk();
        $this->assertTrue($response->viewData('datasets')->hasPages());

        echo "✓ test_admin_datasets_list_is_paginated PASSED\n";
    }

    /**
     * Test: Admin can see dataset with attached files
     */
    public function test_admin_can_view_dataset_with_files(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $path = UploadedFile::fake()->create('data.csv', 100)->store('datasets');
        $dataset->files()->create([
            'file_name' => 'data.csv',
            'file_type' => 'CSV',
            'file_path' => $path,
            'file_size' => 100,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.datasets.edit', $dataset->id));

        $response->assertOk();
        $response->assertViewHas('dataset');

        echo "✓ test_admin_can_view_dataset_with_files PASSED\n";
    }
}

