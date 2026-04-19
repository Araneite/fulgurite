<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();
verifyCsrf();

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId = (int) ($data['repo_id'] ?? 0);

if (!$repoId) jsonResponse(['error' => t('api.check_repo.error.repo_id_required')], 400);

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => t('api.common.error.repo_not_found')], 404);

$restic = RepoManager::getRestic($repo);
$result = $restic->check();

Auth::log('repo_check', "Vérification intégrité: {$repo['name']} — " . ($result['success'] ? 'OK' : 'ERREUR'));

jsonResponse($result);
