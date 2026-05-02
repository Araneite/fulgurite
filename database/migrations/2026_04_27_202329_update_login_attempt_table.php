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
        if (!Schema::hasColumns("fg_login_attempts", ['user_agent', 'scope'])) {
            Schema::table("fg_login_attempts", function (Blueprint $table) {
                $table->string('user_agent')->after('ip_address');
                $table->string('scope', 20)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
