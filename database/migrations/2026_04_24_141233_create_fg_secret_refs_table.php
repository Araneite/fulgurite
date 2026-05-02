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
        Schema::create('fg_secret_refs', function (Blueprint $table) {
            $table->id();

            $table->enum('usage', ['private_key', 'repo_password', 'sudo_password']);
            $table->string('provider');
            $table->string('path_ref');

            // Relations
            $table->morphs("owner");
            $table->userStamps();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_secret_refs');
    }
};
