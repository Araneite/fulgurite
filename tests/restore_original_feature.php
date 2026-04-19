<?php
// Simple tests for original-restore feature flags
require_once __DIR__ . '/../src/bootstrap.php';
$fail = 0;

// Test 1: original disabled globally -> expect exception
try {
    $ctx = [
        'mode' => 'remote',
        'destination_mode' => 'original',
        'repo_id' => 1,
        'repo' => ['id' => 1, 'name' => 'r'],
        'snapshot' => null,
        'host' => ['id' => 1, 'hostname' => 'h', 'ssh_key_id' => 1, 'restore_original_enabled' => 1],
        'can_restore_original' => true,
        'preview_confirmed' => true,
    ];
    try {
        RestoreTargetPlanner::plan($ctx);
        echo "ERROR: expected exception for globally disabled original mode\n";
        $fail++;
    } catch (InvalidArgumentException $e) {
        echo "OK: caught expected exception: " . $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR running test1: " . $e->getMessage() . "\n";
    $fail++;
}

// Test 2: managed strategy still works
try {
    $ctx = [
        'mode' => 'remote',
        'destination_mode' => 'managed',
        'repo_id' => 1,
        'repo' => ['id' => 1, 'name' => 'r'],
        'snapshot' => null,
        'host' => ['id' => 1, 'hostname' => 'h', 'ssh_key_id' => 1],
        'can_restore_original' => false,
    ];
    $plan = RestoreTargetPlanner::plan($ctx);
    if ($plan['strategy'] !== RestoreTargetPlanner::STRATEGY_MANAGED) {
        echo "ERROR: managed strategy produced: " . $plan['strategy'] . "\n";
        $fail++;
    } else {
        echo "OK: managed strategy works\n";
    }
} catch (Throwable $e) {
    echo "ERROR running test2: " . $e->getMessage() . "\n";
    $fail++;
}

exit($fail > 0 ? 1 : 0);
