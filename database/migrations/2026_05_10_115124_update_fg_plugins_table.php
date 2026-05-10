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
        if (Schema::hasTable('fg_plugins')) {
            Schema::table('fg_plugins', function (Blueprint $table) {
                $table->string('path')->nullable()->after('version');
                $table->string('namespace')->nullable()->after('path');
                $table->timestamp('installed_at')->nullable()->after('settings');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fg_plugins')) {
            Schema::table('fg_plugins', function (Blueprint $table) {
                $table->dropColumn('path');
                $table->dropColumn('namespace');
                $table->dropColumn('installed_at');
            });
        }
    }
};
