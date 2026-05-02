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
        Schema::create('fg_notification_events', function (Blueprint $table) {
            $table->id();

            $table->string('profile_key');
            $table->enum('event_key', ['failed', 'success', 'critical', 'warning', 'info', 'emergency']);
            $table->string('dedupe_key')->nullable();
            $table->string('title_template');
            $table->text('body_template')->nullable();
            $table->json('payload_json')->nullable();

            // Relations
            $table->morphs('context');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_notification_events');
    }
};
