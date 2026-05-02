<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProjectDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::create(2026, 4, 27, 10, 30, 0, config('app.timezone'));

        $this->resetTables();

        $core = $this->seedCoreData($now);
        $assets = $this->seedInfrastructureData($core, $now);
        $jobs = $this->seedOperationalData($core, $assets, $now);

        $this->seedTemplateData($core, $now);
        $this->seedNotificationData($core, $assets, $now);
        $this->seedMonitoringData($core, $assets, $jobs, $now);
    }

    private function resetTables(): void
    {
        $tables = [
            'fg_notifications_inbox',
            'fg_notification_deliveries',
            'fg_notification_events',
            'fg_theme_requests',
            'fg_ssh_host_trusts',
            'fg_template_usages',
            'fg_template_payloads',
            'fg_template_sets',
            'fg_copy_jobs',
            'fg_restore_logs',
            'fg_storage_measurements',
            'fg_repo_runtime_status',
            'fg_repo_metrics_hostory',
            'fg_job_runs',
            'fg_job_schedules',
            'fg_jobs',
            'fg_hookables',
            'fg_hook_scripts',
            'fg_backup_jobs',
            'fg_hosts',
            'fg_ssh_keys',
            'fg_secret_broker_events',
            'fg_secret_broker_status',
            'fg_secret_refs',
            'fg_storage_targets',
            'fg_alert_logs',
            'fg_repos',
            'fg_retention_policies',
            'fg_activity_logs',
            'fg_active_sessions',
            'fg_user_invitations',
            'fg_user_settings',
            'fg_contacts',
            'fg_user_roles',
            'fg_roles',
            'fg_app_settings',
            'fg_login_attempts',
            'fg_password_reset_tokens',
            'fg_sessions',
            'fg_users',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * @return array<string, mixed>
     */
    private function seedCoreData(CarbonImmutable $now): array
    {
        $roleIds = [
            'super_admin' => DB::table('fg_roles')->insertGetId([
                'name' => 'Super Administrator',
                'permissions' => json_encode(['users:*', 'roles:*', 'repos:*', 'jobs:*', 'settings:*'], JSON_THROW_ON_ERROR),
                'scope' => 'system',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            'operator' => DB::table('fg_roles')->insertGetId([
                'name' => 'Operations Manager',
                'permissions' => json_encode(['repos:view', 'jobs:run', 'jobs:view', 'restores:create'], JSON_THROW_ON_ERROR),
                'scope' => 'app',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            'auditor' => DB::table('fg_roles')->insertGetId([
                'name' => 'Security Auditor',
                'permissions' => json_encode(['repos:view', 'logs:view', 'reports:view'], JSON_THROW_ON_ERROR),
                'scope' => 'app',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
        ];

        $adminId = DB::table('fg_users')->insertGetId([
            'username' => 'admin',
            'email' => 'admin@fulgurite.local',
            'password' => Hash::make('Admin123!'),
            'role_id' => $roleIds['super_admin'],
            'admin_notes' => 'Compte principal de demonstration avec acces complet.',
            'suspended_until' => null,
            'suspension_reason' => null,
            'expire_at' => null,
            'active' => true,
            'password_set_at' => $now->subDays(120),
            'last_login' => $now->subMinutes(8)->getTimestamp(),
            'contact_id' => null,
            'user_settings_id' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
            'created_at' => $now->subDays(180),
            'updated_at' => $now->subDays(2),
            'deleted_at' => null,
        ]);

        $operatorId = DB::table('fg_users')->insertGetId([
            'username' => 'ops_manager',
            'email' => 'ops.manager@fulgurite.local',
            'password' => Hash::make('OpsManager123!'),
            'role_id' => $roleIds['operator'],
            'admin_notes' => 'Responsable des sauvegardes et des restaurations quotidiennes.',
            'suspended_until' => null,
            'suspension_reason' => null,
            'expire_at' => null,
            'active' => true,
            'password_set_at' => $now->subDays(45),
            'last_login' => $now->subHours(3)->getTimestamp(),
            'contact_id' => null,
            'user_settings_id' => null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'deleted_by' => null,
            'created_at' => $now->subDays(90),
            'updated_at' => $now->subDay(),
            'deleted_at' => null,
        ]);

        $auditorId = DB::table('fg_users')->insertGetId([
            'username' => 'security_audit',
            'email' => 'security.audit@fulgurite.local',
            'password' => Hash::make('Audit123!'),
            'role_id' => $roleIds['auditor'],
            'admin_notes' => 'Compte en lecture pour verifier les incidents et les journaux.',
            'suspended_until' => null,
            'suspension_reason' => null,
            'expire_at' => $now->addMonths(6),
            'active' => true,
            'password_set_at' => $now->subDays(12),
            'last_login' => $now->subDays(1)->getTimestamp(),
            'contact_id' => null,
            'user_settings_id' => null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'deleted_by' => null,
            'created_at' => $now->subDays(35),
            'updated_at' => $now->subDays(1),
            'deleted_at' => null,
        ]);

        $contactIds = [
            'admin' => DB::table('fg_contacts')->insertGetId([
                'first_name' => 'Alice',
                'last_name' => 'Martin',
                'phone' => 612345678,
                'phone_extension' => 101,
                'job_title' => 'Platform Administrator',
                'user_id' => $adminId,
                'created_at' => $now->subDays(180),
                'updated_at' => $now->subDays(3),
            ]),
            'operator' => DB::table('fg_contacts')->insertGetId([
                'first_name' => 'Benoit',
                'last_name' => 'Carpentier',
                'phone' => 623456789,
                'phone_extension' => 204,
                'job_title' => 'Backup Operations Lead',
                'user_id' => $operatorId,
                'created_at' => $now->subDays(90),
                'updated_at' => $now->subDays(2),
            ]),
            'auditor' => DB::table('fg_contacts')->insertGetId([
                'first_name' => 'Claire',
                'last_name' => 'Roussel',
                'phone' => 634567891,
                'phone_extension' => 305,
                'job_title' => 'Security Auditor',
                'user_id' => $auditorId,
                'created_at' => $now->subDays(35),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        $settingIds = [
            'admin' => DB::table('fg_user_settings')->insertGetId([
                'preferred_locale' => 'fr_FR',
                'preferred_timezone' => 'Europe/Paris',
                'preferred_start_page' => 'dashboard',
                'repo_scope_mode' => 'all',
                'repo_scope_json' => json_encode(['include' => ['*']], JSON_THROW_ON_ERROR),
                'host_scope_mode' => 'all',
                'host_scope_json' => json_encode(['include' => ['*']], JSON_THROW_ON_ERROR),
                'force_actions_json' => json_encode(['allow_manual_restore' => true], JSON_THROW_ON_ERROR),
                'primary_second_factor' => 'totp',
                'totp_enabled' => true,
                'totp_secret_id' => 0,
                'user_id' => $adminId,
                'created_at' => $now->subDays(180),
                'updated_at' => $now->subDays(2),
            ]),
            'operator' => DB::table('fg_user_settings')->insertGetId([
                'preferred_locale' => 'fr_FR',
                'preferred_timezone' => 'Europe/Paris',
                'preferred_start_page' => 'repositories',
                'repo_scope_mode' => 'subset',
                'repo_scope_json' => json_encode(['include' => ['prod-finance', 'staging-apps']], JSON_THROW_ON_ERROR),
                'host_scope_mode' => 'subset',
                'host_scope_json' => json_encode(['include' => ['prod-node-01', 'staging-node-01']], JSON_THROW_ON_ERROR),
                'force_actions_json' => json_encode(['require_reason_for_restore' => true], JSON_THROW_ON_ERROR),
                'primary_second_factor' => 'email',
                'totp_enabled' => false,
                'totp_secret_id' => 0,
                'user_id' => $operatorId,
                'created_at' => $now->subDays(90),
                'updated_at' => $now->subDays(1),
            ]),
            'auditor' => DB::table('fg_user_settings')->insertGetId([
                'preferred_locale' => 'en_US',
                'preferred_timezone' => 'UTC',
                'preferred_start_page' => 'reports',
                'repo_scope_mode' => 'all',
                'repo_scope_json' => json_encode(['include' => ['*']], JSON_THROW_ON_ERROR),
                'host_scope_mode' => 'all',
                'host_scope_json' => json_encode(['include' => ['*']], JSON_THROW_ON_ERROR),
                'force_actions_json' => json_encode(['read_only_mode' => true], JSON_THROW_ON_ERROR),
                'primary_second_factor' => 'none',
                'totp_enabled' => false,
                'totp_secret_id' => 0,
                'user_id' => $auditorId,
                'created_at' => $now->subDays(35),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        DB::table('fg_users')->where('id', $adminId)->update([
            'contact_id' => $contactIds['admin'],
            'user_settings_id' => $settingIds['admin'],
            'updated_by' => $adminId,
        ]);

        DB::table('fg_users')->where('id', $operatorId)->update([
            'contact_id' => $contactIds['operator'],
            'user_settings_id' => $settingIds['operator'],
        ]);

        DB::table('fg_users')->where('id', $auditorId)->update([
            'contact_id' => $contactIds['auditor'],
            'user_settings_id' => $settingIds['auditor'],
        ]);

        DB::table('fg_user_roles')->insert([
            [
                'user_id' => $adminId,
                'role_id' => $roleIds['super_admin'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $operatorId,
                'role_id' => $roleIds['operator'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => $auditorId,
                'role_id' => $roleIds['auditor'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('fg_user_invitations')->insert([
            'token_hash' => hash('sha256', 'invite-ops-manager'),
            'expires_at' => $now->subDays(25),
            'accepted_at' => $now->subDays(28),
            'revoked_at' => $now->subDays(20),
            'user_id' => $operatorId,
            'user_setting_id' => $settingIds['operator'],
            'invited_by' => $adminId,
            'contact_id' => $contactIds['operator'],
            'created_at' => $now->subDays(29),
            'updated_at' => $now->subDays(20),
        ]);

        DB::table('fg_active_sessions')->insert([
            [
                'session_token' => hash('sha256', 'admin-session'),
                'ip_address' => '192.168.10.15',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'user_id' => $adminId,
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subMinutes(8),
            ],
            [
                'session_token' => hash('sha256', 'ops-session'),
                'ip_address' => '192.168.10.28',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'user_id' => $operatorId,
                'created_at' => $now->subHours(9),
                'updated_at' => $now->subHours(3),
            ],
        ]);

        DB::table('fg_activity_logs')->insert([
            [
                'username' => 'admin',
                'action' => 'user.created',
                'details' => json_encode(['target' => 'ops_manager', 'channel' => 'manual'], JSON_THROW_ON_ERROR),
                'ip_address' => '192.168.10.15',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'severity' => 'info',
                'user_id' => $adminId,
                'created_at' => $now->subDays(29),
                'updated_at' => $now->subDays(29),
            ],
            [
                'username' => 'ops_manager',
                'action' => 'backup.run',
                'details' => json_encode(['job' => 'Nightly production backup', 'status' => 'success'], JSON_THROW_ON_ERROR),
                'ip_address' => '192.168.10.28',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'severity' => 'info',
                'user_id' => $operatorId,
                'created_at' => $now->subHours(12),
                'updated_at' => $now->subHours(12),
            ],
            [
                'username' => 'security_audit',
                'action' => 'audit.review',
                'details' => json_encode(['scope' => 'alerts', 'result' => 'warning reviewed'], JSON_THROW_ON_ERROR),
                'ip_address' => '192.168.10.44',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4)',
                'severity' => 'warning',
                'user_id' => $auditorId,
                'created_at' => $now->subDays(1),
                'updated_at' => $now->subDays(1),
            ],
        ]);

        DB::table('fg_login_attempts')->insert([
            [
                'scope'=> 'Internal API',
                'ip_address' => '192.168.10.15',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)',
                'success' => true,
                'username' => 'admin',
                'created_at' => $now->subMinutes(8),
                'updated_at' => $now->subMinutes(8),
            ],
            [
                'scope'=> 'Internal API',
                'ip_address' => '192.168.10.90',
                'user_agent'=> 'Mozilla/5.0 (X11; Linux x86_64)',
                'success' => false,
                'username' => 'admin',
                'created_at' => $now->subHours(7),
                'updated_at' => $now->subHours(7),
            ],
            [
                'scope'=> 'Public API',
                'ip_address' => '192.168.10.28',
                "user_agent" => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
                'success' => true,
                'username' => 'ops_manager',
                'created_at' => $now->subHours(3),
                'updated_at' => $now->subHours(3),
            ],
        ]);

        DB::table('fg_app_settings')->insert([
            [
                'setting_key' => 'ui.default_locale',
                'value_format' => 'text',
                'value_text' => 'fr_FR',
                'value_json' => json_encode(['value' => 'fr_FR'], JSON_THROW_ON_ERROR),
                'category' => 'ui',
                'label' => 'Langue par defaut',
                'description' => 'Locale initiale appliquee aux nouveaux comptes.',
                'is_sensitive' => false,
                'is_locked' => false,
                'updated_by' => $adminId,
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subDays(2),
            ],
            [
                'setting_key' => 'security.password_rotation_days',
                'value_format' => 'json',
                'value_text' => '90',
                'value_json' => json_encode(['days' => 90, 'enforced' => true], JSON_THROW_ON_ERROR),
                'category' => 'security',
                'label' => 'Rotation des mots de passe',
                'description' => 'Nombre de jours maximum avant une rotation obligatoire.',
                'is_sensitive' => false,
                'is_locked' => true,
                'updated_by' => $adminId,
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subDays(2),
            ],
        ]);

        DB::table('fg_password_reset_tokens')->insert([
            'email' => 'security.audit@fulgurite.local',
            'token' => hash('sha256', 'reset-security-audit'),
            'created_at' => $now->subHours(5),
        ]);

        DB::table('fg_sessions')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $adminId,
            'ip_address' => '192.168.10.15',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'payload' => base64_encode(json_encode(['guard' => 'web', 'username' => 'admin'], JSON_THROW_ON_ERROR)),
            'last_activity' => $now->subMinutes(8)->getTimestamp(),
        ]);

        return [
            'users' => [
                'admin' => $adminId,
                'operator' => $operatorId,
                'auditor' => $auditorId,
            ],
            'contacts' => $contactIds,
            'settings' => $settingIds,
            'roles' => $roleIds,
        ];
    }

    /**
     * @param array<string, mixed> $core
     * @return array<string, mixed>
     */
    private function seedInfrastructureData(array $core, CarbonImmutable $now): array
    {
        $adminId = $core['users']['admin'];
        $operatorId = $core['users']['operator'];

        $retentionIds = [
            'prod' => DB::table('fg_retention_policies')->insertGetId([
                'name' => 'Production standard',
                'description' => 'Conserve les snapshots critiques pour les environnements de production.',
                'keep_last' => 14,
                'keep_daily' => 30,
                'keep_weekly' => 12,
                'keep_monthly' => 12,
                'keep_yearly' => 3,
                'prune' => true,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now->subDays(120),
                'updated_at' => $now->subDays(3),
            ]),
            'stage' => DB::table('fg_retention_policies')->insertGetId([
                'name' => 'Staging court terme',
                'description' => 'Retention plus legere pour les environnements de validation.',
                'keep_last' => 7,
                'keep_daily' => 10,
                'keep_weekly' => 4,
                'keep_monthly' => 2,
                'keep_yearly' => 0,
                'prune' => true,
                'is_active' => true,
                'is_system' => false,
                'created_at' => $now->subDays(90),
                'updated_at' => $now->subDays(3),
            ]),
        ];

        $repoIds = [
            'prod-finance' => DB::table('fg_repos')->insertGetId([
                'name' => 'prod-finance',
                'description' => 'Depot Restic principal pour les donnees finance et reporting.',
                'alert_hours' => 6,
                'enabled' => true,
                'notifications_enabled' => true,
                'notifications_policies' => json_encode(['mail' => ['critical', 'warning'], 'slack' => ['failed']], JSON_THROW_ON_ERROR),
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subHours(6),
            ]),
            'staging-apps' => DB::table('fg_repos')->insertGetId([
                'name' => 'staging-apps',
                'description' => 'Depot Restic de preproduction pour les applications internes.',
                'alert_hours' => 12,
                'enabled' => true,
                'notifications_enabled' => false,
                'notifications_policies' => json_encode(['mail' => ['failed']], JSON_THROW_ON_ERROR),
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(70),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        $secretRefIds = [
            'admin_private_key' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'private_key',
                'provider' => 'vault',
                'path_ref' => 'kv/ssh/admin-prod',
                'owner_type' => 'App\Models\User',
                'owner_id' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ]),
            'ops_private_key' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'private_key',
                'provider' => 'vault',
                'path_ref' => 'kv/ssh/ops-stage',
                'owner_type' => 'App\Models\User',
                'owner_id' => $operatorId,
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(80),
                'updated_at' => $now->subDays(1),
            ]),
            'prod_repo_password' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'repo_password',
                'provider' => 'vault',
                'path_ref' => 'kv/repos/prod-finance/password',
                'owner_type' => 'App\Models\Repos',
                'owner_id' => $repoIds['prod-finance'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ]),
            'staging_repo_password' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'repo_password',
                'provider' => 'vault',
                'path_ref' => 'kv/repos/staging-apps/password',
                'owner_type' => 'App\Models\Repos',
                'owner_id' => $repoIds['staging-apps'],
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(70),
                'updated_at' => $now->subDays(1),
            ]),
            'sudo_prod' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'sudo_password',
                'provider' => 'vault',
                'path_ref' => 'kv/hosts/prod-node-01/sudo',
                'owner_type' => 'App\Models\User',
                'owner_id' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ]),
            'sudo_stage' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'sudo_password',
                'provider' => 'vault',
                'path_ref' => 'kv/hosts/staging-node-01/sudo',
                'owner_type' => 'App\Models\User',
                'owner_id' => $operatorId,
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(70),
                'updated_at' => $now->subDays(1),
            ]),
            'approved_host_key' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'private_key',
                'provider' => 'known_hosts',
                'path_ref' => 'known_hosts/prod-node-01/approved',
                'owner_type' => 'App\Models\User',
                'owner_id' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subDays(2),
            ]),
            'detected_host_key' => DB::table('fg_secret_refs')->insertGetId([
                'usage' => 'private_key',
                'provider' => 'known_hosts',
                'path_ref' => 'known_hosts/prod-node-01/detected',
                'owner_type' => 'App\Models\User',
                'owner_id' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(1),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        $sshKeyIds = [
            'prod' => DB::table('fg_ssh_keys')->insertGetId([
                'name' => 'prod-admin-key',
                'description' => 'Cle publique pour les noeuds de production.',
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFulguriteProdKey admin@fulgurite.local',
                'checksum_public_key' => hash('sha256', 'prod-admin-key'),
                'private_key_ref' => $secretRefIds['admin_private_key'],
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ]),
            'stage' => DB::table('fg_ssh_keys')->insertGetId([
                'name' => 'stage-ops-key',
                'description' => 'Cle publique pour les environnements de staging.',
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFulguriteStageKey ops@fulgurite.local',
                'checksum_public_key' => hash('sha256', 'stage-ops-key'),
                'private_key_ref' => $secretRefIds['ops_private_key'],
                'created_at' => $now->subDays(80),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        $hostIds = [
            'prod-node-01' => DB::table('fg_hosts')->insertGetId([
                'name' => 'prod-node-01',
                'description' => 'Serveur principal de restauration pour la finance.',
                'hostname' => 'prod-node-01.internal.local',
                'port' => 22,
                'user' => 'deploy',
                'restore_managed_root' => '/srv/restore/prod-finance',
                'restore_origin_enabled' => true,
                'ssh_key_id' => $sshKeyIds['prod'],
                'sudo_pass_ref' => $secretRefIds['sudo_prod'],
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ]),
            'staging-node-01' => DB::table('fg_hosts')->insertGetId([
                'name' => 'staging-node-01',
                'description' => 'Serveur de validation pour les copies de depots.',
                'hostname' => 'staging-node-01.internal.local',
                'port' => 2222,
                'user' => 'backup',
                'restore_managed_root' => '/srv/restore/staging',
                'restore_origin_enabled' => false,
                'ssh_key_id' => $sshKeyIds['stage'],
                'sudo_pass_ref' => $secretRefIds['sudo_stage'],
                'created_at' => $now->subDays(70),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        DB::table('fg_storage_targets')->insert([
            [
                'target_type' => 'repo_path',
                'path' => '/var/lib/restic/prod-finance',
                'label' => 'Production finance storage',
                'backend_type' => 'local',
                'owner_type' => 'App\Models\Repos',
                'owner_id' => $repoIds['prod-finance'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
                'created_at' => $now->subDays(100),
                'updated_at' => $now->subDays(2),
            ],
            [
                'target_type' => 'host_mount',
                'path' => '/mnt/backups/staging-apps',
                'label' => 'Staging mounted target',
                'backend_type' => 'sftp',
                'owner_type' => 'App\Models\User',
                'owner_id' => $operatorId,
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(70),
                'updated_at' => $now->subDays(1),
            ],
        ]);

        DB::table('fg_alert_logs')->insert([
            [
                'repo_name' => 'prod-finance',
                'alert_type' => 'stale_snapshot',
                'message' => 'Le depot de production a depasse le seuil de fraicheur de 6 heures.',
                'notified' => true,
                'repo_id' => $repoIds['prod-finance'],
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subHours(7),
                'updated_at' => $now->subHours(6),
            ],
            [
                'repo_name' => 'staging-apps',
                'alert_type' => 'copy_job_failed',
                'message' => 'La copie du depot de staging a echoue lors du dernier transfert.',
                'notified' => false,
                'repo_id' => $repoIds['staging-apps'],
                'created_by' => $operatorId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_theme_requests')->insert([
            [
                'source_type' => 'url',
                'source_url' => 'https://themes.example.test/emerald-ops',
                'source_filename' => null,
                'store_entry_key' => 'emerald-ops',
                'name' => 'Emerald Ops',
                'description' => 'Theme sobre pour les ecrans de supervision.',
                'request_payoad_json' => json_encode(['primary' => '#0f766e', 'accent' => '#f59e0b'], JSON_THROW_ON_ERROR),
                'status' => 'approved',
                'review_notes' => 'Valide pour un deploiement interne.',
                'installed_theme_key' => 'emerald-ops-v1',
                'requested_by' => $operatorId,
                'reviewed_by' => $adminId,
                'reviewed_at' => $now->subDays(4),
                'created_at' => $now->subDays(5),
                'updated_at' => $now->subDays(4),
            ],
        ]);

        DB::table('fg_ssh_host_trusts')->insert([
            'remote_host' => 'prod-node-01.internal.local',
            'remote_port' => 22,
            'approved_key_type' => 'ssh-ed25519',
            'approved_fingerprint' => 'SHA256:approvedProdFingerprint',
            'detected_key_type' => 'ssh-ed25519',
            'detected_fingerprint' => 'SHA256:detectedProdFingerprint',
            'previous_fingerprint' => 'SHA256:olderProdFingerprint',
            'status' => 'approved',
            'last_context' => 'scheduled backup verification',
            'last_seen_at' => $now->subHours(6),
            'approved_at' => $now->subDays(30),
            'rejected_at' => null,
            'approved_key_ref' => $secretRefIds['approved_host_key'],
            'detected_key_ref' => $secretRefIds['detected_host_key'],
            'approved_by' => $adminId,
            'created_at' => $now->subDays(30),
            'updated_at' => $now->subHours(6),
        ]);

        return [
            'retention_policies' => $retentionIds,
            'repos' => $repoIds,
            'secret_refs' => $secretRefIds,
            'ssh_keys' => $sshKeyIds,
            'hosts' => $hostIds,
        ];
    }

    /**
     * @param array<string, mixed> $core
     * @param array<string, mixed> $assets
     * @return array<string, mixed>
     */
    private function seedOperationalData(array $core, array $assets, CarbonImmutable $now): array
    {
        $adminId = $core['users']['admin'];
        $operatorId = $core['users']['operator'];
        $repoProdId = $assets['repos']['prod-finance'];
        $repoStageId = $assets['repos']['staging-apps'];
        $hostProdId = $assets['hosts']['prod-node-01'];
        $hostStageId = $assets['hosts']['staging-node-01'];

        $jobIds = [
            'backup-prod' => DB::table('fg_jobs')->insertGetId([
                'name' => 'Nightly production backup',
                'enabled' => true,
                'jobable_type' => 'App\Models\Repos',
                'jobable_id' => $repoProdId,
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subHours(12),
            ]),
            'copy-stage' => DB::table('fg_jobs')->insertGetId([
                'name' => 'Staging repo replication',
                'enabled' => true,
                'jobable_type' => 'App\Models\Repos',
                'jobable_id' => $repoStageId,
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(50),
                'updated_at' => $now->subHours(5),
            ]),
        ];

        $backupJobIds = [
            'prod' => DB::table('fg_backup_jobs')->insertGetId([
                'name' => 'Backup finance production',
                'description' => 'Sauvegarde nocturne du depot de production finance.',
                'source_paths' => json_encode(['/srv/finance', '/srv/reporting'], JSON_THROW_ON_ERROR),
                'tags' => json_encode(['prod', 'finance', 'nightly'], JSON_THROW_ON_ERROR),
                'excludes' => json_encode(['/srv/finance/tmp', '/srv/reporting/cache'], JSON_THROW_ON_ERROR),
                'remote_repo_path' => '/var/lib/restic/prod-finance',
                'hostname_override' => 'prod-finance.internal.local',
                'retention_enabled' => true,
                'retention_override_json' => json_encode(['keep_last' => 14], JSON_THROW_ON_ERROR),
                'schedule_enabled' => true,
                'notification_policy' => json_encode(['mail' => ['success', 'failed'], 'slack' => ['failed']], JSON_THROW_ON_ERROR),
                'retry_policy' => json_encode(['max_attempts' => 3, 'backoff_minutes' => 15], JSON_THROW_ON_ERROR),
                'rentention_policy_id' => $assets['retention_policies']['prod'],
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subDays(1),
            ]),
            'stage' => DB::table('fg_backup_jobs')->insertGetId([
                'name' => 'Backup staging applications',
                'description' => 'Sauvegarde reguliere des applications en preproduction.',
                'source_paths' => json_encode(['/srv/staging/apps'], JSON_THROW_ON_ERROR),
                'tags' => json_encode(['staging', 'apps'], JSON_THROW_ON_ERROR),
                'excludes' => json_encode(['/srv/staging/apps/tmp'], JSON_THROW_ON_ERROR),
                'remote_repo_path' => '/var/lib/restic/staging-apps',
                'hostname_override' => 'staging-apps.internal.local',
                'retention_enabled' => true,
                'retention_override_json' => json_encode(['keep_last' => 7], JSON_THROW_ON_ERROR),
                'schedule_enabled' => true,
                'notification_policy' => json_encode(['mail' => ['failed']], JSON_THROW_ON_ERROR),
                'retry_policy' => json_encode(['max_attempts' => 2, 'backoff_minutes' => 10], JSON_THROW_ON_ERROR),
                'rentention_policy_id' => $assets['retention_policies']['stage'],
                'created_at' => $now->subDays(50),
                'updated_at' => $now->subDays(1),
            ]),
        ];

        DB::table('fg_job_schedules')->insert([
            [
                'frequency_type' => 'interval',
                'run_days' => json_encode(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], JSON_THROW_ON_ERROR),
                'run_hours' => json_encode(['01:00'], JSON_THROW_ON_ERROR),
                'interval_hours' => json_encode([24], JSON_THROW_ON_ERROR),
                'next_run_at' => $now->addHours(14),
                'last_run_at' => $now->subHours(10),
                'job_id' => $jobIds['backup-prod'],
                'created_at' => $now->subDays(60),
                'updated_at' => $now->subHours(10),
            ],
            [
                'frequency_type' => 'interval',
                'run_days' => json_encode(['mon', 'wed', 'fri'], JSON_THROW_ON_ERROR),
                'run_hours' => json_encode(['03:00'], JSON_THROW_ON_ERROR),
                'interval_hours' => json_encode([48], JSON_THROW_ON_ERROR),
                'next_run_at' => $now->addDays(1),
                'last_run_at' => $now->subDays(1),
                'job_id' => $jobIds['copy-stage'],
                'created_at' => $now->subDays(50),
                'updated_at' => $now->subDays(1),
            ],
        ]);

        DB::table('fg_job_runs')->insert([
            [
                'trigger_type' => 'schedule',
                'status' => 'success',
                'scheduled_for' => $now->subHours(11),
                'started_at' => $now->subHours(10)->subMinutes(55),
                'completed_at' => $now->subHours(10)->subMinutes(20),
                'attempts' => 1,
                'exit_code' => 0,
                'summary' => 'Sauvegarde terminee avec succes.',
                'details_json' => json_encode(['snapshots' => 1, 'files_changed' => 248], JSON_THROW_ON_ERROR),
                'job_id' => $jobIds['backup-prod'],
                'created_at' => $now->subHours(11),
                'updated_at' => $now->subHours(10)->subMinutes(20),
            ],
            [
                'trigger_type' => 'retry',
                'status' => 'failed',
                'scheduled_for' => $now->subHours(5),
                'started_at' => $now->subHours(5)->subMinutes(5),
                'completed_at' => $now->subHours(4)->subMinutes(48),
                'attempts' => 2,
                'exit_code' => 12,
                'summary' => 'La replication de staging a echoue apres deux tentatives.',
                'details_json' => json_encode(['reason' => 'SSH handshake timeout'], JSON_THROW_ON_ERROR),
                'job_id' => $jobIds['copy-stage'],
                'created_at' => $now->subHours(5),
                'updated_at' => $now->subHours(4)->subMinutes(48),
            ],
        ]);

        DB::table('fg_copy_jobs')->insert([
            [
                'name' => 'Replication staging to validation host',
                'description' => 'Copie le depot staging vers le serveur de validation pour verification.',
                'dest_path' => '/srv/copies/staging-apps',
                'schedule_enabled' => true,
                'notification_policy' => json_encode(['mail' => ['failed'], 'ui' => ['failed', 'success']], JSON_THROW_ON_ERROR),
                'retry_policy' => json_encode(['max_attempts' => 2, 'backoff_minutes' => 20], JSON_THROW_ON_ERROR),
                'repo_src_id' => $repoStageId,
                'job_id' => $jobIds['copy-stage'],
                'host_id' => $hostStageId,
                'repo_src_password_ref' => $assets['secret_refs']['staging_repo_password'],
                'repo_dest_password_ref' => $assets['secret_refs']['prod_repo_password'],
                'src_ssh_key_ref' => $assets['ssh_keys']['stage'],
                'dest_ssh_key_ref' => $assets['ssh_keys']['prod'],
                'src_sudo_password_ref' => $assets['secret_refs']['sudo_stage'],
                'dest_sudo_password_ref' => $assets['secret_refs']['sudo_prod'],
                'created_at' => $now->subDays(45),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_hook_scripts')->insert([
            [
                'name' => 'Notify before backup',
                'description' => 'Prepare le contexte de notification avant lancement.',
                'status' => 'active',
                'content_path' => '/opt/fulgurite/hooks/pre_backup_notify.sh',
                'checksum' => hash('sha256', 'pre_backup_notify.sh'),
                'execution_hook' => 'pre_run',
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(30),
                'updated_at' => $now->subDays(2),
            ],
            [
                'name' => 'Archive failed copy logs',
                'description' => 'Archive les logs apres un echec de copie.',
                'status' => 'active',
                'content_path' => '/opt/fulgurite/hooks/archive_copy_failure.sh',
                'checksum' => hash('sha256', 'archive_copy_failure.sh'),
                'execution_hook' => 'on_failure',
                'created_by' => $adminId,
                'updated_by' => $operatorId,
                'deleted_by' => null,
                'created_at' => $now->subDays(25),
                'updated_at' => $now->subDays(1),
            ],
        ]);

        DB::table('fg_hookables')->insert([
            [
                'event' => 'backup.completed',
                'enabled' => true,
                'priority' => 10,
                'config_json' => json_encode(['notify' => true], JSON_THROW_ON_ERROR),
                'hookable_type' => 'App\Models\Repos',
                'hookable_id' => $repoProdId,
                'created_at' => $now->subDays(30),
                'updated_at' => $now->subDays(2),
            ],
            [
                'event' => 'copy.failed',
                'enabled' => true,
                'priority' => 20,
                'config_json' => json_encode(['archive_logs' => true], JSON_THROW_ON_ERROR),
                'hookable_type' => 'App\Models\Repos',
                'hookable_id' => $repoStageId,
                'created_at' => $now->subDays(25),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_restore_logs')->insert([
            [
                'type' => 'partial',
                'status' => 'success',
                'reason' => 'Restauration d un export comptable demande par le support.',
                'snapshot_id' => 'snapshot-prod-finance-20260426',
                'snapshot_host' => 'prod-finance.internal.local',
                'snapshot_path' => '/srv/finance',
                'target_path' => '/srv/restore/prod-finance/export-2026-04-26',
                'destination_type' => 'remote',
                'requested_path' => '/srv/finance/exports',
                'requested_paths_json' => json_encode(['/srv/finance/exports/2026-04-26'], JSON_THROW_ON_ERROR),
                'overwrite_policy' => 'rename',
                'include_deleted' => false,
                'files_restored' => 84,
                'bytes_restored' => 18350080,
                'exit_code' => 0,
                'error_code' => null,
                'error_message' => null,
                'started_at' => $now->subDays(2)->subMinutes(20),
                'finished_at' => $now->subDays(2),
                'repo_id' => $repoProdId,
                'restored_by' => $operatorId,
                'host_id' => $hostProdId,
            ],
        ]);

        return [
            'jobs' => $jobIds,
            'backup_jobs' => $backupJobIds,
        ];
    }

    /**
     * @param array<string, mixed> $core
     */
    private function seedTemplateData(array $core, CarbonImmutable $now): void
    {
        $adminId = $core['users']['admin'];
        $operatorId = $core['users']['operator'];

        $backupTemplateId = DB::table('fg_template_sets')->insertGetId([
            'template_type' => 'backup',
            'template_key' => 'backup-linux-standard',
            'source_kind' => 'system',
            'name' => 'Linux standard backup',
            'description' => 'Modele pour sauvegarde de serveurs Linux classiques.',
            'category' => 'operations',
            'badges' => json_encode(['stable', 'recommended'], JSON_THROW_ON_ERROR),
            'is_editable' => true,
            'is_active' => true,
            'version' => '1.2.0',
            'parent_template_id' => null,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'deleted_by' => null,
            'created_at' => $now->subDays(40),
            'updated_at' => $now->subDays(3),
        ]);

        $notificationTemplateId = DB::table('fg_template_sets')->insertGetId([
            'template_type' => 'notification',
            'template_key' => 'notification-backup-failure',
            'source_kind' => 'user',
            'name' => 'Backup failure notification',
            'description' => 'Modele de notification en cas d echec d une sauvegarde.',
            'category' => 'communication',
            'badges' => json_encode(['custom'], JSON_THROW_ON_ERROR),
            'is_editable' => true,
            'is_active' => true,
            'version' => '1.0.1',
            'parent_template_id' => $backupTemplateId,
            'created_by' => $adminId,
            'updated_by' => $operatorId,
            'deleted_by' => null,
            'created_at' => $now->subDays(20),
            'updated_at' => $now->subDays(1),
        ]);

        DB::table('fg_template_payloads')->insert([
            [
                'payload' => json_encode([
                    'paths' => ['/srv/data'],
                    'exclude' => ['/srv/data/tmp'],
                    'retention' => ['keep_last' => 7],
                ], JSON_THROW_ON_ERROR),
                'checksum' => hash('sha256', 'backup-linux-standard-payload'),
                'template_set_id' => $backupTemplateId,
                'created_at' => $now->subDays(40),
                'updated_at' => $now->subDays(3),
            ],
            [
                'payload' => json_encode([
                    'title' => 'Backup failed on {{ repo }}',
                    'body' => 'The backup run failed with exit code {{ exit_code }}.',
                ], JSON_THROW_ON_ERROR),
                'checksum' => hash('sha256', 'notification-backup-failure-payload'),
                'template_set_id' => $notificationTemplateId,
                'created_at' => $now->subDays(20),
                'updated_at' => $now->subDays(1),
            ],
        ]);

        DB::table('fg_template_usages')->insert([
            [
                'usage_kind' => 'create_form',
                'template_set_id' => $backupTemplateId,
                'created_at' => $now->subDays(39),
                'updated_at' => $now->subDays(39),
            ],
            [
                'usage_kind' => 'applied_to',
                'template_set_id' => $notificationTemplateId,
                'created_at' => $now->subDays(19),
                'updated_at' => $now->subDays(19),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $core
     * @param array<string, mixed> $assets
     */
    private function seedNotificationData(array $core, array $assets, CarbonImmutable $now): void
    {
        $adminId = $core['users']['admin'];
        $operatorId = $core['users']['operator'];
        $auditorId = $core['users']['auditor'];

        $eventIds = [
            'backup_failed' => DB::table('fg_notification_events')->insertGetId([
                'profile_key' => 'default',
                'event_key' => 'failed',
                'dedupe_key' => 'repo-staging-copy-failed',
                'title_template' => 'Replication failed for staging-apps',
                'body_template' => 'The last copy run on staging-apps failed and requires attention.',
                'payload_json' => json_encode(['repo' => 'staging-apps', 'job' => 'Staging repo replication'], JSON_THROW_ON_ERROR),
                'context_type' => 'App\Models\Repos',
                'context_id' => $assets['repos']['staging-apps'],
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ]),
            'backup_warning' => DB::table('fg_notification_events')->insertGetId([
                'profile_key' => 'default',
                'event_key' => 'warning',
                'dedupe_key' => 'repo-prod-stale',
                'title_template' => 'Repository freshness warning',
                'body_template' => 'The production repository has exceeded its freshness threshold.',
                'payload_json' => json_encode(['repo' => 'prod-finance', 'threshold_hours' => 6], JSON_THROW_ON_ERROR),
                'context_type' => 'App\Models\Repos',
                'context_id' => $assets['repos']['prod-finance'],
                'created_at' => $now->subHours(7),
                'updated_at' => $now->subHours(6),
            ]),
        ];

        DB::table('fg_notification_deliveries')->insert([
            [
                'channel' => 'mail',
                'status' => 'sent',
                'provider_message_id' => 'mail-001-prod-warning',
                'error_code' => null,
                'error_message' => null,
                'request_payload_json' => json_encode(['to' => 'admin@fulgurite.local'], JSON_THROW_ON_ERROR),
                'response_payload_json' => json_encode(['provider' => 'smtp', 'accepted' => true], JSON_THROW_ON_ERROR),
                'recipient_type' => 'App\Models\User',
                'recipient_id' => $adminId,
                'attempt_at' => $now->subHours(6),
                'processed_at' => $now->subHours(6),
                'created_at' => $now->subHours(6),
                'updated_at' => $now->subHours(6),
            ],
            [
                'channel' => 'ui',
                'status' => 'sent',
                'provider_message_id' => 'ui-002-stage-failed',
                'error_code' => null,
                'error_message' => null,
                'request_payload_json' => json_encode(['user_id' => $operatorId], JSON_THROW_ON_ERROR),
                'response_payload_json' => json_encode(['delivered' => true], JSON_THROW_ON_ERROR),
                'recipient_type' => 'App\Models\User',
                'recipient_id' => $operatorId,
                'attempt_at' => $now->subHours(4),
                'processed_at' => $now->subHours(4),
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_notifications_inbox')->insert([
            [
                'title' => 'Replication failed for staging-apps',
                'body' => 'Relancer la copie du depot staging-apps depuis le tableau de bord.',
                'severity' => 'error',
                'link_url' => '/jobs/staging-repo-replication',
                'is_read' => false,
                'user_id' => $operatorId,
                'notification_event_id' => $eventIds['backup_failed'],
                'read_at' => null,
                'expires_at' => $now->addDays(7),
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
            [
                'title' => 'Repository freshness warning',
                'body' => 'Verifier l etat du depot prod-finance et confirmer le prochain snapshot.',
                'severity' => 'warning',
                'link_url' => '/repositories/prod-finance',
                'is_read' => true,
                'user_id' => $adminId,
                'notification_event_id' => $eventIds['backup_warning'],
                'read_at' => $now->subHours(5),
                'expires_at' => $now->addDays(5),
                'created_at' => $now->subHours(7),
                'updated_at' => $now->subHours(5),
            ],
            [
                'title' => 'Staging replication incident reviewed',
                'body' => 'L incident a ete pris en compte par l equipe securite.',
                'severity' => 'info',
                'link_url' => '/alerts/staging-apps',
                'is_read' => false,
                'user_id' => $auditorId,
                'notification_event_id' => $eventIds['backup_failed'],
                'read_at' => null,
                'expires_at' => $now->addDays(10),
                'created_at' => $now->subHours(3),
                'updated_at' => $now->subHours(3),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $core
     * @param array<string, mixed> $assets
     * @param array<string, mixed> $jobs
     */
    private function seedMonitoringData(array $core, array $assets, array $jobs, CarbonImmutable $now): void
    {
        $adminId = $core['users']['admin'];

        DB::table('fg_repo_metrics_hostory')->insert([
            [
                'bucket_granularity' => 'daily',
                'snapshot_count' => 42,
                'total_size_bytes' => 4831838208,
                'total_file_count' => 215204,
                'source' => 'restic snapshots',
                'last_error_message' => '',
                'repo_id' => $assets['repos']['prod-finance'],
                'bucket_start_at' => $now->subDay()->startOfDay(),
                'computed_at' => $now->subHours(6),
                'created_at' => $now->subHours(6),
                'updated_at' => $now->subHours(6),
            ],
            [
                'bucket_granularity' => 'daily',
                'snapshot_count' => 18,
                'total_size_bytes' => 1522049024,
                'total_file_count' => 88442,
                'source' => 'restic snapshots',
                'last_error_message' => 'SSH handshake timeout during copy validation.',
                'repo_id' => $assets['repos']['staging-apps'],
                'bucket_start_at' => $now->subDay()->startOfDay(),
                'computed_at' => $now->subHours(4),
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_repo_runtime_status')->insert([
            [
                'snapshot_count' => 42,
                'freshness_hours' => 5.5,
                'total_size_bytes' => 4831838208,
                'total_files_count' => 215204,
                'last_error_int' => null,
                'last_error_message' => null,
                'status' => 'ok',
                'repo_id' => $assets['repos']['prod-finance'],
                'last_snapshot_at' => $now->subHours(5),
                'last_successfull_read_at' => $now->subHours(5),
                'computed_at' => $now->subHours(4),
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
            [
                'snapshot_count' => 18,
                'freshness_hours' => 15.25,
                'total_size_bytes' => 1522049024,
                'total_files_count' => 88442,
                'last_error_int' => 12,
                'last_error_message' => 'Last copy validation failed on SSH timeout.',
                'status' => 'warning',
                'repo_id' => $assets['repos']['staging-apps'],
                'last_snapshot_at' => $now->subHours(15),
                'last_successfull_read_at' => $now->subDays(1),
                'computed_at' => $now->subHours(4),
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_storage_measurements')->insert([
            [
                'metric_kind' => 'directory_size',
                'status' => 'success',
                'total_bytes' => 8000000000,
                'free_bytes' => 3200000000,
                'used_bytes' => 4800000000,
                'available_bytes' => 3000000000,
                'files_count' => 215204,
                'usage_percent' => 60.00,
                'details_json' => json_encode(['path' => '/var/lib/restic/prod-finance'], JSON_THROW_ON_ERROR),
                'target_type' => 'App\Models\Repos',
                'target_id' => $assets['repos']['prod-finance'],
                'created_at' => $now->subHours(6),
                'updated_at' => $now->subHours(6),
            ],
            [
                'metric_kind' => 'filesystem',
                'status' => 'warning',
                'total_bytes' => 4000000000,
                'free_bytes' => 500000000,
                'used_bytes' => 3500000000,
                'available_bytes' => 450000000,
                'files_count' => 88442,
                'usage_percent' => 87.50,
                'details_json' => json_encode(['host' => 'staging-node-01'], JSON_THROW_ON_ERROR),
                'target_type' => 'App\Models\User',
                'target_id' => $core['users']['operator'],
                'created_at' => $now->subHours(4),
                'updated_at' => $now->subHours(4),
            ],
        ]);

        DB::table('fg_secret_broker_status')->insert([
            [
                'cluster_name' => 'vault-eu-west',
                'node_id' => 'vault-01',
                'node_label' => 'Vault primary',
                'status' => 'ok',
                'error_code' => null,
                'error_message' => null,
                'last_seen_at' => $now->subMinutes(10),
                'last_change_at' => $now->subDays(14),
                'created_at' => $now->subDays(14),
                'updated_at' => $now->subMinutes(10),
            ],
            [
                'cluster_name' => 'vault-eu-west',
                'node_id' => 'vault-02',
                'node_label' => 'Vault secondary',
                'status' => 'down',
                'error_code' => 'TLS_TIMEOUT',
                'error_message' => 'Secondary node did not answer within the timeout window.',
                'last_seen_at' => $now->subHours(2),
                'last_change_at' => $now->subHours(2),
                'created_at' => $now->subDays(14),
                'updated_at' => $now->subHours(2),
            ],
        ]);

        DB::table('fg_secret_broker_events')->insert([
            [
                'endpoint' => 'https://vault-01.internal.local:8200',
                'cluster_name' => 'vault-eu-west',
                'node_id' => 'vault-01',
                'node_label' => 'Vault primary',
                'event_type' => 'healthcheck',
                'severity' => 'success',
                'message' => 'Healthcheck completed successfully.',
                'details_json' => json_encode(['latency_ms' => 41], JSON_THROW_ON_ERROR),
                'created_at' => $now->subMinutes(10),
                'updated_at' => $now->subMinutes(10),
            ],
            [
                'endpoint' => 'https://vault-02.internal.local:8200',
                'cluster_name' => 'vault-eu-west',
                'node_id' => 'vault-02',
                'node_label' => 'Vault secondary',
                'event_type' => 'healthcheck',
                'severity' => 'error',
                'message' => 'Healthcheck failed during TLS negotiation.',
                'details_json' => json_encode(['timeout_seconds' => 15], JSON_THROW_ON_ERROR),
                'created_at' => $now->subHours(2),
                'updated_at' => $now->subHours(2),
            ],
        ]);
    }
}
