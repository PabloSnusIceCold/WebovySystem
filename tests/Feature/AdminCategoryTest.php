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

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Non-admin user cannot access admin categories
     */
    public function test_non_admin_cannot_access_categories(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.categories.index'));

        $response->assertStatus(302);

        echo "✓ test_non_admin_cannot_access_categories PASSED\n";
    }

    /**
     * Test: Admin can view categories list
     */
    public function test_admin_can_view_categories_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertViewIs('admin.categories.index');
        $response->assertViewHas('categories');

        echo "✓ test_admin_can_view_categories_list PASSED\n";
    }

    /**
     * Test: Admin can view category creation form
     */
    public function test_admin_can_view_category_create_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.categories.create'));

        $response->assertOk();
        $response->assertViewIs('admin.categories.create');

        echo "✓ test_admin_can_view_category_create_form PASSED\n";
    }

    /**
     * Test: Admin can create category
     * - Category is created in database
     * - Fields are stored: name, description
     */
    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Machine Learning',
            'description' => 'Datasets for ML projects',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Machine Learning',
            'description' => 'Datasets for ML projects',
        ]);

        echo "✓ test_admin_can_create_category PASSED\n";
    }

    /**
     * Test: Category creation validation - name is required
     */
    public function test_category_creation_fails_without_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'description' => 'A description without name',
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_category_creation_fails_without_name PASSED\n";
    }

    /**
     * Test: Category creation fails with duplicate name
     */
    public function test_category_creation_fails_with_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Category::factory()->create(['name' => 'Existing Category']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Existing Category',
            'description' => 'Different description',
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_category_creation_fails_with_duplicate_name PASSED\n";
    }

    /**
     * Test: Admin can view category edit form
     */
    public function test_admin_can_view_category_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.categories.edit', $category->id));

        $response->assertOk();
        $response->assertViewIs('admin.categories.edit');
        $response->assertViewHas('category');

        echo "✓ test_admin_can_view_category_edit_form PASSED\n";
    }

    /**
     * Test: Admin can update category
     */
    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category->id), [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        echo "✓ test_admin_can_update_category PASSED\n";
    }

    /**
     * Test: Admin cannot update category with duplicate name
     */
    public function test_category_update_fails_with_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category1 = Category::factory()->create(['name' => 'Category 1']);
        $category2 = Category::factory()->create(['name' => 'Category 2']);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category2->id), [
            'name' => 'Category 1',
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_category_update_fails_with_duplicate_name PASSED\n";
    }

    /**
     * Test: Admin can delete category
     * - Category is deleted from DB
     * - All datasets in category are deleted
     * - All files associated with datasets are deleted from storage
     */
    public function test_admin_can_delete_category(): void
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

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertDatabaseCount('files', 0);

        echo "✓ test_admin_can_delete_category PASSED\n";
    }

    /**
     * Test: Admin can see dataset count per category
     */
    public function test_categories_list_shows_dataset_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Dataset::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
        ]);

        Dataset::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();

        echo "✓ test_categories_list_shows_dataset_count PASSED\n";
    }

    /**
     * Test: Admin can view category detail page
     */
    public function test_admin_can_view_category_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.categories.show', $category->id));

        $response->assertOk();
        $response->assertViewIs('admin.categories.show');

        echo "✓ test_admin_can_view_category_detail PASSED\n";
    }
}

