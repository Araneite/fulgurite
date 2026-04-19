<?php
// =============================================================================
// backup_templates.php (API) — CRUD for quick backup templates
// =============================================================================
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requirePermission('backup_jobs.manage');
Auth::requireAdmin();
verifyCsrf();

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($data['action'] ?? $_GET['action'] ?? '');

// ── Lister ───────────────────────────────────────────────────────────────────
if ($action === 'list') {
    jsonResponse(['templates' => QuickBackupTemplateManager::getAll()]);
}

// ── createate ────────────────────────────────────────────────────────────────────
if ($action === 'create') {
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        jsonResponse(['error' => t('backup_templates.api.error.name_required')], 422);
    }
    $id = QuickBackupTemplateManager::create(
        $name,
        trim((string) ($data['description'] ?? '')),
        trim((string) ($data['category'] ?? t('backup_templates.default_category'))),
        QuickBackupTemplateManager::defaultsFromForm($data)
    );
    Auth::log('backup_template_create', "Modèle créé : $name (#$id)");
    jsonResponse(['success' => true, 'id' => $id]);
}

// ── Update ─────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => t('backup_templates.api.error.id_required')], 400);
    }
    $template = QuickBackupTemplateManager::getCustomById($id);
    if (!$template) {
        jsonResponse(['error' => t('backup_templates.api.error.not_found_or_builtin_not_editable')], 404);
    }
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        jsonResponse(['error' => t('backup_templates.api.error.name_required_short')], 422);
    }
    QuickBackupTemplateManager::update(
        $id,
        $name,
        trim((string) ($data['description'] ?? '')),
        trim((string) ($data['category'] ?? t('backup_templates.default_category'))),
        QuickBackupTemplateManager::defaultsFromForm($data)
    );
    Auth::log('backup_template_update', "Modèle mis à jour : $name (#$id)");
    jsonResponse(['success' => true]);
}

// ── remove ────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int) ($data['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => t('backup_templates.api.error.id_required')], 400);
    }
    $template = QuickBackupTemplateManager::getCustomById($id);
    if (!$template) {
        jsonResponse(['error' => t('backup_templates.api.error.not_found_or_builtin_not_deletable')], 404);
    }
    QuickBackupTemplateManager::delete($id);
    Auth::log('backup_template_delete', "Modèle supprimé: #{$id}");
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => t('backup_templates.api.error.action_unknown')], 400);
