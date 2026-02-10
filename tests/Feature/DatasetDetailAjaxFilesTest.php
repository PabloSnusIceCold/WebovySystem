<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DatasetDetailAjaxFilesTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_files_via_ajax(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $res = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('datasets.files.add.ajax', $dataset->id), [
                'files' => [
                    UploadedFile::fake()->create('a.txt', 10, 'text/plain'),
                ],
            ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $this->assertDatabaseCount('files', 1);
        $fileRow = File::first();
        $this->assertNotNull($fileRow);
        Storage::assertExists($fileRow->file_path);
    }

    public function test_owner_can_delete_file_via_ajax(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // create a stored file + DB record
        $path = UploadedFile::fake()->create('a.txt', 10, 'text/plain')->store('datasets');
        $file = $dataset->files()->create([
            'file_name' => 'a.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 10 * 1024,
        ]);

        Storage::assertExists($path);

        $res = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('datasets.files.delete.ajax', ['datasetId' => $dataset->id, 'fileId' => $file->id]));

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::assertMissing($path);
    }

    public function test_non_owner_cannot_delete_file_via_ajax(): void
    {
        Storage::fake();

        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $path = UploadedFile::fake()->create('a.txt', 10, 'text/plain')->store('datasets');
        $file = $dataset->files()->create([
            'file_name' => 'a.txt',
            'file_type' => 'TXT',
            'file_path' => $path,
            'file_size' => 10,
        ]);

        $res = $this->actingAs($attacker)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('datasets.files.delete.ajax', ['datasetId' => $dataset->id, 'fileId' => $file->id]));

        $res->assertStatus(403);
    }
}

