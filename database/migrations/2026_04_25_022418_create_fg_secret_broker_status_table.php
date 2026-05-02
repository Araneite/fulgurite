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
        Schema::create('fg_secret_broker_status', function (Blueprint $table) {
            $table->id();

            // Broker details
            $table->string('cluster_name');
            $table->string('node_id');
            $table->string('node_label');

            // Stats
            $table->enum('status', ['ok', 'down'])->default('ok');
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();

            // Timestamps
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_change_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_secret_broker_status');
    }
};
