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
        Schema::create('fg_job_schedules', function (Blueprint $table) {
            $table->id();

            $table->enum('frequency_type', ['daily, weekly', 'monthly', 'interval']);
            $table->json('run_days');
            $table->json('run_hours');
            $table->json('interval_hours');

            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();

            // Relations
            $table->unsignedBigInteger('job_id');

            $table->foreign('job_id')->references('id')->on('fg_jobs')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_job_schedules');
    }
};
