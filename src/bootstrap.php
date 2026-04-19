<?php
// =============================================================================
// bootstrap.php — Loading all dependencies
// =============================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Profiler.php';
RequestProfiler::bootstrap();
require_once __DIR__ . '/AppConfig.php';
require_once __DIR__ . '/ProcessRunner.php';
require_once __DIR__ . '/FileSystem.php';
require_once __DIR__ . '/DatabaseConfigWriter.php';
require_once __DIR__ . '/FilesystemScopeConfigWriter.php';
require_once __DIR__ . '/FilesystemScopeGuard.php';
require_once __DIR__ . '/JobRetryPolicy.php';
require_once __DIR__ . '/OutboundUrlValidator.php';
require_once __DIR__ . '/OutboundUrlTools.php';
require_once __DIR__ . '/PublicOutboundUrlValidator.php';
require_once __DIR__ . '/TrustedServiceEndpointValidator.php';
require_once __DIR__ . '/OutboundHttpClient.php';

// Fallbacks for binary constants added after initial deployment
if (!defined('RSYNC_BIN')) {
    $rsyncBin = ProcessRunner::locateBinary('rsync', ['/usr/bin/rsync', '/usr/local/bin/rsync']) ?: '/usr/bin/rsync';
    define('RSYNC_BIN', $rsyncBin);
}
if (!defined('SSH_BIN')) {
    $sshBin = ProcessRunner::locateBinary('ssh', ['/usr/bin/ssh', '/usr/local/bin/ssh']) ?: '/usr/bin/ssh';
    define('SSH_BIN', $sshBin);
}
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/InfisicalConfigManager.php';
require_once __DIR__ . '/ThemeManager.php';
require_once __DIR__ . '/ThemePackage.php';
require_once __DIR__ . '/ThemeStore.php';
require_once __DIR__ . '/ThemeRequestManager.php';
require_once __DIR__ . '/ThemeRenderer.php';
require_once __DIR__ . '/SecretRedaction.php';
require_once __DIR__ . '/SecretBrokerEvents.php';
require_once __DIR__ . '/SecretStore.php';
require_once __DIR__ . '/SensitiveEntitySecretManager.php';
require_once __DIR__ . '/BrokerClusterMonitor.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/AppNotificationManager.php';
require_once __DIR__ . '/DbArchive.php';
require_once __DIR__ . '/InfisicalClient.php';
require_once __DIR__ . '/SshHostKeyException.php';
require_once __DIR__ . '/ThirdParty/ChillerlanQrAutoload.php';
require_once __DIR__ . '/Totp.php';
require_once __DIR__ . '/StepUpAuth.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/SshKnownHosts.php';
require_once __DIR__ . '/Restic.php';
require_once __DIR__ . '/SnapshotSearchIndex.php';
require_once __DIR__ . '/RepoSnapshotCatalog.php';
require_once __DIR__ . '/JobQueue.php';
require_once __DIR__ . '/WorkerManager.php';
require_once __DIR__ . '/RunLogManager.php';
require_once __DIR__ . '/SchedulerManager.php';
require_once __DIR__ . '/RepoManager.php';
require_once __DIR__ . '/RepoStatusService.php';
require_once __DIR__ . '/RuntimeTtlCache.php';
require_once __DIR__ . '/DiskSpaceMonitor.php';
require_once __DIR__ . '/SshKeyManager.php';
require_once __DIR__ . '/ProvisioningManager.php';
require_once __DIR__ . '/QuickBackupTemplateManager.php';
require_once __DIR__ . '/RemoteBackupQuickFlow.php';
require_once __DIR__ . '/CopyJobManager.php';
require_once __DIR__ . '/BackupJobManager.php';
require_once __DIR__ . '/HostManager.php';
require_once __DIR__ . '/Notifier.php';
require_once __DIR__ . '/WebAuthn.php';
require_once __DIR__ . '/PerformanceMetrics.php';
require_once __DIR__ . '/ThemeTemplateTags.php';
require_once __DIR__ . '/HookScriptSecurity.php';
require_once __DIR__ . '/HookScriptManager.php';
require_once __DIR__ . '/HookScriptRunner.php';
require_once __DIR__ . '/Translator.php';

// Public API v1 --------------------------------------------------------------
require_once __DIR__ . '/Api/ApiScopes.php';
require_once __DIR__ . '/Api/ApiTokenManager.php';
require_once __DIR__ . '/Api/ApiResponse.php';
require_once __DIR__ . '/Api/ApiRequest.php';
require_once __DIR__ . '/Api/ApiAuth.php';
require_once __DIR__ . '/Api/ApiWebhookManager.php';
require_once __DIR__ . '/Api/ApiRouter.php';
require_once __DIR__ . '/Api/ApiKernel.php';
require_once __DIR__ . '/Api/ApiOpenApi.php';
foreach (glob(__DIR__ . '/Api/Handlers/*.php') as $apiHandlerFile) {
    require_once $apiHandlerFile;
}

// ── Default timezone ─────────────────────────────────────────────────────────
// Initializes the PHP timezone (date_default_timezone_set) as soon as AppConfig is
// available. Without this, CLI scripts use the php.ini CLI timezone
// (often UTC), which shifts all timestamps in worker/cron logs.
date_default_timezone_set(AppConfig::timezone());

// ── Global helpers ────────────────────────────────────────────────────────────

/**
 * Translates an i18n key with optional placeholder replacement.
 * Ex.: t('common.cancel') or t('flash.repos.added', ['name' => $name])
 */
function t(string $key, array $params = []): string {
    return Translator::get($key, $params);
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function routePath(string $target = '/', array $query = []): string {
    $path = trim($target);
    if ($path === '') {
        $path = '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    if (!str_starts_with($path, '/api/') && str_ends_with($path, '.php')) {
        if ($path === '/index.php') {
            $path = '/';
        } else {
            $path = substr($path, 0, -4);
        }
    }

    if (empty($query)) {
        return $path;
    }

    $queryString = http_build_query($query);
    return $queryString === '' ? $path : ($path . '?' . $queryString);
}

function redirectTo(string $target = '/', array $query = [], int $statusCode = 302): void {
    header('Location: ' . routePath($target, $query), true, $statusCode);
    exit;
}

function renderNotificationPolicySummary(array $policy, string $profile): string {
    $lines = Notifier::summarizePolicy($policy, $profile);
    if (empty($lines)) {
        $lines = [t('policy.notification.none')];
    }

    $html = '<div class="policy-summary policy-summary-notification">';
    foreach ($lines as $line) {
        $isGlobal = str_starts_with($line, 'Global');
        $badgeClass = $isGlobal ? 'policy-chip-blue' : 'policy-chip-gray';
        $html .= '<span class="policy-chip ' . $badgeClass . '">' . h($line) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function renderNotificationPolicyEditor(string $prefix, string $profile, array $policy): string {
    $profileMeta = Notifier::getProfile($profile);
    $channels = Notifier::getAvailableChannels();
    $events = $profileMeta['events'] ?? [];
    $isCustom = empty($policy['inherit']);
    $wrapperId = $prefix . '-notification-custom';

    ob_start();
    ?>
    <div class="policy-editor notification-policy-editor" data-prefix="<?= h($prefix) ?>" data-profile="<?= h($profile) ?>">
        <div class="policy-mode-switch">
            <label class="policy-mode-option">
                <input type="radio"
                       name="<?= h($prefix) ?>_notification_mode"
                       value="inherit"
                       <?= !$isCustom ? 'checked' : '' ?>
                       onchange="toggleNotificationPolicyMode('<?= h($prefix) ?>', false)"
                       style="accent-color:var(--accent)">
                <span><?= t('policy.notification.global_channels') ?></span>
            </label>
            <label class="policy-mode-option">
                <input type="radio"
                       name="<?= h($prefix) ?>_notification_mode"
                       value="custom"
                       <?= $isCustom ? 'checked' : '' ?>
                       onchange="toggleNotificationPolicyMode('<?= h($prefix) ?>', true)"
                       style="accent-color:var(--accent)">
                <span><?= t('policy.notification.custom') ?></span>
            </label>
        </div>
        <div class="policy-help">
            <?= t('policy.notification.help') ?>
        </div>
        <div id="<?= h($wrapperId) ?>" class="policy-custom-panel" style="display:<?= $isCustom ? 'block' : 'none' ?>">
            <div class="policy-event-list">
                <?php foreach ($events as $eventKey => $eventLabel): ?>
                <div class="policy-event-card">
                    <div class="policy-event-title"><?= h($eventLabel) ?></div>
                    <div class="policy-channel-grid">
                        <?php foreach ($channels as $channelKey => $channelMeta): ?>
                        <?php $checked = in_array($channelKey, $policy['events'][$eventKey] ?? [], true); ?>
                        <label class="policy-channel-toggle">
                            <input type="checkbox"
                                   name="<?= h($prefix) ?>_notify_<?= h($eventKey) ?>_<?= h($channelKey) ?>"
                                   value="1"
                                   <?= $checked ? 'checked' : '' ?>
                                   style="accent-color:var(--accent);width:16px;height:16px">
                            <span><?= h($channelMeta['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function renderRetryPolicySummary(array $policy): string {
    $items = JobRetryPolicy::summarizePolicy($policy);
    $toneClasses = [
        'blue' => 'policy-chip-blue',
        'gray' => 'policy-chip-gray',
        'green' => 'policy-chip-green',
        'yellow' => 'policy-chip-yellow',
        'purple' => 'policy-chip-purple',
    ];

    $html = '<div class="policy-summary policy-summary-retry">';
    foreach ($items as $item) {
        $tone = (string) ($item['tone'] ?? 'gray');
        $class = $toneClasses[$tone] ?? 'policy-chip-gray';
        $html .= '<span class="policy-chip ' . $class . '">' . h((string) ($item['text'] ?? '')) . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function renderRetryPolicyEditor(string $prefix, array $policy, bool $allowInheritance = true): string {
    $options = JobRetryPolicy::getRetryableOptions();
    $normalized = JobRetryPolicy::decodePolicy(
        JobRetryPolicy::encodePolicy($policy, $allowInheritance),
        $allowInheritance ? JobRetryPolicy::defaultEntityPolicy() : JobRetryPolicy::defaultGlobalPolicy(),
        $allowInheritance
    );
    $isCustom = !$allowInheritance || empty($normalized['inherit']);
    $customWrapperId = $prefix . '-retry-custom';
    $enabledWrapperId = $prefix . '-retry-enabled-options';

    ob_start();
    ?>
    <div class="policy-editor retry-policy-editor" data-prefix="<?= h($prefix) ?>">
        <?php if ($allowInheritance): ?>
        <div class="policy-mode-switch">
            <label class="policy-mode-option">
                <input type="radio"
                       name="<?= h($prefix) ?>_retry_mode"
                       value="inherit"
                       <?= !$isCustom ? 'checked' : '' ?>
                       onchange="toggleRetryPolicyMode('<?= h($prefix) ?>', false)"
                       style="accent-color:var(--accent)">
                <span><?= t('policy.retry.inherit') ?></span>
            </label>
            <label class="policy-mode-option">
                <input type="radio"
                       name="<?= h($prefix) ?>_retry_mode"
                       value="custom"
                       <?= $isCustom ? 'checked' : '' ?>
                       onchange="toggleRetryPolicyMode('<?= h($prefix) ?>', true)"
                       style="accent-color:var(--accent)">
                <span><?= t('policy.retry.custom') ?></span>
            </label>
        </div>
        <?php endif; ?>

        <div class="policy-help">
            <?= t('policy.retry.help') ?>
        </div>

        <div id="<?= h($customWrapperId) ?>" class="policy-custom-panel" style="display:<?= $isCustom ? 'block' : 'none' ?>">
            <label class="settings-toggle" style="margin-bottom:12px">
                <input type="checkbox"
                       name="<?= h($prefix) ?>_retry_enabled"
                       value="1"
                       <?= !empty($normalized['enabled']) ? 'checked' : '' ?>
                       onchange="toggleRetryPolicyEnabled('<?= h($prefix) ?>')">
                <span><?= t('policy.retry.enable') ?></span>
            </label>

            <div id="<?= h($enabledWrapperId) ?>" style="display:<?= !empty($normalized['enabled']) ? 'block' : 'none' ?>">
                <div class="retry-policy-grid">
                    <div class="form-group">
                        <label class="form-label"><?= t('policy.retry.count_label') ?></label>
                        <input type="number"
                               name="<?= h($prefix) ?>_retry_max_retries"
                               class="form-control"
                               min="0"
                               max="10"
                               value="<?= (int) $normalized['max_retries'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('policy.retry.delay_label') ?></label>
                        <input type="number"
                               name="<?= h($prefix) ?>_retry_delay_seconds"
                               class="form-control"
                               min="1"
                               max="600"
                               value="<?= (int) $normalized['delay_seconds'] ?>">
                    </div>
                </div>

                <div class="policy-event-card">
                    <div class="policy-event-title"><?= t('policy.retry.error_categories') ?></div>
                    <div class="policy-channel-grid">
                        <?php foreach ($options as $key => $label): ?>
                        <label class="policy-channel-toggle">
                            <input type="checkbox"
                                   name="<?= h($prefix) ?>_retry_on_<?= h($key) ?>"
                                   value="1"
                                   <?= in_array($key, $normalized['retry_on'], true) ? 'checked' : '' ?>
                                   style="accent-color:var(--accent);width:16px;height:16px">
                            <span><?= h($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="policy-help" style="margin-top:10px">
                        <?= t('policy.retry.auth_never') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' ' . t('units.gb');
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' ' . t('units.mb');
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' ' . t('units.kb');
    return $bytes . ' ' . t('units.bytes');
}

function appServerTimezone(): DateTimeZone {
    static $timezone = null;
    if (!$timezone instanceof DateTimeZone) {
        $timezone = new DateTimeZone(AppConfig::serverTimezone());
    }

    return $timezone;
}

function appDatabaseTimezone(): DateTimeZone {
    static $timezone = null;
    if (!$timezone instanceof DateTimeZone) {
        $timezone = new DateTimeZone('UTC');
    }

    return $timezone;
}

function appDisplayTimezone(): DateTimeZone {
    static $cache = [];
    $name = Auth::isLoggedIn() ? Auth::preferredTimezone() : AppConfig::timezone();
    if (!isset($cache[$name])) {
        $cache[$name] = new DateTimeZone($name);
    }

    return $cache[$name];
}

function parseAppDate(?string $date, ?DateTimeZone $sourceTimezone = null): ?DateTimeImmutable {
    $value = trim((string) $date);
    if ($value === '') {
        return null;
    }

    try {
        if (preg_match('/(?:Z|[+\-]\d{2}:\d{2}|[+\-]\d{4})$/', $value) === 1) {
            return new DateTimeImmutable($value);
        }

        return new DateTimeImmutable($value, $sourceTimezone ?? appDatabaseTimezone());
    } catch (Throwable $e) {
        return null;
    }
}

function formatDateForDisplay(string $date, string $format = 'd/m/Y H:i:s', ?DateTimeZone $sourceTimezone = null): string {
    $parsed = parseAppDate($date, $sourceTimezone);
    if (!$parsed) {
        return $date;
    }

    return $parsed->setTimezone(appDisplayTimezone())->format($format);
}

function formatDate(string $date): string {
    return formatDateForDisplay($date);
}

function formatCurrentDisplayDate(string $format = 'd/m/Y H:i:s', ?DateTimeInterface $now = null): string {
    $current = $now instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($now)
        : new DateTimeImmutable('now', appServerTimezone());

    return $current->setTimezone(appDisplayTimezone())->format($format);
}

function formatTimestampForDisplay(int $timestamp, string $format = 'd/m/Y H:i:s'): string {
    return (new DateTimeImmutable('@' . $timestamp))
        ->setTimezone(appDisplayTimezone())
        ->format($format);
}

function appDateTimestamp(?string $date, ?DateTimeZone $sourceTimezone = null): ?int {
    $parsed = parseAppDate($date, $sourceTimezone);
    return $parsed ? $parsed->getTimestamp() : null;
}

function formatDurationBetween(?string $startedAt, ?string $finishedAt, ?DateTimeZone $sourceTimezone = null): string {
    $started = appDateTimestamp($startedAt, $sourceTimezone);
    $finished = appDateTimestamp($finishedAt, $sourceTimezone);
    if ($started === null || $finished === null) {
        return '';
    }

    $seconds = max(0, $finished - $started);
    return $seconds < 60 ? ($seconds . 's') : (floor($seconds / 60) . 'm' . ($seconds % 60) . 's');
}

function isTextFile(string $filename): bool {
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $basename = strtolower(basename($filename));
    return in_array($ext, TEXT_EXTENSIONS)
        || in_array($basename, ['dockerfile', 'makefile', 'readme', '.env', '.gitignore', 'hosts']);
}

function isSensitivePreviewFile(string $filename): bool {
    $basename = strtolower(trim(basename($filename)));
    if ($basename === '') {
        return false;
    }

    $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    if (in_array($ext, ['pem', 'key'], true)) {
        return true;
    }

    if (in_array($basename, ['.env', 'id_rsa', 'kubeconfig'], true)) {
        return true;
    }

    return str_starts_with($basename, 'secrets.') && strlen($basename) > strlen('secrets.');
}

function canInlinePreviewFile(string $filename): bool {
    return !isSensitivePreviewFile($filename) || Auth::hasPermission('repos.view_sensitive_files');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonResponseCached(array $data, int $code = 200, int $maxAgeSeconds = 5): void {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=' . max(0, $maxAgeSeconds) . ', stale-while-revalidate=30');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonResponseWithEtag(array $data, int $code = 200): void {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        jsonResponse(['error' => 'Serialization error'], 500);
    }

    $etag = '"' . sha1($json) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: private, must-revalidate');

    $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    if ($ifNoneMatch === $etag) {
        http_response_code(304);
        exit;
    }

    http_response_code($code);
    header('Content-Type: application/json');
    echo $json;
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function requestCsrfToken(): ?string {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if ((!is_string($token) || $token === '') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        $jsonBody = requestJsonBody();
        $jsonToken = $jsonBody['csrf_token'] ?? null;
        if (is_string($jsonToken) && $jsonToken !== '') {
            $token = $jsonToken;
        }
    }

    return is_string($token) && $token !== '' ? $token : null;
}

function requestRawBody(): string {
    if (array_key_exists('__fulgurite_raw_body', $GLOBALS)) {
        return (string) $GLOBALS['__fulgurite_raw_body'];
    }
    $raw = file_get_contents('php://input');
    $GLOBALS['__fulgurite_raw_body'] = is_string($raw) ? $raw : '';
    return $GLOBALS['__fulgurite_raw_body'];
}

function requestJsonBody(): array {
    if (array_key_exists('__fulgurite_json_body', $GLOBALS)) {
        $cached = $GLOBALS['__fulgurite_json_body'];
        return is_array($cached) ? $cached : [];
    }
    $decoded = json_decode(requestRawBody(), true);
    $GLOBALS['__fulgurite_json_body'] = is_array($decoded) ? $decoded : [];
    return $GLOBALS['__fulgurite_json_body'];
}

function isValidCsrfRequest(): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? null;
    $token = requestCsrfToken();

    return is_string($sessionToken)
        && $sessionToken !== ''
        && is_string($token)
        && hash_equals($sessionToken, $token);
}

function verifyCsrf(): void {
    if (!isValidCsrfRequest()) {
        http_response_code(403);
        echo json_encode(['error' => t('error.csrf')]);
        exit;
    }
}

/**
 * Rate limiting on API endpoints.
 * Blocks with HTTP 429 if the IP exceeds $maxHits over $windowSecs seconds.
 */
function rateLimitApi(string $endpoint, ?int $maxHits = null, ?int $windowSecs = null): void {
    $rateLimit = AppConfig::getApiRateLimit($endpoint, $maxHits ?? 20, $windowSecs ?? 60);
    $maxHits = (int) ($rateLimit['hits'] ?? ($maxHits ?? 20));
    $windowSecs = (int) ($rateLimit['window_seconds'] ?? ($windowSecs ?? 60));
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        $ip = '0.0.0.0';
    }

    $apcuDecision = rateLimitApiApcuDecision($endpoint, $ip, $maxHits, $windowSecs);
    if (is_array($apcuDecision) && !($apcuDecision['allowed'] ?? false)) {
        http_response_code(429);
        header('Retry-After: ' . (int) ($apcuDecision['retry_after'] ?? $windowSecs));
        Auth::log('rate_limit', "Rate limit atteint sur $endpoint ($ip)", 'warning');
        jsonResponse(['error' => t('error.rate_limit')]);
    }
    if (is_array($apcuDecision) && ($apcuDecision['allowed'] ?? false)) {
        return;
    }

    $db = Database::getInstance();
    // Lazy cleanup (1 chance in 10 to avoid doing it on every request)
    if (random_int(1, 10) === 1) {
        $db->exec("DELETE FROM api_rate_limits WHERE hit_at < datetime('now', '-3600 seconds')");
    }
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM api_rate_limits
        WHERE ip = ? AND endpoint = ?
        AND hit_at >= datetime('now', '-' || ? || ' seconds')
    ");
    $stmt->execute([$ip, $endpoint, $windowSecs]);
    if ((int) $stmt->fetchColumn() >= $maxHits) {
        http_response_code(429);
        header('Retry-After: ' . $windowSecs);
        Auth::log('rate_limit', "Rate limit atteint sur $endpoint ($ip)", 'warning');
        jsonResponse(['error' => t('error.rate_limit')]);
    }
    $db->prepare("INSERT INTO api_rate_limits (ip, endpoint) VALUES (?, ?)")->execute([$ip, $endpoint]);
}

function rateLimitApiApcuDecision(string $endpoint, string $ip, int $maxHits, int $windowSecs): ?array {
    if (!AppConfig::apiRateLimitApcuEnabled() || !fulguriteApcuAvailable()) {
        return null;
    }

    $cacheKey = 'fulgurite:api_rate_limit:' . hash('sha256', strtolower($endpoint) . '|' . $ip . '|' . $windowSecs . '|' . $maxHits);
    $lockKey = $cacheKey . ':lock';
    if (!apcu_add($lockKey, 1, AppConfig::apiRateLimitApcuLockTtlSeconds())) {
        return null;
    }

    try {
        $now = time();
        $windowStart = $now - $windowSecs + 1;
        $hits = apcu_fetch($cacheKey);
        if (!is_array($hits)) {
            $hits = [];
        }

        $pruned = [];
        foreach ($hits as $hitAt) {
            $hitAt = (int) $hitAt;
            if ($hitAt >= $windowStart) {
                $pruned[] = $hitAt;
            }
        }

        $maxTrackedHits = max(50, $maxHits * 3);
        if (count($pruned) > $maxTrackedHits) {
            $pruned = array_slice($pruned, -$maxTrackedHits);
        }

        if (count($pruned) >= $maxHits) {
            $retryAfter = max(1, ((int) ($pruned[0] ?? $now) + $windowSecs) - $now);
            apcu_store($cacheKey, $pruned, $windowSecs + AppConfig::apiRateLimitApcuTtlPaddingSeconds());
            return ['allowed' => false, 'retry_after' => $retryAfter];
        }

        $pruned[] = $now;
        apcu_store($cacheKey, $pruned, $windowSecs + AppConfig::apiRateLimitApcuTtlPaddingSeconds());
        return ['allowed' => true, 'retry_after' => 0];
    } catch (Throwable $e) {
        return null;
    } finally {
        apcu_delete($lockKey);
    }
}

function fulguriteApcuAvailable(): bool {
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    if (!function_exists('apcu_fetch') || !function_exists('apcu_store') || !function_exists('apcu_add') || !function_exists('apcu_delete')) {
        $available = false;
        return $available;
    }

    $enabledRaw = ini_get('apc.enabled');
    if ($enabledRaw === false || $enabledRaw === '') {
        $available = false;
        return $available;
    }

    $available = filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);
    return $available;
}

/**
 * Checks whether a password has been compromised via Have I Been Pwned (k-anonymity).
 * Returns the number of times the password has leaked (0 = safe).
 */
function checkHibp(string $password): int {
    $hash   = strtoupper(sha1($password));
    $prefix = substr($hash, 0, 5);
    $suffix = substr($hash, 5);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: Fulgurite/1.0\r\n",
            'timeout' => 5,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $url      = "https://api.pwnedpasswords.com/range/$prefix";
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return 0; // Échec reseau → on laisse passer

    foreach (explode("\n", $response) as $line) {
        [$lineSuffix, $count] = explode(':', trim($line)) + ['', '0'];
        if (strtoupper($lineSuffix) === $suffix) {
            return (int) $count;
        }
    }
    return 0;
}
