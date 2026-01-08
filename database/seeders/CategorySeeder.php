<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Uncategorized',
                'description' => 'Predvolená kategória pre datasety bez zaradenia.',
            ],
            [
                'name' => 'Klimatológia',
                'description' => 'Dáta o počasí, klíme, teplotách a zrážkach.',
            ],
            [
                'name' => 'Doprava',
                'description' => 'Dáta o doprave, mobilite a infraštruktúre.',
            ],
            [
                'name' => 'Zdravotníctvo',
                'description' => 'Zdravotnícke štatistiky a údaje o verejnom zdraví.',
            ],
            [
                'name' => 'Ekonomika',
                'description' => 'Ekonomické ukazovatele, trh práce a financie.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}

