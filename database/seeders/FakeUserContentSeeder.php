<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\File as HttpFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $diskName = config('filesystems.default', 'local');
        $disk = Storage::disk($diskName);

        // Optional cleanup: remove previously seeded physical files (prevents stale DB paths)
        // We only touch files created by this seeder (prefix: seeded_fake_)
        try {
            foreach ($disk->files('datasets') as $p) {
                if (str_starts_with(basename($p), 'seeded_fake_')) {
                    $disk->delete($p);
                }
            }
        } catch (\Throwable $e) {
            // ignore cleanup errors
        }

        // Remove any existing fake content for a clean reseed
        Repository::where('user_id', $user->id)->delete();
        Dataset::where('user_id', $user->id)->delete();

        $categoryIds = Category::query()->pluck('id')->all();
        if (empty($categoryIds)) {
            $this->call(CategorySeeder::class);
            $categoryIds = Category::query()->pluck('id')->all();
        }

        // create repositories
        $repositories = [];
        for ($i = 1; $i <= 50; $i++) {
            /** @var Repository $repo */
            $repo = Repository::query()->create([
                'user_id' => $user->id,
                'name' => "Fake repository #{$i}",
                'description' => "Seeded repository for fake@gmail.com (#{$i}).",
                'share_token' => (string) Str::uuid(),
            ]);

            $repositories[] = $repo;
        }

        // create datasets
        for ($i = 1; $i <= 100; $i++) {
            $repo = $repositories[($i - 1) % count($repositories)];

            /** @var Dataset $dataset */
            $dataset = Dataset::query()->create([
                'user_id' => $user->id,
                'name' => "Fake dataset #{$i}",
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'repository_id' => $repo->id,
                'description' => "Seeded dataset for fake@gmail.com (#{$i}).",
                // Keep public so downloads are testable without auth/session edge-cases
                'is_public' => true,
                'download_count' => random_int(0, 200),
                'likes_count' => random_int(0, 50),
                'share_token' => null,
            ]);

            // Create files rows and physical dummy files
            $filesCount = random_int(1, 3);
            $extensions = ['csv', 'json', 'txt'];

            for ($f = 1; $f <= $filesCount; $f++) {
                $ext = $extensions[array_rand($extensions)];
                $fileName = "seeded_fake_{$dataset->id}_{$f}.{$ext}";

                $content = match ($ext) {
                    'csv' => "id,value\n1,Seeded {$i}-{$f}\n2,Hello\n",
                    'json' => json_encode([
                        'dataset' => $i,
                        'file' => $f,
                        'note' => 'Seeded dummy file',
                    ], JSON_PRETTY_PRINT),
                    default => "This is a seeded dummy file: {$fileName}\nDataset #{$i}, file #{$f}\n",
                };

                // Write temp file, then store it using putFileAs just like upload()
                $tmpPath = sys_get_temp_dir() . '/' . Str::random(16) . '-' . $fileName;
                file_put_contents($tmpPath, $content);
                $stored = $disk->putFileAs('datasets', new HttpFile($tmpPath), $fileName);
                @unlink($tmpPath);

                $filePath = (string) $stored;

                $dataset->files()->create([
                    'file_name' => $fileName,
                    'file_type' => strtoupper($ext),
                    'file_path' => $filePath,
                    'file_size' => strlen($content),
                ]);
            }
        }
    }
}
