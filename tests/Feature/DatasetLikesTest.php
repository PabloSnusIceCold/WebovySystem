<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatasetLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_toggle_like(): void
    {
        $user = User::factory()->create();
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'likes_count' => 0,
        ]);

        $res = $this->postJson("/datasets/{$dataset->id}/like/toggle");
        $res->assertStatus(401);
    }

    public function test_user_can_toggle_like_public_dataset(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
            'likes_count' => 0,
        ]);

        $this->actingAs($user);

        $res1 = $this->postJson("/datasets/{$dataset->id}/like/toggle");
        $res1->assertOk()->assertJson([
            'success' => true,
            'liked' => true,
        ]);

        $this->assertDatabaseHas('dataset_likes', [
            'dataset_id' => $dataset->id,
            'user_id' => $user->id,
        ]);

        $dataset->refresh();
        $this->assertSame(1, (int) $dataset->likes_count);

        $res2 = $this->postJson("/datasets/{$dataset->id}/like/toggle");
        $res2->assertOk()->assertJson([
            'success' => true,
            'liked' => false,
        ]);

        $this->assertDatabaseMissing('dataset_likes', [
            'dataset_id' => $dataset->id,
            'user_id' => $user->id,
        ]);

        $dataset->refresh();
        $this->assertSame(0, (int) $dataset->likes_count);
    }

    public function test_user_cannot_like_someone_elses_private_dataset(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $dataset = Dataset::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
            'likes_count' => 0,
        ]);

        $this->actingAs($user);

        $res = $this->postJson("/datasets/{$dataset->id}/like/toggle");
        $res->assertStatus(403);

        $this->assertDatabaseMissing('dataset_likes', [
            'dataset_id' => $dataset->id,
            'user_id' => $user->id,
        ]);

        $dataset->refresh();
        $this->assertSame(0, (int) $dataset->likes_count);
    }
}

