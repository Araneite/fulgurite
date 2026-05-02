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
        Schema::create('fg_notification_deliveries', function (Blueprint $table) {
            $table->id();

            $table->string('channel');
            $table->enum('status', ['pending', 'sent', 'skipped', 'cancelled', 'failed', 'deduplicated']);
            $table->string('provider_message_id')->nullable();
            $table->integer('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->json('request_payload_json');
            $table->json('response_payload_json');

            //Relations
            $table->morphs('recipient');

            // Timestamps
            $table->timestamp('attempt_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_notification_deliveries');
    }
};
