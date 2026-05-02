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
        Schema::create('fg_action_logs', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_role')->nullable();
            
            $table->string('severity', 20);
            $table->string('action', 150);
            $table->string('description')->nullable();
            
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            
            $table->json('metadata')->nullable();
            
            // Relations
            $table->foreign('user_id')->references('id')->on('fg_users')->onDelete(null);
            
            $table->timestamps();
            
            // Index
            $table->index('user_id');
            $table->index('action');
            $table->index('severity');
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_action_logs');
    }
};
