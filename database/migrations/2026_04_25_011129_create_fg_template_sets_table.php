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
        Schema::create('fg_template_sets', function (Blueprint $table) {
            $table->id();

            $table->enum('template_type', ['backup', 'copy', 'notification', 'report']);
            $table->string('template_key');
            $table->string('source_kind');

            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->json('badges')->nullable();
            $table->boolean('is_editable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('version')->default('1.0.0');

            // Relations
            $table->unsignedBigInteger('parent_template_id')->nullable();
            $table->userStamps();

            $table->foreign('parent_template_id')->references('id')->on('fg_template_sets');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_template_sets');
    }
};
