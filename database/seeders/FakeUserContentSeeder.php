<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FakeUserContentSeeder extends Seeder
{
    public function run(): void
    {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => 'fake@gmail.com'],
            [
                'username' => 'fake',
                'password' => Hash::make('Fake123!'),
                'role' => 'user',
            ]
        );

        $categoryIds = Category::query()->pluck('id')->all();
        if (empty($categoryIds)) {
            $this->call(CategorySeeder::class);
            $categoryIds = Category::query()->pluck('id')->all();
        }

        // STRICT: ensure EXACT counts by removing any previously created non-seeded content
        // for this user (e.g. test repositories/datasets created manually).
        Repository::query()
            ->where('user_id', $user->id)
            ->where('name', 'not like', 'Fake repository #%')
            ->delete();

        Dataset::query()
            ->where('user_id', $user->id)
            ->where('name', 'not like', 'Fake dataset #%')
            ->delete();

        $repositories = [];
        for ($i = 1; $i <= 50; $i++) {
            $repoName = "Fake repository #{$i}";

            /** @var Repository $repo */
            $repo = Repository::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $repoName],
                [
                    'description' => "Seeded repository for fake@gmail.com (#{$i}).",
                    'share_token' => (string) Str::uuid(),
                ]
            );

            $repositories[] = $repo;
        }

        for ($i = 1; $i <= 100; $i++) {
            $datasetName = "Fake dataset #{$i}";
            $repo = $repositories[($i - 1) % count($repositories)];

            /** @var Dataset $dataset */
            $dataset = Dataset::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $datasetName],
                [
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'repository_id' => $repo->id,
                    'description' => "Seeded dataset for fake@gmail.com (#{$i}).",
                    'is_public' => (bool) random_int(0, 1),
                    'download_count' => random_int(0, 200),
                    'likes_count' => random_int(0, 50),
                    'file_path' => 'datasets/seed/' . Str::random(16) . '.zip',
                    'file_type' => 'ZIP',
                    'file_size' => random_int(50_000, 25_000_000),
                    'share_token' => null,
                ]
            );

            if ($dataset->files()->count() === 0) {
                $filesCount = random_int(1, 3);
                $extensions = ['csv', 'json', 'txt', 'zip'];

                for ($f = 1; $f <= $filesCount; $f++) {
                    $ext = $extensions[array_rand($extensions)];
                    $fileName = "fake_{$i}_{$f}.{$ext}";

                    $dataset->files()->create([
                        'file_name' => $fileName,
                        'file_type' => strtoupper($ext),
                        'file_path' => "datasets/seed/fake/{$user->id}/{$dataset->id}/{$fileName}",
                        'file_size' => random_int(1_000, 15_000_000),
                    ]);
                }
            }
        }
    }
}
