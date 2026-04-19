<?php
declare(strict_types=1);

/**
 * ResetupWizard — Wizard of re-configuration of Fulgurite for the
 * already installed environments (FPM user change, migration,
 * reconfiguration agent secrets, etc.).
 *
 * Steps:
 * 1. STEP_AUTH - Admin authentication required
 * 2. STEP_DIAGNOSTIC — Current state diagnostic
 * 3. STEP_SELECTION — Action selection
 * 4. STEP_SUDO — Sudo rights verification/acquisition
 * 5. STEP_PERMISSIONS - Apply permissions
 * 6. STEP_WORKER — Reconfiguration of worker
 * 7. STEP_AGENT — Reconfiguration of the agent secrets
 */
final class ResetupWizard
{
    // ── Step constants ──────────────────────────────────────────────────────────

    public const STEP_AUTH        = 1;
    public const STEP_DIAGNOSTIC  = 2;
    public const STEP_SELECTION   = 3;
    public const STEP_SUDO        = 4;
    public const STEP_PERMISSIONS = 5;
    public const STEP_WORKER      = 6;
    public const STEP_AGENT       = 7;

    // ── Environment detection ───────────────────────────────────────────────────

    /**
     * Detects if the application runs in a Docker/LXC/containerd container.
     * @return bool True when a containerized environment is detected.
     */
    public static function isDocker(): bool
    {
        if (file_exists('/.dockerenv')) {
            return true;
        }

        $cgroupPath = '/proc/1/cgroup';
        if (!is_readable($cgroupPath)) {
            return false;
        }

        $content = @file_get_contents($cgroupPath);
        if ($content === false) {
            return false;
        }

        return str_contains($content, 'docker')
            || str_contains($content, 'lxc')
            || str_contains($content, 'containerd');
    }

    /**
     * Detects current PHP-FPM / web user.
     *
     * Priority: posix_getpwuid → FULGURITE_WEB_USER env var → 'www-data'.
     *
     * @return string Username.
     */
    public static function detectFpmUser(): string
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $info = @posix_getpwuid(posix_geteuid());
            if (is_array($info) && isset($info['name']) && (string) $info['name'] !== '') {
                return (string) $info['name'];
            }
        }

        $envUser = (string) (getenv('FULGURITE_WEB_USER') ?: '');
        if ($envUser !== '') {
            return $envUser;
        }

        return 'www-data';
    }

    /**
     * Detects current PHP-FPM / web group.
     *
     * Priority: posix_getgrgid → 'www-data'.
     *
     * @return string Group name.
     */
    public static function detectFpmGroup(): string
    {
        if (function_exists('posix_getegid') && function_exists('posix_getgrgid')) {
            $info = @posix_getgrgid(posix_getegid());
            if (is_array($info) && isset($info['name']) && (string) $info['name'] !== '') {
                return (string) $info['name'];
            }
        }

        return 'www-data';
    }

    /**
     * Detects the current Fulgurite worker control mode.
     *
     * @return string Value of 'control_mode': 'systemd' | 'daemon' | 'cron' | 'stopped'.
     */
    public static function detectWorkerMode(): string
    {
        try {
            $status = WorkerManager::getStatus();
            return (string) ($status['control_mode'] ?? 'stopped');
        } catch (\Throwable) {
            return 'stopped';
        }
    }

    /**
     * Checks the state of the Fulgurite secrets agent.
     *
     * @return array{configured: bool, socket_path: string, reachable: bool}
     */
    public static function detectSecretAgent(): array
    {
        $socketPath = SecretStore::agentSocketPath();

        $configured = $socketPath !== '' && file_exists($socketPath);
        $reachable  = false;

        if ($configured) {
            // Tentative of connection TCP/socket for check the disponibilite
            $socket = @fsockopen('unix://'. $socketPath, -1, $errno, $errstr, 2.0);
            if ($socket !== false) {
                $reachable = true;
                fclose($socket);
            }
        }

        return [
            'configured'  => $configured,
            'socket_path' => $socketPath,
            'reachable'   => $reachable,
        ];
    }

    // ── Diagnostic global ──────────────────────────────────────────────────────

    /**
     * Executes full Fulgurite environment diagnostic.
     *
     * @return array{
     * is_docker: bool,
     * fpm_user: string,
     * fpm_group: string,
     * worker_mode: string,
     * worker_running: bool,
     * secret_agent: array,
     * app_directories: array,
     * php_version: string,
     * os: string
     * }
     */
    public static function runDiagnostic(): array
    {
        $fpmUser  = self::detectFpmUser();
        $fpmGroup = self::detectFpmGroup();

        // Worker status
        $workerRunning = false;
        $workerMode    = 'stopped';
        try {
            $workerStatus  = WorkerManager::getStatus();
            $workerRunning = (bool) ($workerStatus['running'] ?? false);
            $workerMode    = (string) ($workerStatus['control_mode'] ?? 'stopped');
        } catch (\Throwable) {
            // Silent fallback: worker inaccessible
        }

        // Application directory permissions
        $appDirectories = [];
        foreach (ResetupPermissions::getAppDirectories() as $dir) {
            $ownership = ResetupPermissions::getCurrentOwnership($dir);
            $appDirectories[] = array_merge(['path' => $dir], $ownership);
        }

        Auth::log(
            'resetup_diagnostic',
            sprintf(
                'Diagnostic re-configuration lancé : user=%s group=%s worker=%s docker=%s',
                $fpmUser,
                $fpmGroup,
                $workerMode,
                self::isDocker() ? 'yes' : 'no'
            ),
            'info'
        );

        return [
            'is_docker'       => self::isDocker(),
            'fpm_user'        => $fpmUser,
            'fpm_group'       => $fpmGroup,
            'worker_mode'     => $workerMode,
            'worker_running'  => $workerRunning,
            'secret_agent'    => self::detectSecretAgent(),
            'app_directories' => $appDirectories,
            'php_version'     => PHP_VERSION,
            'os'              => php_uname('s') . ' ' . php_uname('r'),
        ];
    }

    // ── Labels ─────────────────────────────────────────────────────────────────

    /**
     * Returns the human-readable label of a wizard step.
     *
     * @param int $step Constante STEP_*.
     *
     * @return string Human-readable step label.
     */
    public static function stepLabel(int $step): string
    {
        return match ($step) {
            self::STEP_AUTH        => 'Authentification',
            self::STEP_DIAGNOSTIC  => 'Diagnostic',
            self::STEP_SELECTION   => 'Sélection des actions',
            self::STEP_SUDO        => 'Vérification sudo',
            self::STEP_PERMISSIONS => 'Permissions',
            self::STEP_WORKER      => 'Worker Fulgurite',
            self::STEP_AGENT       => 'Agent secrets',
            default                => 'Étape inconnue',
        };
    }

    // ── Validation ─────────────────────────────────────────────────────────────

    /**
     * Validates a UNIX username or group name.
     *
     * @param string $value Value to validate.
     *
     * @return bool true if validates.
     */
    public static function validateUser(string $value): bool
    {
        return preg_match('/^[a-z_][a-z0-9_-]{0,31}$/i', $value) === 1;
    }

    /**
     * Validates that a path is absolute and contains no traversal.
     *
     * @param string $value Path to validate.
     *
     * @return bool true if validates.
     */
    public static function validatePath(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (!str_starts_with($value, '/')) {
            return false;
        }

        if (str_contains($value, '..')) {
            return false;
        }

        return true;
    }

    // ── Reconfiguration worker ─────────────────────────────────────────────────

    /**
     * Stops worker, updates user/group configuration via DB when possible,
     * then restarts worker.
     *
     * @param string $user New FPM/web user.
     * @param string $group New FPM/web group.
     *
     * @return array{success: bool, steps: array<array{label: string, success: bool, output: string}>}
     */
    public static function applyWorkerReconfig(string $user, string $group): array
    {
        $steps = [];

        if (!self::validateUser($user)) {
            return [
                'success' => false,
                'steps'   => [['label' => 'Validation', 'success' => false, 'output' => 'Nom d\'utilisateur invalide : ' . $user]],
            ];
        }

        if (!self::validateUser($group)) {
            return [
                'success' => false,
                'steps'   => [['label' => 'Validation', 'success' => false, 'output' => 'Nom de groupe invalide : ' . $group]],
            ];
        }

        Auth::log(
            'resetup_worker_reconfig_start',
            sprintf('Début reconfiguration worker : user=%s group=%s', $user, $group),
            'warning'
        );

        // Step 1: Stop the worker
        $stopResult = self::tryStopWorker();
        $steps[]    = [
            'label'   => 'Arrêt du worker',
            'success' => $stopResult['success'],
            'output'  => $stopResult['message'],
        ];

        // Step 2: Update configuration (best-effort via DB)
        $configResult = self::tryUpdateWorkerConfig($user, $group);
        $steps[]      = [
            'label'   => 'Mise à jour configuration',
            'success' => $configResult['success'],
            'output'  => $configResult['message'],
        ];

        // Step 3: Restart the worker
        $restartResult = self::tryRestartWorker();
        $steps[]       = [
            'label'   => 'Redémarrage du worker',
            'success' => $restartResult['success'],
            'output'  => $restartResult['message'],
        ];

        $overallSuccess = $restartResult['success'];

        Auth::log(
            'resetup_worker_reconfig_done',
            sprintf(
                'Reconfiguration worker terminée : user=%s group=%s — %s',
                $user,
                $group,
                $overallSuccess ? 'succès' : 'échec'
            ),
            $overallSuccess ? 'info' : 'warning'
        );

        return [
            'success' => $overallSuccess,
            'steps'   => $steps,
        ];
    }

    // ── Reconfiguration agent secrets ──────────────────────────────────────────

    /**
     * Checks secrets agent socket and attempts a systemd restart if
     * the service is available.
     *
     * @param string $socketPath path of socket a utiliser.
     *
     * @return array{success: bool, configured: bool, reachable: bool, message: string}
     */
    public static function applyAgentReconfig(string $socketPath): array
    {
        if (!self::validatePath($socketPath)) {
            return [
                'success'    => false,
                'configured' => false,
                'reachable'  => false,
                'message'    => 'Chemin de socket invalide : ' . $socketPath,
            ];
        }

        Auth::log(
            'resetup_agent_reconfig',
            'Reconfiguration agent secrets — socket : ' . $socketPath,
            'warning'
        );

        $configured = file_exists($socketPath);
        $reachable  = false;

        if ($configured) {
            $sock = @fsockopen('unix://'. $socketPath, -1, $errno, $errstr, 2.0);
            if ($sock !== false) {
                $reachable = true;
                fclose($sock);
            }
        }

        // if socket is unreachable, attempt restart via systemd
        if (!$reachable) {
            $restartAttempted = false;
            $restartOutput    = '';

            $systemctl = self::findSystemctl();
            $service   = defined('WORKER_SYSTEMD_SERVICE')
                ? (string) WORKER_SYSTEMD_SERVICE
                : 'fulgurite-worker.service';

            // the agent secrets can avoir son propre service
            $agentService = 'fulgurite-secret-agent.service';

            if ($systemctl !== '') {
                $restartAttempted = true;
                $result = ProcessRunner::run(
                    [$systemctl, '--user', 'restart', $agentService],
                    ['timeout' => 10]
                );

                if (!$result['success']) {
                    // Tentative without --user (service systeme)
                    $result = ProcessRunner::run(
                        [$systemctl, 'restart', $agentService],
                        ['timeout' => 10]
                    );
                }

                $restartOutput = $result['output'];

                if ($result['success']) {
                    // Reattente courte for laisser the socket se create
                    usleep(500000);
                    $configured = file_exists($socketPath);
                    if ($configured) {
                        $sock = @fsockopen('unix://'. $socketPath, -1, $errno, $errstr, 2.0);
                        if ($sock !== false) {
                            $reachable = true;
                            fclose($sock);
                        }
                    }
                }
            }

            $message = $reachable
                ? 'Agent redémarré et joignable.'
                : ($restartAttempted
                    ? 'Restart tenté mais agent non joignable : ' . $restartOutput
                    : 'Agent non joignable et systemd non disponible.');

            Auth::log(
                'resetup_agent_reconfig_result',
                sprintf('Agent secrets — socket=%s joignable=%s', $socketPath, $reachable ? 'oui' : 'non'),
                $reachable ? 'info' : 'warning'
            );

            return [
                'success'    => $reachable,
                'configured' => $configured,
                'reachable'  => $reachable,
                'message'    => $message,
            ];
        }

        Auth::log(
            'resetup_agent_reconfig_result',
            sprintf('Agent secrets déjà joignable — socket=%s', $socketPath),
            'info'
        );

        return [
            'success'    => true,
            'configured' => true,
            'reachable'  => true,
            'message'    => 'Agent secrets opérationnel.',
        ];
    }

    // ── Helpers prives ─────────────────────────────────────────────────────────

    /**
     * Tries of arreter the worker of maniere propre.     *
     * @return array{success: bool, message: string}
     */
    private static function tryStopWorker(): array
    {
        try {
            $result = WorkerManager::stop();
            return [
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? $result['output'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur arrêt worker : ' . $e->getMessage()];
        }
    }

    /**
     * Tries of set a day the configuration worker (user/group) en base.     *
     * Uses DatabaseConfigWriter if available, otherwise returns a success
     * partiel with indication of action manuelle.
     *
     * @return array{success: bool, message: string}
     */
    private static function tryUpdateWorkerConfig(string $user, string $group): array
    {
        try {
            if (class_exists('DatabaseConfigWriter')) {
                DatabaseConfigWriter::set('fulgurite_web_user', $user);
                DatabaseConfigWriter::set('fulgurite_web_group', $group);

                return [
                    'success' => true,
                    'message' => sprintf('Configuration mise à jour : FULGURITE_WEB_USER=%s FULGURITE_WEB_GROUP=%s', $user, $group),
                ];
            }

            // Fallback: update via Database::setSetting if available
            if (class_exists('Database')) {
                try {
                    Database::setSetting('fulgurite_web_user', $user);
                    Database::setSetting('fulgurite_web_group', $group);

                    return [
                        'success' => true,
                        'message' => sprintf('Configuration enregistrée en base : user=%s group=%s', $user, $group),
                    ];
                } catch (\Throwable) {
                    // Continue to fallback
                }
            }

            // Last resort: note that manual update is required
            return [
                'success' => true,
                'message' => sprintf(
                    'Mise à jour auto non disponible. Définissez manuellement FULGURITE_WEB_USER=%s et FULGURITE_WEB_GROUP=%s dans votre configuration.',
                    $user,
                    $group
                ),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur mise à jour config : ' . $e->getMessage()];
        }
    }

    /**
     * Tries of redemarrer the worker.     *
     * @return array{success: bool, message: string}
     */
    private static function tryRestartWorker(): array
    {
        try {
            $result = WorkerManager::restart();
            return [
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? $result['output'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erreur redémarrage worker : ' . $e->getMessage()];
        }
    }

    /**
     * Localise the binaire systemctl on the systeme.
     *
     * @return string path absolu to systemctl or chaine vide if introuvable.
     */
    private static function findSystemctl(): string
    {
        foreach (['/bin/systemctl', '/usr/bin/systemctl'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        $found = ProcessRunner::locateBinary('systemctl');
        return $found !== '' ? $found : '';
    }
}
