<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: User can view repositories list
     */
    public function test_user_can_view_repositories_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('repositories.index'));

        $response->assertOk();
        $response->assertViewIs('repositories.index');
        $response->assertViewHas('repositories');
        $response->assertViewHas('datasets');

        echo "✓ test_user_can_view_repositories_list PASSED\n";
    }

    /**
     * Test: User can create repository
     * - Creates repository record in DB
     * - Sets user_id and null share_token initially
     */
    public function test_user_can_create_repository(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('repositories.store'), [
            'name' => 'My Repository',
            'description' => 'A collection of datasets',
        ]);

        $response->assertRedirect(route('repositories.index'));

        $this->assertDatabaseHas('repositories', [
            'user_id' => $user->id,
            'name' => 'My Repository',
            'description' => 'A collection of datasets',
        ]);

        echo "✓ test_user_can_create_repository PASSED\n";
    }

    /**
     * Test: User can create repository with datasets assigned
     * - Datasets have repository_id set to the created repository
     * - Only datasets owned by user can be assigned
     */
    public function test_user_can_create_repository_with_datasets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        $dataset1 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $dataset2 = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $dataset3 = Dataset::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->post(route('repositories.store'), [
            'name' => 'My Repository',
            'dataset_ids' => [$dataset1->id, $dataset2->id, $dataset3->id],
        ]);

        $response->assertRedirect(route('repositories.index'));

        $repository = Repository::where('name', 'My Repository')->firstOrFail();

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset1->id,
            'repository_id' => $repository->id,
        ]);

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset2->id,
            'repository_id' => $repository->id,
        ]);

        // dataset3 (owned by otherUser) should NOT be assigned
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset3->id,
            'repository_id' => null,
        ]);

        echo "✓ test_user_can_create_repository_with_datasets PASSED\n";
    }

    /**
     * Test: Repository creation validation - name is required
     */
    public function test_repository_creation_fails_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('repositories.store'), [
            'description' => 'A description without name',
        ]);

        $response->assertSessionHasErrors('name');

        echo "✓ test_repository_creation_fails_without_name PASSED\n";
    }

    /**
     * Test: User can view repository details
     */
    public function test_user_can_view_repository_details(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $repository = Repository::factory()->create(['user_id' => $user->id]);
        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'repository_id' => $repository->id,
        ]);

        $response = $this->actingAs($user)->get(route('repositories.show', $repository->id));

        $response->assertOk();
        $response->assertViewIs('repositories.show');
        $response->assertViewHas('repository');

        echo "✓ test_user_can_view_repository_details PASSED\n";
    }

    /**
     * Test: User cannot view other user's private repository
     */
    public function test_user_cannot_view_others_repository(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $repository = Repository::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get(route('repositories.show', $repository->id));

        $response->assertStatus(403);

        echo "✓ test_user_cannot_view_others_repository PASSED\n";
    }

    /**
     * Test: Admin can view any repository
     */
    public function test_admin_can_view_any_repository(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $repository = Repository::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin)->get(route('repositories.show', $repository->id));

        $response->assertOk();

        echo "✓ test_admin_can_view_any_repository PASSED\n";
    }

    /**
     * Test: User can generate share token for repository
     * - Generates unique share token (UUID)
     * - Share URL is returned
     * - Token is stored in database
     */
    public function test_user_can_share_repository(): void
    {
        $user = User::factory()->create();
        $repository = Repository::factory()->create([
            'user_id' => $user->id,
            'share_token' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('repositories.share', $repository->id));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('share_url', fn($url) => str_contains($url, '/repositories/share/'));

        $repository->refresh();
        $this->assertNotNull($repository->share_token);

        echo "✓ test_user_can_share_repository PASSED\n";
    }

    /**
     * Test: User cannot share other user's repository
     */
    public function test_user_cannot_share_others_repository(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $repository = Repository::factory()->create([
            'user_id' => $user1->id,
            'share_token' => null,
        ]);

        $response = $this->actingAs($user2)->postJson(route('repositories.share', $repository->id));

        $response->assertStatus(403);

        $repository->refresh();
        $this->assertNull($repository->share_token);

        echo "✓ test_user_cannot_share_others_repository PASSED\n";
    }

    /**
     * Test: Public user can view shared repository
     * - Repository detail page loads with datasets
     * - Datasets are displayed
     */
    public function test_guest_can_view_shared_repository(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $repository = Repository::factory()->create([
            'user_id' => $user->id,
            'share_token' => 'share-token-12345678',
        ]);

        $dataset = Dataset::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'repository_id' => $repository->id,
            'is_public' => true,
        ]);

        $response = $this->get(url('/repositories/share/share-token-12345678'));

        $response->assertOk();
        $response->assertViewIs('repositories.share');

        echo "✓ test_guest_can_view_shared_repository PASSED\n";
    }

    /**
     * Test: Invalid share token returns 404
     */
    public function test_invalid_share_token_returns_404(): void
    {
        $response = $this->get(url('/repositories/share/invalid-token'));

        $response->assertStatus(404);

        echo "✓ test_invalid_share_token_returns_404 PASSED\n";
    }

    /**
     * Test: User can search repositories by name
     */
    public function test_user_can_search_repositories(): void
    {
        $user = User::factory()->create();

        Repository::factory()->create([
            'user_id' => $user->id,
            'name' => 'Sales Data',
        ]);

        Repository::factory()->create([
            'user_id' => $user->id,
            'name' => 'Weather Records',
        ]);

        $response = $this->actingAs($user)->get(route('repositories.index', ['search' => 'Sales']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('repositories')->total());

        echo "✓ test_user_can_search_repositories PASSED\n";
    }

    /**
     * Test: Ensure share token is generated and returned on subsequent calls
     */
    public function test_share_token_is_idempotent(): void
    {
        $user = User::factory()->create();
        $repository = Repository::factory()->create([
            'user_id' => $user->id,
            'share_token' => null,
        ]);

        $response1 = $this->actingAs($user)->postJson(route('repositories.share', $repository->id));
        $token1 = $response1->json('token');

        $response2 = $this->actingAs($user)->postJson(route('repositories.share', $repository->id));
        $token2 = $response2->json('token');

        $this->assertEquals($token1, $token2);

        echo "✓ test_share_token_is_idempotent PASSED\n";
    }
}

