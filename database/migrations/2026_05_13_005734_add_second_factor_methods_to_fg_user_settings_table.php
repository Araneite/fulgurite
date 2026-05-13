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
        if (!Schema::hasColumn('fg_user_settings', 'second_factor_methods')) {
            Schema::table('fg_user_settings', function (Blueprint $table) {
                $table->json('second_factor_methods')->nullable()->after('primary_second_factor');
            });
        }
        
        DB::table('fg_user_settings')
            ->orderBy('id')
            ->get()
            ->each(function ($settings) {
                $methods = [];
                
                $primary = match ($settings->primary_second_factor) {
                    'totp' => 'one_time_code',
                    'email' => 'email',
                    'passkey' => 'passkey',
                    'one_time_code' => 'one_time_code',
                    default => null,
                };
                
                if ($settings->totp_enabled) {
                    $methods[] = 'one_time_code';
                }
                
                if ($primary) {
                    $methods[] = $primary;
                }
                
                $methods = array_values(array_unique($methods));
                $newPrimary = in_array($primary, $methods, true) ? $primary : ($methods[0] ?? '0');
                
                DB::table('fg_user_settings')
                    ->where('id', $settings->id)
                    ->update([
                        'second_factor_methods' => $methods === [] ? null : json_encode($methods, JSON_THROW_ON_ERROR),
                        'primary_second_factor' => $newPrimary,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('fg_user_settings', 'second_factor_methods')) {
            Schema::table('fg_user_settings', function (Blueprint $table) {
                $table->dropColumn('second_factor_methods');
            });
        }
    }
};
