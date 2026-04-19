<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
verifyCsrf();
rateLimitApi('probe_disk', 20, 60);

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$contextType = trim((string) ($data['context_type'] ?? ''));
$contextId = isset($data['context_id']) ? (int) $data['context_id'] : null;

if ($contextType === '') {
    Auth::requirePermission('stats.view');
    $result = DiskSpaceMonitor::runManualChecks();
    jsonResponse([
        'success' => true,
        'count' => count($result['checks']),
        'message' => count($result['checks']) > 0
            ? 'Sonde disque lancee sur les cibles visibles.'
            : 'Aucune cible disque a sonder.',
    ]);
}

if ($contextType === 'repo') {
    if ($contextId === null || $contextId <= 0) {
        jsonResponse(['error' => 'context_id requis pour un depot'], 422);
    }
    Auth::requireRepoAccess($contextId);
    $result = DiskSpaceMonitor::runManualChecks('repo', $contextId);
    $check = $result['checks'][0] ?? null;
    $checkDisplay = null;
    if (is_array($check)) {
        $severity = (string) ($check['severity'] ?? 'unknown');
        $badgeClass = match ($severity) {
            'critical', 'error' => 'badge-red',
            'warning' => 'badge-yellow',
            default => 'badge-green',
        };
        $badgeLabel = match ($severity) {
            'critical' => 'Critique',
            'error' => 'Probe KO',
            'warning' => 'Warning',
            default => 'OK',
        };
        $capacityText = 'Libre ' . formatBytes((int) ($check['free_bytes'] ?? 0))
            . ' / Total ' . formatBytes((int) ($check['total_bytes'] ?? 0));
        if (isset($check['used_percent']) && $check['used_percent'] !== null) {
            $capacityText .= ' - ' . number_format((float) $check['used_percent'], 1) . '% utilises';
        }
        $checkDisplay = [
            'badge_class' => $badgeClass,
            'badge_label' => $badgeLabel,
            'storage_hint' => 'Derniere sonde ' . formatDateForDisplay((string) ($check['checked_at'] ?? ''), 'd/m/Y H:i:s', appServerTimezone()),
            'checked_at_display' => formatDateForDisplay((string) ($check['checked_at'] ?? ''), 'd/m/Y H:i:s', appServerTimezone()),
            'capacity_text' => $capacityText,
        ];
    }
    jsonResponse([
        'success' => true,
        'count' => count($result['checks']),
        'check' => $check,
        'check_display' => $checkDisplay,
        'message' => $check
            ? 'Sonde du depot terminee.'
            : 'Aucune sonde applicable pour ce depot.',
    ]);
}

jsonResponse(['error' => 'context_type non supporte'], 422);
