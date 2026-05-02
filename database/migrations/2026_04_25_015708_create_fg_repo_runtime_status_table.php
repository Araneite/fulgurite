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
        Schema::create('fg_repo_runtime_status', function (Blueprint $table) {
            $table->id();

            $table->integer('snapshot_count');
            $table->decimal('freshness_hours');
            $table->bigInteger('total_size_bytes');
            $table->bigInteger('total_files_count');
            $table->integer('last_error_int')->nullable();
            $table->text('last_error_message')->nullable();
            $table->enum('status', ['ok', 'warning', 'error', 'no_snap', 'unknown'])->default('unknown');

            // Relations
            $table->unsignedBigInteger('repo_id');

            $table->foreign('repo_id')->references('id')->on('fg_repos');

            // Timestamps
            $table->timestamp('last_snapshot_at')->nullable();
            $table->timestamp('last_successfull_read_at')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_repo_runtime_status');
    }
};
