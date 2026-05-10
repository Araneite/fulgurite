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
        Schema::create('fg_plugin_routes', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('plugin_id');
            $table->string('method');
            $table->string('uri');
            $table->json('core_middleware')->nullable();
            $table->boolean('enabled')->default(true);
            
            $table->timestamps();
            
            $table->unique(["plugin_id", "method", "uri"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_plugin_routes');
    }
};
