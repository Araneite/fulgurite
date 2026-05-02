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
        Schema::create('fg_retention_policies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('keep_last');
            $table->integer('keep_daily');
            $table->integer('keep_weekly');
            $table->integer('keep_monthly');
            $table->integer('keep_yearly');
            $table->boolean('prune')->default(false);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_system')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_retention_policies');
    }
};
