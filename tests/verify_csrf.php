<?php

declare(strict_types=1);

function fail(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fail(
            $message
            . "\nExpected: " . var_export($expected, true)
            . "\nActual: " . var_export($actual, true)
        );
    }
}

function runChildScenario(string $scenario): void {
    define('FULGURITE_CLI', true);

    $root = dirname(__DIR__);
    require_once $root . '/src/bootstrap.php';

    unset($_SESSION['csrf_token'], $_POST['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN']);
    $_POST = [];

    $success = false;
    ob_start();
    register_shutdown_function(function () use (&$success): void {
        $body = (string) ob_get_clean();
        $code = http_response_code();
        if ($code === false || $code < 100) {
            $code = $success ? 200 : 500;
        }

        echo json_encode([
            'code' => (int) $code,
            'body' => $body,
        ], JSON_THROW_ON_ERROR);
    });

    switch ($scenario) {
        case 'missing-session-missing-header':
            break;

        case 'incorrect-token':
            $_SESSION['csrf_token'] = 'expected-token';
            $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong-token';
            break;

        case 'correct-token':
            $_SESSION['csrf_token'] = 'expected-token';
            $_SERVER['HTTP_X_CSRF_TOKEN'] = 'expected-token';
            break;

        default:
            fail('Unknown child scenario: ' . $scenario);
    }

    verifyCsrf();
    $success = true;
    http_response_code(200);
    echo 'ok';
}

function executeScenario(string $scenario): array {
    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg(__FILE__)
        . ' --child '
        . escapeshellarg($scenario);

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, __DIR__);
    if (!is_resource($process)) {
        fail('Unable to start child PHP process.');
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fail("Child scenario {$scenario} failed.\nSTDERR:\n" . trim((string) $stderr));
    }

    try {
        $decoded = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        fail("Child scenario {$scenario} returned invalid JSON.\nSTDOUT:\n" . trim((string) $stdout));
    }

    if (!is_array($decoded)) {
        fail("Child scenario {$scenario} returned an unexpected payload.");
    }

    return $decoded;
}

if (($argv[1] ?? null) === '--child') {
    runChildScenario((string) ($argv[2] ?? ''));
    exit(0);
}

$missingSession = executeScenario('missing-session-missing-header');
assertSameValue(403, $missingSession['code'] ?? null, 'Session without csrf_token and no header must be rejected.');

$incorrectToken = executeScenario('incorrect-token');
assertSameValue(403, $incorrectToken['code'] ?? null, 'Incorrect CSRF token must be rejected.');

$correctToken = executeScenario('correct-token');
assertSameValue(200, $correctToken['code'] ?? null, 'Correct CSRF token must be accepted.');

echo "verifyCsrf tests OK.\n";
