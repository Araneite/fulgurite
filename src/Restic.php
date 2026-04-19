<?php
// =============================================================================
// Restic.php — Wrapper around restic commands
// =============================================================================

class Restic {

    private const CACHE_DIR = '/cache/restic';
    private const RUNTIME_CACHE_DIR = '/fulgurite-runtime';
    private const SNAPSHOTS_CACHE_TTL = 60;
    private const LS_CACHE_TTL = 30;
    private const STATS_CACHE_TTL = 300;
    private const SEARCH_CACHE_TTL = 60;
    private const TREE_CACHE_TTL = 900;
    private const SLOW_COMMAND_THRESHOLD_MS = 300;

    private string $repo;
    private string $password;
    private ?string $sftpTmpHome = null;
    private ?string $sshPrivateKey;

    public function __construct(string $repo_path, string $password, ?string $sshPrivateKey = null) {
        $this->repo          = $repo_path;
        $this->password      = $password;
        $this->sshPrivateKey = $sshPrivateKey;
    }

    public function __destruct() {
        if ($this->sftpTmpHome !== null && is_dir($this->sftpTmpHome)) {
            try {
                FileSystem::removeDirectory($this->sftpTmpHome);
            } catch (Throwable $e) {
                error_log('[fulgurite-restic-cleanup] ' . $e->getMessage());
            }
            $this->sftpTmpHome = null;
        }
    }

    private function initSftpTmpHome(): void {
        if ($this->sftpTmpHome !== null) {
            return;
        }

        $sftp = $this->parseSftpRepository();
        if ($sftp === null) {
            return;
        }

        $dir = rtrim(sys_get_temp_dir(), '/\\') . '/fulgurite-sftp-' . bin2hex(random_bytes(8));
        FilesystemScopeGuard::assertPathCreatable($dir, 'write');
        @mkdir($dir . '/.ssh', 0700, true);
        @chmod($dir, 0700);

        $knownHostsFile = SshKnownHosts::knownHostsFile();
        $configLines = ['Host ' . $sftp['host']];
        if ($sftp['port'] !== 22) {
            $configLines[] = '    Port ' . $sftp['port'];
        }
        $configLines[] = '    BatchMode yes';
        $configLines[] = '    StrictHostKeyChecking yes';
        $configLines[] = '    UserKnownHostsFile ' . $knownHostsFile;
        $configLines[] = '    GlobalKnownHostsFile /dev/null';
        $configLines[] = '    ConnectTimeout 10';

        if ($this->sshPrivateKey !== null && $this->sshPrivateKey !== '') {
            $keyFile = $dir . '/.ssh/fulgurite_id';
            @file_put_contents($keyFile, $this->sshPrivateKey);
            @chmod($keyFile, 0600);
            $configLines[] = '    IdentityFile ' . $keyFile;
            $configLines[] = '    IdentitiesOnly yes';
        }

        $configFile = $dir . '/.ssh/config';
        @file_put_contents($configFile, implode("\n", $configLines) . "\n");
        @chmod($configFile, 0600);

        $this->sftpTmpHome = $dir;
    }

    private static function ensureDirectory(string $dir, int $mode = 0700): string {
        FilesystemScopeGuard::assertPathCreatable($dir, 'write');
        if (!is_dir($dir)) {
            mkdir($dir, $mode, true);
        }
        @chmod($dir, $mode);
        return $dir;
    }

    public static function writeTempSecretFile(string $secret, string $prefix = 'fulgurite_secret_'): string {
        $baseDir = rtrim(sys_get_temp_dir(), '/\\');
        if ($baseDir === '') {
            throw new RuntimeException('Repertoire temporaire introuvable.');
        }

        $prefix = preg_replace('/[^a-zA-Z0-9._-]/', '_', $prefix) ?: 'fulgurite_secret_';
        $path = '';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $baseDir . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8)) . '.tmp';
            FilesystemScopeGuard::assertPathCreatable($candidate, 'write');
            if (!file_exists($candidate) && !is_link($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === '') {
            throw new RuntimeException('Impossible de reserver un fichier temporaire de secret.');
        }

        if (@file_put_contents($path, $secret, LOCK_EX) === false) {
            if (is_file($path)) {
                FileSystem::deleteFile($path);
            }
            throw new RuntimeException('Impossible d ecrire le fichier temporaire de secret.');
        }
        @chmod($path, 0600);
        return $path;
    }

    public static function deleteTempSecretFile(?string $path): void {
        if (is_string($path) && $path !== '' && is_file($path)) {
            FileSystem::deleteFile($path);
        }
    }

    private static function getProcessCacheOwner(): string {
        $candidates = [];

        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $userInfo = @posix_getpwuid(posix_geteuid());
            if (is_array($userInfo) && !empty($userInfo['name'])) {
                $candidates[] = (string) $userInfo['name'];
            }
        }

        $candidates[] = (string) ($_SERVER['USER'] ?? '');
        $candidates[] = (string) ($_SERVER['LOGNAME'] ?? '');
        $candidates[] = (string) getenv('USER');
        $candidates[] = (string) getenv('LOGNAME');

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return preg_replace('/[^a-zA-Z0-9._-]/', '_', $candidate) ?: 'unknown';
            }
        }

        return PHP_SAPI === 'cli' ? 'cli' : 'web';
    }

    public static function getRuntimeCacheRootForCurrentProcess(): string {
        $baseDir = rtrim(sys_get_temp_dir(), '/\\') . self::RUNTIME_CACHE_DIR;
        self::ensureDirectory($baseDir, 0777);
        @chmod($baseDir, 01777);

        return self::ensureDirectory($baseDir . '/' . self::getProcessCacheOwner(), 0700);
    }

    private function getCacheRoot(): string {
        $dir = dirname(DB_PATH) . self::CACHE_DIR;
        return self::ensureDirectory($dir, 0700);
    }

    private function getRuntimeCacheRoot(): string {
        return self::getRuntimeCacheRootForCurrentProcess();
    }

    private function getResticEnv(array $extra = []): array {
        $this->initSftpTmpHome();
        $home = $this->sftpTmpHome ?? '/tmp';
        return array_merge([
            'RESTIC_PASSWORD'  => $this->password,
            'RESTIC_CACHE_DIR' => $this->getRuntimeCacheRoot(),
            'HOME'             => $home,
            'XDG_CACHE_HOME'   => '/tmp',
            'PATH'             => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ], $extra);
    }

    private function getRepoCacheKey(): string {
        return substr(sha1($this->repo), 0, 16);
    }

    private function getCachePath(string $bucket, array $parts = []): string {
        $key = sha1(json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $this->getCacheRoot() . '/' . $bucket . '-' . $this->getRepoCacheKey() . '-' . $key . '.json';
    }

    private static function getSlowLogPath(): string {
        $dir = dirname(DB_PATH) . '/logs';
        return self::ensureDirectory($dir, 0700) . '/restic-slow.log';
    }

    private static function normalizeCommandPart(mixed $part): string {
        if ($part === null) {
            return '';
        }
        $part = (string) $part;
        return preg_match('/\s/', $part) ? '"' . addcslashes($part, "\"\\") . '"' : $part;
    }

    private static function commandToString(array $cmd): string {
        return implode(' ', array_map([self::class, 'normalizeCommandPart'], $cmd));
    }

    private static function resolveProcessExitCode(int $procCloseCode, ?array $lastStatus): int {
        if ($procCloseCode >= 0) {
            return $procCloseCode;
        }

        if (is_array($lastStatus) && array_key_exists('exitcode', $lastStatus)) {
            $exitCode = (int) $lastStatus['exitcode'];
            if ($exitCode >= 0) {
                return $exitCode;
            }
        }

        return $procCloseCode;
    }

    private function buildCommand(array $args, bool $json = false, bool $noCache = false): array {
        $cmd = [RESTIC_BIN, '-r', $this->repo];
        foreach ($this->getBackendOptions() as $option) {
            $cmd[] = $option[0];
            $cmd[] = $option[1];
        }
        if ($json) {
            $cmd[] = '--json';
        }
        if ($noCache) {
            $cmd[] = '--no-cache';
        }
        return array_merge($cmd, $args);
    }

    private function getBackendOptions(): array {
        return [];
    }

    private function parseSftpRepository(): ?array {
        $repo = trim($this->repo);
        if ($repo === '') {
            return null;
        }

        if (preg_match('#^sftp://(?:[^@/]+@)?(\[[^\]]+\]|[^:/]+)(?::(\d+))?:?/.+$#', $repo, $matches)) {
            $host = trim((string) ($matches[1] ?? ''), '[]');
            $port = (int) ($matches[2] ?? 22);
            return $host !== '' ? ['host' => $host, 'port' => $port > 0 ? $port : 22] : null;
        }

        if (preg_match('#^sftp:(?!//)(?:[^@/]+@)?([^:]+):.+$#', $repo, $matches)) {
            $host = trim((string) ($matches[1] ?? ''));
            return $host !== '' ? ['host' => $host, 'port' => 22] : null;
        }

        return null;
    }

    private static function logSlowCommand(array $cmd, float $startedAt, int $returnCode): void {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        if ($durationMs < self::slowCommandThresholdMs()) {
            return;
        }

        $line = sprintf(
            "[%s] %dms exit=%d %s\n",
            date('Y-m-d H:i:s'),
            $durationMs,
            $returnCode,
            self::commandToString($cmd)
        );
        @file_put_contents(self::getSlowLogPath(), $line, FILE_APPEND | LOCK_EX);
    }

    private static function snapshotsCacheTtl(): int {
        return AppConfig::resticSnapshotsCacheTtl();
    }

    private static function lsCacheTtl(): int {
        return AppConfig::resticLsCacheTtl();
    }

    private static function statsCacheTtl(): int {
        return AppConfig::resticStatsCacheTtl();
    }

    private static function searchCacheTtl(): int {
        return AppConfig::resticSearchCacheTtl();
    }

    private static function treeCacheTtl(): int {
        return AppConfig::resticTreeCacheTtl();
    }

    private static function slowCommandThresholdMs(): int {
        return AppConfig::performanceSlowCommandThresholdMs();
    }

    private function formatResticError(string $output): string {
        if (!str_contains(strtolower($output), 'permission denied')) {
            return $output;
        }

        $hint = "Erreur de permissions detectee. Verifiez que l'utilisateur du serveur web peut lire le depot {$this->repo}"
              . " et ecrire dans le cache runtime " . $this->getRuntimeCacheRoot() . '.';

        if (str_contains($output, $this->repo . '/')) {
            $hint .= " Les nouveaux fichiers du depot semblent avoir ete crees avec un groupe ou des ACL incompatibles.";
        }

        if (str_contains($output, '/tmp/restic-cache')) {
            $hint .= " Nettoyez l'ancien cache /tmp/restic-cache si des fichiers residuels s'y trouvent encore.";
        }

        return trim($output . "\n" . $hint);
    }

    private function isCachePermissionError(string $output): bool {
        $outputLower = strtolower($output);
        if (!str_contains($outputLower, 'permission denied')) {
            return false;
        }

        return str_contains($outputLower, 'unable to open cache')
            || str_contains($outputLower, 'cachedir.tag')
            || str_contains($outputLower, 'fulgurite-runtime')
            || str_contains($outputLower, '/tmp/restic-cache');
    }

    private function readCache(string $bucket, array $parts, int $ttl): ?array {
        $path = $this->getCachePath($bucket, $parts);
        if (!is_file($path)) {
            return null;
        }

        $modifiedAt = @filemtime($path);
        if ($modifiedAt === false || (time() - $modifiedAt) > $ttl) {
            @unlink($path);
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $payload = json_decode($content, true);
        if (!is_array($payload) || !array_key_exists('value', $payload) || !is_array($payload['value'])) {
            @unlink($path);
            return null;
        }

        if (isset($payload['value']['error'])) {
            @unlink($path);
            return null;
        }

        return $payload['value'];
    }

    private function writeCache(string $bucket, array $parts, array $value): void {
        $path = $this->getCachePath($bucket, $parts);
        $json = json_encode(['value' => $value], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        $tmpPath = $path . '.tmp';
        if (@file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            @unlink($tmpPath);
            return;
        }

        @rename($tmpPath, $path);
    }

    private function remember(string $bucket, array $parts, int $ttl, callable $callback): array {
        $cached = $this->readCache($bucket, $parts, $ttl);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        if (is_array($value) && !isset($value['error'])) {
            $this->writeCache($bucket, $parts, $value);
        }

        return $value;
    }

    public function clearCache(?string $bucket = null): void {
        $pattern = ($bucket ?? '*') . '-' . $this->getRepoCacheKey() . '-*.json';
        foreach (glob($this->getCacheRoot() . '/' . $pattern) ?: [] as $path) {
            @unlink($path);
        }
    }

    // ── Runner principal ──────────────────────────────────────────────────────
    private function run(array $args, bool $json = false, ?string $cwd = null, bool $allowNoCacheFallback = false, bool $forceNoCache = false): array {
        $cmd = $this->buildCommand($args, $json, $forceNoCache);
        $startedAt = microtime(true);
        $result = ProcessRunner::run($cmd, [
            'cwd' => $cwd,
            'env' => $this->getResticEnv(),
        ]);
        $returnCode = (int) ($result['code'] ?? 1);
        self::logSlowCommand($cmd, $startedAt, $returnCode);
        RequestProfiler::recordRestic($cmd, microtime(true) - $startedAt, $returnCode);
        $output = $this->formatResticError((string) ($result['output'] ?? ''));

        $result = [
            'success' => $returnCode === 0,
            'output'  => $output,
            'lines'   => array_values(array_filter(explode("\n", $output))),
            'code'    => $returnCode,
        ];

        if (
            !$result['success']
            && !$forceNoCache
            && $allowNoCacheFallback
            && $this->isCachePermissionError($output)
        ) {
            return $this->run($args, $json, $cwd, false, true);
        }

        return $this->finalizeSftpResult($result, 'restic');
    }

    private function runWithTimeout(array $args, bool $json = false, ?string $cwd = null, int $timeoutSeconds = 8, bool $allowNoCacheFallback = false, bool $forceNoCache = false): array {
        $cmd = $this->buildCommand($args, $json, $forceNoCache);
        $startedAt = microtime(true);
        $result = ProcessRunner::run($cmd, [
            'cwd' => $cwd,
            'env' => $this->getResticEnv(),
            'timeout' => max(1, $timeoutSeconds),
        ]);
        $timedOut = !empty($result['timed_out']);
        $returnCode = (int) ($result['code'] ?? 1);

        self::logSlowCommand($cmd, $startedAt, $returnCode);
        RequestProfiler::recordRestic($cmd, microtime(true) - $startedAt, $returnCode);
        $output = $this->formatResticError((string) ($result['output'] ?? ''));
        if ($timedOut && !str_contains($output, "Timeout apres {$timeoutSeconds}s")) {
            $output = trim($output . "\nTimeout apres {$timeoutSeconds}s");
        }

        $result = [
            'success' => !$timedOut && $returnCode === 0,
            'output' => $output,
            'lines' => array_values(array_filter(explode("\n", $output))),
            'code' => $returnCode,
            'timed_out' => $timedOut,
        ];

        if (
            !$result['success']
            && !$timedOut
            && !$forceNoCache
            && $allowNoCacheFallback
            && $this->isCachePermissionError($output)
        ) {
            return $this->runWithTimeout($args, $json, $cwd, $timeoutSeconds, false, true);
        }

        return $this->finalizeSftpResult($result, 'restic');
    }

    private function finalizeSftpResult(array $result, string $context): array {
        $sftp = $this->parseSftpRepository();
        if ($sftp === null) {
            return $result;
        }

        return SshKnownHosts::finalizeSshResult($result, (string) $sftp['host'], (int) $sftp['port'], $context);
    }

    // ── Runner shell generique ────────────────────────────────────────────────
    public static function runShell(array $cmd, array $env = []): array {
        $env = array_merge([
            'HOME' => '/tmp',
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ], $env);
        $binary = (string) ($cmd[0] ?? '');
        if ($binary === '') {
            return ['success' => false, 'output' => 'Commande vide', 'code' => 1];
        }
        if (!self::canLaunchCommand($binary, (string) ($env['PATH'] ?? ''))) {
            return ['success' => false, 'output' => 'Commande introuvable ou non executable: ' . $binary, 'code' => 127];
        }
        $startedAt = microtime(true);
        $result = ProcessRunner::run($cmd, ['env' => $env]);
        $returnCode = (int) ($result['code'] ?? 1);
        self::logSlowCommand($cmd, $startedAt, $returnCode);
        RequestProfiler::recordRestic($cmd, microtime(true) - $startedAt, $returnCode);

        return [
            'success' => $returnCode === 0,
            'output'  => (string) ($result['output'] ?? ''),
            'code'    => $returnCode,
        ];
    }

    // ── Snapshots ─────────────────────────────────────────────────────────────
    public function snapshots(): array {
        return $this->remember('snapshots', [], self::snapshotsCacheTtl(), function(): array {
            $result = $this->run(['snapshots', '--no-lock'], true, null, true);
            if (!$result['success']) return ['error' => $result['output']];
            return json_decode($result['output'], true) ?? [];
        });
    }

    public function cachedSnapshots(bool $allowExpired = false): ?array {
        return $this->readCache('snapshots', [], $allowExpired ? PHP_INT_MAX : self::snapshotsCacheTtl());
    }

    // ── Lister the files of a snapshot ────────────────────────────────────
    public function ls(string $snapshot, string $path = '/'): array {
        return $this->remember('ls', [$snapshot, $path], self::lsCacheTtl(), function() use ($snapshot, $path): array {
            $result = $this->run(['ls', '--no-lock', $snapshot, $path], true, null, true);
            if (!$result['success']) return ['error' => $result['output']];

            $items      = [];
            $normalPath = rtrim($path, '/');
            foreach ($result['lines'] as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $data = json_decode($line, true);
                if (!$data) continue;
                if (rtrim($data['path'] ?? '', '/') === $normalPath) continue;
                $items[] = $data;
            }
            return $items;
        });
    }

    private function snapshotTree(string $snapshot): array {
        return $this->remember('tree', [$snapshot], self::treeCacheTtl(), function() use ($snapshot): array {
            $result = $this->run(['ls', '--no-lock', '--recursive', $snapshot], true, null, true);
            if (!$result['success']) {
                return ['error' => $result['output']];
            }

            $items = [];
            foreach ($result['lines'] as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $data = json_decode($line, true);
                if (!$data || !isset($data['path'])) {
                    continue;
                }
                $items[] = $data;
            }

            return $items;
        });
    }

    public function lsWithTimeout(string $snapshot, string $path = '/', int $timeoutSeconds = 8): array {
        $result = $this->runWithTimeout(['ls', '--no-lock', $snapshot, $path], true, null, $timeoutSeconds, true);
        if (!$result['success']) {
            return [
                'error' => $result['output'],
                'timed_out' => (bool) ($result['timed_out'] ?? false),
            ];
        }

        $items = [];
        $normalPath = rtrim($path, '/');
        foreach ($result['lines'] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);
            if (!$data) {
                continue;
            }

            if (rtrim($data['path'] ?? '', '/') === $normalPath) {
                continue;
            }

            $items[] = $data;
        }

        if (!empty($items)) {
            $this->writeCache('ls', [$snapshot, $path], $items);
        }

        return $items;
    }

    public function cachedLs(string $snapshot, string $path = '/', bool $allowExpired = false): ?array {
        return $this->readCache('ls', [$snapshot, $path], $allowExpired ? PHP_INT_MAX : self::lsCacheTtl());
    }

    public function tree(string $snapshot): array {
        return $this->snapshotTree($snapshot);
    }

    // ── Dump ──────────────────────────────────────────────────────────────────
    public function dump(string $snapshot, string $filepath): array {
        $result = $this->run(['dump', '--no-lock', $snapshot, $filepath]);
        return ['success' => $result['success'], 'content' => $result['output'], 'error' => $result['success'] ? null : $result['output']];
    }

    public function dumpPreview(string $snapshot, string $filepath, int $timeoutSeconds = 8): array {
        $result = $this->runWithTimeout(['dump', '--no-lock', $snapshot, $filepath], false, null, $timeoutSeconds, true);
        return [
            'success' => $result['success'],
            'content' => $result['success'] ? $result['output'] : '',
            'error' => $result['success'] ? null : $result['output'],
            'timed_out' => !empty($result['timed_out']),
        ];
    }

    private static function canLaunchCommand(string $binary, string $pathEnv): bool {
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return is_file($binary) && is_executable($binary);
        }

        $paths = array_filter(explode(PATH_SEPARATOR, $pathEnv));
        $extensions = [''];
        if (PHP_OS_FAMILY === 'Windows') {
            $extensions = array_merge([''], array_filter(explode(';', getenv('PATHEXT') ?: '.EXE;.BAT;.CMD;.COM')));
        }

        foreach ($paths as $path) {
            foreach ($extensions as $extension) {
                $candidate = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $binary . $extension;
                if (is_file($candidate) && is_executable($candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function dumpRaw(string $snapshot, string $filepath): array {
        $cmd = [RESTIC_BIN, '-r', $this->repo, 'dump', '--no-lock', $snapshot, $filepath];
        $startedAt   = microtime(true);
        $result = ProcessRunner::run($cmd, ['env' => $this->getResticEnv()]);
        $code = (int) ($result['code'] ?? 1);
        self::logSlowCommand($cmd, $startedAt, $code);
        RequestProfiler::recordRestic($cmd, microtime(true) - $startedAt, $code);
        return [
            'success' => $code === 0,
            'data' => $code === 0 ? (string) ($result['stdout'] ?? '') : null,
            'error' => $code !== 0 ? $this->formatResticError((string) ($result['output'] ?? '')) : null,
        ];
    }

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function stats(?string $snapshot = null): array {
        $cacheParts = $snapshot !== null && trim($snapshot) !== '' ? [$snapshot] : [];
        return $this->remember('stats', $cacheParts, self::statsCacheTtl(), function() use ($snapshot): array {
            $args = ['stats', '--no-lock'];
            if ($snapshot !== null && trim($snapshot) !== '') {
                $args[] = $snapshot;
            }
            $result = $this->run($args, true, null, true);
            if (!$result['success']) return ['error' => $result['output']];
            return json_decode($result['output'], true) ?? [];
        });
    }

    // ── Tags ──────────────────────────────────────────────────────────────────
    // Ajouter of tags a a snapshot
    public function addTags(string $snapshotId, array $tags): array {
        $args = ['tag', '--add'];
        foreach ($tags as $tag) { $args[] = $tag; }
        $args[] = $snapshotId;
        $result = $this->run($args);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // Supprimer of tags of a snapshot
    public function removeTags(string $snapshotId, array $tags): array {
        $args = ['tag', '--remove'];
        foreach ($tags as $tag) { $args[] = $tag; }
        $args[] = $snapshotId;
        $result = $this->run($args);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // Replace all snapshot tags
    public function setTags(string $snapshotId, array $tags): array {
        $args = ['tag', '--set'];
        foreach ($tags as $tag) { $args[] = $tag; }
        $args[] = $snapshotId;
        $result = $this->run($args);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── Retention policy ────────────────────────────────────────────────
    public function forget(
        int $keepLast    = 0,
        int $keepDaily   = 0,
        int $keepWeekly  = 0,
        int $keepMonthly = 0,
        int $keepYearly  = 0,
        bool $prune      = true,
        bool $dryRun     = false
    ): array {
        $args = ['forget'];
        if ($keepLast    > 0) { $args[] = '--keep-last';    $args[] = (string)$keepLast; }
        if ($keepDaily   > 0) { $args[] = '--keep-daily';   $args[] = (string)$keepDaily; }
        if ($keepWeekly  > 0) { $args[] = '--keep-weekly';  $args[] = (string)$keepWeekly; }
        if ($keepMonthly > 0) { $args[] = '--keep-monthly'; $args[] = (string)$keepMonthly; }
        if ($keepYearly  > 0) { $args[] = '--keep-yearly';  $args[] = (string)$keepYearly; }
        if ($prune)    $args[] = '--prune';
        if ($dryRun)   $args[] = '--dry-run';

        $result = $this->run($args);
        if ($result['success'] && !$dryRun) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── Copier to a autre repo ─────────────────────────────────────────────
    public function copyTo(string $destRepo, string $destPassword, ?string $snapshotId = null): array {

	// Write passwords into temporary files
        $passFile = null;
        $fromPassFile = null;
        try {
            $passFile = self::writeTempSecretFile($destPassword, 'restic_pass_');
            $fromPassFile = self::writeTempSecretFile($this->password, 'restic_from_pass_');

        $cmd = [
	    RESTIC_BIN,
	    '-r', $destRepo,
	    '--password-file', $passFile,
	    'copy',
	    '--from-password-file', $fromPassFile,
	    '--from-repo', $this->repo
	];
        if ($snapshotId) $cmd[] = $snapshotId;

        $env = $this->getResticEnv([
            'RESTIC_PASSWORD'      => $destPassword,
            'RESTIC_PASSWORD_FROM' => $this->password,
	    'RCLONE_CONFIG'       => '/var/www/.config/rclone/rclone.conf',
        ]);

        $startedAt   = microtime(true);
        $result = ProcessRunner::run($cmd, ['env' => $env]);
        $returnCode = (int) ($result['code'] ?? 1);
        self::logSlowCommand($cmd, $startedAt, $returnCode);
        RequestProfiler::recordRestic($cmd, microtime(true) - $startedAt, $returnCode);

            return [
              'success' => $returnCode === 0,
              'output'  => $this->formatResticError((string) ($result['output'] ?? '')),
              'code'    => $returnCode,
            ];
        } finally {
            self::deleteTempSecretFile($passFile);
            self::deleteTempSecretFile($fromPassFile);
        }
    }

    // ── Supprimer of snapshots ───────────────────────────────────────────────
    public function deleteSnapshot(array $snapshotIds): array {
        if (empty($snapshotIds)) return ['success' => false, 'output' => 'Aucun snapshot spécifié'];
        $result = $this->run(array_merge(['forget', '--prune'], $snapshotIds));
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── Diff ──────────────────────────────────────────────────────────────────
    public function diff(string $snapshotA, string $snapshotB): array {
        $result = $this->run(['diff', '--no-lock', $snapshotA, $snapshotB]);
        if (!$result['success'] && empty($result['output'])) return ['error' => $result['output']];

        $added = $removed = $changed = [];
        foreach ($result['lines'] as $line) {
            if (str_starts_with($line, '+'))      $added[]   = substr($line, 2);
            elseif (str_starts_with($line, '-'))  $removed[] = substr($line, 2);
            elseif (str_starts_with($line, 'M'))  $changed[] = substr($line, 2);
        }

        return [
            'raw'     => $result['output'],
            'added'   => $added,
            'removed' => $removed,
            'changed' => $changed,
            'summary' => ['added' => count($added), 'removed' => count($removed), 'changed' => count($changed)],
        ];
    }

    // ── Recherche ─────────────────────────────────────────────────────────────
    public function search(string $snapshot, string $query): array {
        return $this->remember('search', [$snapshot, $query], self::searchCacheTtl(), function() use ($snapshot, $query): array {
            $tree = $this->snapshotTree($snapshot);
            if (isset($tree['error'])) return ['error' => $tree['error']];

            $matches    = [];
            $queryLower = strtolower($query);
            foreach ($tree as $data) {
                if (str_contains(strtolower(basename($data['path'])), $queryLower)) {
                    $matches[] = $data;
                }
                if (count($matches) >= AppConfig::exploreSearchMaxResults()) break;
            }
            return $matches;
        });
    }

    // ── Check ─────────────────────────────────────────────────────────────────
    public function check(): array {
        $result = $this->run(['check']);
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    public function init(): array {
        $result = $this->run(['init']);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── backup locale ─────────────────────────────────────────────────────
    public function backup(array $paths, array $tags = [], array $excludes = [], string $hostname = ''): array {
        if (empty($paths)) {
            return ['success' => false, 'output' => 'Aucun chemin source spécifié', 'code' => 1];
        }
        $args = ['backup'];
        if ($hostname) { $args[] = '--hostname'; $args[] = $hostname; }
        foreach ($tags     as $tag)  { $args[] = '--tag';     $args[] = $tag; }
        foreach ($excludes as $pat)  { $args[] = '--exclude'; $args[] = $pat; }
        foreach ($paths    as $path) { $args[] = $path; }
        $result = $this->run($args);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output'], 'code' => (int) ($result['code'] ?? ($result['success'] ? 0 : 1))];
    }

    // ── Remote backup via SSH ───────────────────────────────────────────
    // restic runs on the host distant; the repository must be accessible from    // this host (ex: sftp:user@backup-server:/backups/repo or REST/S3).    // Sudoers distant requis (if sudo) : user ALL=(root) NOPASSWD: /usr/bin/restic
    public function backupRemote(
        string $sshUser,
        string $sshHost,
        int    $sshPort,
        string $sshKeyFile,
        string $remoteRepoPath,
        array  $paths,
        array  $tags         = [],
        array  $excludes     = [],
        string $sudoPassword = '',
        string $hostname     = ''
    ): array {
        if (empty($paths)) {
            return ['success' => false, 'output' => 'Aucun chemin source spécifié', 'code' => 1];
        }

        // Write the password to a temporary file on the remote host
        // (compatible with all restic versions, avoids --password-command issues)
        $resticCmd = 'restic -r ' . escapeshellarg($remoteRepoPath)
                   . ' --password-file "$_RPASS"'
                   . ' --cache-dir /tmp/restic-cache backup';
        if ($hostname) { $resticCmd .= ' --hostname ' . escapeshellarg($hostname); }
        foreach ($tags     as $tag) { $resticCmd .= ' --tag '     . escapeshellarg($tag); }
        foreach ($excludes as $pat) { $resticCmd .= ' --exclude ' . escapeshellarg($pat); }
        foreach ($paths    as $p)   { $resticCmd .= ' '           . escapeshellarg($p); }

        $runRestic = $sudoPassword
            ? 'echo ' . escapeshellarg($sudoPassword) . ' | sudo -S ' . $resticCmd
            : $resticCmd;

        $remoteCmd = '_RPASS=$(mktemp)'
                   . ' && printf %s ' . escapeshellarg(trim($this->password)) . ' > "$_RPASS"'
                   . ' && (' . $runRestic . ')'
                   . '; _RC=$?; rm -f "$_RPASS"; exit $_RC';

        $tmpHome = '/tmp/fulgurite-bkp-remote-' . uniqid();
        mkdir($tmpHome . '/.ssh', 0700, true);

        $result = self::runShell(array_merge([
            'ssh',
            '-i', $sshKeyFile,
            '-p', (string) $sshPort,
        ], SshKnownHosts::sshOptions($sshHost, $sshPort, 10), [
            $sshUser . '@' . $sshHost,
            $remoteCmd,
        ]), ['HOME' => $tmpHome]);

        FileSystem::removeDirectory($tmpHome);
        if ($result['success']) {
            $this->clearCache();
        }
        return ['success' => $result['success'], 'output' => $result['output'], 'code' => (int) ($result['code'] ?? ($result['success'] ? 0 : 1))];
    }

    // ── Restore local ─────────────────────────────────────────────────────────
    public function restore(string $snapshot, string $target, string $include = ''): array {
        $args = ['restore', $snapshot, '--target', $target];
        if (!empty($include)) { $args[] = '--include'; $args[] = $include; }
        $result = $this->run($args);
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    public function restoreIncludes(string $snapshot, string $target, array $includes): array {
        $args = ['restore', $snapshot, '--target', $target];
        foreach (array_values(array_unique(array_filter(array_map('strval', $includes)))) as $include) {
            $args[] = '--include';
            $args[] = $include;
        }

        $result = $this->run($args);
        return ['success' => $result['success'], 'output' => $result['output']];
    }

    // ── Restore distant ───────────────────────────────────────────────────────
    public function restoreRemote(
        string $snapshot, string $remoteUser, string $remoteHost,
        int $remotePort, string $remotePath, string $sshKeyFile, string $include = ''
    ): array {
        $log    = [];
        $tmpDir = '/tmp/restic-restore-' . uniqid();
        $success = true;

        // Check that rsync is available (required for remote restores)
        if (!self::canLaunchCommand('rsync', getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin')) {
            return ['success' => false, 'output' => 'Erreur: rsync introuvable ou non executable. Ce binaire est requis pour les restaurations distantes. Verifiez l installation du serveur.'];
        }

        try {
            $log[] = "→ Extraction dans $tmpDir...";
            $args  = ['restore', $snapshot, '--target', $tmpDir];
            if (!empty($include)) { $args[] = '--include'; $args[] = $include; }
            $r = $this->run($args);
            if (!$r['success']) throw new RuntimeException("Extraction échouée:\n" . $r['output']);
            $log[] = "✓ Extraction terminée";

            $rsyncSrc = $tmpDir . '/';
            if (!empty($include)) {
                $c = $tmpDir . $include;
                if (is_dir($c)) $rsyncSrc = $c . '/';
            }

            $log[] = "→ Transfert rsync vers $remoteUser@$remoteHost:$remotePath...";
            $r = self::runShell([
                RSYNC_BIN, '-az',
                '-e', 'ssh -i ' . escapeshellarg($sshKeyFile)
                    . ' -p ' . (string) $remotePort . ' '
                    . SshKnownHosts::sshOptionsString($remoteHost, $remotePort, 10),
                $rsyncSrc, "$remoteUser@$remoteHost:$remotePath",
            ]);
            if (!$r['success']) throw new RuntimeException("rsync échoué:\n" . $r['output']);
            $log[] = "✓ Transfert terminé";

        } catch (RuntimeException $e) {
            $success = false;
            $log[]   = "✗ " . $e->getMessage();
        } finally {
            if (is_dir($tmpDir)) { FileSystem::removeDirectory($tmpDir); $log[] = "→ Nettoyé"; }
        }

        return ['success' => $success, 'output' => implode("\n", $log)];
    }

    // ── Test SSH ──────────────────────────────────────────────────────────────
    public static function testSshConnection(string $user, string $host, int $port, string $keyFile): array {
        $tmpHome = '/tmp/fulgurite-test-' . uniqid();
        mkdir($tmpHome . '/.ssh', 0700, true);
        $result = self::runShell(array_merge([
            'ssh', '-i', $keyFile, '-p', (string)$port,
        ], SshKnownHosts::sshOptions($host, $port, 5), [
            "$user@$host", 'echo OK',
        ]), ['HOME' => $tmpHome]);
        FileSystem::removeDirectory($tmpHome);
        return SshKnownHosts::finalizeSshResult($result, $host, $port, 'ssh_test');
    }

    // ── Ping ──────────────────────────────────────────────────────────────────
    public function ping(): bool {
        return $this->run(['snapshots', '--no-lock', '--last'])['success'];
    }
}
