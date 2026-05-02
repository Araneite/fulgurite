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
        Schema::create('fg_hookables', function (Blueprint $table) {
            $table->id();

            $table->string('event');
            $table->boolean('enabled')->default(false);
            $table->integer('priority')->boolean(1);
            $table->json('config_json')->nullable();

            // Relations
            $table->morphs("hookable");


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_hookables');
    }
};
