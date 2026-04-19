<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');

$repoId   = (int) ($_GET['repo_id']  ?? 0);
$snapshot = $_GET['snapshot'] ?? '';
$filepath = $_GET['path']     ?? '';

if (!$repoId || !$snapshot || !$filepath) {
    http_response_code(400);
    die(t('api.restore.error.missing_params'));
}

$repo = RepoManager::getById($repoId);
if (!$repo) {
    http_response_code(404);
    die(t('api.common.error.repo_not_found'));
}

Auth::requireRepoAccess($repoId);
$restic = RepoManager::getRestic($repo);
$result = $restic->dumpRaw($snapshot, $filepath);

if (!$result['success']) {
    http_response_code(500);
    die('Erreur : ' . ($result['error'] ?? 'inconnue'));
}

$filename = basename($filepath);
$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Determine MIME type
$mimeTypes = [
    'txt' => 'text/plain', 'log' => 'text/plain', 'conf' => 'text/plain',
    'php' => 'text/plain', 'py'  => 'text/plain', 'sh'   => 'text/plain',
    'js'  => 'text/plain', 'css' => 'text/plain', 'html' => 'text/html',
    'json'=> 'application/json', 'xml' => 'application/xml',
    'pdf' => 'application/pdf', 'zip' => 'application/zip',
    'gz'  => 'application/gzip', 'tar' => 'application/x-tar',
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif', 'svg' => 'image/svg+xml',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

Auth::log('file_download', "Téléchargement: $filepath (snapshot: $snapshot, repo: {$repo['name']})");

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($result['data']));
header('Cache-Control: no-cache');

echo $result['data'];
exit;
