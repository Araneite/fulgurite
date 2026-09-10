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
        if (Schema::hasColumn('fg_users', 'role_id')) {
            Schema::table('fg_users', function (Blueprint $table) {
                $table->dropForeign('fg_users_role_id_foreign');
                
                $table->dropColumn('role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fg_users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('password');
            
            $table->foreign('role_id')->references('id')->on('fg_roles');
        });
    }
};
