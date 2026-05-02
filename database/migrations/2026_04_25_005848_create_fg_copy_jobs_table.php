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
        Schema::create('fg_copy_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('dest_path');
            $table->boolean('schedule_enabled')->default(false);
            $table->json('notification_policy')->nullable();
            $table->json('retry_policy')->nullable();

            // Relations
            $table->unsignedBigInteger('repo_src_id');
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('host_id');
            $table->unsignedBigInteger('repo_src_password_ref');
            $table->unsignedBigInteger('repo_dest_password_ref');
            $table->unsignedBigInteger('src_ssh_key_ref');
            $table->unsignedBigInteger('dest_ssh_key_ref');
            $table->unsignedBigInteger('src_sudo_password_ref')->nullable();
            $table->unsignedBigInteger('dest_sudo_password_ref')->nullable();

            $table->foreign('repo_src_id')->references('id')->on('fg_repos');
            $table->foreign('job_id')->references('id')->on('fg_jobs');
            $table->foreign('host_id')->references('id')->on('fg_hosts');
            $table->foreign('repo_src_password_ref')->references('id')->on('fg_secret_refs');
            $table->foreign('repo_dest_password_ref')->references('id')->on('fg_secret_refs');
            $table->foreign('src_ssh_key_ref')->references('id')->on('fg_ssh_keys');
            $table->foreign('dest_ssh_key_ref')->references('id')->on('fg_ssh_keys');
            $table->foreign('src_sudo_password_ref')->references('id')->on('fg_secret_refs');
            $table->foreign('dest_sudo_password_ref')->references('id')->on('fg_secret_refs');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_copy_jobs');
    }
};
