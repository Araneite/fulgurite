<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$repoId   = (int) ($data['repo_id'] ?? 0);

if (!$repoId) jsonResponse(['error' => 'repo_id requis'], 400);

$repo = RepoManager::getById($repoId);
if (!$repo) jsonResponse(['error' => 'Dépôt introuvable'], 404);

$restic = RepoManager::getRestic($repo);

// Check if repository already exists
if ($restic->ping()) {
    jsonResponse(['error' => 'Ce dépôt existe déjà et est accessible — initialisation annulée.'], 400);
}

$result = $restic->init();
Auth::log('repo_init', "Init repo: {$repo['name']} ({$repo['path']}) — " . ($result['success'] ? 'OK' : 'ERREUR'));

jsonResponse($result);
