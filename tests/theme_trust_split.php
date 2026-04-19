<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
require_once $root . '/src/bootstrap.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue(bool $value, string $message): void {
    if (!$value) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function writeThemeManifest(string $path, string $id, string $name): void {
    $manifest = [
        'id' => $id,
        'name' => $name,
        'description' => 'Theme de test',
        'author' => 'Tests',
        'version' => '1.0',
        'type' => 'advanced',
        'variables' => [
            '--bg' => '#0d1117',
            '--bg2' => '#161b22',
            '--bg3' => '#21262d',
            '--border' => '#30363d',
            '--text' => '#e6edf3',
            '--text2' => '#8b949e',
            '--accent' => '#58a6ff',
            '--accent2' => '#1f6feb',
            '--green' => '#3fb950',
            '--red' => '#f85149',
            '--yellow' => '#d29922',
            '--purple' => '#bc8cff',
        ],
    ];

    file_put_contents(
        $path,
        (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}

$safeId = 'unit-safe-' . bin2hex(random_bytes(4));
$trustedId = 'unit-trusted-' . bin2hex(random_bytes(4));
$safeDir = ThemeManager::themesDir() . '/' . $safeId;
$trustedDir = ThemeManager::trustedThemesDir() . '/' . $trustedId;
$tmpZip = null;

try {
    @mkdir($safeDir . '/slots', 0755, true);
    @mkdir($trustedDir . '/slots', 0755, true);
    @mkdir($trustedDir . '/parts', 0755, true);
    @mkdir($trustedDir . '/pages', 0755, true);

    writeThemeManifest($safeDir . '/theme.json', $safeId, 'Safe theme');
    writeThemeManifest($trustedDir . '/theme.json', $trustedId, 'Trusted theme');

    file_put_contents($safeDir . '/style.css', '[data-theme="' . $safeId . '"] .safe-theme { color: red; }');
    file_put_contents($safeDir . '/slots/sidebar.php', '<aside>unsafe slot</aside>');

    file_put_contents($trustedDir . '/style.css', '[data-theme="' . $trustedId . '"] .trusted-theme { color: blue; }');
    file_put_contents($trustedDir . '/slots/sidebar.php', '<?php echo "<aside>trusted slot</aside>";');
    file_put_contents($trustedDir . '/parts/sidebar_logo.php', '<?php echo "<div>trusted part</div>";');
    file_put_contents($trustedDir . '/pages/dashboard.php', '<?php echo "<section>trusted page</section>";');

    ThemeManager::invalidateCache();

    $safeTheme = ThemeManager::getTheme($safeId);
    assertTrueValue(is_array($safeTheme), 'Safe advanced theme was not discovered.');
    assertSameValue('safe', $safeTheme['installation_mode'], 'Safe theme installation mode mismatch.');
    assertTrueValue(empty($safeTheme['slots']), 'Safe theme should not expose slot PHP overrides.');
    assertTrueValue(empty($safeTheme['parts']), 'Safe theme should not expose part PHP overrides.');
    assertTrueValue(empty($safeTheme['pages']), 'Safe theme should not expose page PHP overrides.');
    assertSameValue(false, (bool) ($safeTheme['executes_php'] ?? true), 'Safe theme should not execute PHP.');
    assertSameValue(ThemeManager::defaultSlotPath('sidebar'), ThemeManager::resolveSlotPath('sidebar', $safeId), 'Safe theme should fall back to default slot.');
    assertSameValue(ThemeManager::defaultPartsDir() . '/sidebar_logo.php', ThemeManager::resolvePartPath('sidebar_logo', $safeId), 'Safe theme should fall back to default part.');
    assertSameValue(null, ThemeManager::resolvePagePath('dashboard', $safeId), 'Safe theme should not expose page overrides.');

    $trustedTheme = ThemeManager::getTheme($trustedId);
    assertTrueValue(is_array($trustedTheme), 'Trusted local theme was not discovered.');
    assertSameValue('trusted_local', $trustedTheme['installation_mode'], 'Trusted theme installation mode mismatch.');
    assertTrueValue(!empty($trustedTheme['slots']['sidebar']), 'Trusted theme slot override missing.');
    assertTrueValue(!empty($trustedTheme['parts']['sidebar_logo']), 'Trusted theme part override missing.');
    assertTrueValue(!empty($trustedTheme['pages']['dashboard']), 'Trusted theme page override missing.');
    assertSameValue(true, (bool) ($trustedTheme['executes_php'] ?? false), 'Trusted theme should expose executable PHP overrides.');
    assertSameValue($trustedDir . '/slots/sidebar.php', ThemeManager::resolveSlotPath('sidebar', $trustedId), 'Trusted theme should resolve custom slot.');
    assertSameValue($trustedDir . '/parts/sidebar_logo.php', ThemeManager::resolvePartPath('sidebar_logo', $trustedId), 'Trusted theme should resolve custom part.');
    assertSameValue($trustedDir . '/pages/dashboard.php', ThemeManager::resolvePagePath('dashboard', $trustedId), 'Trusted theme should resolve custom page.');

    if (class_exists('ZipArchive')) {
        $tmpZip = sys_get_temp_dir() . '/fulgurite-theme-trust-' . bin2hex(random_bytes(4)) . '.zip';
        $zip = new ZipArchive();
        assertSameValue(true, $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'Unable to create theme zip fixture.');
        $zip->addFromString('theme.json', (string) file_get_contents($trustedDir . '/theme.json'));
        $zip->addFromString('slots/sidebar.php', '<?php echo "blocked";');
        $zip->close();

        $extracted = ThemePackage::extract($tmpZip);
        assertSameValue(false, (bool) ($extracted['ok'] ?? false), 'ZIP themes with PHP should be rejected.');
        $errors = $extracted['errors'] ?? [];
        assertTrueValue(is_array($errors) && !empty($errors), 'ZIP rejection should provide at least one error.');
    }

    echo "Theme trust split tests OK.\n";
} finally {
    ThemePackage::removeDirRecursive($safeDir);
    ThemePackage::removeDirRecursive($trustedDir);
    if (is_string($tmpZip) && is_file($tmpZip)) {
        @unlink($tmpZip);
    }
    ThemeManager::invalidateCache();
}
