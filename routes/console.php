<?php

use Database\Seeders\FakeUserContentSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ws:reseed-fake {--smoke : Run filesystem+ZIP smoke checks after seeding}', function () {
    $this->info('Seeding fake user content…');

    Artisan::call('db:seed', [
        '--class' => FakeUserContentSeeder::class,
        '--force' => true,
    ]);

    $this->output->write(Artisan::output());

    if ($this->option('smoke')) {
        // Lightweight smoke output - the deeper checks live in seeder/download logging.
        $this->info('Smoke option enabled. Try downloading a seeded dataset/file from the UI.');
    }
})->purpose('Reseed fake@gmail.com datasets/repositories (and create physical files).');
