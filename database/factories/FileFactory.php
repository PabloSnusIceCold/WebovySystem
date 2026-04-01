<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'file_name' => 'test_' . $this->faker->randomLetter() . '.txt',
            'file_type' => $this->faker->randomElement(['CSV', 'TXT', 'JSON', 'XLSX']),
            'file_path' => 'datasets/' . $this->faker->uuid() . '.txt',
            'file_size' => $this->faker->numberBetween(1000, 10000000),
        ];
    }
}

