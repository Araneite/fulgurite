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
        Schema::create('fg_repos', function (Blueprint $table) {
            $table->id();

            // Repository details
            $table->string('name');
            $table->text("description")->nullable();
            $table->integer("alert_hours")->nullable();
            $table->boolean("enabled")->default(true);

            // Notifications
            $table->boolean("notifications_enabled")->default(false);
            $table->json("notifications_policies")->nullable();

            // Relations
            $table->userStamps();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_repos');
    }
};
