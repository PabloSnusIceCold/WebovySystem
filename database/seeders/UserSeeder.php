<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin account (idempotent)
        User::withTrashed()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
                'deleted_at' => null,
            ]
        );

        // Regular users (idempotent via stable emails)
        for ($i = 1; $i <= 20; $i++) {
            $email = "user{$i}@example.com";

            User::withTrashed()->updateOrCreate(
                ['email' => $email],
                [
                    'username' => "user{$i}",
                    'password' => Hash::make('User123!'),
                    'role' => 'user',
                    'deleted_at' => null,
                ]
            );
        }
    }
}

