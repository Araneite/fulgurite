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
        Schema::create('fg_user_settings', function (Blueprint $table) {
            $table->id();

            // User settings
            $table->string("preferred_locale")->default("en/us");
            $table->string("preferred_timezone")->default("UTC");
            $table->string("preferred_start_page")->default("dashboard");
            // Scopes
            $table->string("repo_scope_mode")->default("all");
            $table->json("repo_scope_json")->nullable();
            $table->string("host_scope_mode")->default("all");
            $table->json("host_scope_json")->nullable();
            // Actions
            $table->json("force_actions_json")->nullable();

            // Security
            $table->string("primary_second_factor")->default("0");
            $table->boolean("totp_enabled")->default(false);

            // Relations
            $table->unsignedBigInteger("totp_secret_id")->boolean();
            $table->unsignedBigInteger("user_id");

            //  TODO : Table fg_secret_refs
            // $table->foreign("totp_secret_id")->references("id")->on("fg_secret_refs")->onDelete("cascade");
            $table->foreign("user_id")->references("id")->on("fg_users")->onDelete(null);

            $table->timestamps();
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_user_settings');
    }
};
