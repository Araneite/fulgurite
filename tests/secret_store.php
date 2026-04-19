<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fulgurite-secret-test-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);

$key = base64_encode(random_bytes(32));
putenv('DB_DRIVER=sqlite');
putenv('DB_PATH=' . $tmp . '/fulgurite.db');
putenv('SEARCH_DB_PATH=' . $tmp . '/fulgurite-search.db');
putenv('FULGURITE_SECRET_KEY=' . $key);
putenv('FULGURITE_SECRET_PROVIDER=local');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_PATH'] = $tmp . '/fulgurite.db';
$_ENV['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_ENV['FULGURITE_SECRET_KEY'] = $key;
$_ENV['FULGURITE_SECRET_PROVIDER'] = 'local';
$_SERVER['DB_DRIVER'] = 'sqlite';
$_SERVER['DB_PATH'] = $tmp . '/fulgurite.db';
$_SERVER['SEARCH_DB_PATH'] = $tmp . '/fulgurite-search.db';
$_SERVER['FULGURITE_SECRET_KEY'] = $key;
$_SERVER['FULGURITE_SECRET_PROVIDER'] = 'local';

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

try {
    $db = Database::getInstance();

    $ref = SecretStore::localRef('repo', 100, 'password');
    SecretStore::put($ref, 'repo-secret', ['test' => true]);
    assertSameValue('repo-secret', SecretStore::get($ref), 'SecretStore local decrypt failed.');

    Database::setSetting('smtp_pass', 'smtp-secret');
    assertSameValue('smtp-secret', Database::getSetting('smtp_pass'), 'Sensitive setting decrypt failed.');
    $rawSetting = $db->query("SELECT value FROM settings WHERE key = 'smtp_pass'")->fetchColumn();
    assertTrueValue(is_string($rawSetting) && str_starts_with($rawSetting, 'secret://local/setting/'), 'Sensitive setting was not stored as a secret ref.');

    putenv('FULGURITE_SECRET_KEY=' . base64_encode(random_bytes(32)));
    $_ENV['FULGURITE_SECRET_KEY'] = getenv('FULGURITE_SECRET_KEY');
    $_SERVER['FULGURITE_SECRET_KEY'] = getenv('FULGURITE_SECRET_KEY');
    $failed = false;
    try {
        SecretStore::get($ref);
    } catch (RuntimeException $e) {
        $failed = true;
    }
    assertTrueValue($failed, 'SecretStore should not decrypt with a different key.');
    putenv('FULGURITE_SECRET_KEY=' . $key);
    $_ENV['FULGURITE_SECRET_KEY'] = $key;
    $_SERVER['FULGURITE_SECRET_KEY'] = $key;

    $repoId = RepoManager::add('unit-repo', $tmp . '/repos/unit-repo', 'repo-pass');
    $repo = RepoManager::getById($repoId);
    assertSameValue('repo-pass', RepoManager::getPassword($repo), 'RepoManager::getPassword failed with local encrypted secret.');
    assertTrueValue(str_starts_with((string) $repo['password_ref'], 'secret://local/repo/'), 'Repo password_ref missing.');

    $copyId = CopyJobManager::add('unit-copy', $repoId, $tmp . '/copies/unit-copy', 'dest-pass');
    $copy = CopyJobManager::getById($copyId);
    assertSameValue('dest-pass', CopyJobManager::getDestPassword($copy), 'CopyJobManager::getDestPassword failed.');

    $hostId = HostManager::add('unit-host', '127.0.0.1', 22, 'root', null, null, false, 'sudo-pass');
    $host = HostManager::getById($hostId);
    assertSameValue('sudo-pass', HostManager::getSudoPassword($host), 'HostManager::getSudoPassword failed.');

    $copyRef = (string) $copy['dest_password_ref'];
    CopyJobManager::delete($copyId);
    assertTrueValue(!SecretStore::exists($copyRef), 'Copy job secret was not deleted.');

    $legacyFile = $tmp . '/legacy.pass';
    file_put_contents($legacyFile, "legacy-pass\n");
    chmod($legacyFile, 0600);
    $db->prepare("INSERT INTO repos (name, path, password_file, password_source) VALUES (?, ?, ?, 'file')")
        ->execute(['legacy-repo', $tmp . '/repos/legacy-repo', $legacyFile]);
    $legacyId = (int) $db->lastInsertId();
    $legacyRepo = RepoManager::getById($legacyId);
    assertSameValue('legacy-pass', RepoManager::getPassword($legacyRepo), 'Legacy repo password migration failed.');
    $legacyRepo = RepoManager::getById($legacyId);
    assertTrueValue(!is_file($legacyFile), 'Legacy password file was not removed after migration.');
    assertTrueValue(str_starts_with((string) $legacyRepo['password_ref'], 'secret://local/repo/'), 'Legacy repo password_ref missing after migration.');

    // --- Guard against empty password silently propagating to restic -----
    // This is the root cause of: "reading repository password from stdin
    // Fatal: an empty password is not a password". The SecreatetStore can return
    // '' (e.g. agent returns {ok:true, value:null} → (string)null = '').
    // Previously the ?? guard only caught null, not ''. Verify the fix.

    // Case A: repo createated with empty password stored in SecreatetStore
    $emptyPassRepoId = RepoManager::add('empty-pass-repo', $tmp . '/repos/empty-pass', '');
    $emptyPassRepo = RepoManager::getById($emptyPassRepoId);
    $threwOnEmptyRef = false;
    try {
        RepoManager::getPassword($emptyPassRepo);
    } catch (RuntimeException $e) {
        $threwOnEmptyRef = str_contains($e->getMessage(), 'vide') || str_contains($e->getMessage(), 'introuvable');
    }
    assertTrueValue($threwOnEmptyRef, 'RepoManager::getPassword must throw RuntimeException for an empty stored password (ref path).');

    // Case B: legacy password file that is empty (whitespace-only)
    $emptyLegacyFile = $tmp . '/empty.pass';
    file_put_contents($emptyLegacyFile, "   \n");
    chmod($emptyLegacyFile, 0600);
    $db->prepare("INSERT INTO repos (name, path, password_file, password_source) VALUES (?, ?, ?, 'file')")
        ->execute(['empty-legacy-repo', $tmp . '/repos/empty-legacy-repo', $emptyLegacyFile]);
    $emptyLegacyId = (int) $db->lastInsertId();
    $emptyLegacyRepo = RepoManager::getById($emptyLegacyId);
    $threwOnEmptyFile = false;
    try {
        RepoManager::getPassword($emptyLegacyRepo);
    } catch (RuntimeException $e) {
        $threwOnEmptyFile = str_contains($e->getMessage(), 'vide') || str_contains($e->getMessage(), 'introuvable');
    }
    assertTrueValue($threwOnEmptyFile, 'RepoManager::getPassword must throw RuntimeException for an empty legacy password file.');

    echo "SecretStore tests OK.\n";
} finally {
    if (is_dir($tmp)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmp, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($tmp);
    }
}
