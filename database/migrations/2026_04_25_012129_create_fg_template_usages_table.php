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
        Schema::create('fg_template_usages', function (Blueprint $table) {
            $table->id();

            $table->enum('usage_kind', ['prefill', 'create_form', 'applied_to']);

            // Relations
            $table->unsignedBigInteger('template_set_id');

            $table->foreign('template_set_id')->references('id')->on('fg_template_sets');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_template_usages');
    }
};
