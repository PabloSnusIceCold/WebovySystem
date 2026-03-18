<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If files table exists, migrate per-dataset legacy columns into files table
        if (Schema::hasTable('datasets')) {
            $cols = ['file_path', 'file_type', 'file_size'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('datasets', $c));

            if (!empty($existing) && Schema::hasTable('files')) {
                // Process datasets which still have legacy file_path
                $datasets = DB::table('datasets')
                    ->select('id', 'file_path', 'file_type', 'file_size')
                    ->whereNotNull('file_path')
                    ->get();

                foreach ($datasets as $d) {
                    // Avoid creating duplicate file rows
                    $exists = DB::table('files')
                        ->where('dataset_id', $d->id)
                        ->where('file_path', $d->file_path)
                        ->exists();

                    if (!$exists) {
                        DB::table('files')->insert([
                            'dataset_id' => $d->id,
                            'file_name' => basename($d->file_path),
                            'file_type' => $d->file_type ?? null,
                            'file_path' => $d->file_path,
                            'file_size' => $d->file_size ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Drop only the columns that exist
            if (!empty($existing)) {
                Schema::table('datasets', function (Blueprint $table) use ($existing) {
                    // Some DB drivers (older MySQL) need each drop separately but Laravel handles it.
                    foreach ($existing as $col) {
                        if (Schema::hasColumn('datasets', $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('datasets')) {
            Schema::table('datasets', function (Blueprint $table) {
                // Recreate legacy columns as nullable to allow rollback.
                if (!Schema::hasColumn('datasets', 'file_path')) {
                    $table->string('file_path')->nullable()->after('description');
                }
                if (!Schema::hasColumn('datasets', 'file_type')) {
                    $table->string('file_type', 20)->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('datasets', 'file_size')) {
                    $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
                }
            });
        }
    }
};

