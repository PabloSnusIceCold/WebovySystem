<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetDetailAjaxManageTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_dataset_via_ajax(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Old',
            'description' => 'Old desc',
            'is_public' => true,
        ]);

        $res = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('datasets.update.ajax', $dataset->id), [
                'name' => 'New name',
                'description' => 'New desc',
            ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('dataset.name', 'New name');

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'name' => 'New name',
            'description' => 'New desc',
        ]);
    }

    public function test_non_owner_non_admin_cannot_update_private_dataset_via_ajax(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $attacker = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $res = $this->actingAs($attacker)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->put(route('datasets.update.ajax', $dataset->id), [
                'name' => 'Hacked',
                'description' => 'Hacked',
            ]);

        $res->assertStatus(403);
    }

    public function test_owner_can_delete_dataset_via_ajax(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $res = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('datasets.destroy.ajax', $dataset->id));

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $this->assertDatabaseMissing('datasets', [
            'id' => $dataset->id,
        ]);
    }
}

