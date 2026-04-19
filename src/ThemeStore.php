<?php

/**
 * ThemeStore — installable themes catalog.
 *
 * Catalog is stored in src/theme_store.json (shipped with the application).
 * Admins can extend it locally by creating data/theme_store_extra.json
 * with the same format.
 * Each store entry can be:
 * - inline: directly contains the definition (variables type, no code)
 * - source_url: points to a safe remote zip archive (no executable PHP)
 *
 * Store entries follow the same "safe theme" pipeline as other installable
 * packages. No server code is accepted via source_url.
 */
class ThemeStore {

    private static ?array $cache = null;

    public static function builtinManifest(): string {
        return __DIR__ . '/theme_store.json';
    }

    public static function extraManifest(): string {
        return dirname(__DIR__) . '/data/theme_store_extra.json';
    }

    public static function invalidateCache(): void {
        self::$cache = null;
    }

    /**
     * Returns the liste of entries of catalog.
     * @return array<int,array<string,mixed>>
     */
    public static function listEntries(): array {
        if (self::$cache !== null) return self::$cache;

        $entries = [];
        foreach ([self::builtinManifest(), self::extraManifest()] as $path) {
            if (!is_file($path)) continue;
            $raw = @file_get_contents($path);
            if ($raw === false) continue;
            $data = @json_decode($raw, true);
            if (!is_array($data) || !isset($data['themes']) || !is_array($data['themes'])) continue;
            foreach ($data['themes'] as $entry) {
                if (!is_array($entry) || empty($entry['id'])) continue;
                $entries[(string) $entry['id']] = self::normalizeEntry($entry);
            }
        }

        $entries = array_values($entries);
        usort($entries, static fn($a, $b) => strcasecmp((string) $a['name'], (string) $b['name']));
        self::$cache = $entries;
        return $entries;
    }

    public static function getEntry(string $id): ?array {
        foreach (self::listEntries() as $entry) {
            if ($entry['id'] === $id) return $entry;
        }
        return null;
    }

    /**
     * installed a theme from the store.
     * - if entry has "inline", install directly via ThemeManager::install
     * - if entry has "source_url", download, extract and install via ThemePackage
     *
     * @return array{ok:bool,errors?:string[],id?:string}
     */
    public static function install(string $entryId, bool $overwrite = false): array {
        $entry = self::getEntry($entryId);
        if ($entry === null) {
            return ['ok' => false, 'errors' => ['Entree introuvable dans le store.']];
        }

        if (isset($entry['inline']) && is_array($entry['inline'])) {
            $json = (string) json_encode($entry['inline']);
            return ThemeManager::install($json, $overwrite);
        }

        if (!empty($entry['source_url'])) {
            $fetched = ThemePackage::fetchUrl((string) $entry['source_url']);
            if (!$fetched['ok']) return $fetched;
            $zipPath = $fetched['path'];

            $extracted = ThemePackage::extract($zipPath);
            @unlink($zipPath);
            if (!$extracted['ok']) return $extracted;

            $result = ThemePackage::install($extracted['path'], $overwrite);
            ThemePackage::removeDirRecursive($extracted['path']);
            return $result;
        }

        return ['ok' => false, 'errors' => ['Entree du store sans inline ni source_url.']];
    }

    private static function normalizeEntry(array $entry): array {
        return [
            'id' => (string) ($entry['id'] ?? ''),
            'name' => (string) ($entry['name'] ?? $entry['id'] ?? ''),
            'description' => (string) ($entry['description'] ?? ''),
            'author' => (string) ($entry['author'] ?? ''),
            'version' => (string) ($entry['version'] ?? '1.0'),
            'type' => (string) ($entry['type'] ?? 'variables'),
            'trusted' => !empty($entry['trusted']),
            'source_url' => isset($entry['source_url']) ? (string) $entry['source_url'] : null,
            'inline' => isset($entry['inline']) && is_array($entry['inline']) ? $entry['inline'] : null,
            'preview_variables' => self::extractPreviewVars($entry),
        ];
    }

    private static function extractPreviewVars(array $entry): array {
        $vars = [];
        if (isset($entry['inline']['variables']) && is_array($entry['inline']['variables'])) {
            $vars = $entry['inline']['variables'];
        } elseif (isset($entry['preview_variables']) && is_array($entry['preview_variables'])) {
            $vars = $entry['preview_variables'];
        }
        $keys = ['--bg', '--bg2', '--accent', '--accent2', '--green', '--red', '--yellow', '--purple'];
        $out = [];
        foreach ($keys as $k) {
            if (isset($vars[$k])) $out[$k] = (string) $vars[$k];
        }
        return $out;
    }
}
