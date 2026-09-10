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
        if (Schema::hasTable('fg_user_invitations') && Schema::hasColumns('fg_user_invitations', ['expires_at', 'revoked_at', 'accepted_at'])) {
            Schema::table('fg_user_invitations', function (Blueprint $table) {
                $table->timestamp("expires_at")->nullable()->change();
                $table->timestamp("revoked_at")->nullable()->change();
                $table->timestamp("accepted_at")->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fg_user_invitations') && Schema::hasColumns('fg_user_invitations', ['expires_at', 'revoked_at', 'accepted_at'])) {
            Schema::table('fg_user_invitations', function (Blueprint $table) {
                $table->timestamp("expires_at")->change();
                $table->timestamp("revoked_at")->change();
                $table->timestamp("accepted_at")->change();
            });
        }
    }
};
