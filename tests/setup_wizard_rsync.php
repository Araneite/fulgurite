<?php

declare(strict_types=1);

define('FULGURITE_SETUP', true);

$root = dirname(__DIR__);

require_once $root . '/config/config.php';
require_once $root . '/src/ProcessRunner.php';
require_once $root . '/src/Setup/SetupGuard.php';
require_once $root . '/src/Setup/SetupWizard.php';

function failTest(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$result = SetupWizard::checkPrerequisites();
$rsync = $result['rsync'] ?? null;
if (!is_array($rsync)) {
    failTest('Missing rsync metadata in SetupWizard::checkPrerequisites().');
}

if (!array_key_exists('installed', $rsync) || !is_bool($rsync['installed'])) {
    failTest('rsync.installed should be a boolean.');
}

if (($rsync['restore_warning'] ?? '') !== 'Sans rsync, le restore ne pourra pas fonctionner.') {
    failTest('Unexpected rsync restore warning.');
}

if (!array_key_exists('can_auto_install', $rsync) || !is_bool($rsync['can_auto_install'])) {
    failTest('rsync.can_auto_install should be a boolean.');
}

$rsyncCheck = null;
foreach ((array) ($result['checks'] ?? []) as $check) {
    if (($check['label'] ?? '') === 'Binaire rsync') {
        $rsyncCheck = $check;
        break;
    }
}

if (!is_array($rsyncCheck)) {
    failTest('Missing Binaire rsync check entry.');
}

if (($rsyncCheck['fatal'] ?? null) !== false) {
    failTest('rsync should be optional during setup prerequisites.');
}

echo "SetupWizard rsync prerequisite metadata OK.\n";
