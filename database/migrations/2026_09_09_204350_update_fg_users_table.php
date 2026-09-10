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
        if (Schema::hasTable('fg_users') && !Schema::hasColumn('fg_users', 'remember_token')) {
            Schema::table('fg_users', function (Blueprint $table) {
                $table->string('remember_token')->nullable()->after('last_login')  ;
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fg_users') && Schema::hasColumn('fg_users', 'remember_token')) {
            Schema::table('fg_users', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }
};
