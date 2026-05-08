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
        Schema::create('fg_plugins', function (Blueprint $table) {
            $table->id();
            
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('provider');
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_plugins');
    }
};
