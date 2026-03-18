<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Repository;
use App\Models\Dataset;
use App\Models\File;

$email = 'fake@gmail.com';
$user = User::where('email', $email)->first();
if (!$user) {
    echo "NO_USER\n";
    exit(0);
}
$uid = $user->id;
$repos = Repository::where('user_id', $uid)->count();
$datasets = Dataset::where('user_id', $uid)->count();
$fileCount = File::whereIn('dataset_id', Dataset::where('user_id', $uid)->pluck('id'))->count();

echo "USER_ID={$uid}\nREPOS={$repos}\nDATASETS={$datasets}\nFILES={$fileCount}\n";

