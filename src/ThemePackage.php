<?php

/**
 * ThemePackage — manipulation of packages of themes (file.zip or URL remote). *
 * Gere trois operations :
 * - extract() : extracts a zip in a directory temporary, en validant the security
 * - fetchUrl() : telecharge a package from a URL https (protection SSRF)
 * - install() : validates a package extracts and the installed in data/themes/<id>/
 *
 * All operations enforce strict limits:
 * - size max of zip, size max totale after decompression
 * - liste blanche of files allowed in a package
 * - refus of paths traversals (.., paths absolus)
 * - URLs https only, rejet of IPs internes * - installable packages cannot contain executable PHP
 * - the themes with code must be deployes locally in * data/themes_trusted/ and do not pass through this pipeline
 */
class ThemePackage {
    public const MAX_ZIP_BYTES = 2 * 1024 * 1024;         // 2 Mo en archive
    public const MAX_UNCOMPRESSED_BYTES = 8 * 1024 * 1024; // 8 Mo decompresses
    public const MAX_FILES = 200;
    public const FETCH_TIMEOUT = 15;

    /** Liste blanche of files allowed in a package (relatif a the racine of zip). */
    public const ALLOWED_FILENAMES = [
        'theme.json',
        'style.css',
        'README.md',
        'LICENSE',
        'LICENSE.txt',
    ];

    public const PENDING_DIR = 'themes_pending';

    // ─── Extraction of a zip ─────────────────────────────────────────────────

    /**
     * extracts a file zip in a directory temporary.
     * Applies all security checks.
     *
     * @return array{ok:bool,errors?:string[],path?:string,manifest?:array}
     */
    public static function extract(string $zipPath): array {
        if (!class_exists('ZipArchive')) {
            return ['ok' => false, 'errors' => ["L'extension PHP zip n'est pas installee sur le serveur."]];
        }
        if (!is_file($zipPath)) {
            return ['ok' => false, 'errors' => ['Fichier zip introuvable.']];
        }
        if (filesize($zipPath) > self::MAX_ZIP_BYTES) {
            return ['ok' => false, 'errors' => ['Archive trop volumineuse (max ' . (self::MAX_ZIP_BYTES / 1024) . ' Ko).']];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'errors' => ['Archive zip invalide ou corrompue.']];
        }

        if ($zip->numFiles > self::MAX_FILES) {
            $zip->close();
            return ['ok' => false, 'errors' => ['Trop de fichiers dans l archive (max ' . self::MAX_FILES . ').']];
        }

        // Check each entry before extraction: total size, paths, filenames
        $totalSize = 0;
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                $zip->close();
                return ['ok' => false, 'errors' => ['Entree de zip illisible.']];
            }
            $name = (string) $stat['name'];
            $totalSize += (int) $stat['size'];
            if ($totalSize > self::MAX_UNCOMPRESSED_BYTES) {
                $zip->close();
                return ['ok' => false, 'errors' => ['Archive trop volumineuse apres decompression (potentielle zip bomb).']];
            }
            $validation = self::validateEntryName($name);
            if ($validation !== null) {
                $zip->close();
                return ['ok' => false, 'errors' => ["Fichier refuse : $name ($validation)"]];
            }
            $entries[] = $name;
        }

        // Some archives wrap everything in a root directory: detect and strip it.
        $rootPrefix = self::detectRootPrefix($entries);

        // directory of extraction temporary
        $tmpDir = sys_get_temp_dir() . '/fulgurite_theme_' . bin2hex(random_bytes(8));
        if (!@mkdir($tmpDir, 0700, true)) {
            $zip->close();
            return ['ok' => false, 'errors' => ['Impossible de creer le dossier temporaire.']];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) continue;
            $name = (string) $stat['name'];
            if (str_ends_with($name, '/')) continue; // directory

            $relName = $rootPrefix !== '' && str_starts_with($name, $rootPrefix)
                ? substr($name, strlen($rootPrefix))
                : $name;
            if ($relName === '') continue;

            $targetPath = $tmpDir . '/' . $relName;
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0700, true);
            }

            $stream = $zip->getStream($name);
            if ($stream === false) continue;
            $content = stream_get_contents($stream);
            fclose($stream);
            if ($content === false) continue;
            @file_put_contents($targetPath, $content);
        }
        $zip->close();

        // Read theme.json
        $manifestPath = $tmpDir . '/theme.json';
        if (!is_file($manifestPath)) {
            self::removeDirRecursive($tmpDir);
            return ['ok' => false, 'errors' => ['theme.json manquant a la racine du paquet.']];
        }
        $manifest = @json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            self::removeDirRecursive($tmpDir);
            return ['ok' => false, 'errors' => ['theme.json invalide (JSON mal forme).']];
        }

        $validation = ThemeManager::validate($manifest);
        if (!$validation['ok']) {
            self::removeDirRecursive($tmpDir);
            return ['ok' => false, 'errors' => $validation['errors']];
        }

        $treeValidation = self::validateExtractedThemeTree($tmpDir);
        if ($treeValidation !== []) {
            self::removeDirRecursive($tmpDir);
            return ['ok' => false, 'errors' => $treeValidation];
        }

        // checks style.css if present
        $cssPath = $tmpDir . '/style.css';
        if (is_file($cssPath)) {
            $css = (string) file_get_contents($cssPath);
            if (!ThemeManager::isCssSafe($css)) {
                self::removeDirRecursive($tmpDir);
                return ['ok' => false, 'errors' => ['Le style.css contient des directives interdites.']];
            }
        }

        return [
            'ok' => true,
            'path' => $tmpDir,
            'manifest' => $validation['normalized'],
        ];
    }

    /**
     * installed a package extracts to data/themes/<id>/.
     * Delete source directory at the end.
     *
     * @return array{ok:bool,errors?:string[],id?:string}
     */
    public static function install(string $extractedPath, bool $overwrite = false): array {
        $manifestPath = $extractedPath . '/theme.json';
        if (!is_file($manifestPath)) {
            return ['ok' => false, 'errors' => ['theme.json manquant dans le paquet extrait.']];
        }
        $manifest = @json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            return ['ok' => false, 'errors' => ['theme.json invalide.']];
        }
        $validation = ThemeManager::validate($manifest);
        if (!$validation['ok']) {
            return ['ok' => false, 'errors' => $validation['errors']];
        }
        $id = $validation['normalized']['id'];
        $destDir = ThemeManager::themesDir() . '/' . $id;
        $destJson = ThemeManager::themesDir() . '/' . $id . '.json';

        if ((is_dir($destDir) || is_file($destJson)) && !$overwrite) {
            return ['ok' => false, 'errors' => ["Un theme avec l'id '$id' existe deja."]];
        }

        // Delete existing target if overwrite
        if ($overwrite) {
            if (is_file($destJson)) @unlink($destJson);
            if (is_dir($destDir)) self::removeDirRecursive($destDir);
        }

        if (!@mkdir($destDir, 0755, true)) {
            return ['ok' => false, 'errors' => ['Impossible de creer le dossier de destination.']];
        }

        // Copie only the files safe explicitement allowed.        self::copyValidatedTheme($extractedPath, $destDir);

        // Rewrites theme.json normalized for avoid the fields extra
        $normalized = $validation['normalized'];
        $normalized['id'] = $id; // force
        @file_put_contents(
            $destDir . '/theme.json',
            (string) json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        ThemeManager::invalidateCache();
        return ['ok' => true, 'id' => $id];
    }

    /**
     * Telecharge a package zip from a URL https (protection SSRF).
     * Returns the path local of zip downloads.
     *
     * @return array{ok:bool,errors?:string[],path?:string}
     */
    public static function fetchUrl(string $url): array {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'errors' => ['URL vide.']];
        }
        // Special-case GitHub : convertit blob/tree en archive zip
        $url = self::normalizeGithubUrl($url);

        $download = self::downloadUrlWithValidatedRedirects($url);
        if (!$download['ok']) {
            return $download;
        }
        $data = $download['data'];

        $tmpFile = sys_get_temp_dir() . '/fulgurite_theme_dl_' . bin2hex(random_bytes(6)) . '.zip';
        if (@file_put_contents($tmpFile, $data) === false) {
            return ['ok' => false, 'errors' => ['Impossible d ecrire le fichier telecharge.']];
        }

        // checks the signature zip (PK\x03\x04)
        if (substr($data, 0, 4) !== "PK\x03\x04") {
            @unlink($tmpFile);
            return ['ok' => false, 'errors' => ['Le contenu telecharge n est pas un zip valide.']];
        }

        return ['ok' => true, 'path' => $tmpFile];
    }

    /**
     * Downloads a URL via OutboundHttpClient with IP pinning.
     *
     * Each hop of redirection is revalide by PublicOutboundUrlValidator and the     * connection is anchored to resolved and validated IP via CURLOPT_RESOLVE.
     * Cela elimine toute fenetre TOCTOU / DNS rebinding entre the validation and the
     * connection reelle.
     *
     * @return array{ok:bool,errors?:string[],data?:string}
     */
    private static function downloadUrlWithValidatedRedirects(string $url): array {
        $validator = new PublicOutboundUrlValidator();
        try {
            $response = OutboundHttpClient::request('GET', $url, [
                'headers' => ['Accept: application/zip, application/octet-stream'],
                'timeout' => self::FETCH_TIMEOUT,
                'connect_timeout' => 5,
                'max_redirects' => 5,
                'user_agent' => 'Fulgurite/1.0',
            ], $validator);
        } catch (InvalidArgumentException $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }

        if (!$response['success']) {
            $error = $response['error'] ?? 'Impossible de telecharger l archive depuis l URL.';
            return ['ok' => false, 'errors' => [$error]];
        }

        $data = (string) ($response['body'] ?? '');
        if (strlen($data) > self::MAX_ZIP_BYTES) {
            return ['ok' => false, 'errors' => ['Archive telechargee trop volumineuse.']];
        }

        return ['ok' => true, 'data' => $data];
    }

    // ─── management of pending files (user requests) ──────────────

    public static function pendingDir(): string {
        $dir = dirname(__DIR__) . '/data/' . self::PENDING_DIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Stocke a zip uploade in data/themes_pending/<slug>.zip for revue.
     * the slug is generates aleatoirement for eviter the collisions.
     */
    public static function storePendingFile(string $tmpPath): array {
        if (!is_file($tmpPath)) {
            return ['ok' => false, 'errors' => ['Fichier temporaire introuvable.']];
        }
        if (filesize($tmpPath) > self::MAX_ZIP_BYTES) {
            return ['ok' => false, 'errors' => ['Archive trop volumineuse.']];
        }
        $slug = bin2hex(random_bytes(8)) . '.zip';
        $dest = self::pendingDir() . '/' . $slug;
        if (!@copy($tmpPath, $dest)) {
            return ['ok' => false, 'errors' => ['Impossible d enregistrer le fichier en attente.']];
        }
        return ['ok' => true, 'filename' => $slug, 'path' => $dest];
    }

    public static function pendingFilePath(string $filename): ?string {
        if (!preg_match('/^[a-f0-9]{16}\.zip$/', $filename)) return null;
        $path = self::pendingDir() . '/' . $filename;
        return is_file($path) ? $path : null;
    }

    public static function deletePendingFile(string $filename): void {
        $path = self::pendingFilePath($filename);
        if ($path !== null) {
            @unlink($path);
        }
    }

    // ─── Helpers prives ──────────────────────────────────────────────────────

    /**
     * validates a internal file path in zip.
     * Returns null if OK, otherwise a reason textuelle.
     */
    private static function validateEntryName(string $name): ?string {
        if ($name === '') return 'vide';
        if (str_contains($name, "\0")) return 'null byte';
        if ($name[0] === '/') return 'chemin absolu';
        if (preg_match('#(^|/)\.\.(/|$)#', $name)) return 'traversal';
        if (preg_match('#(^|/)\.#', $name)) return 'fichier cache'; //.htaccess,.git, etc.
        if (str_ends_with($name, '/')) return null; // directory, OK

        // Strip a eventuel prefixe "monthem/" for the validation of basename
        $parts = explode('/', $name);
        $basename = end($parts);
        if ($basename === '' || $basename === false) return 'basename vide';

        // Rejet explicite of paths a plus of 2 niveaux of profondeur
        if (count($parts) > 3) return 'profondeur > 3';

        // Extensions allowed for the packages installables (never of PHP)
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $allowedExts = ['json', 'css', 'md', 'txt'];
        if (!in_array($ext, $allowedExts, true)) {
            return 'extension refusee (.' . $ext . ')';
        }

        return null;
    }

    /** Detects whether all files are packed in a single root directory. */
    private static function detectRootPrefix(array $entries): string {
        $candidate = null;
        foreach ($entries as $entry) {
            if ($entry === '' || $entry[0] === '/') continue;
            $slash = strpos($entry, '/');
            if ($slash === false) return ''; // file a the racine : pas of prefixe commun
            $prefix = substr($entry, 0, $slash + 1);
            if ($candidate === null) $candidate = $prefix;
            elseif ($candidate !== $prefix) return '';
        }
        return (string) $candidate;
    }

    private static function copyValidatedTheme(string $src, string $dest): void {
        $items = @scandir($src) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcItem = $src . '/' . $item;
            $destItem = $dest . '/' . $item;
            if (is_dir($srcItem)) continue;
            if (!in_array($item, self::ALLOWED_FILENAMES, true)) continue;
            @copy($srcItem, $destItem);
        }
    }

    /**
     * a package installable must remain "safe":     * - files allowed only a the racine     * - no PHP overrides directory
     * - no executable server code
     *
     * @return string[]
     */
    private static function validateExtractedThemeTree(string $root): array {
        $errors = [];
        $items = @scandir($root) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $root . '/' . $item;
            if (is_dir($path)) {
                $errors[] = "Les dossiers $item/ sont reserves aux themes trusted deployes localement dans data/themes_trusted/.";
                continue;
            }

            if (!in_array($item, self::ALLOWED_FILENAMES, true)) {
                $errors[] = "Fichier non autorise dans un paquet installable : $item";
            }
        }

        return $errors;
    }

    public static function removeDirRecursive(string $dir): void {
        if (!is_dir($dir)) return;
        foreach ((array) scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::removeDirRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Convertit a URL github.com/user/repo (or /tree/branch) en URL of archive zip.
     */
    private static function normalizeGithubUrl(string $url): string {
        if (!preg_match('#^https://github\.com/([^/]+)/([^/]+)(/tree/([^/]+))?/?$#', $url, $m)) {
            return $url;
        }
        $user = $m[1];
        $repo = rtrim($m[2], '/');
        $branch = $m[4] ?? 'main';
        return "https://codeload.github.com/$user/$repo/zip/refs/heads/$branch";
    }
}
