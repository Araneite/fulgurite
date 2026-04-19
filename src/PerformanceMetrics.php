<?php

class PerformanceMetrics {
    public static function collectLightweight(): array {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpuCount = max(1, self::readCpuCount());
        $meminfo = self::readProcMeminfo();
        $memTotal = (int) ($meminfo['MemTotal'] ?? 0);
        $memAvailable = (int) ($meminfo['MemAvailable'] ?? ($meminfo['MemFree'] ?? 0));
        $memUsed = max(0, $memTotal - $memAvailable);

        $reposDisk = [];
        if (is_dir(REPOS_BASE_PATH)) {
            $diskTotal = @disk_total_space(REPOS_BASE_PATH);
            $diskFree = @disk_free_space(REPOS_BASE_PATH);
            if ($diskTotal !== false && $diskFree !== false) {
                $reposDisk = [
                    'total' => (int) $diskTotal,
                    'free' => (int) $diskFree,
                    'used' => (int) ($diskTotal - $diskFree),
                    'pct' => $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0,
                ];
            }
        }

        return [
            'refreshed_at' => date(DATE_ATOM),
            'load' => [
                'avg1' => (float) ($load[0] ?? 0),
                'avg5' => (float) ($load[1] ?? 0),
                'avg15' => (float) ($load[2] ?? 0),
                'cpu_count' => $cpuCount,
                'avg1_pct' => $cpuCount > 0 ? round(((float) ($load[0] ?? 0) / $cpuCount) * 100, 1) : 0,
            ],
            'memory' => [
                'total' => $memTotal,
                'available' => $memAvailable,
                'used' => $memUsed,
                'pct' => $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0,
            ],
            'io_pressure' => self::readPressureMetric('/proc/pressure/io'),
            'php_fpm' => self::getPhpFpmMetrics(),
            'repos_disk' => $reposDisk,
        ];
    }

    public static function collectRepoSizeMetrics(array $repos, int $scanLimit = 8, int $topLimit = 5): array {
        $sizes = [];
        foreach (array_slice($repos, 0, max(0, $scanLimit)) as $repo) {
            $path = (string) ($repo['path'] ?? '');
            if ($path === '' || !is_dir($path)) {
                continue;
            }

            $size = self::getDirectorySizeBytes($path);
            if ($size === null) {
                continue;
            }

            $sizes[] = [
                'name' => (string) ($repo['name'] ?? basename($path)),
                'path' => $path,
                'size' => $size,
            ];
        }

        usort($sizes, static fn(array $a, array $b) => $b['size'] <=> $a['size']);
        return array_slice($sizes, 0, max(0, $topLimit));
    }

    private static function readCpuCount(): int {
        $value = trim((string) self::command(['getconf', '_NPROCESSORS_ONLN']));
        return ctype_digit($value) ? (int) $value : 1;
    }

    private static function readProcMeminfo(): array {
        $path = '/proc/meminfo';
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            if (preg_match('/(\d+)/', $value, $matches)) {
                $values[trim($key)] = (int) $matches[1] * 1024;
            }
        }

        return $values;
    }

    private static function readPressureMetric(string $path): ?array {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $line = trim((string) ($lines[0] ?? ''));
        if ($line === '') {
            return null;
        }

        $metrics = [];
        foreach (preg_split('/\s+/', $line) as $chunk) {
            if (!str_contains($chunk, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $chunk, 2);
            $metrics[$key] = is_numeric($value) ? (float) $value : $value;
        }

        return $metrics;
    }

    private static function getPhpFpmMetrics(): array {
        $output = trim((string) self::command(['ps', '-C', 'php-fpm', '-o', 'rss=']));
        if ($output === '') {
            return ['count' => 0, 'rss' => 0];
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $output))));
        $rssBytes = 0;
        foreach ($lines as $line) {
            if (preg_match('/^\d+$/', $line)) {
                $rssBytes += ((int) $line) * 1024;
            }
        }

        return [
            'count' => count($lines),
            'rss' => $rssBytes,
        ];
    }

    private static function getDirectorySizeBytes(string $path): ?int {
        $output = trim((string) self::command(['du', '-sb', $path]));
        if ($output === '' || !preg_match('/^(\d+)/', $output, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private static function command(array $command): string {
        $result = ProcessRunner::run($command, ['capture_stderr' => false]);
        if (!$result['success']) {
            return '';
        }

        return (string) ($result['stdout'] ?? '');
    }
}
