<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::check();
Auth::requirePermission('repos.view');

$repoId     = (int) ($_GET['repo_id']   ?? 0);
$snapshot   = $_GET['snapshot']         ?? '';
$path       = $_GET['path']             ?? '/';
$format     = $_GET['format']           ?? 'tar.gz'; // tar.gz | zip

if (!$repoId || !$snapshot) { http_response_code(400); echo t('api.restore.error.missing_params'); exit; }

try {
    $path = FileSystem::normalizeSnapshotDirectoryPath($path);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo $e->getMessage();
    exit;
}

$repo = RepoManager::getById($repoId);
if (!$repo) { http_response_code(404); echo t('api.common.error.repo_not_found'); exit; }

Auth::requireRepoAccess($repoId);
$restic  = RepoManager::getRestic($repo);
$tmpRoot = rtrim(sys_get_temp_dir(), '/\\');
$tmpDir  = $tmpRoot . DIRECTORY_SEPARATOR . uniqid('fulgurite_dl_', true);
if (!@mkdir($tmpDir, 0700, true)) {
    http_response_code(500);
    echo t('api.download_folder.error.staging_create_failed');
    exit;
}

// Extract folder from restic
$result = $restic->restore($snapshot, $tmpDir, $path);
if (!$result['success']) {
    FileSystem::removeDirectory($tmpDir);
    http_response_code(500);
    echo 'Erreur lors de l\'extraction : ' . $result['output'];
    exit;
}

$folderName = basename(rtrim($path, '/')) ?: 'backup';
$safeName   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $folderName);
$archiveName = $safeName . '_' . $snapshot;

try {
    $extractedPath = FileSystem::resolveContainedDirectory($tmpDir, $path);
} catch (InvalidArgumentException $e) {
    FileSystem::removeDirectory($tmpDir);
    http_response_code(400);
    echo $e->getMessage();
    exit;
} catch (RuntimeException $e) {
    FileSystem::removeDirectory($tmpDir);
    http_response_code(422);
    echo $e->getMessage();
    exit;
}

Auth::log('folder_download', "Téléchargement dossier: $path (snapshot: $snapshot, format: $format, repo: {$repo['name']})");

if ($format === 'zip') {
    // createate a ZIP
    $zipFile = $tmpDir . '.zip';
    $zip     = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        FileSystem::removeDirectory($tmpDir);
        http_response_code(500); echo t('api.download_folder.error.zip_create_failed'); exit;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractedPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath   = $file->getRealPath();
            $relativePath = $folderName . '/' . substr($filePath, strlen($extractedPath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $archiveName . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Cache-Control: no-cache');
    readfile($zipFile);
    unlink($zipFile);

} else {
    // createate a tar.gz
    $tarFile = $tmpDir . '.tar.gz';
    if (!FileSystem::createTarGzFromDirectory($extractedPath, $tarFile, $folderName)) {
        FileSystem::removeDirectory($tmpDir);
        http_response_code(500);
        echo t('api.download_folder.error.targz_create_failed');
        exit;
    }

    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $archiveName . '.tar.gz"');
    header('Content-Length: ' . filesize($tarFile));
    header('Cache-Control: no-cache');
    readfile($tarFile);
    unlink($tarFile);
}

FileSystem::removeDirectory($tmpDir);
