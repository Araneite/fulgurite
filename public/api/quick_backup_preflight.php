<?php
// =============================================================================
// quick_backup_preflight.php — Pre-checks for the quick flow
// =============================================================================
require_once __DIR__ . '/../../src/bootstrap.php';
RemoteBackupQuickFlow::requireManagePermissions();
verifyCsrf();
rateLimitApi('quick_backup_preflight', 20, 60);

$data = requestJsonBody();

try {
    $result = RemoteBackupQuickFlow::preview($data);
    Auth::log('quick_backup_preflight', 'Analyse rapide : ' . (!empty($result['success']) ? 'OK' : 'A corriger'));
    jsonResponse($result);
} catch (Throwable $e) {
    jsonResponse(['error' => t('api.quick_backup_preflight.error.prefix') . $e->getMessage()], 500);
}
