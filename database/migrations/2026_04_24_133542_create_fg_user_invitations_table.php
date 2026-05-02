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
        Schema::create('fg_user_invitations', function (Blueprint $table) {
            $table->id();

            $table->string("token_hash");

            // Timestamps
            $table->timestamp("expires_at");
            $table->timestamp("accepted_at");
            $table->timestamp("revoked_at");

            // Relations
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("user_setting_id");
            $table->unsignedBigInteger("invited_by");
            $table->unsignedBigInteger("contact_id");

            $table->foreign('user_id')->references("id")->on("fg_users")->onDelete(null);
            $table->foreign('user_setting_id')->references("id")->on("fg_user_settings")->onDelete(null);
            $table->foreign('invited_by')->references("id")->on("fg_users")->onDelete(null);
            $table->foreign('contact_id')->references("id")->on("fg_contacts")->onDelete(null);

            $table->timestamps();
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fg_user_invitations');
    }
};
