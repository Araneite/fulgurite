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
        Schema::create('fg_restore_logs', function (Blueprint $table) {
            $table->id();

            // Restore logs details
            $table->enum('type', ['full', 'partial'])->default('full');
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'canceled'])->default('success');
            $table->text('reason')->nullable();

            // Snapshot details
            $table->string('snapshot_id');
            $table->string('snapshot_host');
            $table->string('snapshot_path');

            // Recipient of restore details
            $table->string('target_path');
            $table->enum('destination_type', ['local', 'remote', 'download'])->default('local');

            // Files to restore
            $table->string('requested_path')->nullable();
            $table->json('requested_paths_json')->nullable();

            // Restore settings
            $table->enum('overwrite_policy', ['skip', 'overwrite', 'rename'])->default('skip');
            $table->boolean('include_deleted')->default(false);

            // Restore stats
            $table->bigInteger('files_restored')->nullable();
            $table->bigInteger('bytes_restored')->nullable();
            $table->integer('exit_code')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Relations
            $table->unsignedBigInteger('repo_id');
            $table->unsignedBigInteger('restored_by');
            $table->unsignedBigInteger('host_id'); // The target of restore

            $table->foreign('repo_id')->references('id')->on('fg_repos');
            $table->foreign('restored_by')->references('id')->on('fg_users');
            $table->foreign('host_id')->references('id')->on('fg_hosts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_restore_logs');
    }
};
