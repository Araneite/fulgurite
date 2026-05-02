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
        Schema::create('fg_notifications_inbox', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('body');
            $table->string('severity');
            $table->string('link_url')->nullable();
            $table->boolean('is_read')->default(false);

            // Relations
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notification_event_id');

            $table->foreign('user_id')->references('id')->on('fg_users');
            $table->foreign('notification_event_id')->references('id')->on('fg_notification_events')->onDelete('cascade');

            // Timestamps
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_notifications_inbox');
    }
};
