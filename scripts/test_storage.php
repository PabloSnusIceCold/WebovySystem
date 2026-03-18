<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Storage;
$disk = Storage::disk(config('filesystems.default','local'));
$disk->makeDirectory('temp');
$disk->put('temp/test-zip.txt', 'hello');
echo 'EXISTS: ' . (int) $disk->exists('temp/test-zip.txt') . PHP_EOL;
echo 'PATH: ' . $disk->path('temp/test-zip.txt') . PHP_EOL;

