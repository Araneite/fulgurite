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
        Schema::create('fg_app_settings', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('setting_key');
            $table->enum('value_format', ['text', 'json'])->default('text');
            $table->text('value_text');
            $table->json('value_json');

            // UI
            $table->string('category');
            $table->string('label');
            $table->text('description')->nullable();

            // Permissions
            $table->boolean('is_sensitive');
            $table->boolean('is_locked');

            // Relations
            $table->unsignedBigInteger('updated_by');
            $table->foreign('updated_by')->references('id')->on('fg_users');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_app_settings');
    }
};
