<?php
// scripts/force_delete_all.php
require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (Throwable $e) {
    echo "Failed to bootstrap app: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

use App\Models\Dataset;
use App\Models\Repository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

try {
    $count = Dataset::count();
    echo "DATASETS_BEFORE=" . $count . PHP_EOL;
} catch (Throwable $e) {
    echo "DB_ERROR: cannot query datasets: " . $e->getMessage() . PHP_EOL;
    exit(2);
}

$datasets = Dataset::with('files')->get();
$deletedDatasets = 0;
$deletedFiles = 0;
foreach ($datasets as $d) {
    foreach ($d->files as $f) {
        $path = $f->file_path;
        if ($path) {
            try {
                $default = config('filesystems.default', 'local');
                if (Storage::disk($default)->exists($path)) {
                    Storage::disk($default)->delete($path);
                    $deletedFiles++;
                } elseif (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $deletedFiles++;
                } elseif (Storage::exists($path)) {
                    Storage::delete($path);
                    $deletedFiles++;
                }
            } catch (Throwable $e) {
                echo "WARN: failed deleting file {$path}: " . $e->getMessage() . PHP_EOL;
            }
        }
    }
    $d->files()->delete();
    $d->delete();
    $deletedDatasets++;
}

$repos = Repository::count();
Repository::query()->delete();

echo "DELETED_DATASETS={$deletedDatasets}\nDELETED_FILES={$deletedFiles}\nDELETED_REPOS={" . $repos . "}\n";

try {
    $after = Dataset::count();
    echo "DATASETS_AFTER=" . $after . PHP_EOL;
} catch (Throwable $e) {
    echo "DB_ERROR_AFTER: " . $e->getMessage() . PHP_EOL;
}

// list storage folder sizes
$storageDir = __DIR__ . '/../storage/app';
if (is_dir($storageDir)) {
    echo "STORAGE_APP_LISTING:\n";
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storageDir));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $rel = substr($file->getPathname(), strlen(__DIR__ . '/../'));
            echo "F: {$rel} (" . $file->getSize() . ")\n";
        }
    }
}

echo "DONE\n";

