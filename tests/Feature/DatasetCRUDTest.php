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

class DatasetCRUDTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Authenticated user can view upload form
     */
    public function test_user_can_view_upload_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('datasets.upload'));

        $response->assertOk();
        $response->assertViewIs('datasets.upload');
        $response->assertViewHas('categories');

        echo "✓ test_user_can_view_upload_form PASSED\n";
    }

    /**
     * Test: User can upload dataset with single file
     * - Creates dataset record in DB
     * - Creates file record with proper metadata
     * - File is stored in storage/datasets
     * - File path is saved in database
     */
    public function test_user_can_upload_dataset_with_single_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Test Dataset',
            'category_id' => $category->id,
            'description' => 'This is a test dataset',
            'files' => [
                UploadedFile::fake()->create('data.csv', 100, 'text/csv'),
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('datasets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Test Dataset',
            'description' => 'This is a test dataset',
            'is_public' => false,
        ]);

        $this->assertDatabaseCount('files', 1);
        $file = File::first();
        $this->assertEquals('data.csv', $file->file_name);
        $this->assertEquals('CSV', $file->file_type);
        Storage::assertExists($file->file_path);

        echo "✓ test_user_can_upload_dataset_with_single_file PASSED\n";
    }

    /**
     * Test: User can upload dataset with multiple files
     * - All files are stored
     * - All files have corresponding records in database
     * - File types are correctly detected
     */
    public function test_user_can_upload_dataset_with_multiple_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Multi File Dataset',
            'category_id' => $category->id,
            'description' => 'Dataset with multiple files',
            'files' => [
                UploadedFile::fake()->create('file1.csv', 50, 'text/csv'),
                UploadedFile::fake()->create('file2.txt', 30, 'text/plain'),
                UploadedFile::fake()->create('file3.json', 40, 'application/json'),
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $dataset = Dataset::where('name', 'Multi File Dataset')->firstOrFail();
        $this->assertDatabaseCount('files', 3);
        $this->assertEquals(3, $dataset->files->count());

        $fileTypes = $dataset->files->pluck('file_type')->all();
        $this->assertContains('CSV', $fileTypes);
        $this->assertContains('TXT', $fileTypes);
        $this->assertContains('JSON', $fileTypes);

        echo "✓ test_user_can_upload_dataset_with_multiple_files PASSED\n";
    }

    /**
     * Test: User can make dataset public
     */
    public function test_user_can_upload_public_dataset(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Public Dataset',
            'category_id' => $category->id,
            'is_public' => true,
            'files' => [
                UploadedFile::fake()->create('data.txt', 50),
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('datasets', [
            'user_id' => $user->id,
            'name' => 'Public Dataset',
            'is_public' => true,
        ]);

        echo "✓ test_user_can_upload_public_dataset PASSED\n";
    }

    /**
     * Test: Upload validation - name is required
     */
    public function test_upload_fails_without_name(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'category_id' => $category->id,
            'files' => [
                UploadedFile::fake()->create('data.txt', 50),
            ],
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_upload_fails_without_name PASSED\n";
    }

    /**
     * Test: Upload validation - category_id is required
     */
    public function test_upload_fails_without_category(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Test Dataset',
            'files' => [
                UploadedFile::fake()->create('data.txt', 50),
            ],
        ]);

        $response->assertSessionHasErrors('category_id');

        echo "✓ test_upload_fails_without_category PASSED\n";
    }

    /**
     * Test: Upload validation - at least one file is required
     */
    public function test_upload_fails_without_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Test Dataset',
            'category_id' => $category->id,
            'files' => [],
        ]);

        $response->assertSessionHasErrors('files');

        echo "✓ test_upload_fails_without_files PASSED\n";
    }

    /**
     * Test: Upload validation - only allowed file types
     */
    public function test_upload_fails_with_unsupported_file_type(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'Test Dataset',
            'category_id' => $category->id,
            'files' => [
                UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg'),
            ],
        ]);

        $response->assertSessionHasErrors('files.0');

        echo "✓ test_upload_fails_with_unsupported_file_type PASSED\n";
    }

    /**
     * Test: User can view their own dataset
     */
    public function test_user_can_view_own_dataset(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($user)->get(route('datasets.show', $dataset->id));

        $response->assertOk();
        $response->assertViewIs('datasets.show');
        $response->assertViewHas('dataset');

        echo "✓ test_user_can_view_own_dataset PASSED\n";
    }

    /**
     * Test: User cannot view someone else's private dataset
     */
    public function test_user_cannot_view_others_private_dataset(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($otherUser)->get(route('datasets.show', $dataset->id));

        $response->assertStatus(403);

        echo "✓ test_user_cannot_view_others_private_dataset PASSED\n";
    }

    /**
     * Test: Anyone can view public dataset
     */
    public function test_anyone_can_view_public_dataset(): void
    {
        $owner = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        $response = $this->get(route('datasets.show', $dataset->id));

        $response->assertOk();
        $response->assertViewHas('dataset');

        echo "✓ test_anyone_can_view_public_dataset PASSED\n";
    }

    /**
     * Test: User can view edit form for own dataset
     */
    public function test_user_can_view_edit_form_for_own_dataset(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('datasets.edit', $dataset->id));

        $response->assertOk();
        $response->assertViewIs('datasets.edit');

        echo "✓ test_user_can_view_edit_form_for_own_dataset PASSED\n";
    }

    /**
     * Test: User cannot edit someone else's dataset
     */
    public function test_user_cannot_edit_others_dataset(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($otherUser)->get(route('datasets.edit', $dataset->id));

        $response->assertStatus(404);

        echo "✓ test_user_cannot_edit_others_dataset PASSED\n";
    }

    /**
     * Test: User can update own dataset
     */
    public function test_user_can_update_own_dataset(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($user)->put(route('datasets.update', $dataset->id), [
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        $response->assertRedirect(route('datasets.index'));

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'name' => 'New Name',
            'description' => 'New description',
        ]);

        echo "✓ test_user_can_update_own_dataset PASSED\n";
    }

    /**
     * Test: User cannot delete someone else's dataset
     */
    public function test_user_cannot_delete_others_dataset(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($otherUser)->delete(route('datasets.destroy', $dataset->id));

        $response->assertStatus(403);

        $this->assertDatabaseHas('datasets', ['id' => $dataset->id]);

        echo "✓ test_user_cannot_delete_others_dataset PASSED\n";
    }

    /**
     * Test: User can delete own dataset
     * - Dataset record is deleted from DB
     * - Associated files are deleted from storage
     * - File records are deleted from DB
     */
    public function test_user_can_delete_own_dataset(): void
    {
        Storage::fake('local');

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

        $response = $this->actingAs($user)->delete(route('datasets.destroy', $dataset->id));

        $response->assertRedirect();

        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertDatabaseCount('files', 0);
        Storage::assertMissing($path1);
        Storage::assertMissing($path2);

        echo "✓ test_user_can_delete_own_dataset PASSED\n";
    }

    /**
     * Test: User can list their own datasets
     */
    public function test_user_can_list_own_datasets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Dataset::factory(2)->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('datasets.index'));

        $response->assertOk();
        $response->assertViewIs('datasets.index');

        $this->assertEquals(3, $response->viewData('datasets')->total());

        echo "✓ test_user_can_list_own_datasets PASSED\n";
    }

    /**
     * Test: User can search their datasets by name
     */
    public function test_user_can_search_datasets_by_name(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Important Dataset',
        ]);

        Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Random Data',
        ]);

        $response = $this->actingAs($user)->get(route('datasets.index', ['search' => 'Important']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('datasets')->total());

        echo "✓ test_user_can_search_datasets_by_name PASSED\n";
    }

    /**
     * Test: User can search datasets by description
     */
    public function test_user_can_search_datasets_by_description(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Dataset A',
            'description' => 'Contains sales data',
        ]);

        Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Dataset B',
            'description' => 'Weather information',
        ]);

        $response = $this->actingAs($user)->get(route('datasets.index', ['search' => 'sales']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('datasets')->total());

        echo "✓ test_user_can_search_datasets_by_description PASSED\n";
    }

    /**
     * Test: Admin can view and manage any dataset
     */
    public function test_admin_can_view_private_dataset_of_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('datasets.show', $dataset->id));

        $response->assertOk();

        echo "✓ test_admin_can_view_private_dataset_of_user PASSED\n";
    }
}

