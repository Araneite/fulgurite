<?php

declare(strict_types=1);

define('FULGURITE_CLI', true);

$root = dirname(__DIR__);
require_once $root . '/src/bootstrap.php';

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fail(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

function assertThrows(string $expectedClass, callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        if ($e instanceof $expectedClass) {
            return;
        }

        fail(
            $message
            . "\nExpected exception: " . $expectedClass
            . "\nActual exception: " . $e::class
            . "\nMessage: " . $e->getMessage()
        );
    }

    fail($message . "\nExpected exception: " . $expectedClass . "\nActual exception: none");
}

$workspace = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fulgurite-download-guard-' . bin2hex(random_bytes(6));
$stagingDir = $workspace . DIRECTORY_SEPARATOR . 'staging';

try {
    if (!@mkdir($stagingDir . DIRECTORY_SEPARATOR . 'valid folder' . DIRECTORY_SEPARATOR . 'nested', 0700, true)) {
        fail('Unable to create staging fixture.');
    }

    $stagingRealPath = realpath($stagingDir);
    if ($stagingRealPath === false) {
        fail('Unable to resolve staging fixture.');
    }

    $rootPath = FileSystem::resolveContainedDirectory($stagingDir, '/');
    assertSameValue($stagingRealPath, $rootPath, 'Root snapshot path should resolve to the staging directory.');

    $nestedPath = FileSystem::resolveContainedDirectory($stagingDir, '/valid folder/nested');
    assertSameValue(
        $stagingRealPath . DIRECTORY_SEPARATOR . 'valid folder' . DIRECTORY_SEPARATOR . 'nested',
        $nestedPath,
        'Valid nested snapshot directory should resolve inside staging.'
    );

    assertThrows(
        InvalidArgumentException::class,
        static fn () => FileSystem::resolveContainedDirectory($stagingDir, '/../..'),
        'Traversal segments must be rejected before archiving.'
    );

    assertThrows(
        InvalidArgumentException::class,
        static fn () => FileSystem::resolveContainedDirectory($stagingDir, '/C:/Windows'),
        'Host-side absolute path variants must be rejected.'
    );

    assertThrows(
        RuntimeException::class,
        static fn () => FileSystem::resolveContainedDirectory($stagingDir, '/missing'),
        'Missing extracted directories must not silently fall back to the staging root.'
    );

    echo "download_folder path guard tests OK.\n";
} finally {
    FileSystem::removeDirectory($workspace);
}
