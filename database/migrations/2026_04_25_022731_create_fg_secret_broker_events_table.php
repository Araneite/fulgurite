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
        Schema::create('fg_secret_broker_events', function (Blueprint $table) {
            $table->id();

            // Brokder details
            $table->string('endpoint');
            $table->string('cluster_name');
            $table->string('node_id');
            $table->string('node_label');

            // Logs
            $table->string('event_type');
            $table->enum('severity', ['success', 'info', 'warning', 'error', 'unknown'])->default('info');
            $table->text('message');
            $table->json('details_json')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_secret_broker_events');
    }
};
