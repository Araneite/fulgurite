<?php
// =============================================================================
// BackupJobsHandler.php — /api/v1/backup-jobs
// =============================================================================

class BackupJobsHandler {

    public static function publicView(array $job): array {
        return [
            'id' => (int) $job['id'],
            'name' => $job['name'],
            'repo_id' => (int) $job['repo_id'],
            'repo_name' => $job['repo_name'] ?? null,
            'host_id' => isset($job['host_id']) ? (int) $job['host_id'] : null,
            'host_name' => $job['host_name'] ?? null,
            'source_paths' => json_decode($job['source_paths'] ?? '[]', true) ?: [],
            'tags' => json_decode($job['tags'] ?? '[]', true) ?: [],
            'excludes' => json_decode($job['excludes'] ?? '[]', true) ?: [],
            'description' => $job['description'] ?? null,
            'schedule_enabled' => (bool) ($job['schedule_enabled'] ?? 0),
            'schedule_hour' => (int) ($job['schedule_hour'] ?? 0),
            'schedule_days' => $job['schedule_days'] ?? '',
            'notify_on_failure' => (bool) ($job['notify_on_failure'] ?? 0),
            'last_run' => $job['last_run'] ?? null,
            'last_status' => $job['last_status'] ?? null,
            'created_at' => $job['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('backup_jobs:read');
        $jobs = BackupJobManager::getAll(ApiAuth::allowedRepoIds(), ApiAuth::allowedHostIds());
        ApiResponse::ok(array_map([self::class, 'publicView'], $jobs));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('backup_jobs:read');
        $job = BackupJobManager::getById((int) $args['id']);
        if (!$job) ApiResponse::error(404, 'not_found', 'Backup job introuvable');
        self::requireJobAccess($job);
        ApiResponse::ok(self::publicView($job));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('backup_jobs:write');
        $body = ApiRequest::body();
        $name = trim((string) ($body['name'] ?? ''));
        $repoId = (int) ($body['repo_id'] ?? 0);
        if ($name === '' || $repoId <= 0) {
            ApiResponse::error(422, 'validation_error', 'Champs name et repo_id requis');
        }
        ApiAuth::requireRepoAccess($repoId);
        $hostId = self::nullableId($body, 'host_id');
        if ($hostId !== null) {
            ApiAuth::requireHostAccess($hostId);
        }
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $id = BackupJobManager::add(
            $name,
            $repoId,
            (array) ($body['source_paths'] ?? []),
            (array) ($body['tags'] ?? []),
            (array) ($body['excludes'] ?? []),
            (string) ($body['description'] ?? ''),
            !empty($body['schedule_enabled']) ? 1 : 0,
            (int) ($body['schedule_hour'] ?? 2),
            (string) ($body['schedule_days'] ?? '1'),
            !empty($body['notify_on_failure']) ? 1 : 0,
            $hostId,
            (string) ($body['remote_repo_path'] ?? ''),
            (string) ($body['hostname_override'] ?? ''),
        );
        ApiResponse::created(self::publicView(BackupJobManager::getById($id)));
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('backup_jobs:write');
        $id = (int) $args['id'];
        $job = BackupJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Backup job introuvable');
        self::requireJobAccess($job);
        $body = ApiRequest::body();
        if (array_key_exists('repo_id', $body)) {
            $repoId = (int) $body['repo_id'];
            if ($repoId <= 0) {
                ApiResponse::error(422, 'validation_error', 'Champ repo_id invalide');
            }
            ApiAuth::requireRepoAccess($repoId);
            $body['repo_id'] = $repoId;
        }
        if (array_key_exists('host_id', $body)) {
            $body['host_id'] = self::nullableId($body, 'host_id');
            if ($body['host_id'] !== null) {
                ApiAuth::requireHostAccess($body['host_id']);
            }
        }
        BackupJobManager::update($id, $body);
        ApiResponse::ok(self::publicView(BackupJobManager::getById($id)));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('backup_jobs:write');
        $id = (int) $args['id'];
        $job = BackupJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Backup job introuvable');
        self::requireJobAccess($job);
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        BackupJobManager::delete($id);
        ApiResponse::noContent();
    }

    public static function run(array $args): void {
        ApiAuth::requireScope('backup_jobs:run');
        $id = (int) $args['id'];
        $job = BackupJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Backup job introuvable');
        self::requireJobAccess($job);
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);

        $result = BackupJobManager::run($id);
        $event = !empty($result['success']) ? 'backup_job.success' : 'backup_job.failure';
        ApiWebhookManager::dispatch($event, ['id' => $id, 'name' => $job['name'], 'output' => $result['output'] ?? '']);
        ApiResponse::ok($result);
    }

    private static function requireJobAccess(array $job): void {
        ApiAuth::requireRepoAccess((int) $job['repo_id']);
        if (!empty($job['host_id'])) {
            ApiAuth::requireHostAccess((int) $job['host_id']);
        }
    }

    private static function nullableId(array $body, string $field): ?int {
        if (!array_key_exists($field, $body) || $body[$field] === null || $body[$field] === '') {
            return null;
        }

        $id = (int) $body[$field];
        if ($id <= 0) {
            ApiResponse::error(422, 'validation_error', "Champ $field invalide");
        }

        return $id;
    }
}
