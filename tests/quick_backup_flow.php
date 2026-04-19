<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-quick-backup-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';

require_once $root . '/src/bootstrap.php';

function assertTrueCondition(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$db = Database::getInstance();
$db->prepare("
    INSERT INTO users (username, password, role, permissions_json)
    VALUES (?, ?, ?, ?)
")->execute([
    'quick-flow-user',
    password_hash('test', PASSWORD_DEFAULT),
    'admin',
    json_encode([
        'backup_jobs.manage' => true,
        'hosts.manage' => true,
        'repos.manage' => true,
        'sshkeys.manage' => true,
    ], JSON_THROW_ON_ERROR),
]);

$_SESSION['user_id'] = (int) $db->lastInsertId();
$_SESSION['username'] = 'quick-flow-user';
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

$templateId = QuickBackupTemplateManager::create(
    'Template test',
    'Template de verification',
    'Tests',
    QuickBackupTemplateManager::defaultsFromForm([
        'host_user' => 'backup',
        'host_port' => 2222,
        'repo_name_pattern' => '{{host_name}}-repo',
        'repo_path_pattern' => 'sftp:backup@example:/restic/{{host_slug}}',
        'remote_repo_path_pattern' => 'sftp:backup@example:/restic/{{host_slug}}',
        'job_name_pattern' => 'Sauvegarde {{host_name}}',
        'source_paths' => "/etc\n/var/www",
        'excludes' => "*.tmp\n*.log",
        'tags' => 'web,prod',
        'schedule_enabled' => 1,
        'schedule_hour' => 4,
        'schedule_days' => ['1', '3', '5'],
        'retention_enabled' => 1,
        'retention_keep_last' => 0,
        'retention_keep_daily' => 7,
        'retention_keep_weekly' => 4,
        'retention_keep_monthly' => 6,
        'retention_keep_yearly' => 1,
        'retention_prune' => 1,
    ])
);

$template = QuickBackupTemplateManager::getCustomById($templateId);
assertTrueCondition(is_array($template), 'Custom template should be retrievable.');
assertSameValue('Tests', $template['category'], 'Template category should be stored.');
assertSameValue('backup', $template['defaults']['host_user'], 'Template defaults should expose host user.');
assertSameValue('sftp:backup@example:/restic/{{host_slug}}', $template['defaults']['repo_path_pattern'], 'Template repo path pattern should be kept.');

$payload = RemoteBackupQuickFlow::normalizePayload([
    'template_ref' => 'custom:' . $templateId,
    'host_mode' => 'create',
    'host_name' => 'web-prod-01',
    'hostname' => 'web-prod-01.example',
    'repo_mode' => 'create',
]);

assertSameValue('backup', $payload['user'], 'Template should prefill SSH user.');
assertSameValue(2222, $payload['port'], 'Template should prefill SSH port.');
assertSameValue('web-prod-01-repo', $payload['repo_name'], 'Repo name pattern should resolve.');
assertSameValue('sftp:backup@example:/restic/web-prod-01', $payload['repo_path'], 'Repo path pattern should resolve host slug.');
assertSameValue('Sauvegarde web-prod-01', $payload['job_name'], 'Job name pattern should resolve host name.');
assertSameValue(['/etc', '/var/www'], $payload['source_paths'], 'Source paths should come from the template.');
assertSameValue(SecretStore::defaultWritableSource(), $payload['repo_password_source'], 'Quick flow should default to the configured writable secret provider.');

$summary = RemoteBackupQuickFlow::buildHumanSummary($payload);
assertTrueCondition(count($summary) >= 5, 'Human summary should expose the main quick-flow sections.');
assertSameValue('Machine source', $summary[1]['label'], 'Human summary should keep the source machine label.');
assertTrueCondition(in_array('Stockage du secret depot', array_column($summary, 'label'), true), 'Human summary should expose the repo secret storage mode.');

$infisicalPayload = RemoteBackupQuickFlow::normalizePayload([
    'template_ref' => 'custom:' . $templateId,
    'host_mode' => 'create',
    'host_name' => 'web-prod-02',
    'hostname' => 'web-prod-02.example',
    'repo_mode' => 'create',
    'repo_password_source' => 'infisical',
    'repo_infisical_secret_name' => 'RESTIC_REPO_PASSWORD',
]);
assertSameValue('infisical', $infisicalPayload['repo_password_source'], 'Quick flow should preserve the Infisical password source.');
assertSameValue('RESTIC_REPO_PASSWORD', $infisicalPayload['repo_infisical_secret_name'], 'Quick flow should preserve the Infisical secret name.');

$wizardContext = RemoteBackupQuickFlow::wizardContext();
assertTrueCondition(isset($wizardContext['secret_storage_modes']) && is_array($wizardContext['secret_storage_modes']), 'Wizard context should expose secret storage modes.');
assertTrueCondition(in_array('agent', array_column($wizardContext['secret_storage_modes'], 'value'), true), 'Wizard context should expose the agent storage mode.');
assertTrueCondition(in_array('local', array_column($wizardContext['secret_storage_modes'], 'value'), true), 'Wizard context should expose the local storage mode.');
assertTrueCondition(isset($wizardContext['recent_history']) && is_array($wizardContext['recent_history']), 'Wizard context should expose recent quick-backup history.');

RemoteBackupQuickFlow::persistCreationHistory(12, true, "Premiere ligne\nDeuxieme ligne");
$history = RemoteBackupQuickFlow::recentHistory(5);
assertTrueCondition(count($history) >= 1, 'Quick-backup history should be persisted in cron_log.');
assertSameValue('success', $history[0]['status'], 'Quick-backup history should preserve the status.');
assertSameValue(12, (int) $history[0]['job_id'], 'Quick-backup history should preserve the created job id.');

echo "Quick backup flow tests OK.\n";
