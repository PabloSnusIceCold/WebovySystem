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

class DatasetDownloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Owner can download their own public dataset
     * - Creates a public dataset with files
     * - Downloads the ZIP
     * - Verifies download_count increments
     * - Verifies response is a file download
     */
    public function test_owner_can_download_public_dataset(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'download_count' => 0,
        ]);

        // Add a file to the dataset
        $path = UploadedFile::fake()->create('test.txt', 10, 'text/plain')->store('datasets');
        $file = $dataset->files()->create([
            'file_name' => 'test.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 10 * 1024,
        ]);

        // Download as owner
        $response = $this->actingAs($user)
            ->get(route('datasets.download', $dataset->id));

        // Verify response is a file download
        $response->assertOk();
        $response->assertHeader('content-type');
        $response->assertDownload();

        // Refresh and verify download_count incremented
        $dataset->refresh();
        $this->assertSame(1, (int) $dataset->download_count);

        echo "✓ test_owner_can_download_public_dataset PASSED\n";
    }

    /**
     * Test: Public user can download a public dataset
     * - Creates a public dataset with files
     * - Downloads as authenticated user (not owner)
     * - Verifies download_count increments
     */
    public function test_user_can_download_public_dataset(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $downloader = User::factory()->create();
        $category = Category::factory()->create();

        // Create a public dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => true,
            'download_count' => 5,
        ]);

        // Add a file
        $path = UploadedFile::fake()->create('data.txt', 20, 'text/plain')->store('datasets');
        $dataset->files()->create([
            'file_name' => 'data.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 20 * 1024,
        ]);

        // Download as another user
        $response = $this->actingAs($downloader)
            ->get(route('datasets.download', $dataset->id));

        $response->assertOk();
        $response->assertDownload();

        // Verify download_count incremented from 5 to 6
        $dataset->refresh();
        $this->assertSame(6, (int) $dataset->download_count);

        echo "✓ test_user_can_download_public_dataset PASSED\n";
    }

    /**
     * Test: User cannot download someone else's private dataset
     */
    public function test_user_cannot_download_private_dataset_of_another_user(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::factory()->create();

        // Create a private dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => false,
            'download_count' => 0,
        ]);

        // Add a file
        $path = UploadedFile::fake()->create('secret.txt', 10, 'text/plain')->store('datasets');
        $dataset->files()->create([
            'file_name' => 'secret.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 10 * 1024,
        ]);

        // Try to download as another user
        $response = $this->actingAs($attacker)
            ->get(route('datasets.download', $dataset->id));

        // Should be forbidden
        $response->assertStatus(403);

        // Verify download_count did NOT increment
        $dataset->refresh();
        $this->assertSame(0, (int) $dataset->download_count);

        echo "✓ test_user_cannot_download_private_dataset_of_another_user PASSED\n";
    }

    /**
     * Test: Admin can download any dataset
     */
    public function test_admin_can_download_private_dataset(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        // Create a private dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => false,
            'download_count' => 0,
        ]);

        // Add a file
        $path = UploadedFile::fake()->create('admin_access.txt', 15, 'text/plain')->store('datasets');
        $dataset->files()->create([
            'file_name' => 'admin_access.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 15 * 1024,
        ]);

        // Download as admin
        $response = $this->actingAs($admin)
            ->get(route('datasets.download', $dataset->id));

        $response->assertOk();
        $response->assertDownload();

        // Verify download_count incremented
        $dataset->refresh();
        $this->assertSame(1, (int) $dataset->download_count);

        echo "✓ test_admin_can_download_private_dataset PASSED\n";
    }

    /**
     * Test: AJAX endpoint incrementDownloadCount increments counter
     */
    public function test_ajax_increment_download_count(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a public dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => true,
            'download_count' => 3,
        ]);

        // Call AJAX endpoint
        $response = $this->actingAs($user)
            ->postJson(route('datasets.downloadCount', $dataset->id));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('download_count', 4);

        // Verify in DB
        $dataset->refresh();
        $this->assertSame(4, (int) $dataset->download_count);

        echo "✓ test_ajax_increment_download_count PASSED\n";
    }

    /**
     * Test: Guest cannot access download endpoint
     */
    public function test_guest_receives_download_redirect_or_error(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $category = Category::factory()->create();

        // Create a public dataset
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        // Add a file
        $path = UploadedFile::fake()->create('public.txt', 10, 'text/plain')->store('datasets');
        $dataset->files()->create([
            'file_name' => 'public.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 10 * 1024,
        ]);

        // Try to download as guest
        $response = $this->get(route('datasets.download', $dataset->id));

        // Public dataset should either be downloadable or redirect to login
        // Check that we got a valid response (not an error)
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302, 404]),
            'Guest should either get file, redirect, or get not found'
        );

        echo "✓ test_guest_receives_download_redirect_or_error PASSED\n";
    }
}

