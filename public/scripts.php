<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requirePermission('scripts.manage');

function groupScriptVariablesByCategory(array $variables): array {
    $groups = [];
    foreach ($variables as $variable) {
        $category = trim((string) ($variable['category'] ?? 'Systeme')) ?: 'Systeme';
        $groups[$category][] = $variable;
    }

    ksort($groups);
    foreach ($groups as &$items) {
        usort($items, static function (array $left, array $right): int {
            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });
    }
    unset($items);

    return $groups;
}

$flash = null;
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$editorOverride = null;

if (isset($_GET['download'])) {
    $scriptId = (int) ($_GET['id'] ?? 0);
    $script = $scriptId > 0 ? HookScriptManager::getById($scriptId) : null;
    if (!$script) {
        http_response_code(404);
        echo h(t('scripts.not_found'));
        exit;
    }

    try {
        $content = HookScriptManager::getContent($script);
    } catch (Throwable $e) {
        http_response_code(500);
        echo h($e->getMessage());
        exit;
    }

    $filename = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower((string) ($script['name'] ?? 'script'))) ?: 'script';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
    echo $content;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $scriptId = (int) ($_POST['script_id'] ?? 0);
        $name = (string) ($_POST['name'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $scope = (string) ($_POST['execution_scope'] ?? 'both');
        $content = (string) ($_POST['content'] ?? '');

        if (!empty($_FILES['script_file']['tmp_name']) && is_uploaded_file($_FILES['script_file']['tmp_name'])) {
            $content = (string) file_get_contents((string) $_FILES['script_file']['tmp_name']);
        }

        $result = $scriptId > 0
            ? HookScriptManager::update($scriptId, $name, $description, $scope, $content, $currentUserId)
            : HookScriptManager::create($name, $description, $scope, $content, $currentUserId);

        if (!empty($result['ok'])) {
            $savedId = (int) ($result['id'] ?? $scriptId);
            Auth::log($scriptId > 0 ? 'hook_script_update' : 'hook_script_create', "Script approuve enregistre #{$savedId}");
            redirectTo('/scripts.php', ['script_id' => $savedId, 'saved' => '1']);
        }

        $editorOverride = [
            'id' => $scriptId,
            'name' => $name,
            'description' => $description,
            'execution_scope' => $scope,
            'content' => HookScriptSecurity::normalizeContent($content),
        ];
        $flash = ['type' => 'danger', 'msg' => implode(' ', $result['errors'] ?? [t('flash.scripts.validation_failed')])];
    }

    if ($action === 'toggle_status') {
        $scriptId = (int) ($_POST['script_id'] ?? 0);
        $nextStatus = (string) ($_POST['status'] ?? 'disabled');
        if ($scriptId > 0 && HookScriptManager::setStatus($scriptId, $nextStatus, $currentUserId)) {
            Auth::log('hook_script_status', "Script #{$scriptId} passe en {$nextStatus}");
            redirectTo('/scripts.php', ['script_id' => $scriptId]);
        }
        $flash = ['type' => 'danger', 'msg' => t('flash.scripts.status_change_failed')];
    }
}

if ($flash === null && isset($_GET['saved'])) {
    $flash = ['type' => 'success', 'msg' => t('flash.scripts.saved')];
}

$scripts = HookScriptManager::getAll();
$selectedScriptId = max(0, (int) ($_GET['script_id'] ?? 0));
$selectedScript = $selectedScriptId > 0 ? HookScriptManager::getById($selectedScriptId) : ($scripts[0] ?? null);
$selectedContent = '';
$selectedContentError = null;
if ($selectedScript) {
    try {
        $selectedContent = HookScriptManager::getContent($selectedScript);
    } catch (Throwable $e) {
        $selectedContentError = $e->getMessage();
    }
}

$editorState = [
    'id' => (int) ($selectedScript['id'] ?? 0),
    'name' => (string) ($selectedScript['name'] ?? ''),
    'description' => (string) ($selectedScript['description'] ?? ''),
    'execution_scope' => (string) ($selectedScript['execution_scope'] ?? 'both'),
    'content' => $selectedContent,
];
if (is_array($editorOverride)) {
    $editorState = array_merge($editorState, $editorOverride);
}

$activeTab = (string) ($_GET['tab'] ?? '');
if (!in_array($activeTab, ['catalog', 'editor'], true)) {
    $activeTab = ($selectedScriptId > 0 || is_array($editorOverride) || isset($_GET['saved'])) ? 'editor' : 'catalog';
}

$allowedCommands = HookScriptSecurity::allowedCommands();
$bannedCommands = HookScriptSecurity::bannedCommands();
$bannedPatterns = HookScriptSecurity::bannedPatterns();
$systemVariables = array_values(HookScriptSecurity::variableDefinitions());
$variableGroups = groupScriptVariablesByCategory($systemVariables);
$variableCategories = array_keys($variableGroups);
$autocompleteConfig = HookScriptSecurity::autocompleteValues();

$autocompleteItems = [];
foreach ($allowedCommands as $commandKey => $meta) {
    $autocompleteItems[] = [
        'type' => 'command',
        'label' => (string) ($meta['label'] ?? $commandKey),
        'match' => strtolower($commandKey),
        'insert' => (string) ($meta['autocomplete_insert'] ?? ($commandKey . ' ')),
        'snippet' => (string) ($meta['autocomplete_snippet'] ?? $commandKey),
        'description' => (string) ($meta['description'] ?? ''),
        'meta' => implode(' / ', $meta['allowed_scopes'] ?? ['local', 'remote']),
    ];
}
foreach ($systemVariables as $variable) {
    $autocompleteItems[] = [
        'type' => 'variable',
        'label' => (string) ($variable['token'] ?? ''),
        'match' => strtolower((string) ($variable['name'] ?? '')),
        'insert' => (string) ($variable['token'] ?? ''),
        'snippet' => (string) ($variable['token'] ?? ''),
        'description' => (string) ($variable['description'] ?? ''),
        'meta' => trim((string) ($variable['category'] ?? 'Systeme') . (!empty($variable['provider']) ? ' / ' . $variable['provider'] : '')),
    ];
}

$commandContextItems = [
    'systemctl' => [
        1 => array_map(static fn(string $value): array => [
            'label' => $value,
            'insert' => $value . ' ',
            'description' => 'Action systemctl autorisee.',
            'meta' => 'Action',
        ], array_values($autocompleteConfig['systemctl_actions'] ?? [])),
        2 => array_map(static fn(string $value): array => [
            'label' => $value,
            'insert' => $value,
            'description' => 'Service systemd suggere.',
            'meta' => 'Service',
        ], array_values($autocompleteConfig['systemd_units'] ?? [])),
    ],
    'fsfreeze' => [
        1 => array_map(static fn(string $value): array => [
            'label' => $value,
            'insert' => $value . ' ',
            'description' => 'Option fsfreeze autorisee.',
            'meta' => 'Option',
        ], array_values($autocompleteConfig['fsfreeze_actions'] ?? [])),
        2 => array_map(static fn(string $value): array => [
            'label' => $value,
            'insert' => $value,
            'description' => 'Point de montage suggere.',
            'meta' => 'Point de montage',
        ], array_values($autocompleteConfig['mount_points'] ?? [])),
    ],
    'sleep' => [
        1 => array_map(static fn(string $value): array => [
            'label' => $value . ' seconde' . ($value === '1' ? '' : 's'),
            'insert' => $value,
            'description' => 'Duree courante suggeree.',
            'meta' => 'Duree',
        ], array_values($autocompleteConfig['sleep_durations'] ?? [])),
    ],
];

$title = t('scripts.title');
$active = 'scripts';
$actions = '<a class="btn btn-primary" href="' . h(routePath('/scripts.php', ['tab' => 'editor'])) . '">+ ' . h(t('scripts.new_btn')) . '</a>';

include 'layout_top.php';
?>

<style<?= cspNonceAttr() ?>>
.script-page-shell { display:grid; gap:16px; }
:root { --script-help-card-max-height:min(56vh, 560px); }
.script-tabs {
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    padding:10px;
    border:1px solid rgba(255,255,255,.08);
    border-radius:18px;
    background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
    box-shadow:0 18px 36px rgba(15, 23, 42, .12);
}
.script-tab {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:42px;
    padding:0 16px;
    border:1px solid rgba(148, 163, 184, .18);
    border-radius:999px;
    background:rgba(255,255,255,.025);
    color:var(--text2);
    font-weight:600;
    cursor:pointer;
    transition:border-color .15s ease, background .15s ease, color .15s ease, transform .15s ease, box-shadow .15s ease;
}
.script-tab:hover {
    color:var(--text);
    border-color:rgba(96, 165, 250, .35);
    transform:translateY(-1px);
}
.script-tab.is-active {
    color:#f8fafc;
    background:linear-gradient(135deg, rgba(37, 99, 235, .24), rgba(14, 165, 233, .18));
    border-color:rgba(96, 165, 250, .55);
    box-shadow:0 10px 24px rgba(37, 99, 235, .18);
}
.script-tab-count {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:24px;
    height:24px;
    padding:0 8px;
    border-radius:999px;
    background:rgba(15, 23, 42, .46);
    color:var(--text);
    font-size:11px;
}
.script-panel { display:grid; gap:16px; }
.script-panel[hidden] { display:none !important; }
.script-library-card,
.script-studio-card,
.script-preview-card,
.script-security-card,
.script-command-card,
.script-variable-card {
    overflow:hidden;
    border:1px solid rgba(255,255,255,.08);
    box-shadow:0 20px 40px rgba(15, 23, 42, .16);
}
.script-library-card {
    background:
        radial-gradient(circle at top left, rgba(56, 189, 248, .08), transparent 38%),
        linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
}
.script-studio-card {
    position:relative;
    background:
        radial-gradient(circle at top right, rgba(59, 130, 246, .16), transparent 34%),
        radial-gradient(circle at bottom left, rgba(16, 185, 129, .08), transparent 30%),
        linear-gradient(180deg, rgba(15, 23, 42, .92), rgba(15, 23, 42, .84));
}
.script-studio-card .card-header,
.script-library-card .card-header,
.script-preview-card .card-header,
.script-security-card .card-header,
.script-command-card .card-header,
.script-variable-card .card-header {
    border-bottom:1px solid rgba(255,255,255,.08);
}
.script-studio-body { display:grid; gap:18px; }
.script-hero {
    display:grid;
    gap:16px;
    padding:18px;
    border:1px solid rgba(148, 163, 184, .18);
    border-radius:18px;
    background:linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.025));
    box-shadow:inset 0 1px 0 rgba(255,255,255,.04);
}
.script-hero-copy { display:grid; gap:8px; }
.script-hero-title { font-size:20px; font-weight:700; color:var(--text); letter-spacing:-.02em; }
.script-hero-text { font-size:13px; color:rgba(226,232,240,.76); max-width:70ch; line-height:1.55; }
.script-hero-kpis { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; }
.script-hero-kpi {
    display:grid;
    gap:4px;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid rgba(255,255,255,.08);
    background:rgba(15, 23, 42, .42);
}
.script-hero-kpi-value { font-size:18px; font-weight:700; color:var(--text); }
.script-hero-kpi-label { font-size:11px; color:var(--text2); text-transform:uppercase; letter-spacing:.08em; }
.script-field-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
.script-editor-shell { display:grid; gap:14px; }
.script-editor-toolbar {
    display:grid;
    gap:10px;
    padding:14px;
    border:1px solid rgba(148, 163, 184, .18);
    border-radius:16px;
    background:rgba(15, 23, 42, .42);
    backdrop-filter:blur(10px);
}
.script-toolbar-group { display:grid; gap:8px; }
.script-toolbar-title { font-size:12px; font-weight:700; color:var(--text2); text-transform:uppercase; letter-spacing:.06em; }
.script-chip-row { display:flex; flex-wrap:wrap; gap:8px; }
.script-insert-chip {
    border:1px solid rgba(148, 163, 184, .22);
    background:rgba(255,255,255,.04);
    color:var(--text);
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    cursor:pointer;
    transition:border-color .15s ease, transform .15s ease, color .15s ease, background .15s ease, box-shadow .15s ease;
}
.script-insert-chip:hover {
    border-color:rgba(96, 165, 250, .7);
    color:#f8fafc;
    background:rgba(59, 130, 246, .16);
    transform:translateY(-1px);
    box-shadow:0 8px 20px rgba(37, 99, 235, .14);
}
.script-editor-pane {
    border:1px solid rgba(148, 163, 184, .18);
    border-radius:18px;
    overflow:visible;
    background:linear-gradient(180deg, rgba(2, 6, 23, .96), rgba(15, 23, 42, .9));
    box-shadow:inset 0 1px 0 rgba(255,255,255,.03);
}
.script-editor-pane-head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 14px;
    border-bottom:1px solid rgba(148, 163, 184, .14);
    background:rgba(255,255,255,.03);
}
.script-editor-dots { display:flex; align-items:center; gap:8px; }
.script-editor-dot {
    width:10px;
    height:10px;
    border-radius:999px;
    display:inline-block;
}
.script-editor-dot.is-red { background:#fb7185; }
.script-editor-dot.is-yellow { background:#fbbf24; }
.script-editor-dot.is-green { background:#34d399; }
.script-editor-meta { font-size:12px; color:rgba(226,232,240,.68); text-align:right; }
.script-editor-area { position:relative; padding:0; }
.script-editor-area .form-control.mono {
    min-height:340px;
    border:0;
    border-radius:0;
    background:transparent;
    color:#e2e8f0;
    box-shadow:none;
    padding:18px 18px 20px;
    line-height:1.65;
}
.script-editor-area .form-control.mono:focus {
    border:0;
    box-shadow:none;
}
.script-editor-footer {
    display:flex;
    flex-wrap:wrap;
    gap:10px 18px;
    padding:12px 14px 14px;
    border-top:1px solid rgba(148, 163, 184, .12);
    background:rgba(255,255,255,.025);
    font-size:12px;
    color:var(--text2);
}
.script-autocomplete { position:absolute; left:0; right:0; top:100%; margin-top:8px; border:1px solid var(--border); border-radius:12px; background:var(--bg2); box-shadow:var(--shadow); overflow:hidden; z-index:30; max-height:280px; overflow-y:auto; }
.script-autocomplete-item { width:100%; border:0; background:transparent; color:inherit; text-align:left; padding:10px 12px; cursor:pointer; display:grid; gap:4px; border-bottom:1px solid rgba(255,255,255,.04); }
.script-autocomplete-item:last-child { border-bottom:0; }
.script-autocomplete-item:hover,
.script-autocomplete-item.is-active { background:rgba(88,166,255,.12); }
.script-autocomplete-head { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.script-autocomplete-label { font-weight:600; color:var(--text); }
.script-autocomplete-meta { font-size:11px; color:var(--text2); }
.script-autocomplete-desc { font-size:12px; color:var(--text2); }
.script-help-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-top:16px; align-items:start; }
.script-help-section { display:grid; gap:8px; padding-top:10px; border-top:1px solid rgba(255,255,255,.06); }
.script-help-section:first-child { padding-top:0; border-top:0; }
.script-help-section-title { font-size:12px; font-weight:700; color:var(--text2); text-transform:uppercase; letter-spacing:.06em; }
.script-variable-list { display:grid; gap:8px; }
.script-variable-item {
    display:grid;
    gap:4px;
    padding:10px 12px;
    border:1px solid rgba(148, 163, 184, .14);
    border-radius:14px;
    background:rgba(255,255,255,.025);
    transition:border-color .15s ease, transform .15s ease, background .15s ease;
}
.script-variable-item:hover {
    border-color:rgba(96, 165, 250, .32);
    background:rgba(59, 130, 246, .08);
    transform:translateY(-1px);
}
.script-variable-item-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.script-variable-token { font-family:var(--font-mono); font-size:12px; color:var(--accent); }
.script-variable-desc { font-size:12px; color:var(--text2); }
.script-variable-sample { font-size:11px; color:var(--text2); font-family:var(--font-mono); }
.script-command-item { display:grid; gap:6px; padding:12px 0; border-bottom:1px solid var(--border); }
.script-command-item:last-child { border-bottom:0; padding-bottom:0; }
.script-browser-controls { display:grid; gap:12px; margin-bottom:14px; }
.script-browser-pills { display:flex; flex-wrap:wrap; gap:8px; }
.script-browser-pill { border:1px solid var(--border); background:var(--bg3); color:var(--text2); border-radius:999px; padding:6px 10px; font-size:12px; cursor:pointer; transition:border-color .15s ease, color .15s ease, background .15s ease; }
.script-browser-pill.is-active,
.script-browser-pill:hover { border-color:var(--accent); color:var(--text); background:rgba(88,166,255,.1); }
.script-variable-group[hidden],
.script-variable-item[hidden] { display:none !important; }
.script-variable-meta { display:flex; flex-wrap:wrap; gap:6px; }
.script-empty-note { font-size:12px; color:var(--text2); padding:12px 0; }
.script-secret-note { font-size:12px; color:var(--text2); margin-top:8px; }
.script-preview-card .card-body,
.script-security-card .card-body,
.script-command-card .card-body,
.script-variable-card .card-body {
    scrollbar-gutter:stable;
}
.script-security-card .card-body,
.script-command-card .card-body,
.script-variable-card .card-body {
    max-height:var(--script-help-card-max-height);
    overflow:auto;
}

@media (max-width: 1180px) {
    .script-tabs { gap:8px; }
}

@media (max-width: 900px) {
    .script-field-grid,
    .script-hero-kpis { grid-template-columns:1fr; }
}
</style>

<div class="script-page-shell">
    <div class="script-tabs" role="tablist" aria-label="Navigation scripts">
        <button
            type="button"
            class="script-tab <?= $activeTab === 'catalog' ? 'is-active' : '' ?>"
            data-script-tab="catalog"
            role="tab"
            aria-selected="<?= $activeTab === 'catalog' ? 'true' : 'false' ?>"
        >
            <span><?= t('scripts.tab.catalog') ?></span>
            <span class="script-tab-count"><?= count($scripts) ?></span>
        </button>
        <button
            type="button"
            class="script-tab <?= $activeTab === 'editor' ? 'is-active' : '' ?>"
            data-script-tab="editor"
            role="tab"
            aria-selected="<?= $activeTab === 'editor' ? 'true' : 'false' ?>"
        >
            <span><?= $selectedScript ? t('scripts.tab.edit') : t('scripts.tab.add') ?></span>
            <span class="script-tab-count"><?= $selectedScript ? max(1, (int) ($selectedScript['instruction_count'] ?? 0)) : 1 ?></span>
        </button>
    </div>

    <section id="script-panel-catalog" class="script-panel" <?= $activeTab !== 'catalog' ? 'hidden' : '' ?>>
        <div class="card script-library-card">
            <div class="card-header"><?= t('scripts.tab.catalog') ?></div>
            <?php if (empty($scripts)): ?>
            <div class="empty-state" style="padding:24px"><?= t('scripts.empty') ?></div>
            <?php else: ?>
            <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= t('common.name') ?></th>
                        <th><?= t('scripts.table.scope') ?></th>
                        <th><?= t('common.status') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scripts as $script): ?>
                    <?php $isSelected = (int) ($selectedScript['id'] ?? 0) === (int) $script['id']; ?>
                    <tr>
                        <td>
                            <a href="<?= routePath('/scripts.php', ['script_id' => (int) $script['id'], 'tab' => 'editor']) ?>" style="font-weight:500">
                                <?= h($script['name']) ?>
                            </a>
                            <div style="font-size:12px;color:var(--text2)"><?= h($script['description'] ?? '') ?></div>
                        </td>
                        <td><span class="badge badge-blue"><?= h(HookScriptManager::scopeLabel((string) ($script['execution_scope'] ?? 'both'))) ?></span></td>
                        <td><span class="badge <?= ($script['status'] ?? 'active') === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= h($script['status'] ?? 'active') ?></span></td>
                        <td style="text-align:right">
                            <?php if ($isSelected): ?>
                            <span class="badge badge-purple"><?= t('scripts.open_badge') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="script-panel-editor" class="script-panel" <?= $activeTab !== 'editor' ? 'hidden' : '' ?>>
        <div class="card script-studio-card">
            <div class="card-header"><?= $selectedScript ? t('scripts.editor.edit_title') : t('scripts.editor.new_title') ?></div>
            <div class="card-body script-studio-body">
                <div class="script-hero">
                    <div class="script-hero-copy">
                        <div class="script-hero-title"><?= t('scripts.hero.title') ?></div>
                        <div class="script-hero-text">
                            <?= t('scripts.hero.desc') ?>
                        </div>
                    </div>
                    <div class="script-hero-kpis">
                        <div class="script-hero-kpi">
                            <div class="script-hero-kpi-value"><?= count($allowedCommands) ?></div>
                            <div class="script-hero-kpi-label"><?= t('scripts.hero.approved_commands') ?></div>
                        </div>
                        <div class="script-hero-kpi">
                            <div class="script-hero-kpi-value"><?= count($systemVariables) ?></div>
                            <div class="script-hero-kpi-label"><?= t('scripts.hero.available_vars') ?></div>
                        </div>
                        <div class="script-hero-kpi">
                            <div class="script-hero-kpi-value"><?= count(array_filter($systemVariables, static fn(array $variable): bool => !empty($variable['sensitive']))) ?></div>
                            <div class="script-hero-kpi-label"><?= t('scripts.hero.referenced_secrets') ?></div>
                        </div>
                    </div>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="script_id" value="<?= (int) ($editorState['id'] ?? 0) ?>">
                    <div class="script-field-grid">
                        <div class="form-group">
                            <label class="form-label"><?= t('common.name') ?></label>
                            <input id="script-name" type="text" name="name" class="form-control" required maxlength="<?= HookScriptSecurity::maxNameLength() ?>"
                                   value="<?= h((string) ($editorState['name'] ?? '')) ?>" placeholder="freeze-web-volume">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('common.description') ?></label>
                            <input type="text" name="description" class="form-control"
                                   value="<?= h((string) ($editorState['description'] ?? '')) ?>"
                                   placeholder="<?= h(t('scripts.description_placeholder')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= t('scripts.table.scope') ?></label>
                            <select name="execution_scope" class="form-control">
                                <?php foreach (HookScriptSecurity::allowedScopes() as $scope): ?>
                                <option value="<?= h($scope) ?>" <?= (($editorState['execution_scope'] ?? 'both') === $scope) ? 'selected' : '' ?>>
                                    <?= h(HookScriptManager::scopeLabel($scope)) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('scripts.content_label') ?></label>
                        <div class="script-editor-shell">
                            <div class="script-editor-toolbar">
                                <div class="script-toolbar-group">
                                    <div class="script-toolbar-title"><?= t('scripts.toolbar.quick_commands') ?></div>
                                    <div class="script-chip-row">
                                        <?php foreach ($allowedCommands as $commandKey => $meta): ?>
                                        <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($meta['autocomplete_snippet'] ?? $commandKey)) ?>">
                                            <?= h((string) ($meta['label'] ?? $commandKey)) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="script-toolbar-group">
                                    <div class="script-toolbar-title"><?= t('scripts.toolbar.useful_vars') ?></div>
                                    <div class="script-chip-row">
                                        <?php foreach (array_slice(array_values(array_filter($systemVariables, static fn(array $variable): bool => empty($variable['sensitive']))), 0, 8) as $variable): ?>
                                        <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($variable['token'] ?? '')) ?>">
                                            <?= h((string) ($variable['token'] ?? '')) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="script-toolbar-group">
                                    <div class="script-toolbar-title"><?= t('scripts.toolbar.referenced_secrets') ?></div>
                                    <div class="script-chip-row">
                                        <?php foreach (array_slice(array_values(array_filter($systemVariables, static fn(array $variable): bool => !empty($variable['sensitive']))), 0, 4) as $variable): ?>
                                        <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($variable['token'] ?? '')) ?>">
                                            <?= h((string) ($variable['token'] ?? '')) ?>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="script-secret-note">
                                        <?= t('scripts.toolbar.secrets_note') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="script-editor-pane">
                                <div class="script-editor-pane-head">
                                    <div class="script-editor-dots" aria-hidden="true">
                                        <span class="script-editor-dot is-red"></span>
                                        <span class="script-editor-dot is-yellow"></span>
                                        <span class="script-editor-dot is-green"></span>
                                    </div>
                                    <div class="script-editor-meta"><?= t('scripts.editor.meta') ?></div>
                                </div>
                                <div class="script-editor-area">
                                    <textarea id="script-content" name="content" class="form-control mono" rows="14"
                                              placeholder="# Une commande approuvee par ligne&#10;systemctl reload nginx.service&#10;sleep 2&#10;fsfreeze --freeze {{SOURCE_PATH_1}}"><?= h((string) ($editorState['content'] ?? '')) ?></textarea>
                                    <div id="script-autocomplete" class="script-autocomplete" hidden></div>
                                </div>
                                <div class="script-editor-footer">
                                    <span><?= t('scripts.editor.footer_hint1') ?></span>
                                    <span><?= t('scripts.editor.footer_hint2') ?></span>
                                    <span><?= t('scripts.editor.footer_hint3') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= t('scripts.import_label') ?></label>
                        <input type="file" name="script_file" class="form-control" accept=".txt,.script,.hook,.sh,text/plain">
                    </div>
                    <div class="flex gap-2" style="justify-content:flex-end">
                        <?php if ($selectedScript): ?>
                        <a class="btn" href="<?= routePath('/scripts.php', ['tab' => 'editor']) ?>"><?= t('scripts.new_btn') ?></a>
                        <a class="btn" href="<?= routePath('/scripts.php', ['download' => '1', 'id' => (int) $selectedScript['id']]) ?>"><?= t('common.download') ?></a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= $selectedScript ? t('common.save') : t('scripts.create_btn') ?></button>
                    </div>
                </form>

                <?php if ($selectedScript): ?>
                <form method="POST" style="margin-top:12px;display:flex;justify-content:flex-end">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="script_id" value="<?= (int) $selectedScript['id'] ?>">
                    <input type="hidden" name="status" value="<?= ($selectedScript['status'] ?? 'active') === 'active' ? 'disabled' : 'active' ?>">
                    <button type="submit" class="btn <?= ($selectedScript['status'] ?? 'active') === 'active' ? 'btn-danger' : 'btn-success' ?>">
                        <?= ($selectedScript['status'] ?? 'active') === 'active' ? t('common.disable') : t('scripts.reactivate_btn') ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selectedScript): ?>
        <div class="card script-preview-card">
            <div class="card-header"><?= t('scripts.preview.title') ?></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px">
                    <div><strong>Checksum</strong><div class="mono" style="font-size:12px"><?= h((string) ($selectedScript['checksum'] ?? '')) ?></div></div>
                    <div><strong><?= t('scripts.preview.instructions') ?></strong><div><?= (int) ($selectedScript['instruction_count'] ?? 0) ?></div></div>
                    <div><strong><?= t('scripts.preview.created_by') ?></strong><div><?= h((string) ($selectedScript['created_by_username'] ?? t('common.system'))) ?></div></div>
                    <div><strong><?= t('scripts.preview.updated_by') ?></strong><div><?= h((string) ($selectedScript['updated_by_username'] ?? t('common.system'))) ?></div></div>
                </div>
                <?php if ($selectedContentError !== null): ?>
                <div class="alert alert-danger"><?= h($selectedContentError) ?></div>
                <?php else: ?>
                <div class="code-viewer" style="max-height:280px"><?= h($selectedContent) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="script-help-grid">
            <div class="card script-security-card">
                <div class="card-header"><?= t('scripts.security.title') ?></div>
                <div class="card-body">
                    <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
                        <?= t('scripts.security.desc') ?>
                    </div>
                    <div style="margin-bottom:12px">
                        <strong><?= t('scripts.security.limits') ?></strong>
                        <div style="font-size:12px;color:var(--text2);margin-top:4px">
                            <?= t('scripts.security.limits_desc', ['bytes' => HookScriptSecurity::maxBytes(), 'lines' => HookScriptSecurity::maxLines()]) ?>
                        </div>
                    </div>
                    <div style="margin-bottom:12px">
                        <strong><?= t('scripts.security.banned_commands') ?></strong>
                        <div class="policy-summary" style="margin-top:8px">
                            <?php foreach ($bannedCommands as $command): ?>
                            <span class="policy-chip policy-chip-gray"><?= h($command) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <strong><?= t('scripts.security.banned_patterns') ?></strong>
                        <div class="policy-summary" style="margin-top:8px">
                            <?php foreach ($bannedPatterns as $pattern): ?>
                            <span class="policy-chip policy-chip-blue"><?= h($pattern) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card script-command-card">
                <div class="card-header"><?= t('scripts.allowed_commands.title') ?></div>
                <div class="card-body">
                    <?php foreach ($allowedCommands as $commandKey => $meta): ?>
                    <div class="script-command-item">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center">
                            <div style="font-weight:600"><?= h($commandKey) ?></div>
                            <span class="badge badge-blue"><?= h(implode(' / ', $meta['allowed_scopes'] ?? ['local', 'remote'])) ?></span>
                        </div>
                        <div style="font-size:12px;color:var(--text2)"><?= h((string) ($meta['description'] ?? '')) ?></div>
                        <div class="mono" style="font-size:12px"><?= h((string) ($meta['binary'] ?? '')) ?></div>
                        <div class="script-chip-row">
                            <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($meta['autocomplete_insert'] ?? ($commandKey . ' '))) ?>">
                                <?= t('scripts.insert_command') ?>
                            </button>
                            <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($meta['autocomplete_snippet'] ?? $commandKey)) ?>">
                                <?= t('scripts.insert_example') ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card script-variable-card">
                <div class="card-header"><?= t('scripts.variables.title') ?></div>
                <div class="card-body">
                    <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
                        <?= t('scripts.variables.desc') ?>
                    </div>
                    <div class="script-browser-controls">
                        <input id="script-variable-search" type="search" class="form-control" placeholder="<?= h(t('scripts.variables.filter_placeholder')) ?>">
                        <div id="script-variable-categories" class="script-browser-pills">
                            <button type="button" class="script-browser-pill is-active" data-variable-category="all"><?= t('common.all') ?></button>
                            <?php foreach ($variableCategories as $category): ?>
                            <button type="button" class="script-browser-pill" data-variable-category="<?= h($category) ?>"><?= h($category) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="script-variable-empty" class="script-empty-note" hidden><?= t('scripts.variables.empty_filter') ?></div>
                    <?php foreach ($variableGroups as $category => $variables): ?>
                    <div class="script-help-section script-variable-group" data-variable-category="<?= h($category) ?>">
                        <div class="script-help-section-title"><?= h($category) ?></div>
                        <div class="script-variable-list">
                            <?php foreach ($variables as $variable): ?>
                            <?php
                            $searchIndex = strtolower(trim(
                                (string) ($variable['token'] ?? '') . ' ' .
                                (string) ($variable['label'] ?? '') . ' ' .
                                (string) ($variable['description'] ?? '') . ' ' .
                                (string) ($variable['category'] ?? '') . ' ' .
                                (string) ($variable['provider'] ?? '')
                            ));
                            ?>
                            <div class="script-variable-item" data-variable-category="<?= h((string) ($variable['category'] ?? 'Systeme')) ?>" data-variable-search="<?= h($searchIndex) ?>">
                                <div class="script-variable-item-top">
                                    <button type="button" class="script-insert-chip" data-script-insert="<?= h((string) ($variable['token'] ?? '')) ?>">
                                        <?= h((string) ($variable['token'] ?? '')) ?>
                                    </button>
                                    <div class="script-variable-meta">
                                        <span class="badge badge-blue"><?= h((string) ($variable['type'] ?? 'string')) ?></span>
                                        <?php if (!empty($variable['provider'])): ?>
                                        <span class="badge badge-gray"><?= h((string) $variable['provider']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($variable['sensitive'])): ?>
                                        <span class="badge badge-yellow"><?= t('scripts.variables.masked') ?></span>
                                        <?php endif; ?>
                                        <span class="badge <?= !empty($variable['available']) ? 'badge-green' : 'badge-gray' ?>">
                                            <?= !empty($variable['available']) ? t('scripts.variables.available') : t('scripts.variables.unavailable') ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="font-weight:600"><?= h((string) ($variable['label'] ?? '')) ?></div>
                                <div class="script-variable-desc"><?= h((string) ($variable['description'] ?? '')) ?></div>
                                <div class="script-variable-sample"><?= t('scripts.variables.example_prefix') ?> <?= h((string) ($variable['sample'] ?? '')) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script<?= cspNonceAttr() ?>>
(function() {
    const tabButtons = Array.from(document.querySelectorAll('[data-script-tab]'));
    const tabPanels = {
        catalog: document.getElementById('script-panel-catalog'),
        editor: document.getElementById('script-panel-editor'),
    };
    const scriptNameInput = document.getElementById('script-name');

    function setActiveTab(nextTab, options = {}) {
        if (!tabPanels[nextTab]) {
            return;
        }

        const shouldUpdateUrl = options.updateUrl !== false;
        tabButtons.forEach((button) => {
            const isActive = button.getAttribute('data-script-tab') === nextTab;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        Object.entries(tabPanels).forEach(([name, panel]) => {
            if (!panel) {
                return;
            }
            panel.hidden = name !== nextTab;
        });

        if (shouldUpdateUrl && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', nextTab);
            window.history.replaceState({}, '', url);
        }
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', function() {
            const nextTab = button.getAttribute('data-script-tab') || 'catalog';
            setActiveTab(nextTab);
            if (nextTab === 'editor' && scriptNameInput && !scriptNameInput.value.trim()) {
                scriptNameInput.focus();
            }
        });
    });

    setActiveTab(<?= json_encode($activeTab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, { updateUrl: false });

    const textarea = document.getElementById('script-content');
    const panel = document.getElementById('script-autocomplete');
    const items = <?= json_encode($autocompleteItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const commandContexts = <?= json_encode($commandContextItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    if (!textarea || !panel) {
        return;
    }

    const state = { visible: false, selectedIndex: 0, items: [], start: 0 };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function filterEntries(entries, query) {
        const normalized = String(query || '').toLowerCase();
        return entries.filter((item) => {
            if (!normalized) {
                return true;
            }
            const haystack = [item.label || '', item.insert || '', item.description || '', item.meta || '', item.match || ''].join(' ').toLowerCase();
            return haystack.includes(normalized);
        });
    }

    function getContext() {
        const caret = textarea.selectionStart || 0;
        const before = textarea.value.slice(0, caret);
        const lineStart = before.lastIndexOf('\n') + 1;
        const linePrefix = before.slice(lineStart);

        const variableMatch = linePrefix.match(/\{\{[A-Z0-9_]*$/i);
        if (variableMatch) {
            return { type: 'variable', query: variableMatch[0].slice(2).toLowerCase(), start: caret - variableMatch[0].length };
        }

        const leadingWhitespace = (linePrefix.match(/^\s*/) || [''])[0];
        const commandFragment = linePrefix.slice(leadingWhitespace.length);
        if (/^[a-z0-9_.-]*$/i.test(commandFragment)) {
            return { type: 'command', query: commandFragment.toLowerCase(), start: lineStart + leadingWhitespace.length };
        }

        const currentLine = linePrefix.slice(leadingWhitespace.length);
        const trailingSpace = /\s$/.test(currentLine);
        const tokens = currentLine.trim() === '' ? [] : currentLine.trim().split(/\s+/);
        if (tokens.length === 0) {
            return null;
        }

        const command = tokens[0];
        const definition = commandContexts[command];
        if (!definition) {
            return null;
        }

        const argumentIndex = trailingSpace ? tokens.length : tokens.length - 1;
        if (!definition[String(argumentIndex)]) {
            return null;
        }

        const fragment = trailingSpace ? '' : (tokens[tokens.length - 1] || '');
        return { type: 'argument', command, argumentIndex, query: fragment.toLowerCase(), start: trailingSpace ? caret : caret - fragment.length };
    }

    function buildSuggestions(context) {
        if (context.type === 'variable') {
            return filterEntries(items.filter((item) => item.type === 'variable'), context.query).slice(0, 10);
        }
        if (context.type === 'command') {
            return filterEntries(items.filter((item) => item.type === 'command'), context.query).slice(0, 10);
        }
        if (context.type === 'argument') {
            return filterEntries(commandContexts[context.command][String(context.argumentIndex)] || [], context.query).slice(0, 10);
        }
        return [];
    }

    function renderPanel() {
        if (!state.visible || state.items.length === 0) {
            panel.hidden = true;
            panel.innerHTML = '';
            return;
        }

        panel.innerHTML = state.items.map((item, index) => `
            <button type="button" class="script-autocomplete-item ${index === state.selectedIndex ? 'is-active' : ''}" data-autocomplete-index="${index}">
                <span class="script-autocomplete-head">
                    <span class="script-autocomplete-label">${escapeHtml(item.label)}</span>
                    <span class="script-autocomplete-meta">${escapeHtml(item.meta || '')}</span>
                </span>
                <span class="script-autocomplete-desc">${escapeHtml(item.description || '')}</span>
            </button>
        `).join('');
        panel.hidden = false;
    }

    function closePanel() {
        state.visible = false;
        state.items = [];
        state.selectedIndex = 0;
        renderPanel();
    }

    function insertText(value) {
        const start = state.start;
        const end = textarea.selectionStart || start;
        textarea.value = textarea.value.slice(0, start) + value + textarea.value.slice(end);
        const caret = start + value.length;
        textarea.focus();
        textarea.setSelectionRange(caret, caret);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function updateSuggestions() {
        const context = getContext();
        if (!context) {
            closePanel();
            return;
        }

        const matches = buildSuggestions(context);
        if (matches.length === 0) {
            closePanel();
            return;
        }

        state.visible = true;
        state.items = matches;
        state.selectedIndex = 0;
        state.start = context.start;
        renderPanel();
    }

    textarea.addEventListener('input', updateSuggestions);
    textarea.addEventListener('click', updateSuggestions);
    textarea.addEventListener('focus', updateSuggestions);

    textarea.addEventListener('keydown', function(event) {
        if (!state.visible || state.items.length === 0) {
            if (event.ctrlKey && event.code === 'Space') {
                updateSuggestions();
                event.preventDefault();
            }
            return;
        }

        if (event.key === 'ArrowDown') {
            state.selectedIndex = (state.selectedIndex + 1) % state.items.length;
            renderPanel();
            event.preventDefault();
            return;
        }
        if (event.key === 'ArrowUp') {
            state.selectedIndex = (state.selectedIndex - 1 + state.items.length) % state.items.length;
            renderPanel();
            event.preventDefault();
            return;
        }
        if (event.key === 'Enter' || event.key === 'Tab') {
            insertText(state.items[state.selectedIndex].insert);
            closePanel();
            event.preventDefault();
            return;
        }
        if (event.key === 'Escape') {
            closePanel();
            event.preventDefault();
        }
    });

    panel.addEventListener('mousedown', function(event) {
        const target = event.target.closest('[data-autocomplete-index]');
        if (!target) {
            return;
        }
        const index = Number(target.getAttribute('data-autocomplete-index'));
        if (!Number.isInteger(index) || !state.items[index]) {
            return;
        }
        insertText(state.items[index].insert);
        closePanel();
        event.preventDefault();
    });

    document.addEventListener('click', function(event) {
        if (event.target === textarea || panel.contains(event.target)) {
            return;
        }
        closePanel();
    });

    document.querySelectorAll('[data-script-insert]').forEach((button) => {
        button.addEventListener('click', function() {
            textarea.focus();
            const insertion = button.getAttribute('data-script-insert') || '';
            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || start;
            textarea.value = textarea.value.slice(0, start) + insertion + textarea.value.slice(end);
            const caret = start + insertion.length;
            textarea.setSelectionRange(caret, caret);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    const searchInput = document.getElementById('script-variable-search');
    const categoryButtons = Array.from(document.querySelectorAll('.script-browser-pill'));
    const groups = Array.from(document.querySelectorAll('.script-variable-group'));
    const emptyState = document.getElementById('script-variable-empty');
    let activeCategory = 'all';

    function applyVariableFilters() {
        const needle = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;

        groups.forEach((group) => {
            let groupVisible = false;
            group.querySelectorAll('.script-variable-item').forEach((item) => {
                const category = (item.getAttribute('data-variable-category') || '').toLowerCase();
                const searchable = (item.getAttribute('data-variable-search') || '').toLowerCase();
                const categoryMatch = activeCategory === 'all' || category === activeCategory;
                const searchMatch = needle === '' || searchable.includes(needle);
                const visible = categoryMatch && searchMatch;
                item.hidden = !visible;
                if (visible) {
                    groupVisible = true;
                    visibleCount++;
                }
            });
            group.hidden = !groupVisible;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    categoryButtons.forEach((button) => {
        button.addEventListener('click', function() {
            activeCategory = String(button.getAttribute('data-variable-category') || 'all').toLowerCase();
            categoryButtons.forEach((candidate) => candidate.classList.toggle('is-active', candidate === button));
            applyVariableFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyVariableFilters);
    }
    applyVariableFilters();
})();
</script>

<?php include 'layout_bottom.php'; ?>
