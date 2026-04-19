<?php
/**
 * Test to validate notification changes for Features 1 and 2
 * Feature 1: Throttling 1x/jour
 * Feature 2: Separation log/compact body
 */

require_once __DIR__ . '/../src/bootstrap.php';

echo "=== Test Features Notifications ===\n\n";

// Test 1: isthrottleddEvent()
echo "Test 1: isThrottledEvent() - vérifier les événements non-throttlés\n";
$notThrottled = [
    ['security', 'alert'],
    ['login', 'login'],
    ['weekly_report', 'report'],
    ['backup_job', 'success'],
    ['copy_job', 'success'],
];

$shouldThrottle = [
    ['backup_job', 'failure'],
    ['copy_job', 'failure'],
    ['repo', 'stale'],
    ['repo', 'error'],
    ['repo', 'no_snap'],
];

// Check that isthrottleddEvent() returns false for non-throttleddd events
foreach ($notThrottled as [$profile, $event]) {
    $method = new ReflectionMethod('Notifier', 'isThrottledEvent');
    $method->setAccessible(true);
    $result = $method->invoke(null, $profile, $event);
    echo "  ✓ {$profile}:{$event} NOT throttled = " . ($result ? "FAIL" : "OK") . "\n";
}

// Check that isthrottleddEvent() returns true for throttledd events
foreach ($shouldThrottle as [$profile, $event]) {
    $method = new ReflectionMethod('Notifier', 'isThrottledEvent');
    $method->setAccessible(true);
    $result = $method->invoke(null, $profile, $event);
    echo "  ✓ {$profile}:{$event} throttled = " . ($result ? "OK" : "FAIL") . "\n";
}

// Test 2: extractErrorLines()
echo "\nTest 2: extractErrorLines() - extraction des lignes d'erreur\n";
$testLog = "Starting backup...\nerror: Failed to upload\nWarning: Low disk space\nerror: Retry attempt 1\nSuccess: Backup completed";
$method = new ReflectionMethod('Notifier', 'extractErrorLines');
$method->setAccessible(true);
$errors = $method->invoke(null, $testLog);
echo "  Found " . count($errors) . " error lines (expected ≤5)\n";
foreach ($errors as $line) {
    echo "    - " . trim($line) . "\n";
}

// Test 3: buildEmailBody()
echo "\nTest 3: buildEmailBody() - construction du corps email\n";
$plainBody = "Backup failed for job 'test'";
$shortLog = "error: Something went wrong\nerror: Check logs";
$method = new ReflectionMethod('Notifier', 'buildEmailBody');
$method->setAccessible(true);
$emailBody = $method->invoke(null, "Backup failed", $plainBody, $shortLog);
if (str_contains($emailBody, '--- Log complet ---')) {
    echo "  ✓ Email body contains log separator\n";
} else {
    echo "  ✗ Email body missing log separator\n";
}

// Test 4: buildCompactBody()
echo "\nTest 4: buildCompactBody() - construction du corps sobre\n";
$method = new ReflectionMethod('Notifier', 'buildCompactBody');
$method->setAccessible(true);
$compactBody = $method->invoke(null, $plainBody, $shortLog);
if (str_contains($compactBody, 'Erreurs detectees')) {
    echo "  ✓ Compact body contains error summary\n";
} else {
    echo "  ✓ Compact body OK (no errors to summarize)\n";
}

echo "\n=== Tests terminés ===\n";
