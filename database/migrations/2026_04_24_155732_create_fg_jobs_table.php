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
        Schema::create('fg_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->boolean('enabled')->default(false);

            // Relation
            $table->morphs('jobable');

            // by
            $table->userStamps();

            //  Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_jobs');
    }
};
