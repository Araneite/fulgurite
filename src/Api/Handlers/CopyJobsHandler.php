<?php
// =============================================================================
// CopyJobsHandler.php — /api/v1/copy-jobs
// =============================================================================

class CopyJobsHandler {

    public static function publicView(array $job): array {
        return [
            'id' => (int) $job['id'],
            'name' => $job['name'],
            'source_repo_id' => (int) $job['source_repo_id'],
            'source_name' => $job['source_name'] ?? null,
            'dest_path' => $job['dest_path'],
            'description' => $job['description'] ?? null,
            'schedule_enabled' => (bool) ($job['schedule_enabled'] ?? 0),
            'schedule_hour' => (int) ($job['schedule_hour'] ?? 0),
            'schedule_days' => $job['schedule_days'] ?? '',
            'last_run' => $job['last_run'] ?? null,
            'last_status' => $job['last_status'] ?? null,
            'created_at' => $job['created_at'] ?? null,
        ];
    }

    public static function index(array $args): void {
        ApiAuth::requireScope('copy_jobs:read');
        $jobs = CopyJobManager::getAll(ApiAuth::allowedRepoIds());
        ApiResponse::ok(array_map([self::class, 'publicView'], $jobs));
    }

    public static function show(array $args): void {
        ApiAuth::requireScope('copy_jobs:read');
        $job = CopyJobManager::getById((int) $args['id']);
        if (!$job) ApiResponse::error(404, 'not_found', 'Copy job introuvable');
        self::requireJobAccess($job);
        ApiResponse::ok(self::publicView($job));
    }

    public static function create(array $args): void {
        ApiAuth::requireScope('copy_jobs:write');
        $body = ApiRequest::body();
        $name = trim((string) ($body['name'] ?? ''));
        $sourceRepoId = (int) ($body['source_repo_id'] ?? 0);
        $destPath = (string) ($body['dest_path'] ?? '');
        $destPassword = (string) ($body['dest_password'] ?? '');
        if ($name === '' || $sourceRepoId <= 0 || $destPath === '') {
            ApiResponse::error(422, 'validation_error', 'Champs name, source_repo_id, dest_path requis');
        }
        ApiAuth::requireRepoAccess($sourceRepoId);
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $id = CopyJobManager::add(
            $name,
            $sourceRepoId,
            $destPath,
            $destPassword,
            (string) ($body['description'] ?? ''),
            !empty($body['schedule_enabled']) ? 1 : 0,
            (int) ($body['schedule_hour'] ?? 2),
            (string) ($body['schedule_days'] ?? '1'),
            (string) ($body['dest_password_source'] ?? 'agent'),
            (string) ($body['dest_infisical_secret_name'] ?? ''),
        );
        ApiResponse::created(self::publicView(CopyJobManager::getById($id)));
    }

    public static function update(array $args): void {
        ApiAuth::requireScope('copy_jobs:write');
        $id = (int) $args['id'];
        $job = CopyJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Copy job introuvable');
        self::requireJobAccess($job);
        $body = ApiRequest::body();
        if (array_key_exists('source_repo_id', $body)) {
            $sourceRepoId = (int) $body['source_repo_id'];
            if ($sourceRepoId <= 0) {
                ApiResponse::error(422, 'validation_error', 'Champ source_repo_id invalide');
            }
            ApiAuth::requireRepoAccess($sourceRepoId);
            $body['source_repo_id'] = $sourceRepoId;
        }
        CopyJobManager::update($id, $body);
        ApiResponse::ok(self::publicView(CopyJobManager::getById($id)));
    }

    public static function delete(array $args): void {
        ApiAuth::requireScope('copy_jobs:write');
        $id = (int) $args['id'];
        $job = CopyJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Copy job introuvable');
        self::requireJobAccess($job);
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        CopyJobManager::delete($id);
        ApiResponse::noContent();
    }

    public static function run(array $args): void {
        ApiAuth::requireScope('copy_jobs:run');
        $id = (int) $args['id'];
        $job = CopyJobManager::getById($id);
        if (!$job) ApiResponse::error(404, 'not_found', 'Copy job introuvable');
        ApiAuth::requireRepoAccess((int) $job['source_repo_id']);
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $snapshotId = ApiRequest::input('snapshot_id');
        $result = CopyJobManager::run($id, $snapshotId !== null ? (string) $snapshotId : null);
        $event = !empty($result['success']) ? 'copy_job.success' : 'copy_job.failure';
        ApiWebhookManager::dispatch($event, ['id' => $id, 'name' => $job['name'], 'output' => $result['output'] ?? '']);
        ApiResponse::ok($result);
    }

    private static function requireJobAccess(array $job): void {
        ApiAuth::requireRepoAccess((int) $job['source_repo_id']);
    }
}
