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
        Schema::create('fg_job_runs', function (Blueprint $table) {
            $table->id();

            // Details
            $table->enum('trigger_type', ['schedule', 'manual', 'retry', 'system']);
            $table->string('status');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('attempts')->default(0);

            // Audition
            $table->integer('exit_code')->default(0);
            $table->string('summary')->nullable();
            $table->json('details_json')->nullable();

            // Relation
            $table->unsignedBigInteger('job_id');

            $table->foreign('job_id')->references('id')->on('fg_jobs')->onDelete('cascade');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_job_runs');
    }
};
