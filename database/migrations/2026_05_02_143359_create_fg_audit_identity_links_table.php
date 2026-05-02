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
        Schema::create('fg_audit_identity_links', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('action_log_id')->unique();

            // Technical history references
            $table->unsignedBigInteger('user_id_snapshot')->nullable();
            $table->string('actor_identifier', 100);
            $table->string('actor_username_snapshot')->nullable();

            // Sensitive data encrypted application level
            $table->text('email_encrypted')->nullable();
            $table->text('first_name_encrypted')->nullable();
            $table->text('last_name_encrypted')->nullable();
            $table->text('company_name_encrypted')->nullable();

            // Usage
            $table->string('purpose', 50)->default('legal_defense');
            $table->timestamp('retention_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('action_log_id')->references('id')->on('fg_action_logs')->onDelete("cascade");

            $table->index('actor_identifier');
            $table->index('retention_until');
            $table->index('purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_audit_identity_links');
    }
};
