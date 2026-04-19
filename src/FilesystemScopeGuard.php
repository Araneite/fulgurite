<?php
declare(strict_types=1);

final class FilesystemScopeGuard
{
    private const OPERATIONS = ['read', 'write', 'delete', 'chmod', 'chown'];
    private const TEMP_PREFIXES = [
        'fulgurite-',
        'fulgurite_',
        'restic_pass_',
        'restic_from_pass_',
        'fulgurite-sftp-',
        'fulgurite-hook-',
        'fulgurite-host-',
        'fulgurite_theme_',
        'fulgurite_theme_dl_',
        'fulgurite_backup_',
        'fulgurite_copy_',
        'fulgurite_cron_',
        'fulgurite_quick_backup_',
        'fulgurite_scheduler_task',
        'fulgurite_worker_cron_',
        'fulgurite-hostkey-',
        'fulgurite-ssh-',
        'rui_ret_pass_',
        'ssh_key_',
        'ssh_pub_',
    ];
    private static ?array $policyCache = null;

    public static function policyFilePath(): string
    {
        return dirname(__DIR__) . '/config/filesystem_policy.php';
    }

    public static function writeCurrentPolicyFile(): void
    {
        $policy = self::normalizePolicy([
            'version' => 1,
            'scopes' => self::discoverConfiguredScopes(),
        ]);

        $path = self::policyFilePath();
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0750, true)) {
            throw new RuntimeException('Impossible de creer le dossier de configuration de la politique filesystem.');
        }
        if (!FilesystemScopeConfigWriter::writeConfigPhp($path, $policy)) {
            throw new RuntimeException('Impossible d ecrire la politique filesystem.');
        }

        self::$policyCache = $policy;
    }

    public static function describeCurrentPolicy(): array
    {
        return self::runtimePolicy()['scopes'];
    }

    public static function assertPathAllowed(string $path, string $operation, bool $mustExist = true): string
    {
        $operation = self::normalizeOperation($operation);
        $canonicalPath = self::canonicalizePath($path, $mustExist);
        self::rejectProtectedRoots($canonicalPath, 'Chemin cible interdit.');
        self::requireScopeForPath($canonicalPath, $operation);
        return $canonicalPath;
    }

    public static function assertPathCreatable(string $path, string $operation = 'write'): string
    {
        return self::assertPathAllowed($path, $operation, false);
    }

    /**
     * Validates a backup source path for format safety.
     *
     * For local sources ($isLocal = true): also rejects the filesystem root and
     * virtual/special filesystems (/proc, /sys, /dev) that should never be backed up.
     * For remote sources ($isLocal = false): only format is validated since the path
     * lives on a remote host outside this server's scope.
     *
     * This method intentionally does NOT call requireScopeForPath(): backup sources
     * are read by the restic binary (not PHP), so the runtime policy scope is not
     * the right gate here. Scope validation applies to PHP file operations only.
     *
     * @throws InvalidArgumentException|RuntimeException on any violation
     */
    public static function assertValidBackupSourcePath(string $path, bool $isLocal = true): string
    {
        $normalized = self::normalizeAbsolutePath($path);

        if (self::isPathRoot($normalized)) {
            throw new RuntimeException('Le chemin source ne peut pas etre la racine du systeme de fichiers: ' . $normalized);
        }

        if ($isLocal) {
            self::rejectVirtualFilesystemRoots($normalized);
        }

        return $normalized;
    }

    private static function rejectVirtualFilesystemRoots(string $path): void
    {
        $virtual = ['/proc', '/sys', '/dev'];
        foreach ($virtual as $vr) {
            if ($path === $vr || str_starts_with($path, $vr . '/')) {
                throw new RuntimeException('Chemin source interdit (systeme de fichiers virtuel): ' . $path);
            }
        }
    }

    public static function assertMutableTree(string $path, string $operation): string
    {
        $canonicalPath = self::assertPathAllowed($path, $operation, true);
        if (!is_dir($canonicalPath)) {
            throw new RuntimeException('Le chemin cible n est pas un repertoire.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($canonicalPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Lien symbolique refuse dans le perimetre autorise: ' . $item->getPathname());
            }

            self::requireScopeForPath(self::normalizeRealPath($item->getPathname()), self::normalizeOperation($operation));
        }

        return $canonicalPath;
    }

    public static function discoverConfiguredScopes(): array
    {
        $scopes = [];

        foreach (self::discoverInternalScopes() as $scope) {
            $scopes[] = $scope;
        }

        foreach (self::discoverCompatibilityScopes() as $scope) {
            $scopes[] = $scope;
        }

        return self::normalizePolicy(['scopes' => $scopes])['scopes'];
    }

    private static function runtimePolicy(): array
    {
        if (self::$policyCache !== null) {
            return self::$policyCache;
        }

        $path = self::policyFilePath();
        if (is_file($path)) {
            $loaded = require $path;
            if (!is_array($loaded)) {
                throw new RuntimeException('Politique filesystem invalide.');
            }
            return self::$policyCache = self::normalizePolicy($loaded);
        }

        return self::$policyCache = self::normalizePolicy([
            'version' => 1,
            'scopes' => self::discoverConfiguredScopes(),
        ]);
    }

    private static function discoverInternalScopes(): array
    {
        $scopes = [];
        $allPermissions = self::OPERATIONS;

        $dataDir = defined('DB_PATH') ? dirname((string) DB_PATH) : dirname(__DIR__) . '/data';
        $scopes[] = [
            'path' => $dataDir,
            'permissions' => $allPermissions,
            'label' => 'app-data',
        ];

        if (defined('SEARCH_DB_PATH')) {
            $searchDir = dirname((string) SEARCH_DB_PATH);
            if ($searchDir !== $dataDir) {
                $scopes[] = [
                    'path' => $searchDir,
                    'permissions' => $allPermissions,
                    'label' => 'search-data',
                ];
            }
        }

        $configDir = dirname(self::policyFilePath());
        if (self::looksAbsolutePath($configDir)) {
            $scopes[] = [
                'path' => $configDir,
                'permissions' => ['read', 'chmod', 'chown'],
                'label' => 'config-policy',
            ];
        }

        $socketPath = trim((string) getenv('FULGURITE_SECRET_AGENT_SOCKET'));
        if ($socketPath !== '' && self::looksAbsolutePath($socketPath)) {
            $scopes[] = [
                'path' => dirname($socketPath),
                'permissions' => $allPermissions,
                'label' => 'secret-agent-socket',
            ];
        }

        $tempRoot = rtrim((string) sys_get_temp_dir(), '/\\');
        if ($tempRoot !== '' && self::looksAbsolutePath($tempRoot)) {
            $scopes[] = [
                'path' => $tempRoot,
                'permissions' => ['read', 'write', 'delete', 'chmod'],
                'label' => 'fulgurite-temp',
                'first_segment_prefixes' => self::TEMP_PREFIXES,
            ];
        }

        return $scopes;
    }

    private static function discoverCompatibilityScopes(): array
    {
        $scopes = [];
        $allPermissions = self::OPERATIONS;

        if (defined('REPOS_BASE_PATH') && self::looksAbsolutePath((string) REPOS_BASE_PATH)) {
            $scopes[] = [
                'path' => (string) REPOS_BASE_PATH,
                'permissions' => $allPermissions,
                'label' => 'repo-base',
            ];
        }

        $restoreRoot = defined('DB_PATH') ? dirname((string) DB_PATH) . '/restores' : '';
        if (class_exists('AppConfig', false) && method_exists('AppConfig', 'restoreManagedLocalRoot')) {
            try {
                $candidate = trim((string) AppConfig::restoreManagedLocalRoot());
                if ($candidate !== '') {
                    $restoreRoot = $candidate;
                }
            } catch (Throwable) {
            }
        }

        if ($restoreRoot !== '' && self::looksAbsolutePath($restoreRoot)) {
            $scopes[] = [
                'path' => $restoreRoot,
                'permissions' => $allPermissions,
                'label' => 'restore-managed-local-root',
            ];
        }

        foreach (self::discoverExistingLocalRepoScopes() as $scope) {
            $scopes[] = $scope;
        }

        return $scopes;
    }

    private static function discoverExistingLocalRepoScopes(): array
    {
        if (!class_exists('Database', false)) {
            return [];
        }

        try {
            $db = Database::getInstance();
            $rows = $db->query('SELECT path FROM repos')->fetchAll();
        } catch (Throwable) {
            return [];
        }

        $scopes = [];
        foreach ($rows as $row) {
            $path = trim((string) ($row['path'] ?? ''));
            if ($path === '' || !self::looksAbsolutePath($path) || self::looksRemotePath($path)) {
                continue;
            }

            $scopes[] = [
                'path' => $path,
                'permissions' => self::OPERATIONS,
                'label' => 'repo-local',
            ];
        }

        return $scopes;
    }

    private static function normalizePolicy(array $policy): array
    {
        $normalizedScopes = [];
        $seen = [];

        foreach ((array) ($policy['scopes'] ?? []) as $scope) {
            if (!is_array($scope)) {
                continue;
            }

            $normalizedScope = self::normalizeScope($scope);
            $key = json_encode([
                $normalizedScope['path'],
                $normalizedScope['permissions'],
                $normalizedScope['first_segment_prefixes'],
            ], JSON_UNESCAPED_SLASHES);
            if ($key === false || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalizedScopes[] = $normalizedScope;
        }

        usort($normalizedScopes, static function (array $left, array $right): int {
            return strlen((string) $right['path']) <=> strlen((string) $left['path']);
        });

        return [
            'version' => (int) ($policy['version'] ?? 1),
            'scopes' => $normalizedScopes,
        ];
    }

    private static function normalizeScope(array $scope): array
    {
        $path = self::canonicalizePath((string) ($scope['path'] ?? ''), false);
        self::rejectProtectedRoots($path, 'Racine filesystem interdite dans la politique.');

        $permissions = [];
        foreach ((array) ($scope['permissions'] ?? []) as $permission) {
            $permission = self::normalizeOperation((string) $permission);
            if (!in_array($permission, $permissions, true)) {
                $permissions[] = $permission;
            }
        }

        if ($permissions === []) {
            throw new InvalidArgumentException('Permissions filesystem manquantes pour ' . $path);
        }

        $prefixes = [];
        foreach ((array) ($scope['first_segment_prefixes'] ?? []) as $prefix) {
            $prefix = trim((string) $prefix);
            if ($prefix !== '' && !in_array($prefix, $prefixes, true)) {
                $prefixes[] = $prefix;
            }
        }

        return [
            'path' => $path,
            'permissions' => $permissions,
            'label' => trim((string) ($scope['label'] ?? '')),
            'first_segment_prefixes' => $prefixes,
        ];
    }

    private static function normalizeOperation(string $operation): string
    {
        $operation = strtolower(trim($operation));
        $operation = match ($operation) {
            'create' => 'write',
            'delete_recursive' => 'delete',
            default => $operation,
        };

        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException('Operation filesystem non supportee: ' . $operation);
        }

        return $operation;
    }

    private static function canonicalizePath(string $path, bool $mustExist): string
    {
        $normalizedPath = self::normalizeAbsolutePath($path);
        if ($normalizedPath === '' || self::isPathRoot($normalizedPath)) {
            throw new RuntimeException('Le chemin cible est vide ou pointe vers une racine interdite.');
        }

        if ($mustExist) {
            if (!file_exists($normalizedPath) && !is_link($normalizedPath)) {
                throw new RuntimeException('Le chemin cible est introuvable: ' . $normalizedPath);
            }

            self::assertNoSymlinkSegments($normalizedPath);
            $realPath = realpath($normalizedPath);
            if ($realPath === false) {
                throw new RuntimeException('Impossible de resoudre le chemin cible: ' . $normalizedPath);
            }

            return self::normalizeRealPath($realPath);
        }

        [$existingAncestor, $suffixSegments] = self::findExistingAncestor($normalizedPath);
        self::assertNoSymlinkSegments($existingAncestor);
        $ancestorRealPath = realpath($existingAncestor);
        if ($ancestorRealPath === false) {
            throw new RuntimeException('Impossible de resoudre le parent du chemin cible: ' . $normalizedPath);
        }

        $canonicalPath = self::normalizeRealPath($ancestorRealPath);
        if ($suffixSegments !== []) {
            $canonicalPath = rtrim($canonicalPath, '/') . '/' . implode('/', $suffixSegments);
        }

        return self::normalizeAbsolutePath($canonicalPath);
    }

    private static function findExistingAncestor(string $normalizedPath): array
    {
        $segments = self::pathSegments($normalizedPath);
        $root = self::pathRoot($normalizedPath);

        for ($length = count($segments); $length >= 0; $length--) {
            $candidate = self::joinSegments($root, array_slice($segments, 0, $length));
            if (file_exists($candidate) || is_link($candidate)) {
                return [$candidate, array_slice($segments, $length)];
            }
        }

        throw new RuntimeException('Aucun parent existant pour le chemin cible: ' . $normalizedPath);
    }

    private static function assertNoSymlinkSegments(string $normalizedPath): void
    {
        $segments = self::pathSegments($normalizedPath);
        $root = self::pathRoot($normalizedPath);
        $current = $root;

        foreach ($segments as $segment) {
            $current = self::appendPathSegment($current, $segment);
            if (!file_exists($current) && !is_link($current)) {
                break;
            }
            if (is_link($current)) {
                throw new RuntimeException('Lien symbolique refuse: ' . $current);
            }
        }
    }

    private static function requireScopeForPath(string $canonicalPath, string $operation): void
    {
        foreach (self::runtimePolicy()['scopes'] as $scope) {
            if (!in_array($operation, (array) ($scope['permissions'] ?? []), true)) {
                continue;
            }

            if (!self::pathStartsWith($canonicalPath, (string) $scope['path'])) {
                continue;
            }

            $prefixes = (array) ($scope['first_segment_prefixes'] ?? []);
            if ($prefixes !== [] && !self::matchesFirstSegmentPrefix($canonicalPath, (string) $scope['path'], $prefixes)) {
                continue;
            }

            return;
        }

        self::logDeniedOperation($canonicalPath, $operation);
        throw new RuntimeException('Operation filesystem refusee hors perimetre autorise: ' . $canonicalPath);
    }

    private static function matchesFirstSegmentPrefix(string $path, string $scopePath, array $prefixes): bool
    {
        $relative = ltrim(substr($path, strlen(rtrim($scopePath, '/'))), '/');
        if ($relative === '') {
            return false;
        }

        $firstSegment = explode('/', $relative, 2)[0];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($firstSegment, (string) $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function rejectProtectedRoots(string $path, string $message): void
    {
        $forbidden = ['/', '/etc', '/usr', '/bin', '/sbin', '/lib', '/lib64', '/boot', '/dev', '/proc', '/sys'];
        if (in_array($path, $forbidden, true)) {
            throw new RuntimeException($message . ' ' . $path);
        }
    }

    private static function logDeniedOperation(string $path, string $operation): void
    {
        $message = sprintf('Operation %s refusee sur %s', $operation, $path);
        if (class_exists('Auth', false) && method_exists('Auth', 'log')) {
            try {
                Auth::log('filesystem_scope_denied', $message, 'warning');
                return;
            } catch (Throwable) {
            }
        }

        error_log('[fulgurite-filesystem-scope] ' . $message);
    }

    private static function normalizeAbsolutePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            throw new InvalidArgumentException('Chemin filesystem invalide.');
        }

        if (!self::looksAbsolutePath($path)) {
            throw new InvalidArgumentException('Le chemin doit etre absolu.');
        }

        [$root, $segments] = self::splitAbsolutePath($path);
        $normalized = self::joinSegments($root, $segments);
        return $normalized === '' ? $root : $normalized;
    }

    private static function looksAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    private static function splitAbsolutePath(string $path): array
    {
        $path = str_replace('\\', '/', $path);
        if (preg_match('/^[A-Za-z]:\//', $path) === 1) {
            $root = strtoupper(substr($path, 0, 2)) . '/';
            $rest = substr($path, 3);
        } else {
            $root = '/';
            $rest = ltrim($path, '/');
        }

        $segments = [];
        foreach (preg_split('~/+~', $rest) ?: [] as $segment) {
            $segment = trim((string) $segment);
            if ($segment === '') {
                continue;
            }
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Chemin filesystem ambigu ou traversant.');
            }
            $segments[] = $segment;
        }

        return [$root, $segments];
    }

    private static function pathSegments(string $path): array
    {
        [, $segments] = self::splitAbsolutePath($path);
        return $segments;
    }

    private static function pathRoot(string $path): string
    {
        [$root] = self::splitAbsolutePath($path);
        return $root;
    }

    private static function joinSegments(string $root, array $segments): string
    {
        $root = str_replace('\\', '/', $root);
        if ($segments === []) {
            return rtrim($root, '/') === '' ? '/' : rtrim($root, '/') . '/';
        }

        if ($root === '/') {
            return '/' . implode('/', $segments);
        }

        return rtrim($root, '/') . '/' . implode('/', $segments);
    }

    private static function appendPathSegment(string $base, string $segment): string
    {
        if ($base === '/') {
            return '/' . $segment;
        }

        return rtrim($base, '/') . '/' . $segment;
    }

    private static function isPathRoot(string $path): bool
    {
        return $path === '/' || preg_match('/^[A-Z]:\/$/', $path) === 1;
    }

    private static function normalizeRealPath(string $path): string
    {
        return self::normalizeAbsolutePath($path);
    }

    private static function pathStartsWith(string $path, string $scopePath): bool
    {
        $path = rtrim(self::normalizeAbsolutePath($path), '/');
        $scopePath = rtrim(self::normalizeAbsolutePath($scopePath), '/');

        if ($path === $scopePath) {
            return true;
        }

        return $scopePath !== '' && str_starts_with($path, $scopePath . '/');
    }

    private static function looksRemotePath(string $path): bool
    {
        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1
            || preg_match('#^[a-z0-9+.-]+:#i', $path) === 1;
    }
}
