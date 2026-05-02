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

        Schema::create('fg_hook_scripts', function (Blueprint $table) {
            $table->id();

            // Details
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('status');
            $table->text('content_path');
            $table->string('checksum');
            $table->enum('execution_hook', ['pre_run', 'post_run', "on_error", "on_failure"])->default('pre_run');

            // by
            $table->userStamps();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_hook_scripts');
    }
};
