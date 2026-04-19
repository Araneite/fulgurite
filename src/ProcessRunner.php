<?php
declare(strict_types=1);

final class ProcessRunner
{
    private const DEFAULT_PATH = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

    public static function run(array $command, array $options = []): array
    {
        $command = self::normalizeCommand($command);
        if ($command === []) {
            return self::failureResult('Commande vide', 1);
        }

        $cwd = isset($options['cwd']) && is_string($options['cwd']) && $options['cwd'] !== ''
            ? $options['cwd']
            : null;
        $env = array_key_exists('env', $options) && is_array($options['env'])
            ? self::normalizeEnv($options['env'])
            : null;
        $stdin = (string) ($options['stdin'] ?? '');
        $timeout = max(0, (int) ($options['timeout'] ?? 0));
        $stdoutCallback = isset($options['stdout_callback']) && is_callable($options['stdout_callback'])
            ? $options['stdout_callback']
            : null;
        $stderrCallback = isset($options['stderr_callback']) && is_callable($options['stderr_callback'])
            ? $options['stderr_callback']
            : null;
        $progressCallback = isset($options['progress_callback']) && is_callable($options['progress_callback'])
            ? $options['progress_callback']
            : null;
        $captureStdout = !isset($options['capture_stdout']) || $options['capture_stdout'] !== false;
        $captureStderr = !isset($options['capture_stderr']) || $options['capture_stderr'] !== false;
        $previousUmask = null;
        if (array_key_exists('umask', $options) && $options['umask'] !== null) {
            $previousUmask = umask((int) $options['umask']);
        }

        try {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $process = @proc_open($command, $descriptors, $pipes, $cwd, $env);
        } finally {
            if ($previousUmask !== null) {
                umask($previousUmask);
            }
        }

        if (!is_resource($process)) {
            return self::failureResult('Impossible de lancer la commande', 1, $command);
        }

        $status = proc_get_status($process);
        $pid = (int) ($status['pid'] ?? 0);

        if ($stdin !== '') {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $stdoutBuffer = '';
        $stderrBuffer = '';
        $timedOut = false;
        $deadline = $timeout > 0 ? microtime(true) + $timeout : null;
        $startedAt = microtime(true);
        $lastStatus = $status;

        while (true) {
            $read = [];
            if (!feof($pipes[1])) {
                $read[] = $pipes[1];
            }
            if (!feof($pipes[2])) {
                $read[] = $pipes[2];
            }

            if ($read === []) {
                break;
            }

            $write = null;
            $except = null;
            $changed = @stream_select($read, $write, $except, 0, 200000);
            if ($changed === false) {
                break;
            }

            foreach ($read as $stream) {
                $chunk = stream_get_contents($stream);
                if ($chunk === false || $chunk === '') {
                    continue;
                }

                if ($stream === $pipes[1]) {
                    if ($captureStdout) {
                        $stdout .= $chunk;
                    }
                    self::dispatchLines($chunk, $stdoutBuffer, $stdoutCallback);
                    continue;
                }

                if ($captureStderr) {
                    $stderr .= $chunk;
                }
                self::dispatchLines($chunk, $stderrBuffer, $stderrCallback);
            }

            $lastStatus = proc_get_status($process);
            if ($progressCallback !== null) {
                $progressCallback([
                    'elapsed_seconds' => (int) floor(microtime(true) - $startedAt),
                    'pid' => $pid > 0 ? $pid : null,
                    'running' => (bool) ($lastStatus['running'] ?? false),
                    'timed_out' => false,
                ]);
            }
            if (($lastStatus['running'] ?? false) !== true && feof($pipes[1]) && feof($pipes[2])) {
                break;
            }

            if ($deadline !== null && microtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($process);
                usleep(200000);
                $lastStatus = proc_get_status($process);
                if (($lastStatus['running'] ?? false) === true) {
                    proc_terminate($process, 9);
                }
                if ($progressCallback !== null) {
                    $progressCallback([
                        'elapsed_seconds' => (int) floor(microtime(true) - $startedAt),
                        'pid' => $pid > 0 ? $pid : null,
                        'running' => false,
                        'timed_out' => true,
                    ]);
                }
                break;
            }
        }

        $remainingStdout = stream_get_contents($pipes[1]);
        if ($remainingStdout !== false && $remainingStdout !== '') {
            if ($captureStdout) {
                $stdout .= $remainingStdout;
            }
            self::dispatchLines($remainingStdout, $stdoutBuffer, $stdoutCallback);
        }

        $remainingStderr = stream_get_contents($pipes[2]);
        if ($remainingStderr !== false && $remainingStderr !== '') {
            if ($captureStderr) {
                $stderr .= $remainingStderr;
            }
            self::dispatchLines($remainingStderr, $stderrBuffer, $stderrCallback);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        self::flushLineBuffer($stdoutBuffer, $stdoutCallback);
        self::flushLineBuffer($stderrBuffer, $stderrCallback);

        $exitCode = self::resolveExitCode(proc_close($process), $lastStatus);
        if ($timedOut && $exitCode === 0) {
            $exitCode = 124;
        }

        $output = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));
        if ($timedOut) {
            $output = trim($output . "\nTimeout");
        }

        return [
            'success' => !$timedOut && $exitCode === 0,
            'code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'output' => $output,
            'timed_out' => $timedOut,
            'pid' => $pid > 0 ? $pid : null,
            'command' => self::renderCommand($command),
        ];
    }

    public static function startDetached(array $command, array $options = []): array
    {
        $command = self::normalizeCommand($command);
        if ($command === []) {
            return self::failureResult('Commande vide', 1);
        }

        $stdoutFile = (string) ($options['stdout_file'] ?? self::nullDevice());
        $stderrFile = (string) ($options['stderr_file'] ?? $stdoutFile);
        $pidFile = isset($options['pid_file']) && is_string($options['pid_file']) && $options['pid_file'] !== ''
            ? $options['pid_file']
            : null;
        $cwd = isset($options['cwd']) && is_string($options['cwd']) && $options['cwd'] !== ''
            ? $options['cwd']
            : null;
        $env = array_key_exists('env', $options) && is_array($options['env'])
            ? self::normalizeEnv($options['env'])
            : null;
        $append = !isset($options['append']) || $options['append'] !== false;
        $previousUmask = null;
        if (array_key_exists('umask', $options) && $options['umask'] !== null) {
            $previousUmask = umask((int) $options['umask']);
        }

        try {
            $descriptors = [
                0 => ['file', self::nullDevice(), 'r'],
                1 => ['file', $stdoutFile, $append ? 'a' : 'w'],
                2 => ['file', $stderrFile, $append ? 'a' : 'w'],
            ];
            $process = @proc_open($command, $descriptors, $pipes, $cwd, $env);
        } finally {
            if ($previousUmask !== null) {
                umask($previousUmask);
            }
        }

        if (!is_resource($process)) {
            return self::failureResult('Impossible de lancer la commande en arriere-plan', 1, $command);
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $status = proc_get_status($process);
        $wrapperPid = (int) ($status['pid'] ?? 0);
        $exitCode = proc_close($process);
        $pid = $pidFile !== null ? self::waitForPidFile($pidFile) : ($wrapperPid > 0 ? $wrapperPid : null);

        return [
            'success' => $exitCode === 0,
            'code' => $exitCode,
            'pid' => $pid,
            'command' => self::renderCommand($command),
            'output' => $exitCode === 0 ? '' : 'Le lanceur de fond a echoue',
        ];
    }

    public static function startBackgroundPhp(
        string $scriptPath,
        array $args = [],
        ?string $logFile = null,
        ?string $pidFile = null,
        array $env = []
    ): array {
        $phpBinary = trim((string) ($env['PHP_BINARY'] ?? ''));
        unset($env['PHP_BINARY']);
        if ($phpBinary === '') {
            $phpBinary = self::resolvePhpCliBinary();
        }

        $command = array_merge([$phpBinary, $scriptPath], array_map(static fn(mixed $value): string => (string) $value, $args));
        $env['FULGURITE_DETACH'] = '1';
        if ($pidFile !== null && $pidFile !== '') {
            $env['FULGURITE_PID_FILE'] = $pidFile;
        }

        return self::startDetached($command, [
            'stdout_file' => $logFile ?? self::nullDevice(),
            'stderr_file' => $logFile ?? self::nullDevice(),
            'pid_file' => $pidFile,
            'env' => $env,
            'cwd' => dirname($scriptPath),
            'append' => $logFile !== null,
        ]);
    }

    public static function locateBinary(string $binary, array $preferredCandidates = [], ?string $pathEnv = null): string
    {
        $candidates = [];
        foreach ($preferredCandidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }

        $binary = trim($binary);
        if ($binary !== '') {
            $candidates[] = $binary;
        }

        $pathEnv = $pathEnv ?? ((string) getenv('PATH'));
        if ($pathEnv === '') {
            $pathEnv = self::DEFAULT_PATH;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (str_contains($candidate, '/') || str_contains($candidate, '\\')) {
                if (is_file($candidate) && is_executable($candidate)) {
                    return $candidate;
                }
                continue;
            }

            foreach (explode(PATH_SEPARATOR, $pathEnv) as $path) {
                $path = trim($path);
                if ($path === '') {
                    continue;
                }

                foreach (self::candidateExtensions() as $extension) {
                    $resolved = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $candidate . $extension;
                    if (is_file($resolved) && is_executable($resolved)) {
                        return $resolved;
                    }
                }
            }
        }

        return '';
    }

    public static function resolvePhpCliBinary(): string
    {
        $candidates = [];
        if (defined('PHP_CLI_BIN') && trim((string) PHP_CLI_BIN) !== '') {
            $candidates[] = trim((string) PHP_CLI_BIN);
        }
        if (defined('PHP_BINDIR') && trim((string) PHP_BINDIR) !== '') {
            $candidates[] = rtrim((string) PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php';
        }
        $candidates[] = PHP_BINARY;
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

        return PHP_BINARY;
    }

    public static function isPidRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        $result = self::run(['ps', '-p', (string) $pid, '-o', 'pid='], ['capture_stderr' => false]);
        return $result['success'] && trim((string) ($result['stdout'] ?? '')) !== '';
    }

    public static function sendSignal(int $pid, int $signal): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, $signal);
        }

        $result = self::run(['kill', '-' . (string) $signal, (string) $pid], ['capture_stderr' => false]);
        return $result['success'];
    }

    public static function daemonizeFromEnvironment(): void
    {
        $detach = (string) (getenv('FULGURITE_DETACH') ?: ($_SERVER['FULGURITE_DETACH'] ?? ''));
        if ($detach !== '1') {
            return;
        }

        $pidFile = (string) (getenv('FULGURITE_PID_FILE') ?: ($_SERVER['FULGURITE_PID_FILE'] ?? ''));
        if (!function_exists('pcntl_fork') || !function_exists('posix_setsid')) {
            if ($pidFile !== '' && getmypid() !== false) {
                @file_put_contents($pidFile, (string) getmypid(), LOCK_EX);
            }
            self::clearDetachEnvironment();
            return;
        }

        $pid = pcntl_fork();
        if ($pid < 0) {
            exit(1);
        }
        if ($pid > 0) {
            exit(0);
        }

        if (posix_setsid() < 0) {
            exit(1);
        }

        $pid = pcntl_fork();
        if ($pid < 0) {
            exit(1);
        }
        if ($pid > 0) {
            if ($pidFile !== '') {
                @file_put_contents($pidFile, (string) $pid, LOCK_EX);
            }
            exit(0);
        }

        if ($pidFile !== '' && getmypid() !== false) {
            @file_put_contents($pidFile, (string) getmypid(), LOCK_EX);
        }

        @chdir('/');
        @umask(0);
        self::clearDetachEnvironment();
    }

    public static function removeDirectory(string $path, ?string $requiredBase = null): void
    {
        $path = trim($path);
        if ($path === '' || !file_exists($path)) {
            return;
        }

        if ($requiredBase !== null && $requiredBase !== '') {
            $realPath = FilesystemScopeGuard::assertPathAllowed($path, 'delete', true);
            $realBase = FilesystemScopeGuard::assertPathAllowed($requiredBase, 'read', true);
            $normalizedBase = rtrim($realBase, '/');
            if ($realPath !== $normalizedBase && !str_starts_with($realPath, $normalizedBase . '/')) {
                throw new RuntimeException('Suppression refusee hors base autorisee: ' . $realPath);
            }
        }

        if (is_file($path) || is_link($path)) {
            FileSystem::deleteFile($path);
            return;
        }

        FileSystem::removeDirectory($path);
    }

    public static function createTarGzFromDirectory(string $sourcePath, string $archivePath, string $rootName): void
    {
        if (!class_exists('PharData')) {
            throw new RuntimeException('PharData indisponible');
        }

        $sourceRealPath = realpath($sourcePath);
        if ($sourceRealPath === false || !is_dir($sourceRealPath)) {
            throw new RuntimeException('Dossier source introuvable');
        }

        if (!str_ends_with(strtolower($archivePath), '.tar.gz')) {
            throw new InvalidArgumentException('Archive .tar.gz attendue');
        }

        $tarPath = substr($archivePath, 0, -3);
        @unlink($archivePath);
        @unlink($tarPath);

        $archive = new PharData($tarPath);
        $archive->startBuffering();
        $archive->addEmptyDir($rootName);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRealPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $prefixLength = strlen($sourceRealPath) + 1;
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), $prefixLength);
            $archivePathPart = $rootName . '/' . str_replace('\\', '/', $relativePath);
            if ($item->isDir() && !$item->isLink()) {
                $archive->addEmptyDir($archivePathPart);
                continue;
            }
            $archive->addFile($item->getPathname(), $archivePathPart);
        }

        $archive->stopBuffering();
        $archive->compress(Phar::GZ);
        unset($archive);

        $gzPath = $tarPath . '.gz';
        if (!is_file($gzPath)) {
            @unlink($tarPath);
            throw new RuntimeException('Compression .gz echouee');
        }

        if (is_file($archivePath)) {
            @unlink($archivePath);
        }
        if (!@rename($gzPath, $archivePath)) {
            @copy($gzPath, $archivePath);
            @unlink($gzPath);
        }

        @unlink($tarPath);
    }

    public static function nullDevice(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    }

    public static function renderCommand(array $command): string
    {
        $rendered = [];
        foreach ($command as $part) {
            $part = (string) $part;
            $rendered[] = preg_match('/^[a-zA-Z0-9._:\\/=-]+$/', $part) === 1
                ? $part
                : escapeshellarg($part);
        }

        return implode(' ', $rendered);
    }

    public static function commandToString(array $command): string
    {
        return self::renderCommand($command);
    }

    private static function normalizeCommand(array $command): array
    {
        $normalized = [];
        foreach ($command as $part) {
            if ($part === null) {
                continue;
            }
            $normalized[] = (string) $part;
        }

        return $normalized;
    }

    private static function normalizeEnv(array $env): array
    {
        $normalized = [];
        foreach ($env as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $normalized[$key] = (string) $value;
        }

        if (!isset($normalized['PATH']) || trim($normalized['PATH']) === '') {
            $normalized['PATH'] = self::DEFAULT_PATH;
        }

        return $normalized;
    }

    private static function dispatchLines(string $chunk, string &$buffer, ?callable $callback): void
    {
        if ($callback === null) {
            return;
        }

        $buffer .= $chunk;
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = rtrim(substr($buffer, 0, $pos), "\r");
            $buffer = (string) substr($buffer, $pos + 1);
            $callback($line);
        }
    }

    private static function flushLineBuffer(string &$buffer, ?callable $callback): void
    {
        if ($callback !== null && trim($buffer) !== '') {
            $callback(rtrim($buffer, "\r"));
        }
        $buffer = '';
    }

    private static function resolveExitCode(int $exitCode, array|false|null $lastStatus): int
    {
        if ($exitCode !== -1) {
            return $exitCode;
        }

        if (is_array($lastStatus)) {
            if (isset($lastStatus['exitcode']) && (int) $lastStatus['exitcode'] >= 0) {
                return (int) $lastStatus['exitcode'];
            }
            if (isset($lastStatus['termsig']) && (int) $lastStatus['termsig'] > 0) {
                return 128 + (int) $lastStatus['termsig'];
            }
        }

        return 1;
    }

    private static function candidateExtensions(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [''];
        }

        $extensions = getenv('PATHEXT');
        if (!is_string($extensions) || trim($extensions) === '') {
            return ['', '.exe', '.bat', '.cmd', '.com'];
        }

        return array_unique(array_merge([''], array_map('strtolower', array_map('trim', explode(';', $extensions)))));
    }

    private static function waitForPidFile(string $pidFile, int $attempts = 10, int $delayMicros = 100000): ?int
    {
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $raw = @file_get_contents($pidFile);
            if ($raw !== false) {
                $trimmed = trim($raw);
                if (ctype_digit($trimmed)) {
                    return (int) $trimmed;
                }
            }
            usleep($delayMicros);
        }

        return null;
    }

    private static function clearDetachEnvironment(): void
    {
        putenv('FULGURITE_DETACH=0');
        putenv('FULGURITE_PID_FILE');
        unset($_SERVER['FULGURITE_DETACH'], $_SERVER['FULGURITE_PID_FILE']);
    }

    private static function failureResult(string $message, int $code, array $command = []): array
    {
        return [
            'success' => false,
            'code' => $code,
            'stdout' => '',
            'stderr' => $message,
            'output' => $message,
            'timed_out' => false,
            'pid' => null,
            'command' => self::renderCommand($command),
        ];
    }
}
