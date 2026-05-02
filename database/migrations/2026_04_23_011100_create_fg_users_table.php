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
        Schema::create('fg_users', function (Blueprint $table) {
            $table->id();

            // Login information
            $table->string("username")->unique();
            $table->string("email")->unique();
            $table->string("password");

            // Admin management
            $table->string("role");
            $table->text("admin_notes")->nullable();

            // Suspension & access
            $table->timestamp("suspended_until")->nullable();
            $table->text("suspension_reason")->nullable();
            $table->timestamp("expire_at")->nullable();
            $table->boolean("active")->default(false);

            $table->timestamp("password_set_at");

            // Relations columns
            $table->bigInteger("last_login")->nullable();
            $table->unsignedBigInteger("contact_id")->nullable();
            $table->unsignedBigInteger("user_settings_id")->nullable();
            $table->unsignedBigInteger("created_by")->nullable();
            $table->unsignedBigInteger("updated_by")->nullable();
            $table->unsignedBigInteger("deleted_by")->nullable();

            $table->foreign('created_by')->references('id')->on('fg_users');
            $table->foreign('updated_by')->references('id')->on('fg_users');
            $table->foreign('deleted_by')->references('id')->on('fg_users');

            //Indexes
            $table->index("contact_id");
            $table->index("user_settings_id");
            $table->index("created_by");
            $table->index("updated_by");
            $table->index("deleted_by");

            //  Timestamps
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('fg_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('fg_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_users');
        Schema::dropIfExists('fg_password_reset_tokens');
        Schema::dropIfExists('fg_sessions');
    }
};
