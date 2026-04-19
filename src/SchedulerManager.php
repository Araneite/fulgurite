<?php

class SchedulerManager {
    private const DAY_LABELS_SHORT = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mer',
        4 => 'Jeu',
        5 => 'Ven',
        6 => 'Sam',
        7 => 'Dim',
    ];

    private const DAY_LABELS_LONG = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    public static function getDayOptions(): array {
        return self::DAY_LABELS_LONG;
    }

    public static function getScheduleTimezoneName(): string {
        return AppConfig::timezone();
    }

    public static function getScheduleTimezoneLabel(): string {
        return AppConfig::timezoneLabel();
    }

    public static function getServerTimezoneName(): string {
        return AppConfig::serverTimezone();
    }

    public static function getSystemUser(): string {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $userInfo = @posix_getpwuid(posix_geteuid());
            if (is_array($userInfo) && !empty($userInfo['name'])) {
                return (string) $userInfo['name'];
            }
        }

        $candidates = [
            (string) ($_SERVER['USER'] ?? ''),
            (string) ($_SERVER['LOGNAME'] ?? ''),
            (string) getenv('USER'),
            (string) getenv('LOGNAME'),
            get_current_user() ?: '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'utilisateur du serveur web';
    }

    public static function getCronEntryUrl(): string {
        return '';
    }

    public static function getCronScriptPath(): string {
        return dirname(__DIR__) . '/public/cron.php';
    }

    public static function getCronLockFilePath(): string {
        return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fulgurite-cron.lock';
    }

    public static function getCronLine(): string {
        // Removed flock -n: cron.php itself now handles lock acquisition and logs
        // to cron_log if an instance is already active.
        // We keep the log file to retain a trace of stdout in case of a fatal error.
        $logFile = dirname(__DIR__) . '/data/logs/cron.log';
        return sprintf(
            '%d * * * * %s %s >> %s 2>&1',
            AppConfig::cronRunMinute(),
            escapeshellarg(WorkerManager::getPhpCliBinary()),
            escapeshellarg(self::getCronScriptPath()),
            escapeshellarg($logFile)
        );
    }

    public static function getCronInstallCommand(): string {
        $marker = '# Fulgurite cron';
        $cronLine = self::getCronLine();
        $cronScript = self::getCronScriptPath();

        return sprintf(
            '(crontab -l 2>/dev/null | grep -v -F %s | grep -v -F %s | grep -v -F %s | grep -v -F %s; printf "%%s\n%%s\n" %s %s) | crontab -',
            escapeshellarg($marker),
            escapeshellarg($cronScript),
            escapeshellarg('/cron.php?token='),
            escapeshellarg('/cron?token='),
            escapeshellarg($marker),
            escapeshellarg($cronLine)
        );
    }

    public static function getCronEngineStatus(): array {
        $supportsCrontab = DIRECTORY_SEPARATOR !== '\\';
        $current = '';

        if ($supportsCrontab) {
            $result = ProcessRunner::run(['crontab', '-l'], ['capture_stderr' => false]);
            $current = $result['success'] ? trim((string) ($result['stdout'] ?? '')) : '';
        }

        $marker = '# Fulgurite cron';
        $active = $supportsCrontab && (
            str_contains($current, $marker)
            || str_contains($current, self::getCronScriptPath())
            || str_contains($current, '/cron.php?token=')
            || str_contains($current, '/cron?token=')
        );

        // Detect whether the installed line is outdated (old syntax with flock -n
        // or redirect to /dev/null) even if the engine is considered active.
        $expectedLine = self::getCronLine();
        $needsUpdate = $active && !str_contains($current, $expectedLine);

        return [
            'supports_crontab' => $supportsCrontab,
            'active' => $active,
            'needs_update' => $needsUpdate,
            'cron_line' => $expectedLine,
            'install_command' => self::getCronInstallCommand(),
            'crontab' => $current,
            'system_user' => self::getSystemUser(),
            'label' => $supportsCrontab
                ? ($active ? ($needsUpdate ? 'Mise à jour requise' : 'Actif') : 'Inactif')
                : 'Mode manuel local',
        ];
    }

    public static function getGlobalTasks(): array {
        $tasks = [
            self::buildConfigurableTask(
                'weekly_report',
                'Rapport hebdomadaire',
                'Envoie un resume global de l etat des depots sur les canaux actifs.',
                'weekly_report',
                'weekly_report_enabled',
                'weekly_report_day',
                'weekly_report_hour',
                '0',
                '1'
            ),
            self::buildConfigurableTask(
                'integrity_check',
                'Verification d integrite',
                'Lance restic check sur tous les depots et notifie en cas de probleme.',
                'auto_check',
                'integrity_check_enabled',
                'integrity_check_day',
                'integrity_check_hour',
                '1',
                '1'
            ),
            self::buildConfigurableTask(
                'db_vacuum',
                'Maintenance lourde SQLite',
                'Lance un checkpoint WAL puis VACUUM sur les bases principales et index.',
                'db_vacuum',
                'maintenance_vacuum_enabled',
                'maintenance_vacuum_day',
                'maintenance_vacuum_hour',
                '1',
                '7'
            ),
        ];

        usort($tasks, function (array $left, array $right): int {
            return strcmp((string) ($left['next_run'] ?? '9999'), (string) ($right['next_run'] ?? '9999'));
        });

        return $tasks;
    }

    public static function getEngineTasks(): array {
        $lastCronRun = self::getLastExecution('cron_run');
        $lastOptimize = self::getLastExecution('db_optimize');
        $hourlyCadence = 'Toutes les heures a :' . str_pad((string) AppConfig::cronRunMinute(), 2, '0', STR_PAD_LEFT)
            . ' (serveur ' . self::getServerTimezoneName() . ')';

        return [
            [
                'title' => 'Cycle horaire',
                'cadence' => $hourlyCadence,
                'description' => 'Declenche les jobs backup/copy dus pour le creneau courant.',
                'last_run' => $lastCronRun['ran_at'] ?? null,
                'last_status' => $lastCronRun['status'] ?? null,
            ],
            [
                'title' => 'Surveillance depots',
                'cadence' => $hourlyCadence,
                'description' => 'Met a jour les statuss runtime, les stats horaires, les alertes et le catalogue des snapshots.',
                'last_run' => $lastCronRun['ran_at'] ?? null,
                'last_status' => $lastCronRun['status'] ?? null,
            ],
            [
                'title' => 'Traitement file interne',
                'cadence' => $hourlyCadence,
                'description' => 'Traite la file des jobs internes, notamment les rafraichissements d index.',
                'last_run' => $lastCronRun['ran_at'] ?? null,
                'last_status' => $lastCronRun['status'] ?? null,
            ],
            [
                'title' => 'Optimisation SQLite',
                'cadence' => 'Quotidien',
                'description' => 'Execute PRAGMA optimize sur la base principale et la base d index.',
                'last_run' => $lastOptimize['ran_at'] ?? null,
                'last_status' => $lastOptimize['status'] ?? null,
            ],
            [
                'title' => 'Nettoyage runtime',
                'cadence' => $hourlyCadence,
                'description' => 'Nettoie caches expires, sessions inactives et archives anciennes en base.',
                'last_run' => $lastCronRun['ran_at'] ?? null,
                'last_status' => $lastCronRun['status'] ?? null,
            ],
        ];
    }

    public static function getBackupJobSchedules(): array {
        $jobs = BackupJobManager::getAll();
        foreach ($jobs as &$job) {
            $job['schedule_summary'] = self::describeJobSchedule($job);
            $job['next_run'] = self::getNextRunForJob($job);
            $job['manage_url'] = routePath('/backup_jobs.php');
            $job['kind'] = 'backup';
        }
        unset($job);

        usort($jobs, [self::class, 'compareScheduledRows']);
        return $jobs;
    }

    public static function getCopyJobSchedules(): array {
        $jobs = CopyJobManager::getAll();
        foreach ($jobs as &$job) {
            $job['schedule_summary'] = self::describeJobSchedule($job);
            $job['next_run'] = self::getNextRunForJob($job);
            $job['manage_url'] = routePath('/copy_jobs.php');
            $job['kind'] = 'copy';
        }
        unset($job);

        usort($jobs, [self::class, 'compareScheduledRows']);
        return $jobs;
    }

    public static function getRecentCronEntries(int $limit = 30): array {
        $stmt = Database::getInstance()->prepare("
            SELECT *
            FROM cron_log
            ORDER BY ran_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function describeJobSchedule(array $job): string {
        if ((int) ($job['schedule_enabled'] ?? 0) !== 1) {
            return 'Manuel';
        }

        $days = self::normalizeDaysCsv((string) ($job['schedule_days'] ?? '1'));
        if (empty($days)) {
            return 'Planifie sans jour valide';
        }

        $labels = array_map(fn(int $day): string => self::DAY_LABELS_SHORT[$day] ?? (string) $day, $days);
        $hour = max(0, min(23, (int) ($job['schedule_hour'] ?? 0)));

        return implode(' ', $labels) . ' ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
    }

    public static function getNextRunForJob(array $job): ?string {
        if ((int) ($job['schedule_enabled'] ?? 0) !== 1) {
            return null;
        }

        return self::computeNextRun(
            self::normalizeDaysCsv((string) ($job['schedule_days'] ?? '1')),
            (int) ($job['schedule_hour'] ?? 0)
        );
    }

    public static function runWeeklyReportTask(bool $force = false, ?DateTimeInterface $now = null, ?callable $logger = null): array {
        return self::runConfigurableTask(
            'weekly_report',
            'weekly_report_enabled',
            'weekly_report_day',
            'weekly_report_hour',
            $force,
            $now,
            $logger,
            function (DateTimeInterface $runtimeNow) use ($logger): array {
                $repos = RepoManager::getAll();
                $repoStatuses = [];
                $logs = ['[REPORT] Envoi du rapport hebdomadaire'];
                if ($logger !== null) {
                    $logger('[REPORT] Envoi du rapport hebdomadaire');
                }

                foreach ($repos as $repo) {
                    $logs[] = "[REPORT] Analyse depot : {$repo['name']}";
                    if ($logger !== null) {
                        $logger("[REPORT] Analyse depot : {$repo['name']}");
                    }
                    $restic = RepoManager::getRestic($repo);
                    $snaps = $restic->snapshots();
                    $ok = is_array($snaps) && !isset($snaps['error']);
                    $count = $ok ? count($snaps) : 0;
                    $lastSnap = ($ok && $count > 0) ? end($snaps) : null;
                    $hoursAgo = null;
                    $status = 'ok';

                    if (!$ok) {
                        $status = 'error';
                    } elseif ($count === 0) {
                        $status = 'no_snap';
                    } elseif ($lastSnap) {
                        try {
                            $diff = $runtimeNow->getTimestamp() - (new DateTimeImmutable((string) $lastSnap['time']))->getTimestamp();
                            $hoursAgo = round($diff / 3600, 1);
                            if ($hoursAgo > (int) ($repo['alert_hours'] ?? 25)) {
                                $status = 'warning';
                            }
                        } catch (Throwable $e) {
                            $hoursAgo = null;
                        }
                    }

                    $repoStatuses[] = [
                        'repo' => $repo,
                        'count' => $count,
                        'last' => $lastSnap,
                        'hours_ago' => $hoursAgo,
                        'status' => $status,
                    ];
                }

                Notifier::sendWeeklyReport($repoStatuses);
                $logs[] = '[REPORT] Envoye';
                if ($logger !== null) {
                    $logger('[REPORT] Envoye');
                }

                return [
                    'success' => true,
                    'output' => 'Rapport envoye a ' . $runtimeNow->format('H:i'),
                    'logs' => $logs,
                    'persist' => [
                        [
                            'job_type' => 'weekly_report',
                            'status' => 'success',
                            'output' => 'Rapport envoye a ' . $runtimeNow->format('H:i'),
                        ],
                    ],
                ];
            }
        );
    }

    public static function runIntegrityCheckTask(bool $force = false, ?DateTimeInterface $now = null, ?callable $logger = null): array {
        return self::runConfigurableTask(
            'auto_check',
            'integrity_check_enabled',
            'integrity_check_day',
            'integrity_check_hour',
            $force,
            $now,
            $logger,
            function () use ($logger): array {
                $repos = RepoManager::getAll();
                $logs = [];
                $persist = [];
                $allSuccess = true;

                foreach ($repos as $repo) {
                    $logs[] = "[CHECK] Verification integrite : {$repo['name']}";
                    if ($logger !== null) {
                        $logger("[CHECK] Verification integrite : {$repo['name']}");
                    }
                    $restic = RepoManager::getRestic($repo);
                    $result = $restic->check();
                    $success = (bool) ($result['success'] ?? false);
                    $allSuccess = $allSuccess && $success;
                    $statusLine = $success ? 'OK' : 'PROBLEME DETECTE';
                    $logs[] = "[CHECK] {$repo['name']} : {$statusLine}";
                    if ($logger !== null) {
                        $logger("[CHECK] {$repo['name']} : {$statusLine}");
                    }

                    if (!$success) {
                        $output = (string) ($result['output'] ?? '');
                        $policy = Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check');
                        Notifier::dispatchPolicy('integrity_check', $policy, 'failure', "Integrite compromise - {$repo['name']}", $output, [
                            'context_type' => 'scheduler_task',
                            'context_name' => 'integrity_check',
                            'ntfy_priority' => 'urgent',
                        ]);
                    }

                    $persist[] = [
                        'job_type' => 'auto_check',
                        'status' => $success ? 'success' : 'failed',
                        'output' => "[{$repo['name']}]\n" . (string) ($result['output'] ?? ''),
                    ];
                }

                if ($allSuccess && !empty($repos)) {
                    $policy = Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check');
                    Notifier::dispatchPolicy(
                        'integrity_check',
                        $policy,
                        'success',
                        'Verification d integrite - OK',
                        'Tous les depots verifies sont revenus sans erreur.',
                        [
                            'context_type' => 'scheduler_task',
                            'context_name' => 'integrity_check',
                            'ntfy_priority' => 'default',
                        ]
                    );
                }

                return [
                    'success' => $allSuccess,
                    'output' => implode("\n", $logs),
                    'logs' => $logs,
                    'persist' => $persist,
                ];
            }
        );
    }

    public static function runDbVacuumTask(bool $force = false, ?DateTimeInterface $now = null, ?callable $logger = null): array {
        return self::runConfigurableTask(
            'db_vacuum',
            'maintenance_vacuum_enabled',
            'maintenance_vacuum_day',
            'maintenance_vacuum_hour',
            $force,
            $now,
            $logger,
            function () use ($logger): array {
                $db = Database::getInstance();
                $logs = ['[MAINT] Demarrage VACUUM'];
                if ($logger !== null) {
                    $logger('[MAINT] Demarrage VACUUM');
                }

                $deletedNotifications = AppNotificationManager::purgeExpired();
                $logs[] = '[MAINT] Notifications purgees : ' . $deletedNotifications;
                if ($logger !== null) {
                    $logger('[MAINT] Notifications purgees : ' . $deletedNotifications);
                }

                $db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
                $db->exec('VACUUM');

                $indexDb = Database::getIndexInstance();
                $indexDb->exec('PRAGMA wal_checkpoint(TRUNCATE)');
                $indexDb->exec('VACUUM');
                $logs[] = '[MAINT] VACUUM execute';
                if ($logger !== null) {
                    $logger('[MAINT] VACUUM execute');
                }

                $policy = Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum');
                Notifier::dispatchPolicy(
                    'maintenance_vacuum',
                    $policy,
                    'success',
                    'Maintenance SQLite executee',
                    'Les anciennes notifications ont ete purgees puis le checkpoint WAL et le VACUUM ont ete executes sur les bases principale et index.',
                    [
                        'context_type' => 'scheduler_task',
                        'context_name' => 'maintenance_vacuum',
                        'ntfy_priority' => 'default',
                    ]
                );

                return [
                    'success' => true,
                    'output' => 'Purge notifications + VACUUM executes sur les bases principale et index',
                    'logs' => $logs,
                    'persist' => [
                        [
                            'job_type' => 'db_vacuum',
                            'status' => 'success',
                            'output' => 'Purge notifications + VACUUM executes sur les bases principale et index',
                        ],
                    ],
                ];
            }
        );
    }

    private static function runConfigurableTask(
        string $taskKey,
        string $enabledKey,
        string $dayKey,
        string $hourKey,
        bool $force,
        ?DateTimeInterface $now,
        ?callable $logger,
        callable $runner
    ): array {
        $runtimeNow = self::runtimeNow($now);

        $enabled = Database::getSetting($enabledKey, '0') === '1';
        $days = self::normalizeDaysCsv((string) Database::getSetting($dayKey, '1'));
        $hour = max(0, min(23, (int) Database::getSetting($hourKey, '0')));

        if (!$force) {
            if (!$enabled || !self::isDueAt($runtimeNow, $days, $hour) || self::alreadyRanToday($taskKey, $runtimeNow)) {
                return ['executed' => false, 'success' => true, 'output' => '', 'logs' => []];
            }
        }

        try {
            $result = $runner($runtimeNow);
        } catch (Throwable $e) {
            $message = trim($e->getMessage()) ?: 'Erreur inconnue';
            self::insertCronLog($taskKey, 'failed', $message);
            if ($taskKey === 'auto_check') {
                $policy = Notifier::getSettingPolicy('integrity_check_notification_policy', 'integrity_check');
                Notifier::dispatchPolicy('integrity_check', $policy, 'failure', 'Verification d integrite - ECHEC', $message, [
                    'context_type' => 'scheduler_task',
                    'context_name' => 'integrity_check',
                    'ntfy_priority' => 'urgent',
                ]);
            } elseif ($taskKey === 'db_vacuum') {
                $policy = Notifier::getSettingPolicy('maintenance_vacuum_notification_policy', 'maintenance_vacuum');
                Notifier::dispatchPolicy('maintenance_vacuum', $policy, 'failure', 'Maintenance SQLite - ECHEC', $message, [
                    'context_type' => 'scheduler_task',
                    'context_name' => 'maintenance_vacuum',
                    'ntfy_priority' => 'high',
                ]);
            }
            return [
                'executed' => true,
                'success' => false,
                'output' => $message,
                'logs' => ["[$taskKey] ECHEC - $message"],
            ];
        }

        foreach ((array) ($result['persist'] ?? []) as $entry) {
            self::insertCronLog(
                (string) ($entry['job_type'] ?? $taskKey),
                (string) ($entry['status'] ?? ($result['success'] ? 'success' : 'failed')),
                (string) ($entry['output'] ?? $result['output'] ?? '')
            );
        }

        return [
            'executed' => true,
            'success' => (bool) ($result['success'] ?? true),
            'output' => (string) ($result['output'] ?? ''),
            'logs' => (array) ($result['logs'] ?? []),
        ];
    }

    private static function buildConfigurableTask(
        string $taskKey,
        string $title,
        string $description,
        string $jobType,
        string $enabledKey,
        string $dayKey,
        string $hourKey,
        string $defaultEnabled,
        string $defaultDay
    ): array {
        $enabled = Database::getSetting($enabledKey, $defaultEnabled) === '1';
        $days = self::normalizeDaysCsv((string) Database::getSetting($dayKey, $defaultDay));
        $hour = max(0, min(23, (int) Database::getSetting($hourKey, '0')));
        $last = self::getLastExecution($jobType);
        $nextRun = $enabled ? self::computeNextRun($days, $hour) : null;
        $notificationProfile = match ($taskKey) {
            'weekly_report' => 'weekly_report',
            'integrity_check' => 'integrity_check',
            'db_vacuum' => 'maintenance_vacuum',
            default => 'weekly_report',
        };
        $notificationSettingKey = match ($taskKey) {
            'weekly_report' => 'weekly_report_notification_policy',
            'integrity_check' => 'integrity_check_notification_policy',
            'db_vacuum' => 'maintenance_vacuum_notification_policy',
            default => 'weekly_report_notification_policy',
        };
        $notificationPolicy = Notifier::getSettingPolicy($notificationSettingKey, $notificationProfile);
        $labels = array_map(
            fn(int $day): string => self::DAY_LABELS_LONG[$day] ?? (string) $day,
            $days
        );

        return [
            'key' => $taskKey,
            'title' => $title,
            'description' => $description,
            'enabled' => $enabled,
            'enabled_key' => $enabledKey,
            'day' => $days[0] ?? self::normalizeDay((int) $defaultDay),
            'days' => $days,
            'day_key' => $dayKey,
            'hour' => $hour,
            'hour_key' => $hourKey,
            'job_type' => $jobType,
            'schedule_summary' => $enabled
                ? (!empty($labels)
                    ? implode(', ', $labels) . ' ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00'
                    : 'Planifie sans jour valide')
                : 'Desactive',
            'next_run' => $nextRun,
            'last_run' => $last['ran_at'] ?? null,
            'last_status' => $last['status'] ?? null,
            'last_output' => $last['output'] ?? '',
            'notification_profile' => $notificationProfile,
            'notification_setting_key' => $notificationSettingKey,
            'notification_policy' => $notificationPolicy,
            'notification_summary' => Notifier::summarizePolicy($notificationPolicy, $notificationProfile),
        ];
    }

    private static function getLastExecution(string $jobType): ?array {
        $stmt = Database::getInstance()->prepare("
            SELECT *
            FROM cron_log
            WHERE job_type = ?
            ORDER BY ran_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$jobType]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function alreadyRanToday(string $jobType, DateTimeInterface $now): bool {
        $last = self::getLastExecution($jobType);
        if (empty($last['ran_at'])) {
            return false;
        }

        $scheduleNow = self::scheduleNow($now);
        $lastRun = parseAppDate((string) $last['ran_at']);
        if (!$lastRun) {
            return false;
        }

        return $lastRun->setTimezone(self::scheduleTimezone())->format('Y-m-d') === $scheduleNow->format('Y-m-d');
    }

    private static function insertCronLog(string $jobType, string $status, string $output): void {
        Database::getInstance()->prepare("
            INSERT INTO cron_log (job_type, status, output)
            VALUES (?, ?, ?)
        ")->execute([$jobType, $status, $output]);
    }

    private static function computeNextRun(array $days, int $hour, ?DateTimeImmutable $from = null): ?string {
        $runtimeFrom = $from ? DateTimeImmutable::createFromInterface($from) : self::runtimeNow();
        $normalizedDays = self::normalizeDays($days);
        if (empty($normalizedDays)) {
            return null;
        }

        $scheduleFrom = $runtimeFrom->setTimezone(self::scheduleTimezone());

        for ($offset = 0; $offset <= 14; $offset++) {
            $candidateBase = $scheduleFrom->modify('+' . $offset . ' day');
            if ($candidateBase === false) {
                continue;
            }

            $dayOfWeek = (int) $candidateBase->format('N');
            if (!in_array($dayOfWeek, $normalizedDays, true)) {
                continue;
            }

            $candidate = $candidateBase->setTime(max(0, min(23, $hour)), 0, 0);
            if ($candidate > $scheduleFrom) {
                return $candidate->format(DateTimeInterface::ATOM);
            }
        }

        return null;
    }

    private static function isDueAt(DateTimeInterface $now, array $days, int $hour): bool {
        $normalizedDays = self::normalizeDays($days);
        $scheduleNow = self::scheduleNow($now);
        return in_array((int) $scheduleNow->format('N'), $normalizedDays, true)
            && (int) $scheduleNow->format('G') === max(0, min(23, $hour));
    }

    private static function normalizeDaysCsv(string $daysCsv): array {
        $parts = array_filter(array_map('trim', explode(',', $daysCsv)), fn(string $value): bool => $value !== '');
        return self::normalizeDays(array_map('intval', $parts));
    }

    private static function normalizeDays(array $days): array {
        $normalized = [];
        foreach ($days as $day) {
            $value = self::normalizeDay((int) $day);
            if ($value >= 1 && $value <= 7) {
                $normalized[$value] = $value;
            }
        }

        ksort($normalized);
        return array_values($normalized);
    }

    private static function normalizeDay(int $day): int {
        if ($day === 0) {
            return 7;
        }
        return max(1, min(7, $day));
    }

    private static function compareScheduledRows(array $left, array $right): int {
        $leftRank = $left['next_run'] ? 0 : 1;
        $rightRank = $right['next_run'] ? 0 : 1;
        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }

        $leftTimestamp = self::sortableTimestamp($left['next_run'] ?? null);
        $rightTimestamp = self::sortableTimestamp($right['next_run'] ?? null);
        if ($leftTimestamp !== $rightTimestamp) {
            return $leftTimestamp <=> $rightTimestamp;
        }

        return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
    }

    private static function scheduleTimezone(): DateTimeZone {
        static $cache = [];
        $name = self::getScheduleTimezoneName();
        if (!isset($cache[$name])) {
            $cache[$name] = new DateTimeZone($name);
        }

        return $cache[$name];
    }

    private static function runtimeNow(?DateTimeInterface $now = null): DateTimeImmutable {
        return $now instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($now)
            : new DateTimeImmutable('now', new DateTimeZone(self::getServerTimezoneName()));
    }

    private static function scheduleNow(?DateTimeInterface $now = null): DateTimeImmutable {
        return self::runtimeNow($now)->setTimezone(self::scheduleTimezone());
    }

    private static function sortableTimestamp(?string $value): int {
        $parsed = parseAppDate($value);
        return $parsed ? $parsed->getTimestamp() : PHP_INT_MAX;
    }
}
