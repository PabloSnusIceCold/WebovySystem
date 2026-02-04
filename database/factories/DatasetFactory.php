<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dataset>
 */
class DatasetFactory extends Factory
{
    protected $model = Dataset::class;

    public function definition(): array
    {
        $categoryId = Category::query()->value('id')
            ?? Category::query()->insertGetId([
                'name' => 'Uncategorized',
                'description' => 'Factory default category',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'user_id' => User::factory(),
            'category_id' => $categoryId,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'is_public' => true,
            'download_count' => 0,
            'likes_count' => 0,
            // file_path je povinný stĺpec (legacy z 1-súborového datasetu). Pre testy stačí dummy hodnota.
            'file_path' => 'datasets/' . Str::random(16) . '.txt',
            'file_type' => 'TXT',
            'file_size' => 1234,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
