<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // USERS: remove deleted_at if present
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        // DATASETS: remove deleted_at if present
        Schema::table('datasets', function (Blueprint $table) {
            if (Schema::hasColumn('datasets', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->index();
            }
        });

        Schema::table('datasets', function (Blueprint $table) {
            if (!Schema::hasColumn('datasets', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->index();
            }
        });
    }
};

