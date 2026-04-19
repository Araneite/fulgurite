<?php
// =============================================================================
// JobQueueHandler.php — /api/v1/jobs (internal jobs queue)
// =============================================================================

class JobQueueHandler {

    public static function summary(array $args): void {
        ApiAuth::requireScope('jobs_queue:read');
        ApiResponse::ok(JobQueue::getSummary());
    }

    public static function recent(array $args): void {
        ApiAuth::requireScope('jobs_queue:read');
        $limit = ApiRequest::queryInt('limit', 25);
        $limit = max(1, min(200, $limit));
        ApiResponse::ok(JobQueue::getRecentJobs($limit));
    }

    public static function worker(array $args): void {
        ApiAuth::requireScope('jobs_queue:read');
        $name = ApiRequest::query('name', 'default');
        ApiResponse::ok(JobQueue::getWorkerHeartbeat($name) ?? []);
    }

    public static function process(array $args): void {
        ApiAuth::requireScope('jobs_queue:write');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $limit = (int) (ApiRequest::body()['limit'] ?? 3);
        $limit = max(1, min(20, $limit));
        $types = (array) (ApiRequest::body()['types'] ?? []);
        ApiResponse::ok(JobQueue::processDueJobs($limit, $types));
    }

    public static function recover(array $args): void {
        ApiAuth::requireScope('jobs_queue:write');
        if (ApiRequest::isDryRun()) ApiResponse::ok(['dry_run' => true]);
        $minutes = (int) (ApiRequest::body()['older_than_minutes'] ?? 30);
        ApiResponse::ok(['recovered' => JobQueue::recoverStaleRunningJobs($minutes)]);
    }
}
