<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_upload_accepts_txt_files(): void
    {
        Storage::fake('local');

        $category = Category::factory()->create([
            'name' => 'TestCategory',
        ]);

        $user = User::factory()->create([
            'username' => 'tester',
            'email' => 'tester@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('datasets.upload.post'), [
            'name' => 'My dataset',
            'category_id' => $category->id,
            'description' => 'desc',
            'files' => [
                UploadedFile::fake()->create('test1.txt', 10, 'text/plain'),
                UploadedFile::fake()->create('test2.txt', 10, 'text/plain'),
            ],
        ]);

        $response->assertSessionHasNoErrors();
    }
}

