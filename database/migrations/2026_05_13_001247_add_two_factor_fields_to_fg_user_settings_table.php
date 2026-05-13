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
        Schema::table('fg_user_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('fg_user_settings', 'totp_secret')) {
                $table->text('totp_secret')->nullable()->after('totp_enabled');
            }
            
            if (!Schema::hasColumn('fg_user_settings', 'passkey_credentials_json')) {
                $table->json('passkey_credentials_json')->nullable()->after('totp_secret');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fg_user_settings', function (Blueprint $table) {
            $table->dropColumn(['totp_secret', 'passkey_credentials_json']);
        });
    }
};
