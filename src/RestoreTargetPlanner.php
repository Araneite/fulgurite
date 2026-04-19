<?php

class RestoreTargetPlanner {
    public const STRATEGY_MANAGED = 'managed';
    public const STRATEGY_ORIGINAL = 'original';
    public const ORIGINAL_CONFIRMATION_WORD = 'confirmer';

    public static function plan(array $context): array {
        $mode = ((string) ($context['mode'] ?? 'local')) === 'remote' ? 'remote' : 'local';
        $strategy = self::normalizeStrategy((string) ($context['destination_mode'] ?? self::STRATEGY_MANAGED));
        $canRestoreOriginal = !empty($context['can_restore_original']);
        if ($strategy === self::STRATEGY_ORIGINAL && !$canRestoreOriginal) {
            throw new InvalidArgumentException('La destination originale est reservee aux admins.');
        }

        $repo = is_array($context['repo'] ?? null) ? $context['repo'] : [];
        $snapshot = is_array($context['snapshot'] ?? null) ? $context['snapshot'] : null;
        $sshKey = is_array($context['ssh_key'] ?? null) ? $context['ssh_key'] : null;
        $host = is_array($context['host'] ?? null) ? $context['host'] : null;
        $repoId = (int) ($context['repo_id'] ?? ($repo['id'] ?? 0));
        $jobName = trim((string) ($context['job_name'] ?? ''));
        $appendContext = array_key_exists('append_context_subdir', $context)
            ? (bool) $context['append_context_subdir']
            : AppConfig::restoreAppendContextSubdir();
        $include = self::normalizeSnapshotPath((string) ($context['include'] ?? ''));
        $samplePaths = self::normalizeSamplePaths($context['sample_paths'] ?? [], $include, $snapshot);
        $contextSubdir = '';

        if ($strategy === self::STRATEGY_MANAGED) {
            $configuredRoot = self::resolveManagedRoot($mode, $host);
            $baseRoot = self::validateManagedRoot($configuredRoot, $mode === 'local' ? 'locale' : 'distante');
            if ($appendContext) {
                $contextSubdir = self::buildContextSubdir($repo, $jobName);
            }
            $effectiveTarget = self::joinAbsolutePath($baseRoot, $contextSubdir);
        } else {
            $previewConfirmed = !empty($context['preview_confirmed']);
            // included paths to validate against allowlist (include + sample paths)
            $included = $samplePaths;
            if ($include !== '') {
                array_unshift($included, $include);
            }
            self::assertOriginalDestinationAllowed($repoId, $mode, $snapshot, $host, $previewConfirmed, $included);
            $baseRoot = self::normalizeAbsoluteDirectory('/');
            $effectiveTarget = $baseRoot;
            $appendContext = false;
        }

        return [
            'mode' => $mode,
            'strategy' => $strategy,
            'base_root' => $baseRoot,
            'effective_target' => $effectiveTarget,
            'append_context_subdir' => $appendContext,
            'context_subdir' => $contextSubdir,
            'include' => $include,
            'sample_paths' => $samplePaths,
            'preview_paths' => self::buildPreviewPaths($effectiveTarget, $samplePaths),
            'warning' => self::buildWarning($mode, $strategy),
            'mode_label' => $mode === 'remote' ? 'distant' : 'local',
            'strategy_label' => $strategy === self::STRATEGY_ORIGINAL ? 'destination originale' : 'dossier de restores gere',
            'writes_to_original_destination' => $strategy === self::STRATEGY_ORIGINAL,
        ];
    }

    public static function normalizeStrategy(string $value): string {
        $value = strtolower(trim($value));
        return $value === self::STRATEGY_ORIGINAL ? self::STRATEGY_ORIGINAL : self::STRATEGY_MANAGED;
    }

    public static function assertOriginalConfirmation(string $confirmation): void {
        if (strtolower(trim($confirmation)) !== self::ORIGINAL_CONFIRMATION_WORD) {
            throw new InvalidArgumentException('La restauration a la destination originale exige de retaper "' . self::ORIGINAL_CONFIRMATION_WORD . '".');
        }
    }

    public static function findSnapshotOriginHost(int $repoId, ?array $snapshot): ?array {
        if ($repoId <= 0 || !$snapshot) {
            return null;
        }

        $stmt = Database::getInstance()->prepare("
            SELECT h.*, k.name AS ssh_key_name, k.user AS key_user, k.private_key_file,
                   b.hostname_override, b.last_run
            FROM backup_jobs b
            INNER JOIN hosts h ON h.id = b.host_id
            LEFT JOIN ssh_keys k ON k.id = h.ssh_key_id
            WHERE b.repo_id = ?
            ORDER BY b.last_run DESC, b.id DESC
        ");
        $stmt->execute([$repoId]);
        $hosts = $stmt->fetchAll();

        $snapshotHostname = self::normalizeHostIdentity((string) ($snapshot['hostname'] ?? ''));
        if ($snapshotHostname !== '') {
            foreach ($hosts as $host) {
                $candidates = [
                    (string) ($host['hostname_override'] ?? ''),
                    (string) ($host['hostname'] ?? ''),
                    (string) ($host['name'] ?? ''),
                ];
                foreach ($candidates as $candidate) {
                    if (self::normalizeHostIdentity($candidate) === $snapshotHostname) {
                        $host['host'] = (string) ($host['hostname'] ?? '');
                        $host['origin_source'] = 'snapshot_hostname';
                        return $host;
                    }
                }
            }
        }

        $uniqueHosts = [];
        foreach ($hosts as $host) {
            $id = (int) ($host['id'] ?? 0);
            if ($id > 0) {
                $uniqueHosts[$id] = $host;
            }
        }

        if (count($uniqueHosts) === 1) {
            $host = reset($uniqueHosts);
            if (is_array($host)) {
                $host['host'] = (string) ($host['hostname'] ?? '');
                $host['origin_source'] = 'single_repo_host';
                return $host;
            }
        }

        return null;
    }

    public static function validateManagedRoot(string $path, string $label = 'geree'): string {
        $normalized = self::normalizeAbsoluteDirectory($path);
        if ($normalized === '' || self::isFilesystemRoot($normalized)) {
            throw new InvalidArgumentException("La racine de restauration $label doit etre un dossier dedie, jamais la racine du systeme.");
        }

        return $normalized;
    }

    public static function ensureLocalManagedDirectory(string $path): string {
        $path = self::validateManagedRoot($path, 'locale');
        if (!is_dir($path) && !@mkdir($path, AppConfig::restoreManagedDirectoryMode(), true)) {
            throw new RuntimeException('Impossible de creer le dossier de restore local: ' . $path);
        }
        @chmod($path, AppConfig::restoreManagedDirectoryMode());
        if (!is_dir($path)) {
            throw new RuntimeException('Le dossier de restore local est introuvable: ' . $path);
        }

        return $path;
    }

    public static function createLocalRestoreStagingDirectory(string $prefix = 'fulgurite_partial_'): string {
        $restoreRoot = self::ensureLocalManagedDirectory(AppConfig::restoreManagedLocalRoot());
        $tmpRoot = self::ensureLocalManagedDirectory(self::joinAbsolutePath($restoreRoot, 'tmp'));
        $safePrefix = preg_replace('/[^A-Za-z0-9_.-]/', '_', $prefix) ?: 'fulgurite_tmp_';
        $stagingDir = rtrim($tmpRoot, '/\\') . '/' . uniqid($safePrefix, true);

        if (!@mkdir($stagingDir, 0700, true)) {
            throw new RuntimeException('Impossible de creer le dossier temporaire de restore: ' . $stagingDir);
        }
        @chmod($stagingDir, 0700);

        return $stagingDir;
    }

    public static function prepareRemoteManagedDirectory(array $sshKey, string $path): array {
        $path = self::validateManagedRoot($path, 'distante');
        $command = 'mkdir -p -- ' . escapeshellarg($path)
            . ' && chmod ' . AppConfig::restoreManagedDirectoryModeLabel()
            . ' -- ' . escapeshellarg($path);

        $tmpKey = SshKeyManager::getTemporaryKeyFile((int) ($sshKey['ssh_key_id'] ?? 0));
        try {
            $result = Restic::runShell(array_merge([
                SSH_BIN,
                '-i', (string) $tmpKey,
                '-p', (string) ((int) ($sshKey['port'] ?? 22)),
            ], SshKnownHosts::sshOptions((string) ($sshKey['host'] ?? ''), (int) ($sshKey['port'] ?? 22), 10), [
                (string) ($sshKey['user'] ?? '') . '@' . (string) ($sshKey['host'] ?? ''),
                $command,
            ]));
        } finally {
            @unlink($tmpKey);
        }

        return SshKnownHosts::finalizeSshResult($result, (string) ($sshKey['host'] ?? ''), (int) ($sshKey['port'] ?? 22), 'restore_prepare_remote_dir');
    }

    public static function syncExtractedTreeToLocal(string $sourceDir, string $destination): array {
        $destination = self::normalizeAbsoluteDirectory($destination);
        if (!self::isFilesystemRoot($destination)) {
            self::ensureLocalManagedDirectory($destination);
        }
        if (!self::isUsableBinary(defined('RSYNC_BIN') ? RSYNC_BIN : '')) {
            return self::copyExtractedTreeWithPhp($sourceDir, $destination);
        }

        return Restic::runShell([
            RSYNC_BIN,
            '-a',
            rtrim($sourceDir, '/\\') . '/',
            rtrim($destination, '/\\') . '/',
        ]);
    }

    public static function syncExtractedTreeToRemote(string $sourceDir, array $sshKey, string $destination): array {
        $destination = self::normalizeAbsoluteDirectory($destination);

        // Check that rsync is available
        if (!self::isUsableBinary(defined('RSYNC_BIN') ? RSYNC_BIN : '')) {
            return ['success' => false, 'output' => 'Erreur: rsync introuvable ou non executable. Ce binaire est requis pour les synchronisations distantes. Verifiez l installation du serveur.'];
        }

        $tmpKey = SshKeyManager::getTemporaryKeyFile((int) ($sshKey['ssh_key_id'] ?? 0));
        try {
            $sshOpts = 'ssh -i ' . escapeshellarg($tmpKey)
                . ' -p ' . (string) ((int) ($sshKey['port'] ?? 22)) . ' '
                . SshKnownHosts::sshOptionsString((string) ($sshKey['host'] ?? ''), (int) ($sshKey['port'] ?? 22), 10);

            $result = Restic::runShell([
                RSYNC_BIN,
                '-az',
                '-e', $sshOpts,
                rtrim($sourceDir, '/\\') . '/',
                (string) ($sshKey['user'] ?? '') . '@' . (string) ($sshKey['host'] ?? '') . ':' . $destination,
            ]);
        } finally {
            @unlink($tmpKey);
        }

        return SshKnownHosts::finalizeSshResult($result, (string) ($sshKey['host'] ?? ''), (int) ($sshKey['port'] ?? 22), 'restore_sync_remote');
    }

    public static function copyExtractedTreeWithPhp(string $sourceDir, string $destination): array {
        if (!is_dir($sourceDir)) {
            return ['success' => false, 'output' => 'Dossier source introuvable: ' . $sourceDir, 'code' => 1];
        }

        $destination = self::normalizeAbsoluteDirectory($destination);
        if (!is_dir($destination) && !@mkdir($destination, AppConfig::restoreManagedDirectoryMode(), true)) {
            return ['success' => false, 'output' => 'Impossible de creer le dossier de destination: ' . $destination, 'code' => 1];
        }

        $errors = [];
        self::copyDirectoryContents($sourceDir, $destination, $errors);

        return [
            'success' => empty($errors),
            'output' => empty($errors)
                ? 'Copie locale effectuee via PHP (rsync indisponible).'
                : implode("\n", array_slice($errors, 0, 20)),
            'code' => empty($errors) ? 0 : 1,
        ];
    }

    public static function relaxExtractedTreePermissions(string $sourceDir): void {
        if (!is_dir($sourceDir)) {
            return;
        }

        self::relaxTreePermissions($sourceDir);
    }

    public static function removeTree(string $path): void {
        if ($path === '' || !file_exists($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $items = @scandir($path);
        if ($items !== false) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                self::removeTree(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $item);
            }
        }
        @rmdir($path);
    }

    public static function normalizeSnapshotPath(string $path): string {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || $path === '.') {
            return '';
        }

        if (!self::looksAbsolute($path)) {
            $path = '/' . ltrim($path, '/');
        }

        return self::collapseSlashes($path);
    }

    public static function normalizeAbsoluteDirectory(string $path): string {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:\//', $path)) {
            return rtrim(self::collapseSlashes($path), '/');
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        $normalized = self::collapseSlashes($path);
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    public static function joinAbsolutePath(string $base, string $suffix = ''): string {
        $base = self::normalizeAbsoluteDirectory($base);
        $suffix = trim(str_replace('\\', '/', $suffix), '/');
        if ($suffix === '') {
            return $base;
        }

        if ($base === '/') {
            return '/' . $suffix;
        }

        return rtrim($base, '/') . '/' . $suffix;
    }

    public static function buildModeTag(bool $partial, string $mode, string $strategy): string {
        $prefix = $partial ? 'partial_' : '';
        return $prefix . $mode . '_' . $strategy;
    }

    public static function findSnapshot(int $repoId, string $snapshotId): ?array {
        return RepoSnapshotCatalog::findSnapshot($repoId, $snapshotId);
    }

    private static function normalizeSamplePaths(mixed $samplePaths, string $include, ?array $snapshot): array {
        $normalized = [];
        foreach ((array) $samplePaths as $path) {
            $path = self::normalizeSnapshotPath((string) $path);
            if ($path !== '') {
                $normalized[] = $path;
            }
        }

        if ($include !== '') {
            $normalized[] = $include;
        }

        if (empty($normalized) && $snapshot) {
            foreach ((array) ($snapshot['paths'] ?? []) as $path) {
                $path = self::normalizeSnapshotPath((string) $path);
                if ($path !== '') {
                    $normalized[] = $path;
                }
            }
        }

        $normalized = array_values(array_unique($normalized));
        return array_slice($normalized, 0, 5);
    }

    private static function buildPreviewPaths(string $effectiveTarget, array $samplePaths): array {
        if (empty($samplePaths)) {
            return [$effectiveTarget];
        }

        $paths = [];
        foreach ($samplePaths as $path) {
            $paths[] = self::joinPreviewPath($effectiveTarget, $path);
        }

        return $paths;
    }

    private static function joinPreviewPath(string $targetRoot, string $snapshotPath): string {
        $snapshotPath = ltrim(self::normalizeSnapshotPath($snapshotPath), '/');
        if ($snapshotPath === '') {
            return $targetRoot;
        }

        if ($targetRoot === '/') {
            return '/' . $snapshotPath;
        }

        return rtrim($targetRoot, '/') . '/' . $snapshotPath;
    }

    private static function resolveManagedRoot(string $mode, ?array $host): string {
        if ($mode === 'remote') {
            $hostOverride = trim((string) ($host['restore_managed_root'] ?? ''));
            if ($hostOverride !== '') {
                return $hostOverride;
            }

            return AppConfig::restoreManagedRemoteRoot();
        }

        return AppConfig::restoreManagedLocalRoot();
    }

    private static function copyDirectoryContents(string $sourceDir, string $destination, array &$errors): void {
        $items = @scandir($sourceDir);
        if ($items === false) {
            $errors[] = 'Lecture impossible: ' . $sourceDir;
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $source = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR . $item;
            $target = rtrim($destination, '/\\') . DIRECTORY_SEPARATOR . $item;

            if (is_link($source)) {
                if (file_exists($target) || is_link($target)) {
                    @unlink($target);
                }
                $linkTarget = @readlink($source);
                if ($linkTarget === false || !@symlink($linkTarget, $target)) {
                    $errors[] = 'Lien symbolique impossible: ' . $target;
                }
                continue;
            }

            if (is_dir($source)) {
                if (!is_dir($target) && !@mkdir($target, 0775, true)) {
                    $errors[] = 'Creation dossier impossible: ' . $target;
                    continue;
                }
                @chmod($target, 0775);
                self::copyDirectoryContents($source, $target, $errors);
                $perms = @fileperms($source);
                if ($perms !== false) {
                    @chmod($target, $perms & 0777);
                }
                continue;
            }

            if (!is_dir(dirname($target)) && !@mkdir(dirname($target), 0775, true)) {
                $errors[] = 'Creation parent impossible: ' . dirname($target);
                continue;
            }
            if (!@copy($source, $target)) {
                $errors[] = 'Copie fichier impossible: ' . $source . ' -> ' . $target;
                continue;
            }
            $perms = @fileperms($source);
            if ($perms !== false) {
                @chmod($target, $perms & 0777);
            }
        }
    }

    private static function relaxTreePermissions(string $path): void {
        if (is_link($path)) {
            return;
        }

        if (is_dir($path)) {
            @chmod($path, 0775);
            $items = @scandir($path);
            if ($items === false) {
                return;
            }
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                self::relaxTreePermissions(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $item);
            }
            return;
        }

        if (is_file($path)) {
            @chmod($path, 0664);
        }
    }

    private static function isUsableBinary(string $path): bool {
        return $path !== '' && is_file($path) && is_executable($path);
    }

    private static function buildContextSubdir(array $repo, string $jobName): string {
        $raw = [];
        if ($jobName !== '') {
            $raw[] = $jobName;
        }
        $raw[] = (string) ($repo['name'] ?? '');

        foreach ($raw as $candidate) {
            $slug = self::slugify($candidate);
            if ($slug !== '') {
                return $slug;
            }
        }

        return 'restore-target';
    }

    private static function assertOriginalDestinationAllowed(int $repoId, string $mode, ?array $snapshot, ?array $host, bool $previewConfirmed = false, array $includedPaths = []): void {
        if (!AppConfig::restoreOriginalGlobalEnabled()) {
            throw new InvalidArgumentException('La restauration originale est desactivee par la configuration globale.');
        }

        $originHost = self::findSnapshotOriginHost($repoId, $snapshot);

        if ($mode === 'remote') {
            if (!$host || empty($host['id'])) {
                throw new InvalidArgumentException('La destination originale distante exige un hote cible explicite.');
            }

            // Per-host opt-in flag (absent => false)
            if (empty($host['restore_original_enabled'])) {
                throw new InvalidArgumentException('La restauration originale distante est desactivee pour cet hote.');
            }

            if (!$originHost) {
                throw new InvalidArgumentException('Impossible de confirmer l hote d origine de ce snapshot. Utilisez un dossier de restores gere.');
            }
            if ((int) ($host['id'] ?? 0) !== (int) ($originHost['id'] ?? 0)) {
                $label = trim((string) ($originHost['name'] ?? $originHost['hostname'] ?? 'hote d origine'));
                throw new InvalidArgumentException('La destination originale distante est limitee a l hote d origine du snapshot: ' . $label . '.');
            }

            if (!$previewConfirmed) {
                throw new InvalidArgumentException('La restauration originale exige d avoir execute et confirme le preview avant de poursuivre.');
            }

            $allowed = AppConfig::restoreOriginalAllowedPaths();
            if (empty($allowed)) {
                // Fail closed: an unconfigured allowlist must never open the whole filesystem.
                // Set restore_original_allowed_paths in the application settings to enable.
                throw new InvalidArgumentException('La restauration originale distante exige une liste de chemins autorises (restore_original_allowed_paths). Configurez les chemins permis dans les parametres de l application avant d activer cette fonctionnalite.');
            }

            // A full-snapshot original restore (no include filter) requires '/' in the allowlist.
            if ($includedPaths === [] && !in_array('/', $allowed, true)) {
                throw new InvalidArgumentException('Une restauration originale sans filtre de chemin exige que "/" figure explicitement dans restore_original_allowed_paths.');
            }

            // normalize included paths and ensure each is under an allowed prefix
            foreach ($includedPaths as $p) {
                $np = self::normalizeSnapshotPath((string) $p);
                $matched = false;
                foreach ($allowed as $a) {
                    $aNorm = rtrim(str_replace('\\', '/', trim($a)), '/');
                    if ($aNorm === '') continue;
                    // allow exact or subpath
                    if ($aNorm === '/') {
                        $matched = true;
                        break;
                    }
                    $aNorm = self::normalizeAbsoluteDirectory($aNorm);
                    if (str_starts_with($np, $aNorm) || $np === $aNorm) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    throw new InvalidArgumentException('Le chemin inclus "' . $np . '" n est pas autorise pour une restauration originale.');
                }
            }

            return;
        }

        throw new InvalidArgumentException('La restauration a la destination originale locale est interdite. Utilisez un dossier de restores gere.');
    }

    private static function buildWarning(string $mode, string $strategy): string {
        if ($strategy === self::STRATEGY_ORIGINAL) {
            return $mode === 'remote'
                ? 'Mode admin: les fichiers seront reposes a leur emplacement d origine sur l hote distant et peuvent ecraser des fichiers existants.'
                : 'Mode stuck: la restauration originale locale est interdite. Utilisez un dossier de restores gere.';
        }

        return $mode === 'remote'
            ? 'Mode recommande: la restauration est forcee dans un dossier dedie sur l hote cible en conservant l arborescence originale.'
            : 'Mode recommande: la restauration est forcee dans un dossier dedie sur ce serveur en conservant l arborescence originale.';
    }

    private static function looksAbsolute(string $path): bool {
        return str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || (bool) preg_match('/^[A-Za-z]:\//', $path);
    }

    private static function isFilesystemRoot(string $path): bool {
        return $path === '/' || (bool) preg_match('/^[A-Za-z]:$/', rtrim($path, '/'));
    }

    private static function collapseSlashes(string $path): string {
        if (preg_match('/^[A-Za-z]:\//', $path)) {
            $prefix = substr($path, 0, 3);
            $rest = preg_replace('#/+#', '/', substr($path, 3));
            return $prefix . ltrim((string) $rest, '/');
        }

        return preg_replace('#/+#', '/', $path) ?: $path;
    }

    private static function normalizeHostIdentity(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return preg_replace('/[^a-z0-9.-]+/', '', $value) ?: '';
    }

    private static function slugify(string $value): string {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['@', '.'], ['-', '-'], $value);
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?: '';
        $value = trim($value, '-_');
        return $value;
    }
}
