<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\File;
use Illuminate\Support\Facades\Storage;

$default = config('filesystems.default', 'local');
$diskDefault = Storage::disk($default);

$bad = [];
foreach (File::query()->select(['id','dataset_id','file_name','file_path'])->orderBy('id')->cursor() as $f) {
    $path = (string) ($f->file_path ?? '');
    if ($path === '') {
        $bad[] = [
            'id' => (int) $f->id,
            'dataset_id' => (int) $f->dataset_id,
            'file_name' => (string) ($f->file_name ?? ''),
            'file_path' => '',
            'resolved' => 'NULL',
            'problem' => 'empty_file_path',
        ];
        continue;
    }

    $resolved = null;
    if ($diskDefault->exists($path)) {
        $resolved = $diskDefault->path($path);
    } elseif (Storage::disk('public')->exists($path)) {
        $resolved = Storage::disk('public')->path($path);
    } elseif (Storage::exists($path)) {
        $resolved = Storage::path($path);
    }

    if (!$resolved) {
        $bad[] = [
            'id' => (int) $f->id,
            'dataset_id' => (int) $f->dataset_id,
            'file_name' => (string) ($f->file_name ?? ''),
            'file_path' => $path,
            'resolved' => 'NULL',
            'problem' => 'missing_on_all_disks',
        ];
        continue;
    }

    if (is_dir($resolved)) {
        $bad[] = [
            'id' => (int) $f->id,
            'dataset_id' => (int) $f->dataset_id,
            'file_name' => (string) ($f->file_name ?? ''),
            'file_path' => $path,
            'resolved' => $resolved,
            'problem' => 'resolved_is_directory',
        ];
        continue;
    }

    if (!is_file($resolved)) {
        $bad[] = [
            'id' => (int) $f->id,
            'dataset_id' => (int) $f->dataset_id,
            'file_name' => (string) ($f->file_name ?? ''),
            'file_path' => $path,
            'resolved' => $resolved,
            'problem' => 'resolved_not_file',
        ];
        continue;
    }
}

if (empty($bad)) {
    echo "NO_PROBLEMS\n";
    exit(0);
}

echo json_encode($bad, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
