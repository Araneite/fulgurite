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
        Schema::create('fg_backup_jobs', function (Blueprint $table) {
            $table->id();

            // Backup jobs details
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('source_paths');
            $table->json('tags');
            $table->json('excludes');

            // Remote details
            $table->string('remote_repo_path');
            $table->string('hostname_override');

            // Retentions
            $table->boolean('retention_enabled')->default(false);
            $table->json('retention_override_json')->nullable();

            // Scheduler
            $table->boolean('schedule_enabled')->default(true);
            // Notification
            $table->json('notification_policy');
            // Retry policy
            $table->json('retry_policy');

            // Relations
            $table->unsignedBigInteger('rentention_policy_id')->nullable();

            $table->foreign('rentention_policy_id')->references('id')->on('fg_retention_policies');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_backup_jobs');
    }
};
