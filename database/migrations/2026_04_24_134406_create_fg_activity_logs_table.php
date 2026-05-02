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
        Schema::create('fg_activity_logs', function (Blueprint $table) {
            $table->id();

            // Log details
            $table->string('username');
            $table->string('action');
            $table->json('details')->nullable();
            $table->string('ip_address');
            $table->string('user_agent');
            $table->string('severity');

            // Relations
            $table->unsignedBigInteger("user_id");

            $table->foreign("user_id")->references("id")->on("fg_users")->onDelete(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_activity_logs');
    }
};
