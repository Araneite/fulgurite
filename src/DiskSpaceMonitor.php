<?php
declare(strict_types=1);

final class DiskSpaceMonitor
{
    public const SEVERITY_OK = 'ok';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_UNKNOWN = 'unknown';
    private const CACHE_TTL_STATUS_SECONDS = 15;
    private const CACHE_TTL_HEAVY_SECONDS = 20;
    private static array $requestMemo = [];

    public static function performCronChecks(): array
    {
        if (!AppConfig::diskMonitoringEnabled()) {
            return ['checks' => [], 'notifications' => []];
        }

        $notifications = [];
        $checks = [];
        $targets = self::gatherMonitoringTargets();
        self::syncRuntimeTargets($targets);

        foreach ($targets as $target) {
            $previous = self::getRuntimeStatus((string) $target['subject_key']);
            $check = self::probeTarget($target);
            self::persistCheck($check);

            $event = self::resolveNotificationEvent($previous, $check);
            if ($event !== null) {
                $notifications[] = ['event' => $event, 'check' => $check];
            }

            $checks[] = $check;
        }

        return ['checks' => $checks, 'notifications' => $notifications];
    }

    public static function runManualChecks(?string $contextType = null, ?int $contextId = null): array
    {
        if (!AppConfig::diskMonitoringEnabled()) {
            return ['checks' => [], 'notifications' => []];
        }

        $targets = self::gatherMonitoringTargets();
        if ($contextType === null) {
            self::syncRuntimeTargets($targets);
        }
        if ($contextType !== null) {
            $targets = array_values(array_filter($targets, static function (array $target) use ($contextType, $contextId): bool {
                if ((string) ($target['context_type'] ?? '') !== $contextType) {
                    return false;
                }
                if ($contextId !== null && (int) ($target['context_id'] ?? 0) !== $contextId) {
                    return false;
                }
                return true;
            }));
        }

        $checks = [];
        foreach ($targets as $target) {
            $check = self::probeTarget($target);
            self::persistCheck($check);
            $checks[] = $check;
        }

        return ['checks' => $checks, 'notifications' => []];
    }

    public static function dispatchNotification(string $event, array $check): void
    {
        self::dispatchGroupedNotifications([['event' => $event, 'check' => $check]]);
    }

    public static function dispatchGroupedNotifications(array $notifications): void
    {
        if ($notifications === []) {
            return;
        }

        $policy = Notifier::getSettingPolicy('disk_space_notification_policy', 'disk_space');
        foreach (self::groupNotificationsByEvent($notifications) as $event => $payload) {
            [$title, $body, $severity, $contextName] = self::buildGroupedNotificationPayload($event, $payload['volumes']);
            $result = Notifier::dispatchPolicy('disk_space', $policy, $event, $title, $body, [
                'context_type' => 'disk_space',
                'context_name' => $contextName,
                'severity' => $severity,
                'link_url' => routePath('/stats.php'),
                'ntfy_priority' => $event === 'critical' ? 'high' : 'default',
            ]);

            if (!empty($result['success'])) {
                self::markNotifiedMany($payload['subject_keys'], $event);
            }
        }
    }

    private static function groupNotificationsByEvent(array $notifications): array
    {
        $grouped = [];
        foreach ($notifications as $notification) {
            $event = (string) ($notification['event'] ?? '');
            $check = (array) ($notification['check'] ?? []);
            $subjectKey = (string) ($check['subject_key'] ?? '');
            if ($event === '' || $subjectKey === '') {
                continue;
            }

            $eventBucket = $grouped[$event] ?? ['volumes' => [], 'subject_keys' => []];
            $volumeKey = self::notificationVolumeKey($check);
            if (!isset($eventBucket['volumes'][$volumeKey])) {
                $eventBucket['volumes'][$volumeKey] = [
                    'scope' => (string) ($check['scope'] ?? 'local'),
                    'host_name' => (string) ($check['host_name'] ?? ''),
                    'host_id' => isset($check['host_id']) ? (int) $check['host_id'] : null,
                    'path' => (string) ($check['path'] ?? ''),
                    'probe_path' => (string) ($check['probe_path'] ?? $check['path'] ?? ''),
                    'free_bytes' => (int) ($check['free_bytes'] ?? 0),
                    'total_bytes' => (int) ($check['total_bytes'] ?? 0),
                    'used_percent' => $check['used_percent'] !== null ? (float) ($check['used_percent'] ?? 0) : null,
                    'targets' => [],
                ];
            }

            $targetName = trim((string) ($check['context_name'] ?? $check['location_label'] ?? $check['path'] ?? 'Stockage'));
            if ($targetName !== '' && !in_array($targetName, $eventBucket['volumes'][$volumeKey]['targets'], true)) {
                $eventBucket['volumes'][$volumeKey]['targets'][] = $targetName;
            }
            $eventBucket['subject_keys'][$subjectKey] = true;
            $grouped[$event] = $eventBucket;
        }

        foreach ($grouped as $event => $payload) {
            $volumes = array_values($payload['volumes']);
            usort($volumes, static function (array $a, array $b): int {
                $aUsed = $a['used_percent'] ?? 0;
                $bUsed = $b['used_percent'] ?? 0;
                if ($aUsed !== $bUsed) {
                    return $bUsed <=> $aUsed;
                }
                return ((int) ($a['free_bytes'] ?? 0)) <=> ((int) ($b['free_bytes'] ?? 0));
            });
            $grouped[$event] = [
                'volumes' => $volumes,
                'subject_keys' => array_keys($payload['subject_keys']),
            ];
        }

        return $grouped;
    }

    private static function notificationVolumeKey(array $check): string
    {
        return implode('|', [
            (string) ($check['scope'] ?? 'local'),
            (string) (($check['host_id'] ?? '') !== null ? (string) ($check['host_id'] ?? '') : ''),
            (string) ($check['probe_path'] ?? $check['path'] ?? ''),
        ]);
    }

    private static function buildGroupedNotificationPayload(string $event, array $volumes): array
    {
        $count = count($volumes);
        $headline = match ($event) {
            'critical' => $count . ' volume' . ($count > 1 ? 's' : '') . ' disque en situation critique',
            'warning' => $count . ' volume' . ($count > 1 ? 's' : '') . ' disque a surveiller',
            default => $count . ' volume' . ($count > 1 ? 's' : '') . ' disque revenu' . ($count > 1 ? 's' : '') . ' a la normale',
        };
        $title = match ($event) {
            'critical' => 'Critique espace disque - ' . $headline,
            'warning' => 'Alerte espace disque - ' . $headline,
            default => 'Espace disque retabli - ' . $headline,
        };

        $lines = [$headline . '.'];
        $maxLines = 5;
        foreach (array_slice($volumes, 0, $maxLines) as $volume) {
            $lines[] = '- ' . self::describeNotificationVolume($volume);
        }
        if ($count > $maxLines) {
            $lines[] = '- +' . ($count - $maxLines) . ' autre(s) volume(s)';
        }
        $lines[] = '';
        $lines[] = 'Voir ' . routePath('/stats.php') . ' pour le detail.';

        return [
            $title,
            implode("\n", $lines),
            $event === 'critical' ? 'critical' : ($event === 'warning' ? 'warning' : 'success'),
            $headline,
        ];
    }

    private static function describeNotificationVolume(array $volume): string
    {
        $path = (string) ($volume['probe_path'] ?: ($volume['path'] ?? ''));
        $hostName = trim((string) ($volume['host_name'] ?? ''));
        $isRemote = (string) ($volume['scope'] ?? 'local') === 'remote';
        $location = $isRemote
            ? (($hostName !== '' ? $hostName : 'Hote distant') . ' [' . $path . ']')
            : ('Local [' . $path . ']');
        $free = formatBytes((int) ($volume['free_bytes'] ?? 0));
        $total = formatBytes((int) ($volume['total_bytes'] ?? 0));
        $usedPercent = $volume['used_percent'] !== null
            ? number_format((float) $volume['used_percent'], 1, '.', '') . '%'
            : '-';

        $targets = array_values(array_filter(array_map('strval', $volume['targets'] ?? [])));
        $targetLabel = '';
        if ($targets !== []) {
            $visibleTargets = array_slice($targets, 0, 3);
            $targetLabel = ' - cibles: ' . implode(', ', $visibleTargets);
            if (count($targets) > count($visibleTargets)) {
                $targetLabel .= ' +' . (count($targets) - count($visibleTargets));
            }
        }

        return $location . ' - libre ' . $free . ' / ' . $total . ' (' . $usedPercent . ' utilises)' . $targetLabel;
    }

    private static function markNotifiedMany(array $subjectKeys, string $event): void
    {
        $subjectKeys = array_values(array_filter(array_map('strval', $subjectKeys)));
        if ($subjectKeys === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($subjectKeys), '?'));
        $params = array_merge([$event], $subjectKeys);
        Database::getInstance()->prepare("
            UPDATE disk_space_runtime_status
            SET last_notified_event = ?, last_notified_at = datetime('now')
            WHERE subject_key IN ($placeholders)
        ")->execute($params);
    }

    public static function getLatestStatuses(bool $includeHealthy = false): array
    {
        return self::rememberArray('latest-statuses', [$includeHealthy], self::CACHE_TTL_STATUS_SECONDS, static function () use ($includeHealthy): array {
            $where = $includeHealthy ? '' : "WHERE severity IN ('warning', 'critical', 'error')";
            $rows = Database::getInstance()->query("
                SELECT *
                FROM disk_space_runtime_status
                $where
                ORDER BY
                    CASE severity
                        WHEN 'critical' THEN 1
                        WHEN 'error' THEN 2
                        WHEN 'warning' THEN 3
                        WHEN 'ok' THEN 4
                        ELSE 5
                    END,
                    free_bytes ASC,
                    checked_at DESC
            ")->fetchAll();

            foreach ($rows as &$row) {
                $row['total_bytes'] = (int) ($row['total_bytes'] ?? 0);
                $row['free_bytes'] = (int) ($row['free_bytes'] ?? 0);
                $row['used_bytes'] = (int) ($row['used_bytes'] ?? 0);
                $row['required_bytes'] = (int) ($row['required_bytes'] ?? 0);
                $row['used_percent'] = $row['used_percent'] !== null ? (float) $row['used_percent'] : null;
                $row['context_id'] = $row['context_id'] !== null ? (int) $row['context_id'] : null;
                $row['host_id'] = $row['host_id'] !== null ? (int) $row['host_id'] : null;
            }
            unset($row);

            return $rows;
        });
    }

    public static function getSummary(): array
    {
        return self::rememberArray('summary', [], self::CACHE_TTL_STATUS_SECONDS, static function (): array {
            $rows = Database::getInstance()->query("
                SELECT severity, COUNT(*) AS count
                FROM disk_space_runtime_status
                GROUP BY severity
            ")->fetchAll();

            $summary = [
                'total' => 0,
                'ok' => 0,
                'warning' => 0,
                'critical' => 0,
                'error' => 0,
                'updated_at' => null,
            ];

            foreach ($rows as $row) {
                $severity = (string) ($row['severity'] ?? self::SEVERITY_UNKNOWN);
                $count = (int) ($row['count'] ?? 0);
                $summary['total'] += $count;
                if (array_key_exists($severity, $summary)) {
                    $summary[$severity] = $count;
                }
            }

            $summary['updated_at'] = Database::getInstance()->query("
                SELECT MAX(checked_at)
                FROM disk_space_runtime_status
            ")->fetchColumn() ?: null;

            return $summary;
        });
    }

    public static function getRepoForecasts(?array $repoIds = null, ?int $limit = null): array
    {
        $repos = RepoManager::getAll();
        if (is_array($repoIds) && $repoIds !== []) {
            $allowed = array_fill_keys(array_map('intval', $repoIds), true);
            $repos = array_values(array_filter($repos, static fn(array $repo): bool => isset($allowed[(int) ($repo['id'] ?? 0)])));
        }

        if ($repos === []) {
            return [];
        }

        $repoMeta = [];
        foreach ($repos as $repo) {
            $repoId = (int) ($repo['id'] ?? 0);
            if ($repoId <= 0) {
                continue;
            }
            $repoMeta[$repoId] = [
                'repo_id' => $repoId,
                'repo_name' => (string) ($repo['name'] ?? ('Depot #' . $repoId)),
                'repo_path' => (string) ($repo['path'] ?? ''),
            ];
        }

        if ($repoMeta === []) {
            return [];
        }

        $repoKey = array_map('intval', array_keys($repoMeta));
        sort($repoKey);
        $limitKey = $limit !== null ? max(1, (int) $limit) : null;

        return self::rememberArray('repo-forecasts', [$repoKey, $limitKey], self::CACHE_TTL_HEAVY_SECONDS, static function () use ($repoMeta, $limit): array {
            $repoStatusMap = self::getStatusMapByContext('repo');
            $placeholders = implode(', ', array_fill(0, count($repoMeta), '?'));
            $stmt = Database::getInstance()->prepare("
                SELECT repo_id, total_size, recorded_at
                FROM repo_stats_history
                WHERE repo_id IN ($placeholders)
                  AND recorded_at >= datetime('now', '-30 days')
                ORDER BY repo_id ASC, recorded_at DESC, id DESC
            ");
            $stmt->execute(array_keys($repoMeta));
            $rows = $stmt->fetchAll();

            $dailyPointsByRepo = [];
            $latestStatsByRepo = [];
            foreach ($rows as $row) {
                $repoId = (int) ($row['repo_id'] ?? 0);
                if ($repoId <= 0 || !isset($repoMeta[$repoId])) {
                    continue;
                }
                $recordedAt = (string) ($row['recorded_at'] ?? '');
                $dayKey = substr($recordedAt, 0, 10);
                if ($recordedAt === '' || $dayKey === '') {
                    continue;
                }
                $latestStatsByRepo[$repoId] ??= [
                    'total_size' => (int) ($row['total_size'] ?? 0),
                    'recorded_at' => $recordedAt,
                ];
                if (isset($dailyPointsByRepo[$repoId][$dayKey])) {
                    continue;
                }
                $dailyPointsByRepo[$repoId][$dayKey] = [
                    'size' => (int) ($row['total_size'] ?? 0),
                    'recorded_at' => $recordedAt,
                ];
            }

            $forecasts = [];
            foreach ($repoMeta as $repoId => $meta) {
                $diskStatus = $repoStatusMap[$repoId] ?? null;
                $dailyPoints = array_values(array_reverse($dailyPointsByRepo[$repoId] ?? []));
                $latestStats = $latestStatsByRepo[$repoId] ?? null;

                $forecast = [
                    'repo_id' => $repoId,
                    'repo_name' => $meta['repo_name'],
                    'repo_path' => $meta['repo_path'],
                    'latest_total_size' => (int) ($latestStats['total_size'] ?? 0),
                    'latest_recorded_at' => (string) ($latestStats['recorded_at'] ?? ''),
                    'sample_points' => count($dailyPoints),
                    'sample_days' => null,
                    'growth_bytes_per_day' => null,
                    'projected_days_until_full' => null,
                    'projected_fill_at' => null,
                    'free_bytes' => $diskStatus !== null ? (int) ($diskStatus['free_bytes'] ?? 0) : null,
                    'total_bytes' => $diskStatus !== null ? (int) ($diskStatus['total_bytes'] ?? 0) : null,
                    'used_percent' => $diskStatus !== null && isset($diskStatus['used_percent']) && $diskStatus['used_percent'] !== null ? (float) $diskStatus['used_percent'] : null,
                    'status' => 'insufficient_data',
                    'severity' => 'none',
                    'message' => 'Pas assez d historique pour estimer une tendance.',
                ];

                if (count($dailyPoints) >= 3) {
                    $first = $dailyPoints[0];
                    $last = $dailyPoints[count($dailyPoints) - 1];
                    $firstTs = strtotime((string) ($first['recorded_at'] ?? ''));
                    $lastTs = strtotime((string) ($last['recorded_at'] ?? ''));

                    if ($firstTs !== false && $lastTs !== false && $lastTs > $firstTs) {
                        $elapsedDays = max(0.0, ($lastTs - $firstTs) / 86400);
                        $forecast['sample_days'] = $elapsedDays;

                        if ($elapsedDays >= 1.0) {
                            $growthBytesPerDay = (((int) ($last['size'] ?? 0)) - ((int) ($first['size'] ?? 0))) / $elapsedDays;
                            $forecast['growth_bytes_per_day'] = $growthBytesPerDay;

                            if ($growthBytesPerDay > 0) {
                                if ($forecast['free_bytes'] !== null && $forecast['free_bytes'] > 0) {
                                    $daysUntilFull = $forecast['free_bytes'] / $growthBytesPerDay;
                                    $forecast['projected_days_until_full'] = $daysUntilFull;
                                    $forecast['projected_fill_at'] = gmdate('Y-m-d H:i:s', (int) (time() + ($daysUntilFull * 86400)));
                                    $forecast['status'] = 'growing';
                                    $forecast['severity'] = $daysUntilFull <= 7
                                        ? self::SEVERITY_CRITICAL
                                        : ($daysUntilFull <= 30 ? self::SEVERITY_WARNING : self::SEVERITY_OK);
                                    $forecast['message'] = sprintf(
                                        'Croissance %s/jour - saturation estimee dans %s.',
                                        self::formatGrowthRate($growthBytesPerDay),
                                        self::formatForecastHorizon($daysUntilFull)
                                    );
                                } else {
                                    $forecast['status'] = 'probe_missing';
                                    $forecast['severity'] = 'none';
                                    $forecast['message'] = sprintf(
                                        'Croissance %s/jour - lancez une sonde disque pour estimer la saturation.',
                                        self::formatGrowthRate($growthBytesPerDay)
                                    );
                                }
                            } else {
                                $forecast['status'] = 'stable';
                                $forecast['severity'] = self::SEVERITY_OK;
                                $forecast['message'] = 'Croissance stable ou en baisse sur la periode observee.';
                            }
                        }
                    }
                }

                $forecasts[] = $forecast;
            }

            usort($forecasts, static function (array $a, array $b): int {
                $aDays = $a['projected_days_until_full'];
                $bDays = $b['projected_days_until_full'];
                if ($aDays !== null && $bDays !== null) {
                    if ($aDays !== $bDays) {
                        return $aDays <=> $bDays;
                    }
                } elseif ($aDays !== null) {
                    return -1;
                } elseif ($bDays !== null) {
                    return 1;
                }

                $aGrowth = (float) ($a['growth_bytes_per_day'] ?? 0);
                $bGrowth = (float) ($b['growth_bytes_per_day'] ?? 0);
                if ($aGrowth !== $bGrowth) {
                    return $bGrowth <=> $aGrowth;
                }

                return strcasecmp((string) ($a['repo_name'] ?? ''), (string) ($b['repo_name'] ?? ''));
            });

            if ($limit !== null && $limit > 0) {
                $forecasts = array_slice($forecasts, 0, $limit);
            }

            return $forecasts;
        });
    }

    public static function getForecastSummary(?array $forecasts = null): array
    {
        $forecasts ??= self::getRepoForecasts();
        $summary = [
            'total' => count($forecasts),
            'critical' => 0,
            'warning' => 0,
            'ok' => 0,
            'stable' => 0,
            'insufficient_data' => 0,
            'probe_missing' => 0,
        ];

        foreach ($forecasts as $forecast) {
            $status = (string) ($forecast['status'] ?? '');
            $severity = (string) ($forecast['severity'] ?? '');
            if ($status === 'stable') {
                $summary['stable']++;
            } elseif ($status === 'insufficient_data') {
                $summary['insufficient_data']++;
            } elseif ($status === 'probe_missing') {
                $summary['probe_missing']++;
            }

            if ($severity !== '' && array_key_exists($severity, $summary)) {
                $summary[$severity]++;
            }
        }

        return $summary;
    }

    public static function formatForecastHorizon(?float $daysUntilFull): string
    {
        if ($daysUntilFull === null || !is_finite($daysUntilFull) || $daysUntilFull < 0) {
            return 'projection indisponible';
        }
        if ($daysUntilFull < 1) {
            $hours = max(1, (int) round($daysUntilFull * 24));
            return $hours . ' h';
        }
        if ($daysUntilFull < 14) {
            return number_format($daysUntilFull, 1, ',', ' ') . ' j';
        }
        if ($daysUntilFull < 90) {
            return (string) round($daysUntilFull) . ' j';
        }

        return number_format($daysUntilFull / 30, 1, ',', ' ') . ' mois';
    }

    public static function formatGrowthRate(?float $bytesPerDay): string
    {
        if ($bytesPerDay === null || !is_finite($bytesPerDay) || $bytesPerDay <= 0) {
            return '-';
        }

        return formatBytes((int) round($bytesPerDay)) . '/j';
    }

    public static function getHistoryChart(int $days = 7, int $seriesLimit = 4, ?array $repoIds = null): array
    {
        $days = max(1, min(30, $days));
        $seriesLimit = max(1, min(8, $seriesLimit));
        $repoKey = null;
        if (is_array($repoIds)) {
            $repoKey = array_values(array_unique(array_map('intval', $repoIds)));
            sort($repoKey);
        }

        return self::rememberArray('history-chart', [$days, $seriesLimit, $repoKey], self::CACHE_TTL_HEAVY_SECONDS, static function () use ($days, $seriesLimit, $repoKey): array {
            $db = Database::getInstance();
            if (is_array($repoKey)) {
                if ($repoKey === []) {
                    return ['labels' => [], 'datasets' => []];
                }
                $placeholders = implode(', ', array_fill(0, count($repoKey), '?'));
                $candidateStmt = $db->prepare("
                    SELECT subject_key, context_name, host_name, severity, checked_at
                    FROM disk_space_runtime_status
                    WHERE context_type = 'repo'
                      AND context_id IN ($placeholders)
                    ORDER BY
                        CASE severity
                            WHEN 'critical' THEN 1
                            WHEN 'error' THEN 2
                            WHEN 'warning' THEN 3
                            ELSE 4
                        END,
                        checked_at DESC
                    LIMIT " . $seriesLimit . "
                ");
                $candidateStmt->execute($repoKey);
                $candidateRows = $candidateStmt->fetchAll();
            } else {
                $candidateRows = $db->query("
                    SELECT subject_key, context_name, host_name, severity, checked_at
                    FROM disk_space_runtime_status
                    ORDER BY
                        CASE severity
                            WHEN 'critical' THEN 1
                            WHEN 'error' THEN 2
                            WHEN 'warning' THEN 3
                            ELSE 4
                        END,
                        checked_at DESC
                    LIMIT " . $seriesLimit . "
                ")->fetchAll();
            }

            if (empty($candidateRows)) {
                return ['labels' => [], 'datasets' => []];
            }

            $subjectMeta = [];
            foreach ($candidateRows as $row) {
                $subjectKey = (string) ($row['subject_key'] ?? '');
                if ($subjectKey === '' || isset($subjectMeta[$subjectKey])) {
                    continue;
                }
                $label = (string) ($row['context_name'] ?? $subjectKey);
                if (!empty($row['host_name'])) {
                    $label .= ' @ ' . (string) $row['host_name'];
                }
                $subjectMeta[$subjectKey] = [
                    'label' => $label,
                    'severity' => (string) ($row['severity'] ?? self::SEVERITY_UNKNOWN),
                ];
            }

            if ($subjectMeta === []) {
                return ['labels' => [], 'datasets' => []];
            }

            $placeholders = implode(', ', array_fill(0, count($subjectMeta), '?'));
            $stmt = $db->prepare("
                SELECT subject_key, checked_at, used_percent
                FROM disk_space_checks
                WHERE subject_key IN ($placeholders)
                  AND checked_at >= datetime('now', ?)
                ORDER BY checked_at ASC, id ASC
            ");
            $params = array_merge(array_keys($subjectMeta), ['-' . $days . ' days']);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            $labels = [];
            $valuesBySubject = [];
            foreach ($rows as $row) {
                $subjectKey = (string) ($row['subject_key'] ?? '');
                $bucket = substr((string) ($row['checked_at'] ?? ''), 0, 13);
                if ($subjectKey === '' || $bucket === '') {
                    continue;
                }
                $labels[$bucket] = true;
                $valuesBySubject[$subjectKey][$bucket] = $row['used_percent'] !== null ? round((float) $row['used_percent'], 1) : null;
            }

            if ($labels === []) {
                return ['labels' => [], 'datasets' => []];
            }

            ksort($labels);
            $orderedLabels = array_keys($labels);
            $palette = ['#f85149', '#d29922', '#58a6ff', '#3fb950', '#bc8cff', '#79c0ff', '#ff7b72', '#a371f7'];
            $datasets = [];
            $index = 0;
            foreach ($subjectMeta as $subjectKey => $meta) {
                $points = [];
                foreach ($orderedLabels as $label) {
                    $points[] = $valuesBySubject[$subjectKey][$label] ?? null;
                }
                $color = $palette[$index % count($palette)];
                $datasets[] = [
                    'label' => $meta['label'],
                    'data' => $points,
                    'borderColor' => $color,
                    'backgroundColor' => $color . '22',
                    'tension' => 0.25,
                    'fill' => false,
                    'spanGaps' => true,
                ];
                $index++;
            }

            return [
                'labels' => $orderedLabels,
                'datasets' => $datasets,
            ];
        });
    }

    public static function getStatusMapByContext(string $contextType): array
    {
        return self::rememberArray('status-map-context', [$contextType], self::CACHE_TTL_STATUS_SECONDS, static function () use ($contextType): array {
            $stmt = Database::getInstance()->prepare("
                SELECT *
                FROM disk_space_runtime_status
                WHERE context_type = ?
                ORDER BY checked_at DESC
            ");
            $stmt->execute([$contextType]);
            $rows = $stmt->fetchAll();

            $map = [];
            foreach ($rows as $row) {
                $contextId = isset($row['context_id']) ? (int) $row['context_id'] : 0;
                if ($contextId <= 0 || isset($map[$contextId])) {
                    continue;
                }
                $map[$contextId] = $row;
            }

            return $map;
        });
    }

    public static function preflightBackupJob(array $job, array $repo): array
    {
        $isRemote = !empty($job['host_id']) && !empty($job['ssh_key_id']);
        $path = $isRemote
            ? trim((string) ($job['remote_repo_path'] ?? $repo['path'] ?? ''))
            : trim((string) ($repo['path'] ?? ''));

        $target = $isRemote
            ? self::buildRemotePreflightTarget($job, $path, 'backup_job', (int) ($job['id'] ?? 0), (string) ($job['name'] ?? 'Backup'))
            : self::buildLocalPreflightTarget($path, 'backup_job', (int) ($job['id'] ?? 0), (string) ($job['name'] ?? 'Backup'));

        return self::runPreflight(
            $target,
            self::estimateBackupRequiredBytes((int) ($repo['id'] ?? 0)),
            'backup'
        );
    }

    public static function preflightCopyJob(array $job, array $sourceRepo, ?string $snapshotId = null): array
    {
        $path = trim((string) ($job['dest_path'] ?? ''));
        $target = self::buildLocalPreflightTarget($path, 'copy_job', (int) ($job['id'] ?? 0), (string) ($job['name'] ?? 'Copy'));

        return self::runPreflight(
            $target,
            self::estimateCopyRequiredBytes($sourceRepo, $snapshotId),
            'copy'
        );
    }

    public static function preflightRestore(array $repo, string $snapshotId, string $mode, string $targetPath, ?array $host = null): array
    {
        $target = $mode === 'remote'
            ? self::buildRemotePreflightTarget($host ?? [], $targetPath, 'restore', (int) ($repo['id'] ?? 0), (string) ($repo['name'] ?? 'Restore'))
            : self::buildLocalPreflightTarget($targetPath, 'restore', (int) ($repo['id'] ?? 0), (string) ($repo['name'] ?? 'Restore'));

        return self::runPreflight(
            $target,
            self::estimateRestoreRequiredBytes($repo, $snapshotId),
            $mode === 'remote' ? 'restauration distante' : 'restauration locale'
        );
    }

    public static function isFilesystemPath(string $path): bool
    {
        $path = trim($path);
        return $path !== ''
            && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)
            && !preg_match('#^[a-z0-9+.-]+:#i', $path);
    }

    private static function gatherMonitoringTargets(): array
    {
        $targets = [];

        foreach (RepoManager::getAll() as $repo) {
            $path = trim((string) ($repo['path'] ?? ''));
            if (!self::isFilesystemPath($path)) {
                continue;
            }
            $targets[] = self::buildLocalTarget(
                'repo:' . (int) $repo['id'],
                'repo',
                (int) $repo['id'],
                (string) ($repo['name'] ?? 'Depot'),
                $path
            );
        }

        foreach (HostManager::getAll() as $host) {
            if (empty($host['ssh_key_id'])) {
                continue;
            }
            $targets[] = self::buildRemoteTarget(
                'host:' . (int) $host['id'],
                'host',
                (int) $host['id'],
                (string) ($host['name'] ?? 'Hote'),
                $host,
                '/'
            );
        }

        $byKey = [];
        foreach ($targets as $target) {
            $byKey[(string) $target['subject_key']] = $target;
        }

        return array_values($byKey);
    }

    private static function runPreflight(array $target, int $requiredBytes, string $operation): array
    {
        if (!AppConfig::diskPreflightEnabled()) {
            return [
                'allowed' => true,
                'supported' => false,
                'message' => 'Preflight disque desactive',
                'required_bytes' => $requiredBytes,
            ];
        }

        if ($target === []) {
            return [
                'allowed' => true,
                'supported' => false,
                'message' => 'Cible de stockage non sondable',
                'required_bytes' => $requiredBytes,
            ];
        }

        $check = self::probeTarget($target, $requiredBytes);
        if ($check['severity'] === self::SEVERITY_ERROR) {
            return [
                'allowed' => false,
                'supported' => true,
                'message' => (string) ($check['message'] ?? 'Verification disque impossible'),
                'required_bytes' => $requiredBytes,
                'check' => $check,
            ];
        }

        $requiredFreeBytes = max(
            AppConfig::diskPreflightMinFreeBytes(),
            (int) ceil($requiredBytes * (1 + (AppConfig::diskPreflightMarginPercent() / 100)))
        );

        if ((int) ($check['free_bytes'] ?? 0) < $requiredFreeBytes) {
            return [
                'allowed' => false,
                'supported' => true,
                'message' => sprintf(
                    'Espace insufficient pour %s sur %s : libre %s, requis %s.',
                    $operation,
                    (string) ($check['location_label'] ?? $check['path'] ?? 'la cible'),
                    formatBytes((int) ($check['free_bytes'] ?? 0)),
                    formatBytes($requiredFreeBytes)
                ),
                'required_bytes' => $requiredFreeBytes,
                'check' => $check,
            ];
        }

        return [
            'allowed' => true,
            'supported' => true,
            'message' => sprintf(
                'Espace disponible %s sur %s',
                formatBytes((int) ($check['free_bytes'] ?? 0)),
                (string) ($check['location_label'] ?? $check['path'] ?? 'la cible')
            ),
            'required_bytes' => $requiredFreeBytes,
            'check' => $check,
        ];
    }

    private static function buildLocalTarget(string $subjectKey, string $contextType, ?int $contextId, string $contextName, string $path): array
    {
        return [
            'subject_key' => $subjectKey,
            'scope' => 'local',
            'context_type' => $contextType,
            'context_id' => $contextId,
            'context_name' => $contextName,
            'location_label' => $contextName,
            'path' => $path,
            'host_id' => null,
            'host_name' => null,
        ];
    }

    private static function buildRemoteTarget(string $subjectKey, string $contextType, ?int $contextId, string $contextName, array $host, string $path): array
    {
        return [
            'subject_key' => $subjectKey,
            'scope' => 'remote',
            'context_type' => $contextType,
            'context_id' => $contextId,
            'context_name' => $contextName,
            'location_label' => $contextName,
            'path' => $path,
            'host_id' => isset($host['id']) ? (int) $host['id'] : null,
            'host_name' => (string) ($host['name'] ?? $host['hostname'] ?? ''),
            'host' => $host,
        ];
    }

    private static function buildLocalPreflightTarget(string $path, string $contextType, int $contextId, string $contextName): array
    {
        if (!self::isFilesystemPath($path)) {
            return [];
        }

        return self::buildLocalTarget($contextType . ':preflight:' . $contextId, $contextType, $contextId, $contextName, $path);
    }

    private static function buildRemotePreflightTarget(array $host, string $path, string $contextType, int $contextId, string $contextName): array
    {
        if (empty($host['ssh_key_id']) || !self::isFilesystemPath($path)) {
            return [];
        }

        return self::buildRemoteTarget($contextType . ':preflight:' . $contextId, $contextType, $contextId, $contextName, $host, $path);
    }

    private static function probeTarget(array $target, int $requiredBytes = 0): array
    {
        $scope = (string) ($target['scope'] ?? 'local');
        $path = trim((string) ($target['path'] ?? ''));
        $requiredBytes = max(0, $requiredBytes);

        $probe = $scope === 'remote'
            ? HostManager::probeFilesystem($target['host'] ?? [], $path)
            : self::probeLocalFilesystem($path);

        $check = [
            'subject_key' => (string) ($target['subject_key'] ?? sha1($scope . ':' . $path)),
            'scope' => $scope,
            'context_type' => (string) ($target['context_type'] ?? 'system'),
            'context_id' => $target['context_id'] ?? null,
            'context_name' => (string) ($target['context_name'] ?? $path),
            'location_label' => (string) ($target['location_label'] ?? $path),
            'host_id' => $target['host_id'] ?? null,
            'host_name' => (string) ($target['host_name'] ?? ''),
            'path' => $path,
            'probe_path' => (string) ($probe['probe_path'] ?? $path),
            'total_bytes' => (int) ($probe['total_bytes'] ?? 0),
            'free_bytes' => (int) ($probe['free_bytes'] ?? 0),
            'used_bytes' => (int) ($probe['used_bytes'] ?? 0),
            'used_percent' => isset($probe['used_percent']) ? (float) $probe['used_percent'] : null,
            'required_bytes' => $requiredBytes,
            'status' => !empty($probe['success']) ? 'reachable' : 'probe_error',
            'severity' => self::SEVERITY_UNKNOWN,
            'message' => '',
            'checked_at' => (new DateTimeImmutable('now', appServerTimezone()))->format('Y-m-d H:i:s'),
        ];

        if (empty($probe['success'])) {
            $check['severity'] = self::SEVERITY_ERROR;
            $check['message'] = trim((string) ($probe['output'] ?? 'Probe disque impossible'));
            return $check;
        }

        $check['severity'] = self::evaluateSeverity(
            $scope === 'remote',
            $check['total_bytes'],
            $check['free_bytes'],
            (float) ($check['used_percent'] ?? 0)
        );
        $check['message'] = self::buildStatusMessage($check);

        return $check;
    }

    private static function probeLocalFilesystem(string $path): array
    {
        if (!self::isFilesystemPath($path)) {
            return ['success' => false, 'output' => 'Chemin local non sondable'];
        }

        $probePath = self::resolveLocalProbePath($path);
        if ($probePath === '') {
            return ['success' => false, 'output' => 'Aucun point de montage local exploitable'];
        }

        $total = @disk_total_space($probePath);
        $free = @disk_free_space($probePath);
        if ($total === false || $free === false) {
            return ['success' => false, 'output' => 'Lecture de l espace disque local impossible'];
        }

        $used = max(0, (int) $total - (int) $free);

        return [
            'success' => true,
            'probe_path' => $probePath,
            'total_bytes' => (int) $total,
            'free_bytes' => (int) $free,
            'used_bytes' => $used,
            'used_percent' => $total > 0 ? round(($used / (int) $total) * 100, 1) : 0.0,
        ];
    }

    private static function resolveLocalProbePath(string $path): string
    {
        $candidate = rtrim(trim($path), '/\\');
        if ($candidate === '') {
            return '';
        }

        while ($candidate !== '' && !file_exists($candidate)) {
            $parent = dirname($candidate);
            if ($parent === $candidate) {
                break;
            }
            $candidate = $parent;
        }

        if ($candidate === '' || !file_exists($candidate)) {
            return DIRECTORY_SEPARATOR;
        }

        return $candidate;
    }

    private static function evaluateSeverity(bool $isRemote, int $totalBytes, int $freeBytes, float $usedPercent): string
    {
        if ($totalBytes <= 0) {
            return self::SEVERITY_ERROR;
        }

        $warningFree = $isRemote ? AppConfig::diskRemoteWarningFreeBytes() : AppConfig::diskLocalWarningFreeBytes();
        $criticalFree = $isRemote ? AppConfig::diskRemoteCriticalFreeBytes() : AppConfig::diskLocalCriticalFreeBytes();
        $warningPct = $isRemote ? AppConfig::diskRemoteWarningPercent() : AppConfig::diskLocalWarningPercent();
        $criticalPct = $isRemote ? AppConfig::diskRemoteCriticalPercent() : AppConfig::diskLocalCriticalPercent();

        if ($freeBytes <= $criticalFree || $usedPercent >= $criticalPct) {
            return self::SEVERITY_CRITICAL;
        }
        if ($freeBytes <= $warningFree || $usedPercent >= $warningPct) {
            return self::SEVERITY_WARNING;
        }

        return self::SEVERITY_OK;
    }

    private static function buildStatusMessage(array $check): string
    {
        $hostPart = (string) ($check['host_name'] ?? '') !== '' ? ' @ ' . (string) $check['host_name'] : '';
        return sprintf(
            '%s%s - libre %s / total %s (%s%% utilises)',
            (string) ($check['path'] ?? ''),
            $hostPart,
            formatBytes((int) ($check['free_bytes'] ?? 0)),
            formatBytes((int) ($check['total_bytes'] ?? 0)),
            number_format((float) ($check['used_percent'] ?? 0), 1, '.', '')
        );
    }

    private static function resolveNotificationEvent(?array $previous, array $current): ?string
    {
        $currentSeverity = (string) ($current['severity'] ?? self::SEVERITY_UNKNOWN);
        $previousSeverity = (string) ($previous['severity'] ?? self::SEVERITY_UNKNOWN);

        if (in_array($currentSeverity, [self::SEVERITY_WARNING, self::SEVERITY_CRITICAL, self::SEVERITY_ERROR], true)) {
            $event = $currentSeverity === self::SEVERITY_WARNING ? 'warning' : 'critical';
            if ($previousSeverity !== $currentSeverity) {
                return $event;
            }

            $lastNotifiedAt = !empty($previous['last_notified_at']) ? strtotime((string) $previous['last_notified_at']) : false;
            if ($lastNotifiedAt === false || (time() - $lastNotifiedAt) >= 21600) {
                return $event;
            }
        }

        if ($currentSeverity === self::SEVERITY_OK && in_array($previousSeverity, [self::SEVERITY_WARNING, self::SEVERITY_CRITICAL, self::SEVERITY_ERROR], true)) {
            return 'recovered';
        }

        return null;
    }

    private static function buildNotificationPayload(string $event, array $check): array
    {
        $target = (string) ($check['location_label'] ?? $check['path'] ?? 'Stockage');
        $scopeLabel = (($check['scope'] ?? 'local') === 'remote' ? 'distant' : 'local');
        $hostSuffix = !empty($check['host_name']) ? ' (' . (string) $check['host_name'] . ')' : '';
        $path = (string) ($check['path'] ?? '');
        $free = formatBytes((int) ($check['free_bytes'] ?? 0));
        $total = formatBytes((int) ($check['total_bytes'] ?? 0));
        $usedPercent = number_format((float) ($check['used_percent'] ?? 0), 1, '.', '');

        return match ($event) {
            'warning' => [
                'Alerte espace disque - ' . $target,
                "Le stockage **$target** ($scopeLabel$hostSuffix) approche de la saturation.\n"
                . "**Chemin** : `$path`\n"
                . "**Libre** : $free / $total\n"
                . "**Utilisation** : $usedPercent%",
                'warning',
            ],
            'critical' => [
                'Critique espace disque - ' . $target,
                "Le stockage **$target** ($scopeLabel$hostSuffix) est en situation critique.\n"
                . "**Chemin** : `$path`\n"
                . "**Libre** : $free / $total\n"
                . "**Utilisation** : $usedPercent%\n"
                . (!empty($check['message']) ? "\n" . (string) $check['message'] : ''),
                'critical',
            ],
            default => [
                'Espace disque retabli - ' . $target,
                "Le stockage **$target** ($scopeLabel$hostSuffix) est revenu dans une plage saine.\n"
                . "**Chemin** : `$path`\n"
                . "**Libre** : $free / $total\n"
                . "**Utilisation** : $usedPercent%",
                'success',
            ],
        };
    }

    private static function persistCheck(array $check): void
    {
        $db = Database::getInstance();

        $db->prepare("
            INSERT INTO disk_space_checks
            (subject_key, scope, context_type, context_id, context_name, host_id, host_name, path, probe_path, total_bytes, free_bytes, used_bytes, used_percent, required_bytes, severity, status, message, checked_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $check['subject_key'],
            $check['scope'],
            $check['context_type'],
            $check['context_id'],
            $check['context_name'],
            $check['host_id'],
            $check['host_name'],
            $check['path'],
            $check['probe_path'],
            $check['total_bytes'],
            $check['free_bytes'],
            $check['used_bytes'],
            $check['used_percent'],
            $check['required_bytes'],
            $check['severity'],
            $check['status'],
            $check['message'],
            $check['checked_at'],
        ]);

        $db->prepare("
            INSERT INTO disk_space_runtime_status
            (subject_key, scope, context_type, context_id, context_name, host_id, host_name, path, probe_path, total_bytes, free_bytes, used_bytes, used_percent, required_bytes, severity, status, message, checked_at, last_change_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ON CONFLICT(subject_key) DO UPDATE SET
                scope = excluded.scope,
                context_type = excluded.context_type,
                context_id = excluded.context_id,
                context_name = excluded.context_name,
                host_id = excluded.host_id,
                host_name = excluded.host_name,
                path = excluded.path,
                probe_path = excluded.probe_path,
                total_bytes = excluded.total_bytes,
                free_bytes = excluded.free_bytes,
                used_bytes = excluded.used_bytes,
                used_percent = excluded.used_percent,
                required_bytes = excluded.required_bytes,
                status = excluded.status,
                message = excluded.message,
                checked_at = excluded.checked_at,
                severity = excluded.severity,
                last_change_at = CASE
                    WHEN disk_space_runtime_status.severity != excluded.severity THEN datetime('now')
                    ELSE disk_space_runtime_status.last_change_at
                END
        ")->execute([
            $check['subject_key'],
            $check['scope'],
            $check['context_type'],
            $check['context_id'],
            $check['context_name'],
            $check['host_id'],
            $check['host_name'],
            $check['path'],
            $check['probe_path'],
            $check['total_bytes'],
            $check['free_bytes'],
            $check['used_bytes'],
            $check['used_percent'],
            $check['required_bytes'],
            $check['severity'],
            $check['status'],
            $check['message'],
            $check['checked_at'],
        ]);
    }

    private static function syncRuntimeTargets(array $targets): void
    {
        $db = Database::getInstance();
        $subjectKeys = array_values(array_filter(array_map(static fn(array $target): string => (string) ($target['subject_key'] ?? ''), $targets)));
        if ($subjectKeys === []) {
            $db->exec("DELETE FROM disk_space_runtime_status");
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($subjectKeys), '?'));
        $stmt = $db->prepare("
            DELETE FROM disk_space_runtime_status
            WHERE subject_key NOT IN ($placeholders)
        ");
        $stmt->execute($subjectKeys);
    }

    private static function getRuntimeStatus(string $subjectKey): ?array
    {
        $stmt = Database::getInstance()->prepare("
            SELECT *
            FROM disk_space_runtime_status
            WHERE subject_key = ?
            LIMIT 1
        ");
        $stmt->execute([$subjectKey]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    private static function markNotified(string $subjectKey, string $event): void
    {
        Database::getInstance()->prepare("
            UPDATE disk_space_runtime_status
            SET last_notified_event = ?, last_notified_at = datetime('now')
            WHERE subject_key = ?
        ")->execute([$event, $subjectKey]);
    }

    private static function estimateBackupRequiredBytes(int $repoId): int
    {
        $latestSize = self::latestKnownRepoTotalSize($repoId);
        $growth = self::latestPositiveRepoGrowth($repoId);
        if ($growth > 0) {
            return $growth;
        }
        if ($latestSize > 0) {
            return max(268435456, (int) round($latestSize * 0.02));
        }
        return 1073741824;
    }

    private static function estimateCopyRequiredBytes(array $repo, ?string $snapshotId = null): int
    {
        $repoId = (int) ($repo['id'] ?? 0);
        $latestSize = self::latestKnownRepoTotalSize($repoId);
        if ($latestSize > 0 && $snapshotId === null) {
            return $latestSize;
        }

        $stats = RepoManager::getRestic($repo)->stats($snapshotId);
        if (!isset($stats['error'])) {
            $totalSize = (int) ($stats['total_size'] ?? 0);
            if ($totalSize > 0) {
                return $totalSize;
            }
        }

        return max($latestSize, 1073741824);
    }

    private static function estimateRestoreRequiredBytes(array $repo, string $snapshotId): int
    {
        $stats = RepoManager::getRestic($repo)->stats($snapshotId);
        if (!isset($stats['error'])) {
            $totalSize = (int) ($stats['total_size'] ?? 0);
            if ($totalSize > 0) {
                return $totalSize;
            }
        }

        return max(self::latestKnownRepoTotalSize((int) ($repo['id'] ?? 0)), 1073741824);
    }

    private static function latestKnownRepoTotalSize(int $repoId): int
    {
        if ($repoId <= 0) {
            return 0;
        }

        $stmt = Database::getInstance()->prepare("
            SELECT total_size
            FROM repo_stats_history
            WHERE repo_id = ?
            ORDER BY recorded_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$repoId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function latestPositiveRepoGrowth(int $repoId): int
    {
        if ($repoId <= 0) {
            return 0;
        }

        $stmt = Database::getInstance()->prepare("
            SELECT total_size
            FROM repo_stats_history
            WHERE repo_id = ?
            ORDER BY recorded_at DESC, id DESC
            LIMIT 5
        ");
        $stmt->execute([$repoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($rows) || count($rows) < 2) {
            return 0;
        }

        $previous = null;
        foreach ($rows as $value) {
            $size = (int) $value;
            if ($previous !== null) {
                $delta = $previous - $size;
                if ($delta > 0) {
                    return $delta;
                }
            }
            $previous = $size;
        }

        return 0;
    }

    /**
     * @param callable():array $resolver
     */
    private static function rememberArray(string $bucket, array $parts, int $ttlSeconds, callable $resolver): array
    {
        $memoKeyPayload = json_encode([$bucket, $parts], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $memoKey = is_string($memoKeyPayload) && $memoKeyPayload !== ''
            ? $memoKeyPayload
            : ($bucket . '|' . serialize($parts));
        if (array_key_exists($memoKey, self::$requestMemo)) {
            return self::$requestMemo[$memoKey];
        }

        $value = RuntimeTtlCache::rememberArray('disk-space-monitor:' . $bucket, $parts, $ttlSeconds, $resolver);
        self::$requestMemo[$memoKey] = $value;

        return $value;
    }
}
