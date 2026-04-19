<?php
// Migration script: move existing SSH private key files into SecretStore
// Usage: php migrate_ssh_keys_to_secretstore.php [--apply]

require_once __DIR__ . '/../src/bootstrap.php';

$apply = in_array('--apply', $argv, true);
$db = Database::getInstance();
$rows = $db->query("SELECT id, private_key_file FROM ssh_keys ORDER BY id")->fetchAll();

if (!$rows) {
    echo "No ssh_keys rows found.\n";
    exit(0);
}

$baseKeysDir = rtrim(dirname(DB_PATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ssh_keys';

$summary = [
    'total' => count($rows),
    'skipped_already_secret' => 0,
    'missing_file' => 0,
    'migrated' => 0,
    'errors' => [],
];

foreach ($rows as $r) {
    $id = (int) ($r['id'] ?? 0);
    $ref = (string) ($r['private_key_file'] ?? '');

    if ($ref === '') {
        $summary['missing_file']++;
        echo "#{$id}: empty private_key_file, skipping\n";
        continue;
    }

    if (str_starts_with($ref, 'secret://')) {
        $summary['skipped_already_secret']++;
        echo "#{$id}: already secret (" . $ref . ")\n";
        continue;
    }

    // Try given path first
    if (is_file($ref)) {
        $filePath = $ref;
    } else {
        // Try in keys dir
        $candidate = $baseKeysDir . DIRECTORY_SEPARATOR . basename($ref);
        if (is_file($candidate)) {
            $filePath = $candidate;
        } else {
            $summary['missing_file']++;
            echo "#{$id}: file not found (" . $ref . ")\n";
            continue;
        }
    }

    $content = @file_get_contents($filePath);
    if ($content === false) {
        $summary['errors'][] = "#{$id}: cannot read $filePath";
        echo "#{$id}: cannot read $filePath\n";
        continue;
    }

    $secretRef = "secret://local/ssh_key/{$id}/private";

    echo "#{$id}: will store into {$secretRef} (file: {$filePath})\n";

    if ($apply) {
        try {
            SecretStore::put($secretRef, $content);
            $db->prepare("UPDATE ssh_keys SET private_key_file = ? WHERE id = ?")->execute([$secretRef, $id]);
            @unlink($filePath);
            $summary['migrated']++;
            echo "#{$id}: migrated\n";
        } catch (Throwable $e) {
            $summary['errors'][] = "#{$id}: exception: " . $e->getMessage();
            echo "#{$id}: error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nSummary:\n";
foreach ($summary as $k => $v) {
    if ($k === 'errors') continue;
    echo "  {$k}: {$v}\n";
}
if (!empty($summary['errors'])) {
    echo "Errors:\n";
    foreach ($summary['errors'] as $err) echo " - {$err}\n";
}

if (!$apply) {
    echo "\nDry-run mode. To perform migration, rerun with --apply\n";
}
