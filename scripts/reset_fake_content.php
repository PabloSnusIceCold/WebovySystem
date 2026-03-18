<?php
// scripts/reset_fake_content.php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dataset;
use App\Models\File;
use App\Models\Repository;
use Illuminate\Support\Facades\Storage;

$email = 'fake@gmail.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User {$email} not found.\n";
    exit(0);
}

$uid = $user->id;

// Delete datasets and their files (DB rows and physical files)
$datasets = Dataset::where('user_id', $uid)->get();
$deletedDatasets = 0;
$deletedFiles = 0;
foreach ($datasets as $d) {
    // delete physical files
    foreach ($d->files as $f) {
        if (!empty($f->file_path) && Storage::exists($f->file_path)) {
            Storage::delete($f->file_path);
            $deletedFiles++;
        }
    }
    // also try legacy path on dataset
    if (!empty($d->file_path) && Storage::exists($d->file_path)) {
        Storage::delete($d->file_path);
        $deletedFiles++;
    }

    // delete file rows
    $d->files()->delete();

    // delete dataset
    $d->delete();
    $deletedDatasets++;
}

// Delete repositories for this user
$deletedRepos = Repository::where('user_id', $uid)->delete();

// Optionally delete storage directory seeds
$seedDir = "datasets/seed/fake/{$uid}";
if (Storage::exists($seedDir)) {
    Storage::deleteDirectory($seedDir);
}

// Finally, delete the user as well (so seeder will recreate clean)
// BUT we should not delete if you want to keep the user. Seeder uses updateOrCreate; to ensure fresh, delete user.
$user->delete();

echo "Deleted datasets: {$deletedDatasets}, deleted files: {$deletedFiles}, deleted repos: {$deletedRepos}.\n";
echo "User {$email} (id={$uid}) removed.\n";

