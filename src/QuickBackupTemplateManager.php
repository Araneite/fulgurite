<?php

class QuickBackupTemplateManager {
    public const BUILTIN_PREFIX = 'builtin:';
    public const CUSTOM_PREFIX = 'custom:';

    public static function defaultReference(): string {
        return self::BUILTIN_PREFIX . 'system-server';
    }

    public static function getAll(): array {
        return array_merge(self::getBuiltinTemplates(), self::getCustomTemplates());
    }

    public static function getBuiltinTemplates(): array {
        return [
            self::builtinTemplate(
                'system-server',
                t('quick_backup.template.system_server.name'),
                t('quick_backup.template.system_server.description'),
                t('quick_backup.template.system_server.category'),
                t('quick_backup.template.system_server.badge'),
                [
                    'host_user' => 'root',
                    'host_port' => 22,
                    'repo_name_pattern' => '{{host_name}}',
                    'repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}',
                    'remote_repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}',
                    'job_name_pattern' => t('quick_backup.template.pattern.system_server.job_name'),
                    'source_paths' => ['/etc', '/home', '/root', '/var/spool/cron'],
                    'excludes' => ['/home/*/.cache', '*.tmp'],
                    'tags' => ['linux', 'system'],
                    'schedule_enabled' => true,
                    'schedule_hour' => 2,
                    'schedule_days' => ['1', '2', '3', '4', '5', '6', '7'],
                    'retention_enabled' => true,
                    'retention_keep_last' => 0,
                    'retention_keep_daily' => 7,
                    'retention_keep_weekly' => 4,
                    'retention_keep_monthly' => 6,
                    'retention_keep_yearly' => 1,
                    'retention_prune' => true,
                ]
            ),
            self::builtinTemplate(
                'linux-web',
                t('quick_backup.template.linux_web.name'),
                t('quick_backup.template.linux_web.description'),
                t('quick_backup.template.linux_web.category'),
                t('quick_backup.template.linux_web.badge'),
                [
                    'host_user' => 'root',
                    'host_port' => 22,
                    'repo_name_pattern' => '{{host_name}}-web',
                    'repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-web',
                    'remote_repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-web',
                    'job_name_pattern' => t('quick_backup.template.pattern.linux_web.job_name'),
                    'source_paths' => ['/etc', '/var/www', '/var/spool/cron'],
                    'excludes' => ['/var/www/*/cache', '/var/www/*/tmp', '*.log'],
                    'tags' => ['linux', 'web', 'prod'],
                    'schedule_enabled' => true,
                    'schedule_hour' => 2,
                    'schedule_days' => ['1', '2', '3', '4', '5', '6', '7'],
                    'retention_enabled' => true,
                    'retention_keep_last' => 0,
                    'retention_keep_daily' => 7,
                    'retention_keep_weekly' => 4,
                    'retention_keep_monthly' => 3,
                    'retention_keep_yearly' => 1,
                    'retention_prune' => true,
                ]
            ),
            self::builtinTemplate(
                'mysql-server',
                t('quick_backup.template.mysql_server.name'),
                t('quick_backup.template.mysql_server.description'),
                t('quick_backup.template.mysql_server.category'),
                t('quick_backup.template.mysql_server.badge'),
                [
                    'host_user' => 'root',
                    'host_port' => 22,
                    'repo_name_pattern' => '{{host_name}}-mysql',
                    'repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-mysql',
                    'remote_repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-mysql',
                    'job_name_pattern' => t('quick_backup.template.pattern.mysql_server.job_name'),
                    'source_paths' => ['/etc/mysql', '/var/lib/mysql'],
                    'excludes' => ['/var/lib/mysql/#innodb_redo', '/var/lib/mysql/#innodb_temp'],
                    'tags' => ['mysql', 'database'],
                    'schedule_enabled' => true,
                    'schedule_hour' => 1,
                    'schedule_days' => ['1', '2', '3', '4', '5', '6', '7'],
                    'retention_enabled' => true,
                    'retention_keep_last' => 0,
                    'retention_keep_daily' => 7,
                    'retention_keep_weekly' => 4,
                    'retention_keep_monthly' => 6,
                    'retention_keep_yearly' => 1,
                    'retention_prune' => true,
                ]
            ),
            self::builtinTemplate(
                'postgres-server',
                t('quick_backup.template.postgres_server.name'),
                t('quick_backup.template.postgres_server.description'),
                t('quick_backup.template.postgres_server.category'),
                t('quick_backup.template.postgres_server.badge'),
                [
                    'host_user' => 'root',
                    'host_port' => 22,
                    'repo_name_pattern' => '{{host_name}}-pgsql',
                    'repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-pgsql',
                    'remote_repo_path_pattern' => 'sftp:backup@backup.example:/restic/{{host_slug}}-pgsql',
                    'job_name_pattern' => t('quick_backup.template.pattern.postgres_server.job_name'),
                    'source_paths' => ['/etc/postgresql', '/var/lib/postgresql'],
                    'excludes' => ['*.pid', '*.sock'],
                    'tags' => ['postgresql', 'database'],
                    'schedule_enabled' => true,
                    'schedule_hour' => 1,
                    'schedule_days' => ['1', '2', '3', '4', '5', '6', '7'],
                    'retention_enabled' => true,
                    'retention_keep_last' => 0,
                    'retention_keep_daily' => 7,
                    'retention_keep_weekly' => 4,
                    'retention_keep_monthly' => 6,
                    'retention_keep_yearly' => 1,
                    'retention_prune' => true,
                ]
            ),
        ];
    }

    public static function getCustomTemplates(): array {
        $db = Database::getInstance();
        $rows = $db->query("
            SELECT *
            FROM quick_backup_templates
            ORDER BY name
        ")->fetchAll();

        return array_map(static function (array $row): array {
            $defaults = json_decode((string) ($row['defaults_json'] ?? '{}'), true);
            if (!is_array($defaults)) {
                $defaults = [];
            }
            if ($defaults === []) {
                $defaults = [
                    'source_paths' => json_decode((string) ($row['source_paths'] ?? '[]'), true) ?: [],
                    'excludes' => json_decode((string) ($row['excludes'] ?? '[]'), true) ?: [],
                    'tags' => json_decode((string) ($row['tags'] ?? '[]'), true) ?: [],
                    'schedule_hour' => (int) ($row['schedule_hour'] ?? 2),
                    'schedule_days' => explode(',', (string) ($row['schedule_days'] ?? '1')),
                    'retention_keep_last' => (int) ($row['retention_keep_last'] ?? 0),
                    'retention_keep_daily' => (int) ($row['retention_keep_daily'] ?? 0),
                    'retention_keep_weekly' => (int) ($row['retention_keep_weekly'] ?? 0),
                    'retention_keep_monthly' => (int) ($row['retention_keep_monthly'] ?? 0),
                    'retention_keep_yearly' => (int) ($row['retention_keep_yearly'] ?? 0),
                    'retention_prune' => !empty($row['retention_prune']),
                ];
            }

            return [
                'reference' => self::CUSTOM_PREFIX . (int) ($row['id'] ?? 0),
                'id' => (int) ($row['id'] ?? 0),
                'key' => 'custom-' . (int) ($row['id'] ?? 0),
                'source' => 'custom',
                'editable' => true,
                'name' => (string) ($row['name'] ?? 'Template'),
                'description' => (string) ($row['description'] ?? ''),
                'category' => (string) ($row['category'] ?? t('backup_templates.default_category')),
                'badge' => t('backup_templates.default_category'),
                'defaults' => self::normalizeDefaults($defaults),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }, $rows);
    }

    public static function getByReference(?string $reference): ?array {
        $reference = trim((string) $reference);
        if ($reference === '') {
            $reference = self::defaultReference();
        }

        if (str_starts_with($reference, self::BUILTIN_PREFIX)) {
            $key = substr($reference, strlen(self::BUILTIN_PREFIX));
            foreach (self::getBuiltinTemplates() as $template) {
                if (($template['key'] ?? '') === $key) {
                    return $template;
                }
            }
            return null;
        }

        if (str_starts_with($reference, self::CUSTOM_PREFIX)) {
            $id = (int) substr($reference, strlen(self::CUSTOM_PREFIX));
            return self::getCustomById($id);
        }

        if (ctype_digit($reference)) {
            return self::getCustomById((int) $reference);
        }

        return null;
    }

    public static function getCustomById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        foreach (self::getCustomTemplates() as $template) {
            if ((int) ($template['id'] ?? 0) === $id) {
                return $template;
            }
        }

        return null;
    }

    public static function create(string $name, string $description, string $category, array $defaults): int {
        $db = Database::getInstance();
        $normalized = self::normalizeDefaults($defaults);
        $db->prepare("
            INSERT INTO quick_backup_templates (
                name, description, category, defaults_json,
                source_paths, excludes, tags, schedule_hour, schedule_days,
                retention_keep_last, retention_keep_daily, retention_keep_weekly,
                retention_keep_monthly, retention_keep_yearly, retention_prune, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ")->execute([
            trim($name),
            trim($description),
            trim($category),
            json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            json_encode($normalized['source_paths'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            json_encode($normalized['excludes'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            json_encode($normalized['tags'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            (int) $normalized['schedule_hour'],
            implode(',', (array) $normalized['schedule_days']),
            (int) $normalized['retention_keep_last'],
            (int) $normalized['retention_keep_daily'],
            (int) $normalized['retention_keep_weekly'],
            (int) $normalized['retention_keep_monthly'],
            (int) $normalized['retention_keep_yearly'],
            !empty($normalized['retention_prune']) ? 1 : 0,
        ]);

        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $name, string $description, string $category, array $defaults): void {
        $normalized = self::normalizeDefaults($defaults);
        Database::getInstance()->prepare("
            UPDATE quick_backup_templates
            SET name = ?, description = ?, category = ?, defaults_json = ?,
                source_paths = ?, excludes = ?, tags = ?, schedule_hour = ?, schedule_days = ?,
                retention_keep_last = ?, retention_keep_daily = ?, retention_keep_weekly = ?,
                retention_keep_monthly = ?, retention_keep_yearly = ?, retention_prune = ?,
                updated_at = datetime('now')
            WHERE id = ?
        ")->execute([
            trim($name),
            trim($description),
            trim($category),
            json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            json_encode($normalized['source_paths'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            json_encode($normalized['excludes'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            json_encode($normalized['tags'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            (int) $normalized['schedule_hour'],
            implode(',', (array) $normalized['schedule_days']),
            (int) $normalized['retention_keep_last'],
            (int) $normalized['retention_keep_daily'],
            (int) $normalized['retention_keep_weekly'],
            (int) $normalized['retention_keep_monthly'],
            (int) $normalized['retention_keep_yearly'],
            !empty($normalized['retention_prune']) ? 1 : 0,
            $id,
        ]);
    }

    public static function delete(int $id): void {
        Database::getInstance()->prepare("
            DELETE FROM quick_backup_templates
            WHERE id = ?
        ")->execute([$id]);
    }

    public static function duplicate(int $id): ?int {
        $template = self::getCustomById($id);
        if (!$template) {
            return null;
        }

        return self::create(
            (string) ($template['name'] ?? t('common.template')) . ' ' . t('quick_backup.template.copy_suffix'),
            (string) ($template['description'] ?? ''),
            (string) ($template['category'] ?? t('backup_templates.default_category')),
            (array) ($template['defaults'] ?? [])
        );
    }

    public static function defaultsFromForm(array $data): array {
        return self::normalizeDefaults([
            'host_user' => trim((string) ($data['host_user'] ?? 'root')),
            'host_port' => max(1, (int) ($data['host_port'] ?? 22)),
            'repo_name_pattern' => trim((string) ($data['repo_name_pattern'] ?? '{{host_name}}')),
            'repo_path_pattern' => trim((string) ($data['repo_path_pattern'] ?? '')),
            'remote_repo_path_pattern' => trim((string) ($data['remote_repo_path_pattern'] ?? '')),
            'job_name_pattern' => trim((string) ($data['job_name_pattern'] ?? t('quick_backup.template.default_job_name_pattern'))),
            'source_paths' => self::splitLines((string) ($data['source_paths'] ?? '')),
            'excludes' => self::splitLines((string) ($data['excludes'] ?? '')),
            'tags' => self::splitCsv((string) ($data['tags'] ?? '')),
            'schedule_enabled' => !empty($data['schedule_enabled']),
            'schedule_hour' => max(0, min(23, (int) ($data['schedule_hour'] ?? 2))),
            'schedule_days' => self::normalizeDays($data['schedule_days'] ?? []),
            'retention_enabled' => !empty($data['retention_enabled']),
            'retention_keep_last' => max(0, (int) ($data['retention_keep_last'] ?? 0)),
            'retention_keep_daily' => max(0, (int) ($data['retention_keep_daily'] ?? 0)),
            'retention_keep_weekly' => max(0, (int) ($data['retention_keep_weekly'] ?? 0)),
            'retention_keep_monthly' => max(0, (int) ($data['retention_keep_monthly'] ?? 0)),
            'retention_keep_yearly' => max(0, (int) ($data['retention_keep_yearly'] ?? 0)),
            'retention_prune' => !empty($data['retention_prune']),
        ]);
    }

    public static function splitLines(string $value): array {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $value))));
    }

    public static function splitCsv(string $value): array {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    public static function normalizeDays(mixed $value): array {
        $days = is_array($value) ? $value : [$value];
        $normalized = [];
        foreach ($days as $day) {
            $day = (string) $day;
            if (in_array($day, ['1', '2', '3', '4', '5', '6', '7'], true)) {
                $normalized[] = $day;
            }
        }
        $normalized = array_values(array_unique($normalized));
        return $normalized === [] ? ['1'] : $normalized;
    }

    public static function normalizeDefaults(array $defaults): array {
        return [
            'host_user' => trim((string) ($defaults['host_user'] ?? 'root')) ?: 'root',
            'host_port' => max(1, (int) ($defaults['host_port'] ?? 22)),
            'repo_name_pattern' => trim((string) ($defaults['repo_name_pattern'] ?? '{{host_name}}')) ?: '{{host_name}}',
            'repo_path_pattern' => trim((string) ($defaults['repo_path_pattern'] ?? '')),
            'remote_repo_path_pattern' => trim((string) ($defaults['remote_repo_path_pattern'] ?? '')),
            'job_name_pattern' => trim((string) ($defaults['job_name_pattern'] ?? t('quick_backup.template.default_job_name_pattern')))
                ?: t('quick_backup.template.default_job_name_pattern'),
            'source_paths' => array_values(array_filter(array_map('trim', (array) ($defaults['source_paths'] ?? [])))),
            'excludes' => array_values(array_filter(array_map('trim', (array) ($defaults['excludes'] ?? [])))),
            'tags' => array_values(array_filter(array_map('trim', (array) ($defaults['tags'] ?? [])))),
            'schedule_enabled' => !empty($defaults['schedule_enabled']),
            'schedule_hour' => max(0, min(23, (int) ($defaults['schedule_hour'] ?? 2))),
            'schedule_days' => self::normalizeDays($defaults['schedule_days'] ?? ['1']),
            'retention_enabled' => !empty($defaults['retention_enabled']),
            'retention_keep_last' => max(0, (int) ($defaults['retention_keep_last'] ?? 0)),
            'retention_keep_daily' => max(0, (int) ($defaults['retention_keep_daily'] ?? 0)),
            'retention_keep_weekly' => max(0, (int) ($defaults['retention_keep_weekly'] ?? 0)),
            'retention_keep_monthly' => max(0, (int) ($defaults['retention_keep_monthly'] ?? 0)),
            'retention_keep_yearly' => max(0, (int) ($defaults['retention_keep_yearly'] ?? 0)),
            'retention_prune' => !empty($defaults['retention_prune']),
        ];
    }

    private static function builtinTemplate(
        string $key,
        string $name,
        string $description,
        string $category,
        string $badge,
        array $defaults
    ): array {
        return [
            'reference' => self::BUILTIN_PREFIX . $key,
            'id' => null,
            'key' => $key,
            'source' => 'builtin',
            'editable' => false,
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'badge' => $badge,
            'defaults' => self::normalizeDefaults($defaults),
        ];
    }
}
