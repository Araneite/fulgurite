<?php
// =============================================================================
// public/api/v1/index.php — Entry point for the public v1 REST API
// =============================================================================

// PHP errors must never pollute API JSON/HTML responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Global handler: converts any uncaught exception into JSON 500 response// without exposing internal details to the client.
set_exception_handler(function (Throwable $e): void {
    error_log(sprintf(
        '[api-v1] Uncaught %s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode([
        'data' => null, 'meta' => null,
        'error' => ['code' => 'internal_error', 'message' => function_exists('t') ? t('api.v1.error.internal_server') : 'Internal server error.'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../../../src/bootstrap.php';

$method = ApiRequest::method();
$path   = ApiRequest::path();

// ===========================================================================
// 100% public endpoints — before any auth or API activation checks
// ===========================================================================

if ($method === 'GET' && ($path === '/openapi.json' || $path === '/openapi')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    echo json_encode(ApiOpenApi::document(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'GET' && ($path === '/docs' || $path === '/swagger')) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Fulgurite API Docs</title></head>'
       . '<body><h1>Fulgurite API Docs</h1>'
       . '<p>La specification OpenAPI est disponible au format JSON.</p>'
       . '<p><a href="openapi.json">Ouvrir openapi.json</a></p>'
       . '</body></html>';
    exit;
}

if ($method === 'GET' && $path === '/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
    exit;
}

// ===========================================================================
// The rest of the API requires bootstrap, activation, and authentication
// ===========================================================================

ApiKernel::bootstrap();

if (!AppConfig::isApiEnabled()) {
    ApiResponse::error(503, 'api_disabled', t('api.v1.error.api_disabled'));
}

// authentication ------------------------------------------------------------
ApiAuth::authenticate();
ApiKernel::applyAuthenticatedCors();
ApiKernel::checkIdempotency();

// Routes ---------------------------------------------------------------------
$router = new ApiRouter();

// Identite
$router->get('/me', [MeHandler::class, 'index']);

// API tokens
$router->get('/api-tokens', [ApiTokensHandler::class, 'index']);
$router->post('/api-tokens', [ApiTokensHandler::class, 'create']);
$router->get('/api-tokens/{id}', [ApiTokensHandler::class, 'show']);
$router->patch('/api-tokens/{id}', [ApiTokensHandler::class, 'update']);
$router->post('/api-tokens/{id}/revoke', [ApiTokensHandler::class, 'revoke']);
$router->delete('/api-tokens/{id}', [ApiTokensHandler::class, 'delete']);

// Webhooks
$router->get('/webhooks', [WebhooksHandler::class, 'index']);
$router->post('/webhooks', [WebhooksHandler::class, 'create']);
$router->get('/webhooks/events', [WebhooksHandler::class, 'events']);
$router->get('/webhooks/{id}', [WebhooksHandler::class, 'show']);
$router->patch('/webhooks/{id}', [WebhooksHandler::class, 'update']);
$router->delete('/webhooks/{id}', [WebhooksHandler::class, 'delete']);
$router->post('/webhooks/{id}/test', [WebhooksHandler::class, 'test']);
$router->get('/webhooks/{id}/deliveries', [WebhooksHandler::class, 'deliveries']);

// Repositories$router->get('/repos', [ReposHandler::class, 'index']);
$router->post('/repos', [ReposHandler::class, 'create']);
$router->get('/repos/{id}', [ReposHandler::class, 'show']);
$router->patch('/repos/{id}', [ReposHandler::class, 'update']);
$router->delete('/repos/{id}', [ReposHandler::class, 'delete']);
$router->post('/repos/{id}/check', [ReposHandler::class, 'check']);
$router->get('/repos/{id}/stats', [ReposHandler::class, 'stats']);

// Snapshots
$router->get('/repos/{id}/snapshots', [SnapshotsHandler::class, 'index']);
$router->get('/repos/{id}/snapshots/{sid}', [SnapshotsHandler::class, 'show']);
$router->get('/repos/{id}/snapshots/{sid}/files', [SnapshotsHandler::class, 'listFiles']);
$router->delete('/repos/{id}/snapshots/{sid}', [SnapshotsHandler::class, 'delete']);
$router->put('/repos/{id}/snapshots/{sid}/tags', [SnapshotsHandler::class, 'setTags']);

// Backup jobs
$router->get('/backup-jobs', [BackupJobsHandler::class, 'index']);
$router->post('/backup-jobs', [BackupJobsHandler::class, 'create']);
$router->get('/backup-jobs/{id}', [BackupJobsHandler::class, 'show']);
$router->patch('/backup-jobs/{id}', [BackupJobsHandler::class, 'update']);
$router->delete('/backup-jobs/{id}', [BackupJobsHandler::class, 'delete']);
$router->post('/backup-jobs/{id}/run', [BackupJobsHandler::class, 'run']);

// Copy jobs
$router->get('/copy-jobs', [CopyJobsHandler::class, 'index']);
$router->post('/copy-jobs', [CopyJobsHandler::class, 'create']);
$router->get('/copy-jobs/{id}', [CopyJobsHandler::class, 'show']);
$router->patch('/copy-jobs/{id}', [CopyJobsHandler::class, 'update']);
$router->delete('/copy-jobs/{id}', [CopyJobsHandler::class, 'delete']);
$router->post('/copy-jobs/{id}/run', [CopyJobsHandler::class, 'run']);

// Restores
$router->get('/restores', [RestoresHandler::class, 'index']);
$router->post('/restores', [RestoresHandler::class, 'create']);
$router->get('/restores/{id}', [RestoresHandler::class, 'show']);

// Hosts
$router->get('/hosts', [HostsHandler::class, 'index']);
$router->post('/hosts', [HostsHandler::class, 'create']);
$router->get('/hosts/{id}', [HostsHandler::class, 'show']);
$router->patch('/hosts/{id}', [HostsHandler::class, 'update']);
$router->delete('/hosts/{id}', [HostsHandler::class, 'delete']);
$router->post('/hosts/{id}/test', [HostsHandler::class, 'test']);

// SSH keys
$router->get('/ssh-keys', [SshKeysHandler::class, 'index']);
$router->post('/ssh-keys', [SshKeysHandler::class, 'create']);
$router->get('/ssh-keys/{id}', [SshKeysHandler::class, 'show']);
$router->delete('/ssh-keys/{id}', [SshKeysHandler::class, 'delete']);
$router->post('/ssh-keys/{id}/test', [SshKeysHandler::class, 'test']);

// Scheduler
$router->get('/scheduler/tasks', [SchedulerHandler::class, 'tasks']);
$router->get('/scheduler/backup-schedules', [SchedulerHandler::class, 'backupSchedules']);
$router->get('/scheduler/copy-schedules', [SchedulerHandler::class, 'copySchedules']);
$router->get('/scheduler/cron-log', [SchedulerHandler::class, 'cronLog']);
$router->post('/scheduler/tasks/{key}/run', [SchedulerHandler::class, 'runTask']);

// Notifications
$router->get('/notifications', [NotificationsHandler::class, 'index']);
$router->get('/notifications/unread-count', [NotificationsHandler::class, 'unreadCount']);
$router->post('/notifications/{id}/read', [NotificationsHandler::class, 'markRead']);
$router->post('/notifications/read-all', [NotificationsHandler::class, 'markAllRead']);
$router->delete('/notifications/{id}', [NotificationsHandler::class, 'delete']);
$router->delete('/notifications/read', [NotificationsHandler::class, 'deleteAllRead']);

// Stats
$router->get('/stats/summary', [StatsHandler::class, 'summary']);
$router->get('/stats/repo-runtime', [StatsHandler::class, 'repoRuntime']);
$router->get('/stats/repos/{id}/history', [StatsHandler::class, 'repoHistory']);

// Logs
$router->get('/logs/activity', [LogsHandler::class, 'activity']);
$router->get('/logs/cron', [LogsHandler::class, 'cron']);
$router->get('/logs/api-tokens', [LogsHandler::class, 'apiTokens']);

// Job queue
$router->get('/jobs/summary', [JobQueueHandler::class, 'summary']);
$router->get('/jobs/recent', [JobQueueHandler::class, 'recent']);
$router->get('/jobs/worker', [JobQueueHandler::class, 'worker']);
$router->post('/jobs/process', [JobQueueHandler::class, 'process']);
$router->post('/jobs/recover', [JobQueueHandler::class, 'recover']);

// Users
$router->get('/users', [UsersHandler::class, 'index']);
$router->post('/users', [UsersHandler::class, 'create']);
$router->get('/users/{id}', [UsersHandler::class, 'show']);
$router->patch('/users/{id}', [UsersHandler::class, 'update']);
$router->delete('/users/{id}', [UsersHandler::class, 'delete']);

// Settings
$router->get('/settings', [SettingsHandler::class, 'index']);
$router->get('/settings/{key}', [SettingsHandler::class, 'show']);
$router->put('/settings/{key}', [SettingsHandler::class, 'update']);

$router->dispatch($method, $path);
