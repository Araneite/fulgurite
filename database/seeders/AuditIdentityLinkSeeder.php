<?php

namespace Database\Seeders;

use App\Models\ActionLog;
use App\Models\AuditIdentityLink;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuditIdentityLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->limit(3)->get();
        $logs = ActionLog::query()->limit(3)->get();

        foreach ($logs as $index => $log) {
            $user = $users->get($index % max($users->count(), 1));

            if (!$user) {
                continue;
            }

            AuditIdentityLink::updateOrCreate(
                ['action_log_id' => $log->id],
                [
                    'user_id_snapshot' => $user->id,
                    'actor_identifier' => 'user#' . $user->id,
                    'actor_username_snapshot' => $user->username,
                    'email_encrypted' => $user->email,
                    'first_name_encrypted' => $user->first_name,
                    'last_name_encrypted' => $user->last_name,
                    'company_name_encrypted' => null,
                    'purpose' => 'legal_defense',
                    'retention_until' => now()->addYear(),
                    'created_by' => $user->id,
                ]
            );
        }
    }
}
