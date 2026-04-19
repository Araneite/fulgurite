<?php
declare(strict_types=1);

/**
 * ResetupPermissions — permission management on critical Fulgurite directories
 * within the reconfiguration wizard context.
 *
 * Security principles:
 * - Strict path validation (absolute, no directory traversal).
 * - User/group name validation via regex.
 * - Sudo password is erased immediately after usage.
 * - All actions are traced via Auth::log().
 */
final class ResetupPermissions
{
    /** Target octal mode for application directories. */
    private const TARGET_MODE = '750';

    /** Regex of validation of noms UNIX (user/group). */
    private const USER_GROUP_REGEX = '/^[a-z_][a-z0-9_-]{0,31}$/i';

    // ── Application directories ───────────────────────────────────────────

    /**
     * Returns the liste of directories critical of the application.
     *
     * All the paths are derives of the constante DB_PATH.
     *
     * @return string[]
     */
    public static function getAppDirectories(): array
    {
        $base = dirname(DB_PATH);
        $paths = [
            $base,
            $base . '/run',
            $base . '/logs',
            $base . '/passwords',
            $base . '/exports',
        ];

        $policyPath = FilesystemScopeGuard::policyFilePath();
        if (is_file($policyPath)) {
            $paths[] = $policyPath;
        }

        return array_values(array_unique($paths));
    }

    // ── Read current state ──────────────────────────────────────────────

    /**
     * Returns ownership and permission information for a path.
     *
     * @param string $path path absolu a inspecter.
     *
     * @return array{user: string, group: string, mode: string, exists: bool}
     */
    public static function getCurrentOwnership(string $path): array
    {
        $empty = ['user' => '', 'group' => '', 'mode' => '', 'exists' => false];

        if (!self::isValidPath($path)) {
            return $empty;
        }

        if (!file_exists($path)) {
            return $empty;
        }

        $stat = @stat($path);
        if ($stat === false) {
            return $empty;
        }

        $userInfo  = function_exists('posix_getpwuid') ? @posix_getpwuid((int) $stat['uid']) : false;
        $groupInfo = function_exists('posix_getgrgid') ? @posix_getgrgid((int) $stat['gid']) : false;

        $user  = is_array($userInfo)  ? (string) $userInfo['name']  : (string) $stat['uid'];
        $group = is_array($groupInfo) ? (string) $groupInfo['name'] : (string) $stat['gid'];
        $mode  = sprintf('%04o', $stat['mode'] & 0777);

        return [
            'user'   => $user,
            'group'  => $group,
            'mode'   => $mode,
            'exists' => true,
        ];
    }

    // ── Chown ──────────────────────────────────────────────────────────────────

    /**
     * Applies chown on a path via sudo if needed.
     *
     * @param string $user Target username.
     * @param string $group Target group name.
     * @param string $path Target absolute path.
     * @param string|null $sudoPassword Sudo password (cleared after use).
     *
     * @return array{success: bool, output: string, code: int}
     */
    public static function applyChown(
        string $user,
        string $group,
        string $path,
        ?string $sudoPassword = null
    ): array {
        if (!self::validateUser($user)) {
            $sudoPassword = null;
            unset($sudoPassword);
            return ['success' => false, 'output' => 'Nom d\'utilisateur invalide : ' . $user, 'code' => 1];
        }

        if (!self::validateUser($group)) {
            $sudoPassword = null;
            unset($sudoPassword);
            return ['success' => false, 'output' => 'Nom de groupe invalide : ' . $group, 'code' => 1];
        }

        if (!self::isValidPath($path)) {
            $sudoPassword = null;
            unset($sudoPassword);
            return ['success' => false, 'output' => 'Chemin invalide : ' . $path, 'code' => 1];
        }

        $path = FilesystemScopeGuard::assertPathAllowed($path, 'chown', true);
        $command = ['/bin/chown', $user . ':' . $group, $path];

        if ($sudoPassword !== null) {
            $result = ResetupSudoRunner::runWithPassword($sudoPassword, $command);
            unset($sudoPassword);
        } else {
            unset($sudoPassword);
            $result = ResetupSudoRunner::runNoPassword($command);
        }

        Auth::log(
            'resetup_chown',
            sprintf('chown %s:%s sur %s — code %d', $user, $group, $path, $result['code']),
            $result['success'] ? 'info' : 'warning'
        );

        return $result;
    }

    // ── Chmod ──────────────────────────────────────────────────────────────────

    /**
     * Applies chmod on a path via sudo if needed.
     *
     * @param string $mode Octal mode (e.g. '750').
     * @param string $path Target absolute path.
     * @param string|null $sudoPassword Sudo password (cleared after usage).
     *
     * @return array{success: bool, output: string, code: int}
     */
    public static function applyChmod(
        string $mode,
        string $path,
        ?string $sudoPassword = null
    ): array {
        if (!preg_match('/^[0-7]{3,4}$/', $mode)) {
            $sudoPassword = null;
            unset($sudoPassword);
            return ['success' => false, 'output' => 'Mode chmod invalide : ' . $mode, 'code' => 1];
        }

        if (!self::isValidPath($path)) {
            $sudoPassword = null;
            unset($sudoPassword);
            return ['success' => false, 'output' => 'Chemin invalide : ' . $path, 'code' => 1];
        }

        $path = FilesystemScopeGuard::assertPathAllowed($path, 'chmod', true);
        $command = ['/bin/chmod', $mode, $path];

        if ($sudoPassword !== null) {
            $result = ResetupSudoRunner::runWithPassword($sudoPassword, $command);
            unset($sudoPassword);
        } else {
            unset($sudoPassword);
            $result = ResetupSudoRunner::runNoPassword($command);
        }

        Auth::log(
            'resetup_chmod',
            sprintf('chmod %s sur %s — code %d', $mode, $path, $result['code']),
            $result['success'] ? 'info' : 'warning'
        );

        return $result;
    }

    // ── Application globale ────────────────────────────────────────────────────

    /**
     * Applique chown + chmod 750 on a ensemble of paths.
     *
     * Missing paths are created when possible. If an operation fails
     * and interactive sudo is unavailable, manual commands are included.
     *
     * @param string $user Target username.
     * @param string $group Target group name.
     * @param string[] $paths Target absolute paths.
     * @param string|null $sudoPassword Sudo password (cleared after use).
     *
     * @return array{success: bool, results: array, manual_commands: array}
     */
    public static function applyAll(
        string $user,
        string $group,
        array $paths,
        ?string $sudoPassword = null
    ): array {
        if (!self::validateUser($user)) {
            unset($sudoPassword);
            return [
                'success'         => false,
                'results'         => [],
                'manual_commands' => [],
            ];
        }

        if (!self::validateUser($group)) {
            unset($sudoPassword);
            return [
                'success'         => false,
                'results'         => [],
                'manual_commands' => [],
            ];
        }

        $results        = [];
        $allSuccess     = true;
        $manualCommands = [];

        foreach ($paths as $path) {
            $path = (string) $path;

            if (!self::isValidPath($path)) {
                $results[] = [
                    'path'    => $path,
                    'success' => false,
                    'error'   => 'Chemin invalide',
                ];
                $allSuccess = false;
                continue;
            }

            // create the directory if needed
            if (!file_exists($path)) {
                FilesystemScopeGuard::assertPathCreatable($path, 'write');
                if (!@mkdir($path, 0750, true)) {
                    $results[] = [
                        'path'    => $path,
                        'success' => false,
                        'error'   => 'Impossible de créer le répertoire',
                    ];
                    $allSuccess = false;
                    continue;
                }
            }

            // chown
            $chownResult = self::applyChown($user, $group, $path, $sudoPassword);
            // Do not unset here: reused for other paths.

            // chmod
            $chmodResult = self::applyChmod(self::TARGET_MODE, $path, $sudoPassword);

            $pathSuccess = $chownResult['success'] && $chmodResult['success'];
            if (!$pathSuccess) {
                $allSuccess = false;
            }

            $results[] = [
                'path'         => $path,
                'success'      => $pathSuccess,
                'chown_result' => $chownResult,
                'chmod_result' => $chmodResult,
            ];
        }

        // Effacement definitif of mot of passe
        unset($sudoPassword);

        if (!$allSuccess) {
            $manualCommands = self::generateManualCommands($user, $group, $paths);
        }

        Auth::log(
            'resetup_apply_all',
            sprintf(
                'Application permissions %s:%s mode %s sur %d chemins — %s',
                $user,
                $group,
                self::TARGET_MODE,
                count($paths),
                $allSuccess ? 'succès' : 'échec partiel'
            ),
            $allSuccess ? 'info' : 'warning'
        );

        return [
            'success'         => $allSuccess,
            'results'         => $results,
            'manual_commands' => $manualCommands,
        ];
    }

    // ── Manual commands ────────────────────────────────────────────────────────

    /**
     * Generates manual sudo commands for chown and chmod on paths.
     *
     * @param string $user Target username.
     * @param string $group Target group name.
     * @param string[] $paths Target absolute paths.
     *
     * @return string[] Shell command lines ready to execute.
     */
    public static function generateManualCommands(string $user, string $group, array $paths): array
    {
        $lines = [];

        foreach ($paths as $path) {
            $path = (string) $path;
            if (!self::isValidPath($path)) {
                continue;
            }

            $path = file_exists($path)
                ? FilesystemScopeGuard::assertPathAllowed($path, 'chown', true)
                : FilesystemScopeGuard::assertPathCreatable($path, 'write');

            $escapedPath  = escapeshellarg($path);
            $escapedOwner = escapeshellarg($user . ':' . $group);

            $lines[] = 'sudo chown ' . $escapedOwner . ' ' . $escapedPath;
            $lines[] = 'sudo chmod ' . self::TARGET_MODE . ' ' . $escapedPath;
        }

        return $lines;
    }

    // ── Validation ─────────────────────────────────────────────────────────────

    /**
     * Validates a UNIX username or group name.
     *
     * @param string $value Value to validate.
     *
     * @return bool true when value matches POSIX regex.
     */
    public static function validateUser(string $value): bool
    {
        return preg_match(self::USER_GROUP_REGEX, $value) === 1;
    }

    /**
     * Validates that a path is absolute and contains no traversal.
     *
     * @param string $path path a valider.
     *
     * @return bool true if validates.
     */
    public static function isValidPath(string $path): bool
    {
        try {
            $candidate = trim($path);
            if ($candidate === '') {
                return false;
            }

            FilesystemScopeGuard::assertPathAllowed($candidate, 'read', file_exists($candidate));
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
