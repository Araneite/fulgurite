<?php

class WorkerManager {
    private const DEFAULT_NAME = 'default';
    private const DEFAULT_SLEEP = 5;
    private const DEFAULT_LIMIT = 3;
    private const DEFAULT_STALE_MINUTES = 30;
    private const HEARTBEAT_STALE_SECONDS = 20;
    private const CRON_MARKER_PREFIX = '# Fulgurite worker ';

    private static function defaultSleep(): int {
        return AppConfig::workerSleepSeconds();
    }

    private static function defaultLimit(): int {
        return AppConfig::workerLimit();
    }

    private static function defaultStaleMinutes(): int {
        return AppConfig::workerStaleMinutes();
    }

    private static function heartbeatStaleSeconds(): int {
        return AppConfig::workerHeartbeatStaleSeconds();
    }

    public static function getStatus(string $name = self::DEFAULT_NAME): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $pidFile = self::getPidFilePath($name);
        $pid = self::readPidFile($pidFile);
        $heartbeat = JobQueue::getWorkerHeartbeat($name);
        $heartbeatRecent = self::isHeartbeatRecent($heartbeat);
        $pidRunning = $pid !== null && self::isPidRunning($pid);
        $cronScheduled = self::isCronEnabled($name);
        $systemd = self::getSystemdStatus();

        $daemonRunning = $pidRunning || $heartbeatRecent;
        $systemdRunning = !empty($systemd['configured']) && !empty($systemd['active']);

        if (!$daemonRunning && is_file($pidFile)) {
            @unlink($pidFile);
            $pid = null;
        }

        $controlMode = 'stopped';
        if ($systemdRunning) {
            $controlMode = 'systemd';
        } elseif (!empty($systemd['configured'])) {
            $controlMode = 'systemd';
        } elseif ($daemonRunning) {
            $controlMode = 'daemon';
        } elseif ($cronScheduled) {
            $controlMode = 'cron';
        }

        $running = $systemdRunning || $daemonRunning;
        $active = $running || $cronScheduled;
        $heartbeatPayload = $heartbeat;
        if (!empty($heartbeatPayload['at'])) {
            $heartbeatPayload['at_formatted'] = formatDate((string) $heartbeatPayload['at']);
        }

        return [
            'name' => $name,
            'running' => $running,
            'active' => $active,
            'scheduled' => $cronScheduled,
            'pid_running' => $pidRunning,
            'heartbeat_recent' => $heartbeatRecent,
            'pid' => $systemdRunning ? ($systemd['main_pid'] ?: $pid) : $pid,
            'pid_file' => $pidFile,
            'log_file' => self::getLogFilePath(),
            'php_bin' => self::getPhpCliBinary(),
            'worker_script' => self::getWorkerScriptPath(),
            'cron_line' => $cronScheduled ? self::buildCronLine($name, self::defaultLimit(), self::defaultStaleMinutes()) : '',
            'heartbeat' => $heartbeatPayload,
            'control_mode' => $controlMode,
            'control_label' => self::getControlLabel($controlMode, $systemdRunning, $cronScheduled),
            'systemd' => $systemd,
            'systemd_configured' => (bool) ($systemd['configured'] ?? false),
            'systemd_active' => (bool) ($systemd['active'] ?? false),
            'systemd_service' => (string) ($systemd['service'] ?? ''),
        ];
    }

    public static function start(
        string $name = self::DEFAULT_NAME,
        int $sleepSeconds = self::DEFAULT_SLEEP,
        int $limit = self::DEFAULT_LIMIT,
        int $staleMinutes = self::DEFAULT_STALE_MINUTES
    ): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $sleepSeconds = $sleepSeconds > 0 ? $sleepSeconds : self::defaultSleep();
        $limit = $limit > 0 ? $limit : self::defaultLimit();
        $staleMinutes = $staleMinutes > 0 ? $staleMinutes : self::defaultStaleMinutes();
        $status = self::getStatus($name);
        if (!empty($status['systemd_configured'])) {
            return self::startSystemd($name, $status);
        }

        if ($status['running']) {
            return [
                'success' => true,
                'already_running' => true,
                'message' => 'Worker deja en cours',
                'status' => $status,
            ];
        }

        $pidFile = self::getPidFilePath($name);
        $logFile = self::getLogFilePath();
        self::ensureParentDir($pidFile);
        self::ensureParentDir($logFile);

        $workerScript = self::getWorkerScriptPath();
        $phpBin = self::getPhpCliBinary();
        $launch = ProcessRunner::startBackgroundPhp($workerScript, [
            '--name=' . $name,
            '--sleep=' . max(1, $sleepSeconds),
            '--limit=' . max(1, $limit),
            '--stale-minutes=' . max(1, $staleMinutes),
            '--pid-file=' . $pidFile,
        ], $logFile, $pidFile);
        $launcherCode = (int) ($launch['code'] ?? 1);
        $output = trim((string) ($launch['output'] ?? ''));

        $status = self::waitForWorkerStart($name, 3000);

        if (!$status['running']) {
            $cronEnabled = self::enableCronSchedule($name, $limit, $staleMinutes);
            $runOnce = self::runOnce($name, $limit, $staleMinutes);
            $status = self::getStatus($name);

            if ($cronEnabled) {
                Auth::log('worker_start', "Worker planifie depuis l'interface ($name)");
                return [
                    'success' => true,
                    'message' => 'Worker active via planification cron',
                    'output' => $runOnce['output'] ?? '',
                    'diagnostic' => self::buildStartDiagnostic($phpBin, $workerScript, $pidFile, $logFile, $launcherCode),
                    'status' => $status,
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de demarrer le worker',
                'output' => $output,
                'diagnostic' => self::buildStartDiagnostic($phpBin, $workerScript, $pidFile, $logFile, $launcherCode),
                'status' => $status,
            ];
        }

        Auth::log('worker_start', "Worker lance depuis l'interface ($name)");

        return [
            'success' => true,
            'message' => 'Worker demarre',
            'status' => $status,
        ];
    }

    public static function stop(string $name = self::DEFAULT_NAME, bool $force = false): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $status = self::getStatus($name);
        if (!empty($status['systemd_configured'])) {
            return self::stopSystemd($name, $status);
        }

        $pid = !empty($status['pid']) ? (int) $status['pid'] : null;
        if (($pid === null || $pid <= 0) && !empty($status['heartbeat']['pid'])) {
            $pid = (int) $status['heartbeat']['pid'];
        }

        $cronDisabled = self::disableCronSchedule($name);

        if ($pid === null || $pid <= 0) {
            return [
                'success' => true,
                'already_stopped' => true,
                'message' => $cronDisabled ? 'Worker desactive' : 'Worker deja arrete',
                'status' => self::getStatus($name),
            ];
        }

        $signal = $force ? 9 : 15;
        self::sendSignal($pid, $signal);

        for ($i = 0; $i < 10; $i++) {
            usleep(200000);
            if (!self::isPidRunning($pid)) {
                break;
            }
        }

        if (!$force && self::isPidRunning($pid)) {
            self::sendSignal($pid, 9);
            for ($i = 0; $i < 10; $i++) {
                usleep(200000);
                if (!self::isPidRunning($pid)) {
                    break;
                }
            }
        }

        $status = self::getStatus($name);
        if ($status['running']) {
            return [
                'success' => false,
                'message' => 'Impossible d arreter le worker',
                'status' => $status,
            ];
        }

        @unlink(self::getPidFilePath($name));
        Auth::log('worker_stop', "Worker arrete depuis l'interface ($name)");

        return [
            'success' => true,
            'message' => 'Worker arrete',
            'status' => $status,
        ];
    }

    public static function restart(
        string $name = self::DEFAULT_NAME,
        int $sleepSeconds = self::DEFAULT_SLEEP,
        int $limit = self::DEFAULT_LIMIT,
        int $staleMinutes = self::DEFAULT_STALE_MINUTES
    ): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $sleepSeconds = $sleepSeconds > 0 ? $sleepSeconds : self::defaultSleep();
        $limit = $limit > 0 ? $limit : self::defaultLimit();
        $staleMinutes = $staleMinutes > 0 ? $staleMinutes : self::defaultStaleMinutes();
        $status = self::getStatus($name);
        if (!empty($status['systemd_configured'])) {
            return self::restartSystemd($name, $status);
        }

        self::stop($name);
        return self::start($name, $sleepSeconds, $limit, $staleMinutes);
    }

    // ── Phase 1 : installation cron explicite ─────────────────────────────────

    public static function installCron(
        string $name = self::DEFAULT_NAME,
        int $limit = self::DEFAULT_LIMIT,
        int $staleMinutes = self::DEFAULT_STALE_MINUTES
    ): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $limit = $limit > 0 ? $limit : self::defaultLimit();
        $staleMinutes = $staleMinutes > 0 ? $staleMinutes : self::defaultStaleMinutes();

        if (!function_exists('exec')) {
            return ['success' => false, 'message' => 'La fonction exec est desactivee dans PHP.'];
        }

        $success = self::enableCronSchedule($name, $limit, $staleMinutes);
        if ($success) {
            Auth::log('worker_cron_install', "Cron worker installe depuis l'interface ($name)");
        }

        return [
            'success' => $success,
            'message' => $success ? 'Cron worker installe avec succes' : 'Echec de l\'installation du cron worker',
            'cron_line' => self::buildCronLine($name, $limit, $staleMinutes),
            'status' => self::getStatus($name),
        ];
    }

    public static function uninstallCron(string $name = self::DEFAULT_NAME): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();

        if (!function_exists('exec')) {
            return ['success' => false, 'message' => 'La fonction exec est desactivee dans PHP.'];
        }

        $alreadyAbsent = !self::isCronEnabled($name);
        if ($alreadyAbsent) {
            return [
                'success' => true,
                'message' => 'Cron deja absent',
                'status' => self::getStatus($name),
            ];
        }

        $success = self::disableCronSchedule($name);
        if ($success) {
            Auth::log('worker_cron_uninstall', "Cron worker desinstalle depuis l'interface ($name)");
        }

        return [
            'success' => $success,
            'message' => $success ? 'Cron worker desinstalle avec succes' : 'Echec de la desinstallation du cron worker',
            'status' => self::getStatus($name),
        ];
    }

    // ── Phase 2 : generation of file systemd (installation guidee) ─────────

    public static function generateSystemdUnit(string $name = self::DEFAULT_NAME): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $phpBin = self::getPhpCliBinary();
        $workerScript = self::getWorkerScriptPath();
        $logFile = self::getLogFilePath();
        $logDir = dirname($logFile);
        $sleep = self::defaultSleep();
        $limit = self::defaultLimit();
        $staleMinutes = self::defaultStaleMinutes();
        $serviceName = self::getSystemdServiceName() ?: 'fulgurite-worker.service';

        $webUser = self::getConfiguredWebUser();
        $webGroup = self::getConfiguredWebGroup($webUser);

        $appRoot = dirname(dirname($workerScript));

        $unitLines = [
            '[Unit]',
            'Description=Fulgurite Worker Daemon',
            'After=network.target',
            '',
            '[Service]',
            'Type=simple',
            "User={$webUser}",
            "Group={$webGroup}",
            "WorkingDirectory={$appRoot}",
            "ExecStart={$phpBin} {$workerScript} --name={$name} --sleep={$sleep} --limit={$limit} --stale-minutes={$staleMinutes}",
            'Restart=on-failure',
            'RestartSec=10',
            "StandardOutput=append:{$logFile}",
            "StandardError=append:{$logFile}",
            '',
            '[Install]',
            'WantedBy=multi-user.target',
        ];
        $unitContent = implode("\n", $unitLines) . "\n";

        $serviceFilePath = "/etc/systemd/system/{$serviceName}";

        $instructionLines = [
            "# 1. Creer le fichier de service systemd",
            "sudo tee {$serviceFilePath} << 'UNIT_EOF'",
            $unitContent,
            "UNIT_EOF",
            "",
            "# 2. Creer le repertoire de logs si necessaire",
            "sudo mkdir -p {$logDir}",
            "sudo chown {$webUser}:{$webGroup} {$logDir}",
            "",
            "# 3. Recharger systemd et activer le service au demarrage",
            "sudo systemctl daemon-reload",
            "sudo systemctl enable {$serviceName}",
            "sudo systemctl start {$serviceName}",
            "",
            "# 4. Verifier l'etat",
            "sudo systemctl status {$serviceName}",
        ];

        $sudoersLine = "{$webUser} ALL=(root) NOPASSWD: "
            . "/bin/systemctl start {$serviceName}, "
            . "/bin/systemctl stop {$serviceName}, "
            . "/bin/systemctl restart {$serviceName}, "
            . "/usr/bin/systemctl start {$serviceName}, "
            . "/usr/bin/systemctl stop {$serviceName}, "
            . "/usr/bin/systemctl restart {$serviceName}";

        return [
            'success' => true,
            'unit_content' => $unitContent,
            'service_name' => $serviceName,
            'service_file' => $serviceFilePath,
            'instructions' => implode("\n", $instructionLines),
            'sudoers_hint' => $sudoersLine,
            'web_user' => $webUser,
            'web_group' => $webGroup,
            'php_bin' => $phpBin,
            'worker_script' => $workerScript,
            'log_file' => $logFile,
        ];
    }

    // ── Phase 3 : auto-installation systemd via script helper sudo ────────────

    private static function getHelperScriptPath(): string {
        return dirname(__DIR__) . '/scripts/fulgurite-worker-setup';
    }

    public static function canAutoInstallSystemd(): array {
        $helper = self::getHelperScriptPath();
        $sudo = self::getSudoBinary();

        if (!function_exists('exec')) {
            return ['can' => false, 'reason' => 'La fonction exec est desactivee dans PHP.'];
        }

        if (!is_file($helper) || !is_executable($helper)) {
            return [
                'can' => false,
                'reason' => 'Script helper absent ou non executable : ' . $helper,
                'helper' => $helper,
            ];
        }

        if ($sudo === '') {
            return ['can' => false, 'reason' => 'Binaire sudo introuvable.', 'helper' => $helper];
        }

        $checkResult = ProcessRunner::run([$sudo, '-n', $helper, '--check'], ['validate_binary' => false]);
        $testCode = (int) ($checkResult['code'] ?? 1);

        if ($testCode !== 0) {
            return [
                'can' => false,
                'reason' => 'Le sudoers n\'autorise pas l\'execution sans mot de passe. '
                    . 'Ajoutez dans /etc/sudoers.d/fulgurite-worker : '
                    . self::getConfiguredWebUser()
                    . ' ALL=(root) NOPASSWD: ' . $helper,
                'helper' => $helper,
            ];
        }

        return ['can' => true, 'reason' => '', 'helper' => $helper];
    }

    public static function installSystemd(string $name = self::DEFAULT_NAME): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $check = self::canAutoInstallSystemd();

        if (!$check['can']) {
            return ['success' => false, 'message' => $check['reason'], 'status' => self::getStatus($name)];
        }

        $helper = self::getHelperScriptPath();
        $sudo = self::getSudoBinary();
        $phpBin = self::getPhpCliBinary();
        $workerScript = self::getWorkerScriptPath();
        $logFile = self::getLogFilePath();
        $serviceName = self::getSystemdServiceName() ?: 'fulgurite-worker.service';

        $webUser = self::getConfiguredWebUser();
        $webGroup = self::getConfiguredWebGroup($webUser);

        $parts = [
            $sudo, '-n',
            $helper, 'install',
            '--service=' . $serviceName,
            '--php-bin=' . $phpBin,
            '--worker-script=' . $workerScript,
            '--log-file=' . $logFile,
            '--user=' . $webUser,
            '--group=' . $webGroup,
            '--name=' . $name,
            '--sleep=' . self::defaultSleep(),
            '--limit=' . self::defaultLimit(),
            '--stale-minutes=' . self::defaultStaleMinutes(),
        ];

        $result = ProcessRunner::run($parts, ['validate_binary' => false]);
        $code = (int) ($result['code'] ?? 1);
        $outputStr = trim((string) ($result['output'] ?? ''));

        if ($code === 0) {
            Auth::log('worker_systemd_install', "Worker systemd installe depuis l'interface ($serviceName)");
            return [
                'success' => true,
                'message' => 'Worker systemd installe et demarre',
                'output' => $outputStr,
                'status' => self::getStatus($name),
            ];
        }

        return [
            'success' => false,
            'message' => 'Echec installation worker systemd',
            'output' => $outputStr,
            'status' => self::getStatus($name),
        ];
    }

    public static function uninstallSystemd(string $name = self::DEFAULT_NAME): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $check = self::canAutoInstallSystemd();

        if (!$check['can']) {
            return ['success' => false, 'message' => $check['reason'], 'status' => self::getStatus($name)];
        }

        $helper = self::getHelperScriptPath();
        $sudo = self::getSudoBinary();
        $serviceName = self::getSystemdServiceName() ?: 'fulgurite-worker.service';

        $parts = [
            $sudo, '-n',
            $helper, 'uninstall',
            '--service=' . $serviceName,
        ];

        $result = ProcessRunner::run($parts, ['validate_binary' => false]);
        $code = (int) ($result['code'] ?? 1);
        $outputStr = trim((string) ($result['output'] ?? ''));

        if ($code === 0) {
            Auth::log('worker_systemd_uninstall', "Worker systemd desinstalle depuis l'interface ($serviceName)");
            return [
                'success' => true,
                'message' => 'Worker systemd desinstalle',
                'output' => $outputStr,
                'status' => self::getStatus($name),
            ];
        }

        return [
            'success' => false,
            'message' => 'Echec desinstallation worker systemd',
            'output' => $outputStr,
            'status' => self::getStatus($name),
        ];
    }

    public static function runOnce(
        string $name = self::DEFAULT_NAME,
        int $limit = self::DEFAULT_LIMIT,
        int $staleMinutes = self::DEFAULT_STALE_MINUTES
    ): array {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        $limit = $limit > 0 ? $limit : self::defaultLimit();
        $staleMinutes = $staleMinutes > 0 ? $staleMinutes : self::defaultStaleMinutes();
        $phpBin = self::getPhpCliBinary();
        $workerScript = self::getWorkerScriptPath();
        $result = ProcessRunner::run([
            $phpBin,
            $workerScript,
            '--once',
            '--name=' . $name,
            '--limit=' . max(1, $limit),
            '--stale-minutes=' . max(1, $staleMinutes),
        ]);
        $code = (int) ($result['code'] ?? 1);

        return [
            'success' => $code === 0,
            'message' => $code === 0 ? 'Execution unique terminee' : 'Execution unique en erreur',
            'output' => (string) ($result['output'] ?? ''),
            'status' => self::getStatus($name),
        ];
    }

    public static function getPidFilePath(string $name = self::DEFAULT_NAME): string {
        return dirname(DB_PATH) . '/run/worker-' . self::sanitizeName($name) . '.pid';
    }

    public static function getLockFilePath(string $name = self::DEFAULT_NAME): string {
        return dirname(DB_PATH) . '/run/worker-' . self::sanitizeName($name) . '.lock';
    }

    public static function getLogFilePath(): string {
        return dirname(DB_PATH) . '/logs/job-worker.log';
    }

    public static function getWorkerScriptPath(): string {
        return dirname(__DIR__) . '/public/worker.php';
    }

    public static function getPhpCliBinary(): string {
        $candidates = [];

        if (defined('PHP_CLI_BIN') && PHP_CLI_BIN !== '') {
            $candidates[] = PHP_CLI_BIN;
        }

        if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
            $candidates[] = rtrim(PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php';
        }

        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $basename = strtolower(basename($candidate));
            if (str_contains($basename, 'php-fpm')) {
                continue;
            }

            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return ProcessRunner::resolvePhpCliBinary();
    }

    private static function startSystemd(string $name, array $status): array {
        if (!empty($status['systemd_active'])) {
            return [
                'success' => true,
                'already_running' => true,
                'message' => 'Worker systemd deja actif',
                'status' => $status,
            ];
        }

        $result = self::runSystemdAction('start');
        $newStatus = self::waitForSystemdState($name, true, 5000);

        if (!empty($newStatus['systemd_active'])) {
            self::disableCronSchedule($name);
            Auth::log('worker_start', "Worker systemd demarre depuis l'interface ($name)");
            return [
                'success' => true,
                'message' => 'Worker systemd demarre',
                'status' => $newStatus,
                'output' => trim((string) ($result['output'] ?? '')),
            ];
        }

        return [
            'success' => false,
            'message' => 'Impossible de demarrer le worker systemd',
            'diagnostic' => self::buildSystemdDiagnostic($result, 'start'),
            'status' => $newStatus,
        ];
    }

    private static function stopSystemd(string $name, array $status): array {
        $result = self::runSystemdAction('stop');
        $newStatus = self::waitForSystemdState($name, false, 5000);

        if (empty($newStatus['systemd_active'])) {
            self::disableCronSchedule($name);
            Auth::log('worker_stop', "Worker systemd arrete depuis l'interface ($name)");
            return [
                'success' => true,
                'message' => 'Worker systemd arrete',
                'status' => $newStatus,
                'output' => trim((string) ($result['output'] ?? '')),
            ];
        }

        return [
            'success' => false,
            'message' => 'Impossible d arreter le worker systemd',
            'diagnostic' => self::buildSystemdDiagnostic($result, 'stop'),
            'status' => $newStatus,
        ];
    }

    private static function restartSystemd(string $name, array $status): array {
        $result = self::runSystemdAction('restart');
        $newStatus = self::waitForSystemdState($name, true, 5000);

        if (!empty($newStatus['systemd_active'])) {
            self::disableCronSchedule($name);
            Auth::log('worker_restart', "Worker systemd redemarre depuis l'interface ($name)");
            return [
                'success' => true,
                'message' => 'Worker systemd redemarre',
                'status' => $newStatus,
                'output' => trim((string) ($result['output'] ?? '')),
            ];
        }

        return [
            'success' => false,
            'message' => 'Impossible de redemarrer le worker systemd',
            'diagnostic' => self::buildSystemdDiagnostic($result, 'restart'),
            'status' => $newStatus,
        ];
    }

    private static function getSystemdStatus(): array {
        $service = self::getSystemdServiceName();
        if ($service === '') {
            return [
                'configured' => false,
                'service' => '',
                'active' => false,
                'main_pid' => null,
                'load_state' => '',
                'active_state' => '',
                'sub_state' => '',
                'unit_file_state' => '',
                'error' => '',
                'command' => '',
            ];
        }

        if (!function_exists('exec')) {
            return [
                'configured' => true,
                'service' => $service,
                'active' => false,
                'main_pid' => null,
                'load_state' => '',
                'active_state' => '',
                'sub_state' => '',
                'unit_file_state' => '',
                'error' => 'La fonction exec est desactivee dans PHP.',
                'command' => '',
            ];
        }

        $result = self::runSystemdCommand([
            'show',
            $service,
            '--no-page',
            '--property=LoadState,ActiveState,SubState,MainPID,UnitFileState',
        ], true);

        $parsed = self::parseSystemdShowOutput($result['output'] ?? '');

        return [
            'configured' => true,
            'service' => $service,
            'active' => ($parsed['ActiveState'] ?? '') === 'active',
            'main_pid' => ctype_digit((string) ($parsed['MainPID'] ?? '')) ? (int) $parsed['MainPID'] : null,
            'load_state' => (string) ($parsed['LoadState'] ?? ''),
            'active_state' => (string) ($parsed['ActiveState'] ?? ''),
            'sub_state' => (string) ($parsed['SubState'] ?? ''),
            'unit_file_state' => (string) ($parsed['UnitFileState'] ?? ''),
            'error' => trim((string) ($result['error'] ?? '')),
            'command' => (string) ($result['command'] ?? ''),
        ];
    }

    private static function parseSystemdShowOutput(string $output): array {
        $values = [];
        foreach (preg_split('/\r?\n/', trim($output)) as $line) {
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value);
        }
        return $values;
    }

    private static function runSystemdAction(string $action): array {
        return self::runSystemdCommand([$action, self::getSystemdServiceName()], false);
    }

    private static function runSystemdCommand(array $parts, bool $readOnly): array {
        $service = self::getSystemdServiceName();
        $systemctl = self::getSystemctlBinary();
        $sudo = self::getSudoBinary();
        $commands = [];

        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $commands[] = array_merge([$systemctl], $parts);
        } else {
            if (!$readOnly && self::shouldUseSudoForSystemd() && $sudo !== '') {
                $commands[] = array_merge([$sudo, '-n', $systemctl], $parts);
            }
            $commands[] = array_merge([$systemctl], $parts);
            if ($readOnly && self::shouldUseSudoForSystemd() && $sudo !== '') {
                $commands[] = array_merge([$sudo, '-n', $systemctl], $parts);
            }
        }

        $last = [
            'success' => false,
            'code' => 127,
            'output' => '',
            'error' => '',
            'command' => '',
            'service' => $service,
        ];

        foreach ($commands as $command) {
            $result = ProcessRunner::run($command, ['validate_binary' => false]);
            $code = (int) ($result['code'] ?? 1);
            $combined = trim((string) ($result['output'] ?? ''));
            $last = [
                'success' => $code === 0,
                'code' => $code,
                'output' => $combined,
                'error' => $code === 0 ? '' : $combined,
                'command' => ProcessRunner::commandToString($command),
                'service' => $service,
            ];
            if ($code === 0) {
                break;
            }
        }

        return $last;
    }

    private static function waitForSystemdState(string $name, bool $shouldBeActive, int $timeoutMs = 5000): array {
        $deadline = microtime(true) + ($timeoutMs / 1000);
        $status = self::getStatus($name);

        while ((bool) ($status['systemd_active'] ?? false) !== $shouldBeActive && microtime(true) < $deadline) {
            usleep(250000);
            $status = self::getStatus($name);
        }

        return $status;
    }

    private static function getControlLabel(string $controlMode, bool $systemdRunning, bool $cronScheduled): string {
        return match ($controlMode) {
            'systemd' => $systemdRunning ? 'Systemd' : 'Systemd configure',
            'daemon' => 'Daemon web',
            'cron' => $cronScheduled ? 'Cron' : 'Arrete',
            default => 'Arrete',
        };
    }

    private static function getSystemdServiceName(): string {
        if (defined('WORKER_SYSTEMD_SERVICE') && trim((string) WORKER_SYSTEMD_SERVICE) !== '') {
            return trim((string) WORKER_SYSTEMD_SERVICE);
        }
        return '';
    }

    private static function getSystemctlBinary(): string {
        $candidates = [];
        if (defined('WORKER_SYSTEMCTL_BIN') && trim((string) WORKER_SYSTEMCTL_BIN) !== '') {
            $candidates[] = trim((string) WORKER_SYSTEMCTL_BIN);
        }
        $candidates[] = '/bin/systemctl';
        $candidates[] = '/usr/bin/systemctl';

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return '/bin/systemctl';
    }

    private static function getSudoBinary(): string {
        $candidates = [];
        if (defined('WORKER_SUDO_BIN') && trim((string) WORKER_SUDO_BIN) !== '') {
            $candidates[] = trim((string) WORKER_SUDO_BIN);
        }
        $candidates[] = '/usr/bin/sudo';
        $candidates[] = '/bin/sudo';

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private static function shouldUseSudoForSystemd(): bool {
        return !defined('WORKER_SYSTEMD_USE_SUDO') || (bool) WORKER_SYSTEMD_USE_SUDO;
    }

    private static function getConfiguredWebUser(): string {
        $env = getenv('FULGURITE_WEB_USER');
        if (is_string($env) && self::isValidSystemIdentity($env)) {
            return $env;
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $info = posix_getpwuid(posix_geteuid());
            if (!empty($info['name']) && self::isValidSystemIdentity((string) $info['name'])) {
                return (string) $info['name'];
            }
        }

        return 'www-data';
    }

    private static function getConfiguredWebGroup(string $fallbackUser): string {
        $env = getenv('FULGURITE_WEB_GROUP');
        if (is_string($env) && self::isValidSystemIdentity($env)) {
            return $env;
        }

        if (function_exists('posix_getgrgid') && function_exists('posix_getegid')) {
            $info = posix_getgrgid(posix_getegid());
            if (!empty($info['name']) && self::isValidSystemIdentity((string) $info['name'])) {
                return (string) $info['name'];
            }
        }

        return $fallbackUser !== '' ? $fallbackUser : 'www-data';
    }

    private static function isValidSystemIdentity(string $value): bool {
        return preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', trim($value)) === 1;
    }

    private static function buildSystemdDiagnostic(array $result, string $action): string {
        $service = self::getSystemdServiceName();
        $parts = [
            'mode=systemd',
            'service=' . $service,
            'action=' . $action,
            'command=' . (string) ($result['command'] ?? ''),
            'code=' . (int) ($result['code'] ?? 1),
        ];

        $output = trim((string) ($result['output'] ?? ''));
        if ($output !== '') {
            $parts[] = 'output=' . $output;
        }

        if (str_contains(strtolower($output), 'password is required') || str_contains(strtolower($output), 'a password is required')) {
            $parts[] = 'hint=Ajoute une regle sudoers pour autoriser ' . self::getConfiguredWebUser() . ' a piloter ' . $service . ' sans mot de passe.';
        } elseif (str_contains(strtolower($output), 'interactive authentication required') || str_contains(strtolower($output), 'access denied')) {
            $parts[] = 'hint=Autorise ' . self::getConfiguredWebUser() . ' a executer sudo -n systemctl start/stop/restart ' . $service . '.';
        } elseif ($output === '') {
            $parts[] = 'hint=Verifie que le service existe et que ' . self::getConfiguredWebUser() . ' peut executer systemctl ou sudo -n systemctl.';
        }

        return implode(' | ', $parts);
    }

    private static function buildShellCommand(array $parts): string {
        return implode(' ', array_map('escapeshellarg', $parts));
    }

    private static function readPidFile(string $pidFile): ?int {
        if (!is_file($pidFile)) {
            return null;
        }

        $raw = trim((string) @file_get_contents($pidFile));
        return ctype_digit($raw) ? (int) $raw : null;
    }

    private static function isPidRunning(int $pid): bool {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        $result = ProcessRunner::run(['ps', '-p', (string) $pid, '-o', 'pid='], ['validate_binary' => false]);
        $output = preg_split('/\r?\n/', trim((string) ($result['output'] ?? ''))) ?: [];
        return ((int) ($result['code'] ?? 1)) === 0 && !empty(array_filter(array_map('trim', $output)));
    }

    private static function sendSignal(int $pid, int $signal): void {
        if ($pid <= 0) {
            return;
        }

        if (function_exists('posix_kill')) {
            @posix_kill($pid, $signal);
            return;
        }

        ProcessRunner::run(['kill', '-' . (int) $signal, (string) $pid], ['validate_binary' => false]);
    }

    private static function sanitizeName(string $name): string {
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        return $sanitized !== '' ? $sanitized : self::DEFAULT_NAME;
    }

    private static function ensureParentDir(string $path): void {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private static function getCronMarker(string $name): string {
        return self::CRON_MARKER_PREFIX . self::sanitizeName($name);
    }

    private static function buildCronLine(string $name, int $limit, int $staleMinutes): string {
        return sprintf(
            '* * * * * %s %s --once --name=%s --limit=%d --stale-minutes=%d >> %s 2>&1',
            escapeshellarg(self::getPhpCliBinary()),
            escapeshellarg(self::getWorkerScriptPath()),
            escapeshellarg($name),
            max(1, $limit),
            max(1, $staleMinutes),
            escapeshellarg(self::getLogFilePath())
        );
    }

    public static function getCronLine(string $name = self::DEFAULT_NAME, int $limit = self::DEFAULT_LIMIT, int $staleMinutes = self::DEFAULT_STALE_MINUTES): string {
        $name = trim($name) !== '' ? $name : AppConfig::workerDefaultName();
        return self::buildCronLine($name, $limit, $staleMinutes);
    }

    public static function isCronEnabled(string $name = self::DEFAULT_NAME): bool {
        $current = self::readCrontab();
        return str_contains($current, self::getCronMarker($name));
    }

    private static function enableCronSchedule(string $name, int $limit, int $staleMinutes): bool {
        $current = self::readCrontab();
        $marker = self::getCronMarker($name);
        $cronLine = self::buildCronLine($name, $limit, $staleMinutes);
        $lines = preg_split('/\r?\n/', $current);
        $cleaned = [];
        foreach ($lines as $line) {
            if (str_contains($line, $marker) || str_contains($line, self::getWorkerScriptPath())) {
                continue;
            }
            $cleaned[] = $line;
        }

        $cleaned[] = $marker;
        $cleaned[] = $cronLine;
        return self::writeCrontab(implode("\n", array_filter($cleaned, static fn($line) => $line !== '')) . "\n");
    }

    private static function disableCronSchedule(string $name): bool {
        $current = self::readCrontab();
        $marker = self::getCronMarker($name);
        $lines = preg_split('/\r?\n/', $current);
        $cleaned = [];
        $changed = false;
        foreach ($lines as $line) {
            if (str_contains($line, $marker) || str_contains($line, self::getWorkerScriptPath())) {
                $changed = true;
                continue;
            }
            $cleaned[] = $line;
        }

        if (!$changed) {
            return false;
        }

        return self::writeCrontab(implode("\n", array_filter($cleaned, static fn($line) => $line !== '')) . "\n");
    }

    private static function readCrontab(): string {
        $result = ProcessRunner::run(['crontab', '-l'], ['validate_binary' => false]);
        return ((int) ($result['code'] ?? 1)) === 0 ? trim((string) ($result['stdout'] ?? '')) : '';
    }

    private static function writeCrontab(string $content): bool {
        $tmpFile = tempnam(sys_get_temp_dir(), 'fulgurite_worker_cron_');
        if ($tmpFile === false) {
            return false;
        }

        file_put_contents($tmpFile, $content);
        $result = ProcessRunner::run(['crontab', $tmpFile], ['validate_binary' => false]);
        @unlink($tmpFile);
        return ((int) ($result['code'] ?? 1)) === 0;
    }

    private static function isHeartbeatRecent(?array $heartbeat): bool {
        if (empty($heartbeat['at'])) {
            return false;
        }

        try {
            $at = new DateTime((string) $heartbeat['at']);
        return ((new DateTime())->getTimestamp() - $at->getTimestamp()) <= self::heartbeatStaleSeconds();
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function waitForWorkerStart(string $name, int $timeoutMs = 3000): array {
        $deadline = microtime(true) + ($timeoutMs / 1000);
        $status = self::getStatus($name);

        while (!$status['running'] && microtime(true) < $deadline) {
            usleep(250000);
            $status = self::getStatus($name);
        }

        return $status;
    }

    private static function hasSetsid(): bool {
        static $hasSetsid = null;
        if ($hasSetsid !== null) {
            return $hasSetsid;
        }

        $hasSetsid = ProcessRunner::locateBinary('setsid', ['/usr/bin/setsid', '/bin/setsid']) !== '';
        return $hasSetsid;
    }

    private static function buildStartDiagnostic(
        string $phpBin,
        string $workerScript,
        string $pidFile,
        string $logFile,
        int $launcherCode
    ): string {
        $details = [
            'php=' . $phpBin,
            'worker=' . $workerScript,
            'pid_file=' . $pidFile,
            'log_file=' . $logFile,
            'launcher_code=' . $launcherCode,
            'php_exists=' . (is_file($phpBin) ? 'yes' : 'no'),
            'php_executable=' . (is_executable($phpBin) ? 'yes' : 'no'),
            'worker_exists=' . (is_file($workerScript) ? 'yes' : 'no'),
        ];

        $logTail = self::readLogTail($logFile, 8);
        if ($logTail !== '') {
            $details[] = 'log_tail=' . $logTail;
        }

        return implode(' | ', $details);
    }

    private static function readLogTail(string $path, int $limit = 8): string {
        if (!is_file($path)) {
            return '';
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines) || empty($lines)) {
            return '';
        }

        return implode(' || ', array_slice($lines, -max(1, $limit)));
    }
}
