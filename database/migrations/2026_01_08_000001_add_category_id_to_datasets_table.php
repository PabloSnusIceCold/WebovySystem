<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Add as nullable first to keep existing rows valid.
        if (!Schema::hasColumn('datasets', 'category_id')) {
            Schema::table('datasets', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('user_id');
            });
        }

        // 2) Ensure there is at least one default category and backfill existing datasets.
        $defaultCategoryId = DB::table('categories')->where('name', 'Uncategorized')->value('id');

        if (!$defaultCategoryId) {
            $defaultCategoryId = (int) DB::table('categories')->insertGetId([
                'name' => 'Uncategorized',
                'description' => 'Default category created during migration.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultCategoryId = (int) $defaultCategoryId;
        }

        // Fill NULLs.
        DB::table('datasets')
            ->whereNull('category_id')
            ->update(['category_id' => $defaultCategoryId]);

        // Fix invalid values (category_id pointing to non-existing categories).
        // Different SQL dialects need different approach (SQLite doesn't support UPDATE ... JOIN).
        $driver = (string) DB::connection()->getDriverName();
        $dbName = (string) DB::connection()->getDatabaseName();

        if ($driver === 'mysql') {
            // MySQL-safe update with LEFT JOIN.
            DB::statement(
                "UPDATE `datasets` d " .
                "LEFT JOIN `categories` c ON c.id = d.category_id " .
                "SET d.category_id = ? " .
                "WHERE c.id IS NULL",
                [$defaultCategoryId]
            );
        } else {
            // SQLite / others: update rows where category_id is not present in categories.
            $validCategoryIds = DB::table('categories')->pluck('id')->all();
            if (!empty($validCategoryIds)) {
                DB::table('datasets')
                    ->whereNotIn('category_id', $validCategoryIds)
                    ->update(['category_id' => $defaultCategoryId]);
            }
        }

        // 3) Make category_id required.
        Schema::table('datasets', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });

        // 4) Add FK + index only if they don't exist.
        // information_schema doesn't exist on SQLite -> just try to add constraints/indexes.
        if ($driver !== 'mysql') {
            // SQLite: foreign keys / indexes are generally safe to attempt (may no-op depending on sqlite config)
            try {
                Schema::table('datasets', function (Blueprint $table) {
                    $table->foreign('category_id')
                        ->references('id')
                        ->on('categories')
                        ->cascadeOnDelete();
                });
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                Schema::table('datasets', function (Blueprint $table) {
                    $table->index('category_id');
                });
            } catch (\Throwable $e) {
                // ignore
            }

            return;
        }

        $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'datasets')
            ->where('COLUMN_NAME', 'category_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (!$fkExists) {
            Schema::table('datasets', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        $indexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'datasets')
            ->where('INDEX_NAME', 'datasets_category_id_index')
            ->exists();

        if (!$indexExists) {
            Schema::table('datasets', function (Blueprint $table) {
                $table->index('category_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        // We keep categories table intact (it may contain real data).
    }
};
