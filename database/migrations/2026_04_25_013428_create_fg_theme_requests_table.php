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
        Schema::create('fg_theme_requests', function (Blueprint $table) {
            $table->id();

            $table->enum('source_type', ['file_upload', 'url']);
            $table->string('source_url')->nullable();
            $table->string('source_filename')->nullable();
            $table->string('store_entry_key')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('request_payoad_json')->nullable();
            $table->string('status');
            $table->string('review_notes')->nullable();
            $table->string('installed_theme_key');

            // Relations
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();

            $table->foreign('requested_by')->references('id')->on('fg_users');
            $table->foreign('reviewed_by')->references('id')->on('fg_users');

            // Timestamps
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_requests');
    }
};
