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
        Schema::create('fg_active_sessions', function (Blueprint $table) {
            $table->id();

            // Hashed token
            $table->string("session_token");
            $table->string("ip_address");
            $table->string("user_agent");

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
        Schema::dropIfExists('fg_active_sessions');
    }
};
