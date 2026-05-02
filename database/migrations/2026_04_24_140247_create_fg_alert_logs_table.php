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
        Schema::create('fg_alert_logs', function (Blueprint $table) {
            $table->id();

            $table->string('repo_name');
            $table->string('alert_type');
            $table->text('message');
            $table->boolean('notified')->default(false);

            // Relations
            $table->unsignedBigInteger("repo_id");
            $table->userStamps();


            $table->foreign('repo_id')->references('id')->on('fg_repos')->onDelete(null);

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_alert_logs');
    }
};
