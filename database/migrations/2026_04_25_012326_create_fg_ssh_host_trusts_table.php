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
        Schema::create('fg_ssh_host_trusts', function (Blueprint $table) {
            $table->id();

            $table->string('remote_host');
            $table->integer('remote_port');
            $table->string('approved_key_type');
            $table->string('approved_fingerprint');
            $table->string('detected_key_type');
            $table->string('detected_fingerprint');
            $table->string('previous_fingerprint')->nullable();
            $table->string('status');
            $table->string('last_context');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Relations
            $table->unsignedBigInteger('approved_key_ref');
            $table->unsignedBigInteger('detected_key_ref');
            $table->unsignedBigInteger('approved_by');

            $table->foreign('approved_key_ref')->references('id')->on('fg_secret_refs');
            $table->foreign('detected_key_ref')->references('id')->on('fg_secret_refs');
            $table->foreign('approved_by')->references('id')->on('fg_users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_ssh_host_trusts');
    }
};
