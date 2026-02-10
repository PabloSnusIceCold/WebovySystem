<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->pluck('id')->all();
        $categories = Category::query()->pluck('id')->all();

        if (empty($users) || empty($categories)) {
            // FK dependencies not present; nothing to seed.
            return;
        }

        $fileTypes = ['CSV', 'TXT', 'XLSX', 'JSON', 'XML', 'ARFF', 'ZIP'];

        for ($i = 1; $i <= 40; $i++) {
            $datasetName = "Demo dataset #{$i}";

            /** @var \App\Models\Dataset $dataset */
            $dataset = Dataset::query()->updateOrCreate(
                ['name' => $datasetName],
                [
                    'description' => "Testovací dataset vytvorený seederom (#{$i}).",
                    'is_public' => (bool) random_int(0, 1),
                    'category_id' => $categories[array_rand($categories)],
                    'user_id' => $users[array_rand($users)],
                    'download_count' => random_int(0, 50),
                    // keep these columns filled in case UI relies on them
                    'file_path' => "datasets/demo/dataset{$i}.zip",
                    'file_type' => 'ZIP',
                    'file_size' => random_int(25_000, 7_000_000),
                    'share_token' => null,
                ]
            );

            // Seed 1–3 files (idempotent: remove old and recreate)
            $dataset->files()->delete();

            $filesCount = random_int(1, 3);
            for ($f = 1; $f <= $filesCount; $f++) {
                $ext = strtolower($fileTypes[array_rand($fileTypes)]);
                $fileName = "file{$f}_dataset{$i}.{$ext}";

                File::create([
                    'dataset_id' => $dataset->id,
                    'file_name' => $fileName,
                    'file_type' => strtoupper($ext),
                    'file_path' => "datasets/demo/dataset{$i}/{$fileName}",
                    'file_size' => random_int(1_000, 15_000_000),
                ]);
            }
        }
    }
}
