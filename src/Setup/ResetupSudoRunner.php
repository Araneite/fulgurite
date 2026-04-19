<?php
declare(strict_types=1);

/**
 * ResetupSudoRunner — execution of privileged commands via sudo in the
 * Fulgurite reconfiguration wizard.
 *
 * Security principles:
 * - Sudo password is never logged.
 * - unset() is called immediately after password usage.
 * - Strict validation: first command argument must be an absolute path
 *   without directory traversal.
 * - Fixed timeout to avoid indefinite blocking.
 */
final class ResetupSudoRunner
{
     /** Maximum timeout (seconds) for any sudo execution. */
    private const SUDO_TIMEOUT = 30;

     /** Candidate paths for the sudo binary. */
    private const SUDO_CANDIDATES = ['/usr/bin/sudo', '/bin/sudo'];

    // ── Localisation of binaire ────────────────────────────────────────────────

    /**
     * Returns the path absolute of binary sudo available on the systeme.
     *
     * @throws RuntimeException if sudo is introuvable.
     */
    public static function getSudoBinary(): string
    {
        foreach (self::SUDO_CANDIDATES as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        // Fallback via PATH
        $found = ProcessRunner::locateBinary('sudo');
        if ($found !== '') {
            return $found;
        }

        throw new RuntimeException('Binaire sudo introuvable sur ce système.');
    }

    // ── Test without mot of passe ─────────────────────────────────────────────────

    /**
     * Teste if sudo is utilisable without mot of passe (regle NOPASSWD).
     *
     * @return bool true if sudo -n whoami returns exit code 0.
     */
    public static function testNoPassword(): bool
    {
        try {
            $sudo = self::getSudoBinary();
        } catch (RuntimeException) {
            return false;
        }

        $result = ProcessRunner::run(
            [$sudo, '-n', 'whoami'],
            ['timeout' => self::SUDO_TIMEOUT]
        );

        return $result['code'] === 0;
    }

    // ── Execution with mot of passe ────────────────────────────────────────────

    /**
     * Executes a command via `sudo -S` by piping the password to stdin.
     *
     * Password is wiped from memory immediately after ProcessRunner::run().
     * It is never logged.
     *
     * @param string $sudoPassword Sudo password (cleared after usage).
     * @param array $command Command to execute (without sudo prefix).
     *
     * @return array{success: bool, output: string, code: int}
     *
     * @throws InvalidArgumentException if the commande echoue the validation.
     */
    public static function runWithPassword(string $sudoPassword, array $command): array
    {
        self::validateCommand($command);

        try {
            $sudo = self::getSudoBinary();
        } catch (RuntimeException $e) {
            unset($sudoPassword);
            return ['success' => false, 'output' => $e->getMessage(), 'code' => 1];
        }

        $fullCommand = array_merge([$sudo, '-S', '--'], $command);
        // the mot of passe must be suivi of a saut of ligne for sudo -S
        $stdinPayload = $sudoPassword . "\n";
        unset($sudoPassword);

        $result = ProcessRunner::run(
            $fullCommand,
            [
                'stdin'   => $stdinPayload,
                'timeout' => self::SUDO_TIMEOUT,
            ]
        );
        unset($stdinPayload);

        Auth::log(
            'resetup_sudo_with_password',
            'Commande sudo exécutée : ' . self::renderSafeCommand($command),
            'warning'
        );

        return [
            'success' => $result['code'] === 0,
            'output'  => $result['output'],
            'code'    => $result['code'],
        ];
    }

    // ── Execution without mot of passe ────────────────────────────────────────────

    /**
     * Executes a command via `sudo -n` (without password).
     *
     * @param array $command Commande a executer (without sudo en tete).
     *
     * @return array{success: bool, output: string, code: int}
     *
     * @throws InvalidArgumentException if the commande echoue the validation.
     */
    public static function runNoPassword(array $command): array
    {
        self::validateCommand($command);

        try {
            $sudo = self::getSudoBinary();
        } catch (RuntimeException $e) {
            return ['success' => false, 'output' => $e->getMessage(), 'code' => 1];
        }

        $fullCommand = array_merge([$sudo, '-n', '--'], $command);

        $result = ProcessRunner::run(
            $fullCommand,
            ['timeout' => self::SUDO_TIMEOUT]
        );

        Auth::log(
            'resetup_sudo_no_password',
            'Commande sudo (NOPASSWD) exécutée : ' . self::renderSafeCommand($command),
            'info'
        );

        return [
            'success' => $result['code'] === 0,
            'output'  => $result['output'],
            'code'    => $result['code'],
        ];
    }

    // ── Generation of commandes manuelles ─────────────────────────────────────

    /**
     * generates the commandes a executer manuellement (fallback without sudo auto).
     *
     * @param array $commands Tableau of tableaux of tokens of commande.
     *
     * @return string[] Lignes of commande pretes a copier/coller.
     */
    public static function generateManualCommands(array $commands): array
    {
        $lines = [];

        foreach ($commands as $command) {
            if (!is_array($command) || $command === []) {
                continue;
            }

            $tokens = array_map('strval', $command);
            $rendered = implode(
                ' ',
                array_map(
                    static fn(string $t): string =>
                        preg_match('/^[a-zA-Z0-9._:\\/=@%+,-]+$/', $t) === 1 ? $t : escapeshellarg($t),
                    $tokens
                )
            );

            $lines[] = 'sudo ' . $rendered;
        }

        return $lines;
    }

    // ── Internal validation ─────────────────────────────────────────────────────

    /**
     * validates qu'a commande is sure for be transmise a sudo.
     *
     * Regles :
     * - command must not be empty.
     * - the premier token (the executable) must be a path absolu.
     * - No token may contain directory traversal sequence `..`.
     *
     * @throws InvalidArgumentException En cas of commande invalide.
     */
    private static function validateCommand(array $command): void
    {
        if ($command === []) {
            throw new InvalidArgumentException('La commande sudo ne peut pas être vide.');
        }

        $executable = (string) reset($command);

        if (!str_starts_with($executable, '/')) {
            throw new InvalidArgumentException(
                'Le premier argument de la commande sudo doit être un chemin absolu. Reçu : ' .
                htmlspecialchars($executable, ENT_QUOTES, 'UTF-8')
            );
        }

        foreach ($command as $token) {
            if (str_contains((string) $token, '..')) {
                throw new InvalidArgumentException(
                    'Traversée de répertoire interdite dans la commande sudo (token contient "..") : ' .
                    htmlspecialchars((string) $token, ENT_QUOTES, 'UTF-8')
                );
            }
        }
    }

    /**
     * Returns a representation lisible of the commande without data sensibles.
     */
    private static function renderSafeCommand(array $command): string
    {
        return implode(' ', array_map(
            static fn(mixed $t): string =>
                preg_match('/^[a-zA-Z0-9._:\\/=@%+,-]+$/', (string) $t) === 1
                    ? (string) $t
                    : escapeshellarg((string) $t),
            $command
        ));
    }
}
