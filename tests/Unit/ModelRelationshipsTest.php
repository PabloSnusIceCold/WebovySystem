<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User has many datasets
     */
    public function test_user_has_many_datasets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Dataset::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals(3, $user->datasets()->count());

        echo "✓ test_user_has_many_datasets PASSED\n";
    }

    /**
     * Test: Dataset belongs to user
     */
    public function test_dataset_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals($user->id, $dataset->user->id);

        echo "✓ test_dataset_belongs_to_user PASSED\n";
    }

    /**
     * Test: Dataset belongs to category
     */
    public function test_dataset_belongs_to_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals($category->id, $dataset->category->id);

        echo "✓ test_dataset_belongs_to_category PASSED\n";
    }

    /**
     * Test: Category has many datasets
     */
    public function test_category_has_many_datasets(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();

        Dataset::factory(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals(5, $category->datasets()->count());

        echo "✓ test_category_has_many_datasets PASSED\n";
    }

    /**
     * Test: Dataset has many files
     */
    public function test_dataset_has_many_files(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        File::factory(3)->create(['dataset_id' => $dataset->id]);

        $this->assertEquals(3, $dataset->files()->count());

        echo "✓ test_dataset_has_many_files PASSED\n";
    }

    /**
     * Test: File belongs to dataset
     */
    public function test_file_belongs_to_dataset(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $file = File::factory()->create(['dataset_id' => $dataset->id]);

        $this->assertEquals($dataset->id, $file->dataset->id);

        echo "✓ test_file_belongs_to_dataset PASSED\n";
    }

    /**
     * Test: User has many repositories
     */
    public function test_user_has_many_repositories(): void
    {
        $user = User::factory()->create();

        Repository::factory(3)->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->repositories()->count());

        echo "✓ test_user_has_many_repositories PASSED\n";
    }

    /**
     * Test: Repository belongs to user
     */
    public function test_repository_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $repository = Repository::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $repository->user->id);

        echo "✓ test_repository_belongs_to_user PASSED\n";
    }

    /**
     * Test: Repository has many datasets
     */
    public function test_repository_has_many_datasets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $repository = Repository::factory()->create(['user_id' => $user->id]);

        Dataset::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'repository_id' => $repository->id,
        ]);

        $this->assertEquals(3, $repository->datasets()->count());

        echo "✓ test_repository_has_many_datasets PASSED\n";
    }

    /**
     * Test: Dataset can belong to repository (nullable)
     */
    public function test_dataset_can_belong_to_repository_or_be_null(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $dataset1 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'repository_id' => null,
        ]);

        $repository = Repository::factory()->create(['user_id' => $user->id]);
        $dataset2 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'repository_id' => $repository->id,
        ]);

        $this->assertNull($dataset1->repository_id);
        $this->assertEquals($repository->id, $dataset2->repository_id);

        echo "✓ test_dataset_can_belong_to_repository_or_be_null PASSED\n";
    }

    /**
     * Test: Dataset has likes (many-to-many through dataset_likes)
     */
    public function test_dataset_has_many_liked_by_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
        ]);

        $dataset->likedByUsers()->attach([$user2->id]);

        $this->assertEquals(1, $dataset->likedByUsers()->count());

        echo "✓ test_dataset_has_many_liked_by_users PASSED\n";
    }

    /**
     * Test: Dataset total_size attribute calculates correctly
     */
    public function test_dataset_total_size_attribute(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 1000,
        ]);

        File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 2000,
        ]);

        $this->assertEquals(3000, $dataset->total_size);

        echo "✓ test_dataset_total_size_attribute PASSED\n";
    }

    /**
     * Test: Dataset total_size_mb attribute
     */
    public function test_dataset_total_size_mb_attribute(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 1048576, // 1 MB
        ]);

        File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 1048576, // 1 MB
        ]);

        $this->assertEquals(2.0, $dataset->total_size_mb);

        echo "✓ test_dataset_total_size_mb_attribute PASSED\n";
    }

    /**
     * Test: File size_human attribute formats correctly
     */
    public function test_file_size_human_attribute(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $file1 = File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 512, // 0.5 KB
        ]);

        $file2 = File::factory()->create([
            'dataset_id' => $dataset->id,
            'file_size' => 1048576, // 1 MB
        ]);

        $this->assertStringContainsString('KB', $file1->size_human);
        $this->assertStringContainsString('MB', $file2->size_human);

        echo "✓ test_file_size_human_attribute PASSED\n";
    }

    /**
     * Test: Dataset can access user's other repositories
     */
    public function test_dataset_user_has_access_to_repositories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Repository::factory(3)->create(['user_id' => $user->id]);

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals(3, $dataset->user->repositories()->count());

        echo "✓ test_dataset_user_has_access_to_repositories PASSED\n";
    }

    /**
     * Test: Dataset download_count and likes_count are integers
     */
    public function test_dataset_counts_are_integers(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'download_count' => 5,
            'likes_count' => 10,
        ]);

        $this->assertIsInt($dataset->download_count);
        $this->assertIsInt($dataset->likes_count);

        echo "✓ test_dataset_counts_are_integers PASSED\n";
    }

    /**
     * Test: Dataset is_public is boolean
     */
    public function test_dataset_is_public_is_boolean(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $dataset1 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        $dataset2 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $this->assertTrue($dataset1->is_public);
        $this->assertFalse($dataset2->is_public);

        echo "✓ test_dataset_is_public_is_boolean PASSED\n";
    }
}

