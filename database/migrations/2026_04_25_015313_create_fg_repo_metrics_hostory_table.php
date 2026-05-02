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
        Schema::create('fg_repo_metrics_hostory', function (Blueprint $table) {
            $table->id();

            $table->string('bucket_granularity');
            $table->integer('snapshot_count');
            $table->bigInteger('total_size_bytes');
            $table->bigInteger('total_file_count');
            $table->string('source');
            $table->text('last_error_message');

            // Relations
            $table->unsignedBigInteger('repo_id');

            $table->foreign('repo_id')->references('id')->on('fg_repos');

            // Timestamps
            $table->timestamp('bucket_start_at')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_repo_metrics_hostory');
    }
};
