<?php

/**
 * ThemeManager — discovery, validation, installation and rendering of themes UI.
 *
 * Deux formats of themes are supported : *
 * 1. Themes "variables" (JSON simple — backward compatibility) :
 * data/themes/<id>.json
 * Define CSS variables only. 100% safe (no code).
 *
 * 2. Themes "advanced" safe (directories installables) : * data/themes/<id>/theme.json metadata + variables
 * data/themes/<id>/style.css CSS libre (filtre contre injection)
 *
 * 3. Themes "trusted code" (deployes locally) : * data/themes_trusted/<id>/theme.json
 * data/themes_trusted/<id>/style.css
 * data/themes_trusted/<id>/slots|parts|pages/*.php
 *
 * Installable themes via UI/zip/store can no longer include PHP.
 * the overrides PHP remain possible, but only for themes
 * explicitement deployes cote server in the canal trusted.
 */
class ThemeManager {
    public const DEFAULT_THEME_ID = 'dark';

    /** Themes built-in, non removable. */    public const BUILTIN_THEMES = ['dark', 'light'];

    /** Liste allowlist of variables CSS accepted in a theme. */
    public const ALLOWED_VARIABLES = [
        '--bg', '--bg2', '--bg3', '--border', '--text', '--text2',
        '--accent', '--accent2', '--green', '--red', '--yellow', '--purple',
        '--font-mono', '--font-sans', '--radius', '--shadow',
    ];

    /** Variables required (otherwise the theme is rejected). */
    public const REQUIRED_VARIABLES = [
        '--bg', '--bg2', '--bg3', '--border', '--text', '--text2',
        '--accent', '--accent2', '--green', '--red', '--yellow', '--purple',
    ];

    /** Slots of layout qu'a theme can override. */
    public const ALLOWED_SLOTS = ['sidebar', 'topbar', 'footer', 'head'];

    /** Parts (partials reusable) that themes or slots can include. */
    public const ALLOWED_PARTS = [
        'sidebar_logo', 'sidebar_nav', 'sidebar_user',
        'topbar_notifications', 'topbar_title',
    ];

    /** Pages that can be fully overridden by a theme. */
    public const ALLOWED_PAGES = [
        'dashboard', 'repos', 'logs', 'notifications', 'restores',
        'stats', 'copy_jobs', 'backup_jobs', 'scheduler', 'hosts',
        'users', 'sshkeys', 'settings', 'performance', 'profile',
    ];

    /** size max of a file theme.json or of a style.css. */
    public const MAX_JSON_BYTES = 64 * 1024;
    public const MAX_CSS_BYTES = 256 * 1024;

    private static ?array $cache = null;

    // ── paths ──────────────────────────────────────────────────────────────

    public static function themesDir(): string {
        return dirname(__DIR__) . '/data/themes';
    }

    public static function trustedThemesDir(): string {
        return dirname(__DIR__) . '/data/themes_trusted';
    }

    public static function builtinDir(): string {
        return __DIR__ . '/themes_builtin';
    }

    /** path of slots by default (extraits of layout_top.php). */
    public static function defaultSlotsDir(): string {
        return __DIR__ . '/themes_builtin/_default/slots';
    }

    // ── Seeding initial ──────────────────────────────────────────────────────

    private static function ensureSeeded(): void {
        $dir = self::themesDir();
        // Detect if the themes directory is created for the first time.
        // Only non-built-in themes (e.g. horizon) are seeded on first install;
        // after removal they do not return automatically.
        $isFirstBoot = !is_dir($dir);
        if ($isFirstBoot) {
            @mkdir($dir, 0755, true);
        }
        $builtinDir = self::builtinDir();
        if (!is_dir($builtinDir)) {
            return;
        }

        // Seed simple JSON files — only themes marked as built-in.
        // They are re-seeded whenever missing (protection).
        foreach ((array) glob($builtinDir . '/*.json') as $src) {
            $id = basename($src, '.json');
            if (!in_array($id, self::BUILTIN_THEMES, true)) {
                continue; // non-built-in JSON themes: seed only on initial install
            }
            $dest = $dir . '/' . basename($src);
            if (!file_exists($dest)) {
                @copy($src, $dest);
            }
        }

        // Seed theme directories.
        // - Built-in themes (BUILTIN_THEMES): re-seeded whenever missing.
        // - Non-built-in themes (e.g. horizon): seeded only once on first install.
        //   Admins can remove them freely afterwards.
        foreach ((array) glob($builtinDir . '/*', GLOB_ONLYDIR) as $srcDir) {
            $name = basename($srcDir);
            if ($name === '' || $name[0] === '_') {
                continue; // _default/ and other meta directories
            }
            $isBuiltin = in_array($name, self::BUILTIN_THEMES, true);
            if (!$isBuiltin && !$isFirstBoot) {
                continue; // non-builtin : seed initial uniquement
            }
            $destDir = $dir . '/' . $name;
            if (!is_dir($destDir)) {
                self::copyDirRecursive($srcDir, $destDir);
            }
        }
    }

    private static function copyDirRecursive(string $src, string $dest): void {
        if (!is_dir($dest)) {
            @mkdir($dest, 0755, true);
        }
        $items = @scandir($src) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcItem = $src . '/' . $item;
            $destItem = $dest . '/' . $item;
            if (is_dir($srcItem)) {
                self::copyDirRecursive($srcItem, $destItem);
            } else {
                @copy($srcItem, $destItem);
            }
        }
    }

    // ── Reading themes ───────────────────────────────────────────────────

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function listThemes(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::ensureSeeded();

        $themes = [];

        // 1. Simple JSON themes (data/themes/*.json)
        foreach ((array) glob(self::themesDir() . '/*.json') as $file) {
            $theme = self::loadJsonTheme((string) $file);
            if ($theme !== null) {
                $theme['builtin'] = self::isBuiltin($theme['id']);
                $theme['installation_mode'] = $theme['builtin'] ? 'builtin' : 'safe';
                $themes[$theme['id']] = $theme;
            }
        }

        // 2. Themes directory (data/themes/<id>/theme.json)
        foreach ((array) glob(self::themesDir() . '/*', GLOB_ONLYDIR) as $dir) {
            $themeId = basename((string) $dir);
            $allowCode = self::shouldExecuteThemeCodeFromThemesDir($themeId);
            $theme = self::loadFolderTheme(
                (string) $dir,
                $allowCode,
                $allowCode ? 'shipped' : 'safe'
            );
            if ($theme !== null) {
                $theme['builtin'] = self::isBuiltin($theme['id']);
                // a theme directory takes precedence over a possible JSON with the same id
                $themes[$theme['id']] = $theme;
            }
        }

        // 3. Themes of trust deployes locally (data/themes_trusted/<id>/theme.json)
        foreach ((array) glob(self::trustedThemesDir() . '/*', GLOB_ONLYDIR) as $dir) {
            $theme = self::loadFolderTheme((string) $dir, true, 'trusted_local');
            if ($theme !== null) {
                $theme['builtin'] = false;
                $themes[$theme['id']] = $theme;
            }
        }

        uasort($themes, static function (array $a, array $b): int {
            $aRank = self::sortRank($a);
            $bRank = self::sortRank($b);
            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        self::$cache = $themes;
        return $themes;
    }

    public static function themeExists(string $id): bool {
        return isset(self::listThemes()[$id]);
    }

    public static function getTheme(string $id): ?array {
        return self::listThemes()[$id] ?? null;
    }

    public static function isBuiltin(string $id): bool {
        return in_array($id, self::BUILTIN_THEMES, true);
    }

    private static function shouldExecuteThemeCodeFromThemesDir(string $id): bool {
        if ($id === '') {
            return false;
        }

        if (self::isBuiltin($id)) {
            return true;
        }

        return is_dir(self::builtinDir() . '/' . $id);
    }

    private static function sortRank(array $theme): int {
        if (!empty($theme['builtin'])) {
            return 0;
        }

        return match ((string) ($theme['installation_mode'] ?? 'safe')) {
            'shipped' => 1,
            'trusted_local' => 2,
            default => 3,
        };
    }

    public static function resolveThemeId(?string $id): string {
        $id = (string) $id;
        if ($id !== '' && self::themeExists($id)) {
            return $id;
        }
        return self::DEFAULT_THEME_ID;
    }

    public static function invalidateCache(): void {
        self::$cache = null;
    }

    // ── Rendering CSS ────────────────────────────────────────────────────────────

    /**
     * Returns CSS of all installed themes (variables only),
     * a injecter in a <style> of <head>.
     */
    public static function renderCss(): string {
        $css = '';
        foreach (self::listThemes() as $theme) {
            $css .= self::renderThemeVariables($theme);
        }
        return $css;
    }

    /**
     * Returns the CSS free-form of a theme "advanced" (style.css), wrapped
     * in a selector of attribut for qu'il ne s'applique qu'to the theme active.     * Empty if the theme has no custom CSS.
     */
    public static function renderThemeStylesheet(string $id): string {
        $theme = self::getTheme($id);
        if ($theme === null || empty($theme['has_css']) || empty($theme['path'])) {
            return '';
        }
        $cssPath = $theme['path'] . '/style.css';
        if (!is_file($cssPath)) {
            return '';
        }
        $raw = (string) @file_get_contents($cssPath);
        if ($raw === '' || strlen($raw) > self::MAX_CSS_BYTES) {
            return '';
        }
        if (!self::isCssSafe($raw)) {
            return '';
        }
        // Wrap for it applies only when data-theme=<id>
        return "/* theme:$id */\n[data-theme=\"$id\"] {\n} \n" . $raw;
    }

    private static function renderThemeVariables(array $theme): string {
        $id = (string) $theme['id'];
        // Note: we no longer use :root as the default theme fallback,
        // because it conflicts in specificity with [data-theme="xxx"]
        // (same score) and lets the last emitted theme override the active one.
        // <html data-theme="..."> is always defined by layout_top.php.
        $selector = '[data-theme="' . $id . '"]';

        $lines = [$selector . '{'];
        foreach ($theme['variables'] as $name => $value) {
            $lines[] = '  ' . $name . ': ' . $value . ';';
        }
        $lines[] = '}';
        return implode("\n", $lines) . "\n";
    }

    // ── Slots (templates) ────────────────────────────────────────────────────

    /**
     * Returns the path of file of template for a slot given.
     * Cherche of first in the theme active, otherwise falls back on the template by default.
     */
    public static function resolveSlotPath(string $slot, string $themeId): string {
        if (!in_array($slot, self::ALLOWED_SLOTS, true)) {
            return self::defaultSlotsDir() . '/' . preg_replace('/[^a-z]/', '', $slot) . '.php';
        }
        $theme = self::getTheme($themeId);
        if ($theme !== null && !empty($theme['slots'][$slot])) {
            return (string) $theme['slots'][$slot];
        }
        return self::defaultSlotsDir() . '/' . $slot . '.php';
    }

    /** path absolute of a slot by default (force the fallback, ignore the override of theme). */
    public static function defaultSlotPath(string $slot): string {
        if (!in_array($slot, self::ALLOWED_SLOTS, true)) return '';
        return self::defaultSlotsDir() . '/' . $slot . '.php';
    }

    /** path of parts by default (reutilisables). */
    public static function defaultPartsDir(): string {
        return __DIR__ . '/themes_builtin/_default/parts';
    }

    /**
     * Resolves the path of a part : of first in <theme>/parts/<name>.php,
     * otherwise in the parts by default.
     */
    public static function resolvePartPath(string $part, string $themeId): string {
        if (!in_array($part, self::ALLOWED_PARTS, true)) return '';
        $theme = self::getTheme($themeId);
        if ($theme !== null && !empty($theme['parts'][$part])) {
            return (string) $theme['parts'][$part];
        }
        return self::defaultPartsDir() . '/' . $part . '.php';
    }

    /**
     * Resolves the path of a template of page for a theme (s'il en provides a).
     * Returns null if the theme does not override this page.
     */
    public static function resolvePagePath(string $pageId, string $themeId): ?string {
        if (!in_array($pageId, self::ALLOWED_PAGES, true)) return null;
        $theme = self::getTheme($themeId);
        if ($theme === null || empty($theme['pages'][$pageId])) return null;
        return (string) $theme['pages'][$pageId];
    }

    // ── Installation / suppression ───────────────────────────────────────────

    /**
     * installed a theme JSON simple (variables only) from son content raw.     * "advanced" themes with PHP overrides must be deployed via
     * data/themes_trusted/ for of reasons of security.
     *
     * @return array{ok:bool,errors?:string[],id?:string}
     */
    public static function install(string $jsonContent, bool $overwrite = false): array {
        $data = @json_decode($jsonContent, true);
        if (!is_array($data)) {
            return ['ok' => false, 'errors' => ['JSON invalide ou mal forme.']];
        }

        $result = self::validate($data);
        if (!$result['ok']) {
            return $result;
        }

        $normalized = $result['normalized'];
        $id = $normalized['id'];

        // if a directory already exists for this id, refuse (directory takes priority)
        $folderPath = self::themesDir() . '/' . $id;
        if (is_dir($folderPath)) {
            return [
                'ok' => false,
                'errors' => ["Un theme dossier existe deja sous data/themes/$id/. Modifiez-le directement sur le disque ou supprimez-le d'abord."],
            ];
        }

        $path = self::themesDir() . '/' . $id . '.json';
        if (file_exists($path) && !$overwrite) {
            return [
                'ok' => false,
                'errors' => ["Un theme avec l'identifiant '$id' existe deja. Cochez 'ecraser' pour le remplacer."],
            ];
        }

        self::ensureSeeded();
        $payload = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payload === false || @file_put_contents($path, $payload) === false) {
            return ['ok' => false, 'errors' => ['Impossible d ecrire le fichier sur le disque.']];
        }

        self::invalidateCache();
        return ['ok' => true, 'id' => $id];
    }

    /**
     * Remove a theme installed (the builtins are protected).
     * Removes either a JSON file or a full directory.
     *
     * @return array{ok:bool,errors?:string[]}
     */
    public static function delete(string $id): array {
        if (self::isBuiltin($id)) {
            return ['ok' => false, 'errors' => ['Les themes integres ne peuvent pas etre supprimes.']];
        }
        if (!preg_match('/^[a-z0-9_-]{1,32}$/', $id)) {
            return ['ok' => false, 'errors' => ['Identifiant de theme invalide.']];
        }
        if (is_dir(self::trustedThemesDir() . '/' . $id)) {
            return ['ok' => false, 'errors' => ['Ce theme trusted est deployee via data/themes_trusted/. Supprimez-le manuellement sur le serveur.']];
        }

        $jsonPath = self::themesDir() . '/' . $id . '.json';
        $dirPath = self::themesDir() . '/' . $id;
        $deleted = false;

        if (is_file($jsonPath)) {
            if (!@unlink($jsonPath)) {
                return ['ok' => false, 'errors' => ['Impossible de supprimer le fichier.']];
            }
            $deleted = true;
        }
        if (is_dir($dirPath)) {
            if (!self::removeDirRecursive($dirPath)) {
                return ['ok' => false, 'errors' => ['Impossible de supprimer le dossier du theme.']];
            }
            $deleted = true;
        }

        if (!$deleted) {
            return ['ok' => false, 'errors' => ['Theme introuvable.']];
        }
        self::invalidateCache();
        return ['ok' => true];
    }

    private static function removeDirRecursive(string $dir): bool {
        if (!is_dir($dir)) return true;
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                if (!self::removeDirRecursive($path)) return false;
            } else {
                if (!@unlink($path)) return false;
            }
        }
        return @rmdir($dir);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * validates a definition of theme (fields metadata + variables CSS).
     * @return array{ok:bool,errors:string[],normalized:array}
     */
    public static function validate(array $data): array {
        $errors = [];

        $id = strtolower(trim((string) ($data['id'] ?? '')));
        if (!preg_match('/^[a-z0-9_-]{1,32}$/', $id)) {
            $errors[] = "Identifiant invalide (1-32 caracteres : a-z, 0-9, _, -).";
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Nom invalide (requis, max 100 caracteres).';
        }

        $description = trim((string) ($data['description'] ?? ''));
        if (mb_strlen($description) > 500) {
            $errors[] = 'Description trop longue (max 500 caracteres).';
        }

        $author = trim((string) ($data['author'] ?? ''));
        if (mb_strlen($author) > 200) {
            $errors[] = 'Auteur trop long (max 200 caracteres).';
        }

        $version = trim((string) ($data['version'] ?? '1.0'));
        if ($version === '' || !preg_match('/^[0-9a-zA-Z.\-_]{1,20}$/', $version)) {
            $errors[] = 'Version invalide (1-20 caracteres alphanumeriques).';
            $version = '1.0';
        }

        $type = strtolower(trim((string) ($data['type'] ?? 'variables')));
        if (!in_array($type, ['variables', 'advanced'], true)) {
            $errors[] = 'Type de theme invalide (attendu : variables ou advanced).';
            $type = 'variables';
        }

        $rawVars = $data['variables'] ?? [];
        if (!is_array($rawVars)) {
            $errors[] = 'Le champ "variables" doit etre un objet.';
            $rawVars = [];
        }

        $variables = [];
        foreach ($rawVars as $key => $value) {
            $key = (string) $key;
            if (!in_array($key, self::ALLOWED_VARIABLES, true)) {
                $errors[] = "Variable non autorisee : {$key}";
                continue;
            }
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                $errors[] = "Valeur invalide pour {$key} (chaine attendue).";
                continue;
            }
            $value = trim((string) $value);
            if ($value === '') {
                $errors[] = "Valeur vide pour {$key}.";
                continue;
            }
            if (!self::isValidCssValue($key, $value)) {
                $errors[] = "Valeur CSS refusee pour {$key} : {$value}";
                continue;
            }
            $variables[$key] = $value;
        }

        foreach (self::REQUIRED_VARIABLES as $required) {
            if (!isset($variables[$required])) {
                $errors[] = "Variable requise manquante : {$required}";
            }
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
            'normalized' => [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'author' => $author,
                'version' => $version,
                'type' => $type,
                'variables' => $variables,
            ],
        ];
    }

    /**
     * Filtre a feuille style.css : refuse the directives potentiellement
     * dangereuses (@import externe, expression(), javascript:, behavior:...).
     */
    public static function isCssSafe(string $css): bool {
        if (mb_strlen($css) > self::MAX_CSS_BYTES) return false;
        $lower = strtolower($css);
        $forbidden = [
            'expression(',
            'javascript:',
            'behavior:',
            '-moz-binding',
            '<script',
            '</style',
        ];
        foreach ($forbidden as $needle) {
            if (strpos($lower, $needle) !== false) return false;
        }
        // @import allowed only to https:// or relative paths
        // (never data:, file:, javascript:).
        if (preg_match_all('/@import\s+(?:url\()?["\']?([^"\')]+)/i', $css, $matches)) {
            foreach ($matches[1] as $target) {
                $t = strtolower(trim($target));
                if (!str_starts_with($t, 'https://') && !str_starts_with($t, '/') && !str_starts_with($t, './') && !str_starts_with($t, '../')) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validates a CSS value according to variable type.
     * Blocks any CSS injection attempt.
     */
    private static function isValidCssValue(string $varName, string $value): bool {
        if (preg_match('/[{};<>\\\\]/', $value)) {
            return false;
        }
        $lower = strtolower($value);
        if (strpos($lower, '@import') !== false
            || strpos($lower, 'expression') !== false
            || strpos($lower, 'javascript:') !== false) {
            return false;
        }

        if ($varName === '--font-mono' || $varName === '--font-sans') {
            return (bool) preg_match("/^[a-zA-Z0-9 ,'\"\\-_.]{1,200}$/", $value);
        }

        if ($varName === '--radius') {
            return (bool) preg_match('/^\d{1,3}(\.\d+)?(px|em|rem|%)?$/', $value);
        }

        if ($varName === '--shadow') {
            if (mb_strlen($value) > 300) {
                return false;
            }
            return (bool) preg_match('/^[a-zA-Z0-9 ,.\-#()%\/]+$/', $value);
        }

        return self::isValidColor($value);
    }

    private static function isValidColor(string $value): bool {
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1) {
            return true;
        }
        if (preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[0-9 ,.\-%\/]+\s*\)$/i', $value) === 1) {
            return true;
        }
        if (preg_match('/^[a-zA-Z]{3,20}$/', $value) === 1) {
            return true;
        }
        return false;
    }

    // ── Helpers prives ───────────────────────────────────────────────────────

    /** Loaded a theme JSON simple (data/themes/xxx.json). */
    private static function loadJsonTheme(string $path): ?array {
        if (!is_file($path)) return null;
        if (filesize($path) > self::MAX_JSON_BYTES) return null;
        $content = @file_get_contents($path);
        if ($content === false) return null;
        $data = @json_decode($content, true);
        if (!is_array($data)) return null;
        $result = self::validate($data);
        if (!$result['ok']) return null;
        $theme = $result['normalized'];
        $theme['path'] = null;
        $theme['has_css'] = false;
        $theme['slots'] = [];
        $theme['parts'] = [];
        $theme['pages'] = [];
        $theme['executes_php'] = false;
        return $theme;
    }

    /**
     * Loaded a theme directory.
     *
     * - $allowCode=false: installable/safe theme, no PHP override is loaded
     * - $allowCode=true : theme trusted, the overrides PHP are enabled
     */
    private static function loadFolderTheme(string $dir, bool $allowCode = false, string $installationMode = 'safe'): ?array {
        $jsonPath = $dir . '/theme.json';
        if (!is_file($jsonPath)) return null;
        if (filesize($jsonPath) > self::MAX_JSON_BYTES) return null;
        $content = @file_get_contents($jsonPath);
        if ($content === false) return null;
        $data = @json_decode($content, true);
        if (!is_array($data)) return null;

        // Force the id from the nom of directory
        $data['id'] = basename($dir);

        $result = self::validate($data);
        if (!$result['ok']) return null;
        $theme = $result['normalized'];
        $theme['path'] = $dir;
        $theme['installation_mode'] = $installationMode;

        // CSS optionnel
        $cssPath = $dir . '/style.css';
        $theme['has_css'] = is_file($cssPath) && filesize($cssPath) <= self::MAX_CSS_BYTES;

        // Slots optionnels
        $theme['slots'] = [];
        if ($allowCode) {
            $slotsDir = $dir . '/slots';
            if (is_dir($slotsDir)) {
                foreach (self::ALLOWED_SLOTS as $slot) {
                    $candidate = $slotsDir . '/' . $slot . '.php';
                    if (is_file($candidate)) {
                        $theme['slots'][$slot] = $candidate;
                    }
                }
            }
        }

        // Parts optionnelles (partials reutilisables)
        $theme['parts'] = [];
        if ($allowCode) {
            $partsDir = $dir . '/parts';
            if (is_dir($partsDir)) {
                foreach (self::ALLOWED_PARTS as $part) {
                    $candidate = $partsDir . '/' . $part . '.php';
                    if (is_file($candidate)) {
                        $theme['parts'][$part] = $candidate;
                    }
                }
            }
        }

        // Pages optionnelles (surcharges completes)
        $theme['pages'] = [];
        if ($allowCode) {
            $pagesDir = $dir . '/pages';
            if (is_dir($pagesDir)) {
                foreach (self::ALLOWED_PAGES as $page) {
                    $candidate = $pagesDir . '/' . $page . '.php';
                    if (is_file($candidate)) {
                        $theme['pages'][$page] = $candidate;
                    }
                }
            }
        }

        $theme['executes_php'] = !empty($theme['slots']) || !empty($theme['parts']) || !empty($theme['pages']);

        return $theme;
    }
}
