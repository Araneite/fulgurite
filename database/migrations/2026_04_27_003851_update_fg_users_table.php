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
        
        if (Schema::hasColumn("fg_users", "role")) Schema::dropColumns("fg_users", "role");
        if (Schema::hasColumn("fg_users", "role_id")) return;

        Schema::table('fg_users', function (Blueprint $table) {
            $table->unsignedBigInteger("role_id")->default(1);
            
            $table->foreign('role_id')->references('id')->on('fg_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
