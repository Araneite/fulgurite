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
        Schema::create('fg_storage_targets', function (Blueprint $table) {
            $table->id();

            $table->enum('target_type', ["host_mount", "repo_path"]);
            $table->string("path");
            $table->string("label");
            $table->string("backend_type");

            // Relations
            $table->morphs("owner"); // Polymorph relation depends on owner_type
            $table->userStamps();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_storage_targets');
    }
};
