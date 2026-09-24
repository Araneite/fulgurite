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
        // TODO : Remove this code and find a solution to alter the table for SQLite databases
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        DB::statement("
            ALTER TABLE fg_roles
            ADD CONSTRAINT chk_roles_scope_project
            CHECK (
                (scope = 'system' AND project_id IS NULL)
                OR
                (scope = 'project' AND project_id IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        DB::statement("
            ALTER TABLE fg_roles
            DROP CONSTRAINT chk_roles_scope_project
        ");
    }
};
