<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Dataset;
use Illuminate\Support\Facades\Storage;

$dataset = Dataset::with('files')->whereHas('files')->first();
if (!$dataset) {
    echo "NO_DATASET_WITH_FILES\n";
    exit;
}

echo "Dataset: {$dataset->id} - {$dataset->name}\n";
$defaultDisk = config('filesystems.default','local');
$disk = Storage::disk($defaultDisk);

foreach ($dataset->files as $file) {
    $path = $file->file_path;
    echo "File row id={$file->id} path={$path}\n";
    echo "  on defaultDisk exists? ".(int)$disk->exists($path)."\n";
    echo "  on public exists? ".(int)Storage::disk('public')->exists($path)."\n";
    echo "  global Storage exists? ".(int)Storage::exists($path)."\n";
}

