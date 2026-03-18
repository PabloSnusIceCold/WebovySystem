<?php
// scripts/smoke_download_pipeline.php
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Dataset;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// ensure we have at least one user+category
$user = User::query()->first();
$cat = Category::query()->first();
if (!$user || !$cat) {
    echo "Missing user or category (need at least 1 of each).\n";
    exit(1);
}

Auth::login($user);

$diskName = config('filesystems.default', 'local');
$disk = Storage::disk($diskName);

// create a physical file with content
$content = "Smoke test file - " . date('c') . "\n";
$relPath = 'datasets/smoke-' . uniqid() . '.txt';
$disk->put($relPath, $content);

$dataset = Dataset::create([
    'user_id' => $user->id,
    'category_id' => $cat->id,
    'is_public' => true,
    'name' => 'Smoke dataset ' . uniqid(),
    'description' => 'smoke',
]);

$file = $dataset->files()->create([
    'file_name' => basename($relPath),
    'file_type' => 'TXT',
    'file_path' => $relPath,
    'file_size' => strlen($content),
]);

$abs = $disk->path($relPath);
$ok = $disk->exists($relPath) && is_file($abs);

echo "DISK={$diskName}\n";
echo "DATASET_ID={$dataset->id}\n";
echo "FILE_ID={$file->id}\n";
echo "REL={$relPath}\n";
echo "ABS={$abs}\n";
echo "EXISTS=" . ($ok ? 'yes' : 'no') . "\n";

echo "DONE\n";

