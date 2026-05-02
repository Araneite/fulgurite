<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fg_storage_measurements', function (Blueprint $table) {
            $table->id();

            //
            $table->enum('metric_kind', ['filesystem', 'directory_size'])->default('directory_size');
            $table->enum('status', ['success', 'warning', 'error', 'unknown'])->default('unknown');
            $table->bigInteger('total_bytes')->nullable();
            $table->bigInteger('free_bytes')->nullable();
            $table->bigInteger('used_bytes')->nullable();
            $table->bigInteger('available_bytes')->nullable();
            $table->bigInteger('files_count')->nullable();
            $table->decimal('usage_percent')->nullable();
            $table->json('details_json')->nullable();

            // Relations
            $table->morphs('target');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_stroage_measurements');
    }
};
