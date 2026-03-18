<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// find a dataset with files
$dataset = Dataset::with('files')->whereHas('files')->first();
if (!$dataset) {
    echo "NO_DATASET_WITH_FILES\n";
    exit(0);
}

echo "Dataset id={$dataset->id} name={$dataset->name}\n";

$diskName = config('filesystems.default', 'local');
$disk = Storage::disk($diskName);
$tempDir = 'temp';
$disk->makeDirectory($tempDir);

$safeBase = ($dataset->name ?: 'dataset');
$safeBase = preg_replace('/[^A-Za-z0-9_\-. ]+/', '', $safeBase) ?: 'dataset';
$zipRelative = $tempDir . '/' . $safeBase . '-' . Str::random(12) . '.zip';
$zipPath = $disk->path($zipRelative);

// Ensure dir
$dir = dirname($zipPath);
if (!is_dir($dir)) mkdir($dir, 0777, true);

$zip = new \ZipArchive();
$opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
if ($opened !== true) {
    echo "ZIP_OPEN_FAILED: ".var_export($opened, true)."\n";
    exit(1);
}

$added = 0;
foreach ($dataset->files as $file) {
    echo "Checking file path: {$file->file_path}\n";
    if (!$file->file_path) continue;
    if (!$disk->exists($file->file_path)) {
        echo "File missing on disk: {$file->file_path}\n";
        continue;
    }
    $absolute = $disk->path($file->file_path);
    $nameInZip = $file->file_name ?: basename($absolute);
    if ($zip->locateName($nameInZip) !== false) $nameInZip = Str::random(6).'-'.$nameInZip;
    $zip->addFile($absolute, $nameInZip);
    $added++;
}
$zip->close();

clearstatcache(true, $zipPath);
$exists = file_exists($zipPath) ? 'yes' : 'no';

echo "ZIP path: {$zipPath}\nZIP relative: {$zipRelative}\nZIP exists: {$exists}\nFiles added: {$added}\n";

if ($exists === 'yes') {
    echo "OK\n";
}


