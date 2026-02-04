<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dataset_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['dataset_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_likes');
    }
};

