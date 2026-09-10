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
        if (!Schema::hasTable('fg_user_invitations') || !Schema::hasColumns('fg_user_invitations', ['user_id', 'user_setting_id', 'contact_id'])) return;
        
        Schema::table('fg_user_invitations', function (Blueprint $table) {
            // Drop existing relations with user
            $table->dropForeign('fg_user_invitations_user_id_foreign');
            $table->dropForeign('fg_user_invitations_user_setting_id_foreign');
            $table->dropForeign('fg_user_invitations_contact_id_foreign');
            
            $table->dropColumn('user_id');
            $table->dropColumn('user_setting_id');
            $table->dropColumn('contact_id');
            
            // Create new structure
            $table->string('email')->after('id');
            $table->string('username')->nullable()->after('email');
            $table->json('payload')->after('username');
            $table->string('status')->after('token_hash');
            
            $table->unsignedBigInteger('accepted_user_id')->nullable()->after('revoked_at');
            $table->foreign('accepted_user_id')->references('id')->on('fg_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('fg_user_invitations') || !Schema::hasColumns('fg_user_invitations', ['email', 'username', 'payload', 'status', 'accepted_user_id'])) return;
        
        Schema::table('fg_user_invitations', function (Blueprint $table) {
            // Drop relation
            $table->dropForeign('fg_user_invitations_accepted_user_id_foreign');
            $table->dropColumn('accepted_user_id');
            
            // Drop columns
            $table->dropColumn('email');
            $table->dropColumn('username');
            $table->dropColumn('payload');
            $table->dropColumn('status');
            
            // Restore old columns
            $table->unsignedBigInteger('user_id')->nullable()->after('revoked_at');
            $table->unsignedBigInteger('user_setting_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('contact_id')->nullable()->after('user_setting_id');
            
            // Restore relations
            $table->foreign('user_id')->references('id')->on('fg_users');
            $table->foreign('user_setting_id')->references('id')->on('fg_user_settings');
            $table->foreign('contact_id')->references('id')->on('fg_contacts');
        });
    }
};
