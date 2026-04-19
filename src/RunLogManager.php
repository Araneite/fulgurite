<?php

class RunLogManager {
    private const TYPE_PREFIXES = [
        'backup' => 'fulgurite_backup_',
        'copy' => 'fulgurite_copy_',
        'cron' => 'fulgurite_cron_',
        'quick_backup' => 'fulgurite_quick_backup_',
        'scheduler_task' => 'fulgurite_scheduler_task_',
        'setup' => 'fulgurite_setup_',
    ];

    private const REQUIRED_PERMISSIONS = [
        'backup' => 'backup_jobs.manage',
        'copy' => 'copy_jobs.manage',
        'cron' => 'scheduler.manage',
        'quick_backup' => 'backup_jobs.manage',
        'scheduler_task' => 'scheduler.manage',
        'setup' => 'hosts.manage',
    ];

    private const DEFAULT_EXPIRY_SECONDS = 86400;
    private const POLL_MAX_BYTES = 262144;
    private const POLL_MAX_LINES = 600;

    public static function createRun(string $type, ?int $userId = null, ?string $permission = null, int $ttlSeconds = self::DEFAULT_EXPIRY_SECONDS): array {
        self::assertKnownType($type);

        $runId = bin2hex(random_bytes(16));
        $permission = $permission ?: self::requiredPermissionForType($type);
        $userId = $userId ?? (int) (Auth::currentUser()['id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Utilisateur courant introuvable pour le run de log');
        }

        self::cleanupExpiredRuns();

        $now = gmdate('Y-m-d H:i:s');
        $expiresAt = gmdate('Y-m-d H:i:s', time() + max(60, $ttlSeconds));
        Database::getInstance()->prepare("
            INSERT INTO job_log_runs (run_id, type, user_id, permission_required, created_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$runId, $type, $userId, $permission, $now, $expiresAt]);

        return self::getRunFiles($type, $runId);
    }

    public static function sanitizeRunId(string $runId): string {
        return preg_replace('/[^a-zA-Z0-9_.-]/', '', $runId) ?? '';
    }

    public static function getRunFiles(string $type, string $runId): array {
        self::assertKnownType($type);
        $prefix = self::TYPE_PREFIXES[$type];

        $runId = self::sanitizeRunId($runId);
        if ($runId === '') {
            throw new InvalidArgumentException('run_id vide');
        }

        $basePath = self::getTempDir() . DIRECTORY_SEPARATOR . $prefix . $runId;
        $logFile = $basePath . '.log';

        return [
            'run_id' => $runId,
            'log_file' => $logFile,
            'done_file' => $logFile . '.done',
            'pid_file' => $basePath . '.pid',
            'result_file' => $basePath . '.result.json',
        ];
    }

    public static function requireAccessibleRun(string $type, string $runId): array {
        self::assertKnownType($type);
        Auth::requirePermission(self::requiredPermissionForType($type));

        $runId = self::sanitizeRunId($runId);
        if ($runId === '') {
            jsonResponse(['error' => 'run_id requis'], 400);
        }

        $stmt = Database::getInstance()->prepare("
            SELECT run_id, type, user_id, permission_required, expires_at
            FROM job_log_runs
            WHERE run_id = ?
            LIMIT 1
        ");
        $stmt->execute([$runId]);
        $run = $stmt->fetch();

        if (!$run || (string) $run['type'] !== $type) {
            jsonResponse(['error' => 'run_id introuvable ou expire'], 404);
        }

        if (strcmp((string) $run['expires_at'], gmdate('Y-m-d H:i:s')) < 0) {
            self::deleteRunMetadata($runId);
            jsonResponse(['error' => 'run_id expire'], 410);
        }

        $currentUserId = (int) (Auth::currentUser()['id'] ?? 0);
        if ($currentUserId <= 0 || (int) $run['user_id'] !== $currentUserId) {
            Auth::log('job_log_access_denied', 'Tentative de lecture du run ' . $runId . ' (' . $type . ') appartenant a un autre utilisateur', 'warning');
            jsonResponse(['error' => 'Acces refuse a ce run'], 403);
        }

        $storedPermission = (string) ($run['permission_required'] ?? '');
        if ($storedPermission !== '' && $storedPermission !== self::requiredPermissionForType($type)) {
            Auth::requirePermission($storedPermission);
        }

        return $run;
    }

    public static function deleteRunMetadata(string $runId): void {
        $runId = self::sanitizeRunId($runId);
        if ($runId === '') {
            return;
        }

        Database::getInstance()->prepare("DELETE FROM job_log_runs WHERE run_id = ?")->execute([$runId]);
    }

    public static function requiredPermissionForType(string $type): string {
        self::assertKnownType($type);
        return self::REQUIRED_PERMISSIONS[$type];
    }

    /**
     * Lit incrementalement a file of log from a offset octet.
     *
     * Protocole:
     * - request moderne: last_offset_bytes (or offset_bytes)
     * - request legacy: offset (offset en lignes non vides)
     *
     * response:
     * - lines: lignes normalisees without CRLF and without lignes vides
     * - next_offset_bytes: offset octet a reutiliser
     * - offset: offset legacy en lignes (for compatibility)
     * - has_more / eof / protocol / offset_reset
     */
    public static function readIncrementalLog(string $logFile, array $requestData): array {
        $maxBytes = self::POLL_MAX_BYTES;
        $maxLines = self::POLL_MAX_LINES;

        $legacyOffset = max(0, (int) ($requestData['offset'] ?? 0));
        $rawByteOffset = $requestData['last_offset_bytes'] ?? ($requestData['offset_bytes'] ?? null);
        $usingByteOffset = is_numeric($rawByteOffset);
        $byteOffset = $usingByteOffset ? max(0, (int) $rawByteOffset) : 0;
        $protocol = $usingByteOffset ? 'bytes' : 'lines_legacy';

        $fileSize = @filesize($logFile);
        if (!is_int($fileSize) || $fileSize < 0) {
            return [
                'lines' => [],
                'offset' => $legacyOffset,
                'next_offset_bytes' => 0,
                'offset_bytes' => 0,
                'max_bytes' => $maxBytes,
                'max_lines' => $maxLines,
                'has_more' => false,
                'eof' => true,
                'offset_reset' => false,
                'protocol' => $protocol,
            ];
        }

        $offsetReset = false;
        if ($usingByteOffset) {
            if ($byteOffset > $fileSize) {
                $byteOffset = 0;
                $offsetReset = true;
            }
        } else {
            $byteOffset = self::byteOffsetForLegacyLineOffset($logFile, $legacyOffset);
        }

        $handle = @fopen($logFile, 'rb');
        if (!is_resource($handle)) {
            return [
                'lines' => [],
                'offset' => $legacyOffset,
                'next_offset_bytes' => 0,
                'offset_bytes' => 0,
                'max_bytes' => $maxBytes,
                'max_lines' => $maxLines,
                'has_more' => false,
                'eof' => true,
                'offset_reset' => $offsetReset,
                'protocol' => $protocol,
            ];
        }

        if ($byteOffset > 0 && @fseek($handle, $byteOffset, SEEK_SET) !== 0) {
            $byteOffset = 0;
            $offsetReset = true;
            @rewind($handle);
        }

        $lines = [];
        $bytesRead = 0;
        while (!feof($handle) && $bytesRead < $maxBytes && count($lines) < $maxLines) {
            $line = fgets($handle);
            if ($line === false) {
                break;
            }

            $bytesRead += strlen($line);
            $normalized = rtrim($line, "\r\n");
            if (trim($normalized) !== '') {
                $lines[] = $normalized;
            }
        }

        $nextOffsetBytes = (int) ftell($handle);
        fclose($handle);

        $finalFileSize = @filesize($logFile);
        if (!is_int($finalFileSize) || $finalFileSize < 0) {
            $finalFileSize = $fileSize;
        }

        $hasMore = $nextOffsetBytes < $finalFileSize;
        $nextLegacyOffset = $legacyOffset + count($lines);

        return [
            'lines' => $lines,
            'offset' => $nextLegacyOffset,
            'next_offset_bytes' => $nextOffsetBytes,
            'offset_bytes' => $nextOffsetBytes,
            'max_bytes' => $maxBytes,
            'max_lines' => $maxLines,
            'has_more' => $hasMore,
            'eof' => !$hasMore,
            'offset_reset' => $offsetReset,
            'protocol' => $protocol,
        ];
    }

    private static function assertKnownType(string $type): void {
        if (!isset(self::TYPE_PREFIXES[$type], self::REQUIRED_PERMISSIONS[$type])) {
            throw new InvalidArgumentException('Type de run inconnu: ' . $type);
        }
    }

    private static function byteOffsetForLegacyLineOffset(string $logFile, int $lineOffset): int {
        if ($lineOffset <= 0) {
            return 0;
        }

        $handle = @fopen($logFile, 'rb');
        if (!is_resource($handle)) {
            return 0;
        }

        $count = 0;
        while (!feof($handle) && $count < $lineOffset) {
            $line = fgets($handle);
            if ($line === false) {
                break;
            }
            $normalized = rtrim($line, "\r\n");
            if (trim($normalized) !== '') {
                $count++;
            }
        }

        $offset = (int) ftell($handle);
        fclose($handle);
        return $offset;
    }

    private static function cleanupExpiredRuns(): void {
        if (random_int(1, 10) !== 1) {
            return;
        }

        Database::getInstance()
            ->prepare("DELETE FROM job_log_runs WHERE expires_at < ?")
            ->execute([gmdate('Y-m-d H:i:s')]);
    }

    private static function getTempDir(): string {
        $tempDir = rtrim(sys_get_temp_dir(), '\\/');
        return $tempDir !== '' ? $tempDir : (__DIR__ . '/../tmp');
    }
}
