<?php

function exploreViewCacheTtl(): int {
    return AppConfig::exploreViewCacheTtl();
}

function explorePageSize(): int {
    return AppConfig::explorePageSize();
}

function exploreSearchMaxResults(): int {
    return AppConfig::exploreSearchMaxResults();
}

function exploreMaxFileSizeBytes(): int {
    return AppConfig::exploreMaxFileSizeBytes();
}

function buildExplorePayload(array $repo, Restic $restic, ?string $snapshot, string $path, string $action, int $page = 1): array {
    $cacheableActions = ['browse', 'partial', 'search', 'stats'];
    $cacheKeyParts = [
        (int) $repo['id'],
        (string) $snapshot,
        $path,
        $action,
        max(1, $page),
        $_SESSION['role'] ?? 'viewer',
    ];

    if (in_array($action, $cacheableActions, true)) {
        $cached = readExploreViewCache($cacheKeyParts, exploreViewCacheTtl());
        if ($cached !== null) {
            return $cached;
        }
    }

    $repoId = (int) $repo['id'];
    $snapshotList = RepoSnapshotCatalog::getSnapshots($repoId);
    $hasError = false;
    $errorMessage = null;
    $catalogPending = false;

    if (empty($snapshotList)) {
        $cachedSnapshots = $restic->cachedSnapshots(true);
        if (is_array($cachedSnapshots) && !isset($cachedSnapshots['error'])) {
            $snapshotList = array_values($cachedSnapshots);
            if (!empty($snapshotList)) {
                RepoSnapshotCatalog::sync($repoId, $snapshotList);
                $snapshotList = RepoSnapshotCatalog::getSnapshots($repoId);
            }
        }
    }

    $snapshotData = findExploreSnapshotData($snapshotList, $snapshot);

    if (empty($snapshotList) || ($snapshot !== null && $snapshot !== '' && $snapshotData === null)) {
        $liveSnapshots = $restic->snapshots();
        if (is_array($liveSnapshots) && !isset($liveSnapshots['error'])) {
            RepoSnapshotCatalog::sync($repoId, $liveSnapshots);
            $snapshotList = RepoSnapshotCatalog::getSnapshots($repoId);
            $snapshotData = findExploreSnapshotData($snapshotList, $snapshot);
        } elseif (isset($liveSnapshots['error'])) {
            $hasError = true;
            $errorMessage = (string) $liveSnapshots['error'];
        }
    }

    if (empty($snapshotList)) {
        foreach (RepoStatusService::getStatuses(false) as $status) {
            if ((int) ($status['id'] ?? 0) !== $repoId) {
                continue;
            }
            $catalogPending = ((int) ($status['count'] ?? 0)) > 0 || ($status['status'] ?? '') === 'pending';
            break;
        }
    }

    ob_start();
    renderExploreSnapshotsPanel($repoId, $snapshotList, $hasError, $snapshot, $errorMessage, $catalogPending);
    $snapshotsHtml = ob_get_clean();

    ob_start();
    renderExploreMainPanel($repo, $restic, $snapshot, $path, $action, $snapshotList, $snapshotData, max(1, $page));
    $contentHtml = ob_get_clean();

    $payload = [
        'snapshots_html' => $snapshotsHtml,
        'content_html' => $contentHtml,
        'meta' => buildExploreMeta($repoId, $snapshotList, $snapshotData),
        'status' => inferExplorePayloadStatus($snapshotsHtml, $contentHtml, $snapshotList, $snapshotData, $catalogPending),
        'poll_after_ms' => 5000,
    ];

    if (in_array($action, $cacheableActions, true) && ($payload['status'] ?? 'ready') === 'ready') {
        writeExploreViewCache($cacheKeyParts, $payload);
    }

    return $payload;
}

function inferExplorePayloadStatus(
    string $snapshotsHtml,
    string $contentHtml,
    array $snapshotList,
    ?array $snapshotData,
    bool $catalogPending
): string {
    if ($catalogPending || empty($snapshotList) || ($snapshotData === null && !empty($snapshotList))) {
        return 'pending';
    }

    $pendingNeedles = [
        t('explore.pending.content'),
        t('explore.pending.tree'),
        t('explore.pending.snapshot'),
        t('explore.pending.catalog'),
        t('explore.pending.deferred'),
        t('explore.pending.not_synced'),
    ];

    foreach ($pendingNeedles as $needle) {
        if (str_contains($snapshotsHtml, $needle) || str_contains($contentHtml, $needle)) {
            return 'pending';
        }
    }

    return 'ready';
}

function getExploreViewCacheRoot(): string {
    $dir = dirname(DB_PATH) . '/cache/explore';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir;
}

function getExploreViewCachePath(array $parts): string {
    $key = sha1(json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return getExploreViewCacheRoot() . '/' . $key . '.json';
}

function readExploreViewCache(array $parts, int $ttl): ?array {
    $path = getExploreViewCachePath($parts);
    if (!is_file($path)) {
        return null;
    }

    $modifiedAt = @filemtime($path);
    if ($modifiedAt === false || (time() - $modifiedAt) > $ttl) {
        @unlink($path);
        return null;
    }

    $content = @file_get_contents($path);
    if ($content === false) {
        return null;
    }

    $payload = json_decode($content, true);
    return is_array($payload) ? $payload : null;
}

function writeExploreViewCache(array $parts, array $payload): void {
    $path = getExploreViewCachePath($parts);
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }

    $tmpPath = $path . '.tmp';
    if (@file_put_contents($tmpPath, $json, LOCK_EX) === false) {
        @unlink($tmpPath);
        return;
    }

    @rename($tmpPath, $path);
}

function buildExploreMeta(int $repoId, array $snapshots, ?array $snapshotData): array {
    require_once __DIR__ . '/RestoreTargetPlanner.php';

    $normalizedSnapshots = [];
    foreach ($snapshots as $snap) {
        $normalizedSnapshots[] = [
            'short_id' => (string) ($snap['short_id'] ?? ''),
            'time' => (string) ($snap['time'] ?? ''),
            'formatted_time' => !empty($snap['time']) ? formatDate($snap['time']) : '',
            'hostname' => (string) ($snap['hostname'] ?? ''),
            'tags' => array_values(array_map('strval', $snap['tags'] ?? [])),
            'paths' => array_values(array_map('strval', $snap['paths'] ?? [])),
        ];
    }

    $originHost = RestoreTargetPlanner::findSnapshotOriginHost($repoId, $snapshotData);
    $originHostMeta = $originHost ? [
        'id' => (int) ($originHost['id'] ?? 0),
        'name' => (string) ($originHost['name'] ?? ''),
        'hostname' => (string) ($originHost['hostname'] ?? ''),
        'user' => (string) ($originHost['user'] ?? ''),
        'port' => (int) ($originHost['port'] ?? 22),
        'origin_source' => (string) ($originHost['origin_source'] ?? ''),
    ] : null;

    return [
        'snapshots' => $normalizedSnapshots,
        'current_snapshot' => $snapshotData ? [
            'short_id' => (string) ($snapshotData['short_id'] ?? ''),
            'tags' => array_values(array_map('strval', $snapshotData['tags'] ?? [])),
            'paths' => array_values(array_map('strval', $snapshotData['paths'] ?? [])),
            'hostname' => (string) ($snapshotData['hostname'] ?? ''),
            'time' => (string) ($snapshotData['time'] ?? ''),
            'formatted_time' => !empty($snapshotData['time']) ? formatDate($snapshotData['time']) : '',
        ] : null,
        'original_restore' => [
            'confirmation_word' => RestoreTargetPlanner::ORIGINAL_CONFIRMATION_WORD,
            'origin_host' => $originHostMeta,
        ],
    ];
}

function findExploreSnapshotData(array $snapshots, ?string $snapshot): ?array {
    if (!$snapshot) {
        return null;
    }

    foreach ($snapshots as $snap) {
        if (($snap['short_id'] ?? null) === $snapshot) {
            return $snap;
        }
    }

    return null;
}

function normalizeExplorePath(string $path): string {
    $path = trim($path);
    if ($path === '' || $path === '.') {
        return '/';
    }

    $normalized = '/' . trim($path, '/');
    return $normalized === '//' ? '/' : $normalized;
}

function buildSyntheticExploreItems(array $snapshotPaths, string $currentPath): array {
    $currentPath = normalizeExplorePath($currentPath);
    $items = [];

    foreach ($snapshotPaths as $savedPath) {
        $normalizedSavedPath = normalizeExplorePath((string) $savedPath);
        if ($normalizedSavedPath === $currentPath) {
            continue;
        }

        if ($currentPath !== '/' && !str_starts_with($normalizedSavedPath, $currentPath . '/')) {
            continue;
        }

        $remainder = $currentPath === '/'
            ? ltrim($normalizedSavedPath, '/')
            : ltrim(substr($normalizedSavedPath, strlen($currentPath)), '/');

        if ($remainder === '') {
            continue;
        }

        $nextSegment = strtok($remainder, '/');
        if ($nextSegment === false || $nextSegment === '') {
            continue;
        }

        $itemPath = $currentPath === '/'
            ? '/' . $nextSegment
            : $currentPath . '/' . $nextSegment;

        $items[$itemPath] = [
            'path' => $itemPath,
            'name' => $nextSegment,
            'type' => 'dir',
            'size' => 0,
            'mtime' => null,
        ];
    }

    ksort($items, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($items);
}

function paginateExploreItems(array $items, int $page, ?int $pageSize = null): array {
    $totalItems = count($items);
    $pageSize = $pageSize ?? explorePageSize();
    $pageSize = max(1, $pageSize);
    $pageCount = max(1, (int) ceil($totalItems / $pageSize));
    $page = min(max(1, $page), $pageCount);

    return [
        'page' => $page,
        'page_count' => $pageCount,
        'page_size' => $pageSize,
        'total_items' => $totalItems,
        'items' => array_slice($items, ($page - 1) * $pageSize, $pageSize),
    ];
}

function renderExplorePagination(int $repoId, string $snapshot, string $action, string $path, array $pagination): void {
    if (($pagination['page_count'] ?? 1) <= 1) {
        return;
    }

    $page = (int) ($pagination['page'] ?? 1);
    $pageCount = (int) ($pagination['page_count'] ?? 1);
    $totalItems = (int) ($pagination['total_items'] ?? 0);
    $startPage = max(1, $page - 2);
    $endPage = min($pageCount, $startPage + 4);
    $startPage = max(1, $endPage - 4);
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);flex-wrap:wrap">
        <div style="font-size:12px;color:var(--text2)">
            <?= h(t('explore.pagination.summary', ['total' => (string) $totalItems, 'page' => (string) $page, 'pages' => (string) $pageCount])) ?>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ($page > 1): ?>
            <a class="btn btn-sm" href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=<?= $action ?>&path=<?= urlencode($path) ?>&page=<?= $page - 1 ?>"><?= h(t('common.prev')) ?></a>
            <?php endif; ?>
            <?php for ($pageNo = $startPage; $pageNo <= $endPage; $pageNo++): ?>
            <a class="btn btn-sm <?= $pageNo === $page ? 'btn-primary' : '' ?>" href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=<?= $action ?>&path=<?= urlencode($path) ?>&page=<?= $pageNo ?>"><?= $pageNo ?></a>
            <?php endfor; ?>
            <?php if ($page < $pageCount): ?>
            <a class="btn btn-sm" href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=<?= $action ?>&path=<?= urlencode($path) ?>&page=<?= $page + 1 ?>"><?= h(t('common.next')) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function renderExploreSnapshotsPanel(
    int $repoId,
    array $snapshots,
    bool $hasError,
    ?string $activeSnapshot,
    ?string $errorMessage = null,
    bool $catalogPending = false
): void {
    ?>
    <div class="card" style="height:fit-content">
        <div class="card-header">
            <span><?= h(t('explore.snapshots')) ?></span>
            <span class="badge badge-blue"><?= $hasError ? 0 : count($snapshots) ?></span>
        </div>
        <?php if ($hasError): ?>
        <div style="padding:12px"><div class="alert alert-danger" style="font-size:12px"><?= h((string) $errorMessage) ?></div></div>
        <?php elseif ($catalogPending): ?>
        <div class="empty-state" style="padding:24px">
            <div><?= h(t('explore.pending.catalog')) ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:6px"><?= h(t('explore.pending.catalog_desc')) ?></div>
        </div>
        <?php elseif (empty($snapshots)): ?>
        <div class="empty-state" style="padding:24px">
            <div><?= h(t('explore.pending.catalog_not_synced')) ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:6px"><?= h(t('explore.pending.catalog_not_synced_desc')) ?></div>
        </div>
        <?php else: ?>
        <div style="max-height:600px;overflow-y:auto">
            <?php foreach ($snapshots as $snap): ?>
            <?php $isActive = $activeSnapshot === ($snap['short_id'] ?? null); ?>
            <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snap['short_id']) ?>&action=browse&path=%2F"
               style="display:block;padding:10px 12px;border-bottom:1px solid var(--border);
                      text-decoration:none;background:<?= $isActive ? 'var(--bg3)' : 'transparent' ?>;
                      transition:background .1s"
               onmouseover="this.style.background='var(--bg3)'"
               onmouseout="this.style.background='<?= $isActive ? 'var(--bg3)' : 'transparent' ?>'">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
                    <span style="font-family:var(--font-mono);font-size:12px;color:var(--accent)"><?= h($snap['short_id']) ?></span>
                    <?php foreach ($snap['tags'] ?? [] as $tag): ?>
                    <span class="badge badge-gray" style="font-size:10px"><?= h($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:11px;color:var(--text2)"><?= !empty($snap['time']) ? formatDate($snap['time']) : '—' ?></div>
                <div style="font-size:11px;color:var(--text2)"><?= h($snap['hostname'] ?? '') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function renderExploreMainPanel(
    array $repo,
    Restic $restic,
    ?string $snapshot,
    string $path,
    string $action,
    array $snapshots,
    ?array $snapshotData,
    int $page = 1
): void {
    $repoId = (int) $repo['id'];

    if (!$snapshot): ?>
    <div class="card">
        <div class="empty-state" style="padding:64px">
            <div><?= h(t('explore.select_snapshot_prompt')) ?></div>
        </div>
    </div>
    <?php
        return;
    endif;

    if ($snapshotData === null): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px">
            <div><?= h(t('explore.pending.snapshot')) ?></div>
            <div style="font-size:12px;color:var(--text2);margin-top:8px"><?= h(t('explore.pending.snapshot_desc')) ?></div>
        </div>
    </div>
    <?php
        return;
    endif;
    ?>

    <div class="card mb-4">
        <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
            <div>
                <div style="font-size:11px;color:var(--text2)"><?= h(t('explore.snapshot')) ?></div>
                <div style="font-family:var(--font-mono);font-size:13px;color:var(--accent)"><?= h($snapshot) ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2)"><?= h(t('common.date')) ?></div>
                <div style="font-size:13px"><?= $snapshotData ? formatDate($snapshotData['time']) : '—' ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2)"><?= h(t('common.host')) ?></div>
                <div style="font-size:13px"><?= h($snapshotData['hostname'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text2)"><?= h(t('explore.paths')) ?></div>
                <div style="font-size:12px;font-family:var(--font-mono)"><?= h(implode(', ', $snapshotData['paths'] ?? [])) ?></div>
            </div>
            <?php if (Auth::canRestore() || Auth::isAdmin()): ?>
            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
                <?php if (Auth::canRestore()): ?>
                <button class="btn btn-success btn-sm" onclick="document.getElementById('modal-restore').classList.add('show')">↩ <?= h(t('explore.restore_btn')) ?></button>
                <a class="btn btn-sm" href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=partial&path=<?= urlencode($path) ?>"><?= h(t('explore.partial_restore.title')) ?></a>
                <?php endif; ?>
                <?php if (Auth::isAdmin()): ?>
                <button class="btn btn-sm" onclick="document.getElementById('modal-diff').classList.add('show')">⇄ <?= h(t('explore.compare_btn')) ?></button>
                <button class="btn btn-sm" onclick="document.getElementById('modal-file-diff').classList.add('show')"><?= h(t('explore.file_diff.title')) ?></button>
                <button class="btn btn-sm" onclick="document.getElementById('modal-tags').classList.add('show')"><?= h(t('common.tags')) ?></button>
                <button class="btn btn-sm btn-warning" onclick="document.getElementById('modal-retention').classList.add('show')">✂ <?= h(t('explore.retention_modal.title')) ?></button>
                <button class="btn btn-sm btn-danger" data-snapshot="<?= h($snapshot) ?>" onclick="deleteSnapshot(this.dataset.snapshot)"><?= h(t('common.delete')) ?></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px">
        <?php
        $tabs = [
            'browse' => t('explore.tab.browse'),
            'partial' => t('explore.tab.partial'),
            'search' => t('explore.tab.search'),
            'stats' => t('explore.tab.stats'),
        ];
        foreach ($tabs as $tabKey => $tabLabel):
        ?>
        <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=<?= $tabKey ?>&path=<?= urlencode($path) ?>"
           style="padding:8px 16px;font-size:13px;
                  color:<?= $action === $tabKey ? 'var(--text)' : 'var(--text2)' ?>;
                  border-bottom:2px solid <?= $action === $tabKey ? 'var(--accent)' : 'transparent' ?>;
                  text-decoration:none;font-weight:<?= $action === $tabKey ? '500' : '400' ?>">
            <?= $tabLabel ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($action === 'browse'): ?>

    <?php if (!empty($snapshotData['paths'])): ?>
    <div class="flex items-center gap-2 mb-4" style="flex-wrap:wrap">
        <span style="font-size:12px;color:var(--text2)"><?= h(t('explore.paths')) ?> :</span>
        <?php foreach ($snapshotData['paths'] as $savedPath): ?>
        <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=<?= urlencode($savedPath) ?>"
           class="badge badge-blue" style="font-family:var(--font-mono);font-size:11px;padding:3px 8px">
            <?= h($savedPath) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="breadcrumb mb-4">
        <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=/"><?= h(t('common.root')) ?></a>
        <?php
        $parts = array_values(array_filter(explode('/', $path)));
        $current = '';
        foreach ($parts as $part):
            $current .= '/' . $part;
        ?>
        <span>/</span>
        <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=<?= urlencode($current) ?>"><?= h($part) ?></a>
        <?php endforeach; ?>
    </div>

    <?php
    $items = SnapshotSearchIndex::listDirectory($repoId, $snapshot, $path);
    if ($items === null) {
        $items = $restic->cachedLs($snapshot, $path, true);
    }
    if ($items === null && !empty($snapshotData['paths'])) {
        $items = buildSyntheticExploreItems($snapshotData['paths'], $path);
    }
    if ($items === null || $items === []) {
        JobQueue::enqueueSnapshotFullIndex($repoId, $snapshot, 'explore_missing_index', 185);
        $items = $restic->lsWithTimeout($snapshot, $path, 6);
    }
    if ($items === null):
    ?>
    <div class="empty-state" style="padding:32px">
        <div><?= h(t('explore.pending.content')) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:6px"><?= h(t('explore.pending.content_desc')) ?></div>
    </div>
    <?php
    elseif (is_array($items) && !empty($items['timed_out'])):
    ?>
    <div class="empty-state" style="padding:32px">
        <div><?= h(t('explore.pending.deferred')) ?></div>
        <div style="font-size:12px;color:var(--text2);margin-top:6px"><?= h(t('explore.pending.deferred_desc')) ?></div>
    </div>
    <?php
    elseif (isset($items['error'])):
    ?>
    <div class="alert alert-danger"><?= h($items['error']) ?></div>
    <?php else:
        $dirs = [];
        $files = [];
        foreach ($items as $item) {
            if (!isset($item['type'])) {
                continue;
            }
            if ($item['type'] === 'dir') {
                $dirs[] = $item;
            } else {
                $files[] = $item;
            }
        }
        usort($dirs, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        usort($files, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        $pagination = paginateExploreItems(array_merge($dirs, $files), $page);
        $pagedItems = $pagination['items'];
        $dirs = array_values(array_filter($pagedItems, static fn($item) => ($item['type'] ?? '') === 'dir'));
        $files = array_values(array_filter($pagedItems, static fn($item) => ($item['type'] ?? '') !== 'dir'));
    ?>
    <div class="card">
        <?php renderExplorePagination($repoId, $snapshot, 'browse', $path, $pagination); ?>
        <div class="file-explorer" data-virtualize="1" data-virtual-threshold="120" data-virtual-height="70vh">
            <?php if ($path !== '/'): ?>
            <?php $parent = dirname($path); if ($parent === '.') { $parent = '/'; } ?>
            <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=<?= urlencode($parent) ?>&page=1"
               class="file-item dir" style="border-bottom:1px solid var(--border)">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h6l2 2h6v9H1z"/></svg>
                ..
            </a>
            <?php endif; ?>

            <?php foreach ($dirs as $item):
                $itemPath = (string) ($item['path'] ?? (rtrim($path, '/') . '/' . ($item['name'] ?? '')));
            ?>
            <div class="file-item dir" style="border-bottom:1px solid var(--border);display:flex;align-items:center;gap:6px">
                <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=<?= urlencode($itemPath) ?>&page=1"
                   style="display:flex;align-items:center;gap:6px;flex:1;text-decoration:none;color:inherit">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h6l2 2h6v9H1z"/></svg>
                    <?= h($item['name']) ?>/
                </a>
                <span class="file-mtime"><?= !empty($item['mtime']) ? formatDate($item['mtime']) : '' ?></span>
                <div style="display:flex;gap:4px;margin-left:auto">
                    <a href="/api/download_folder.php?repo_id=<?= $repoId ?>&snapshot=<?= urlencode($snapshot) ?>&path=<?= urlencode($itemPath) ?>&format=tar.gz"
                       class="btn btn-sm" style="padding:2px 5px;font-size:10px" title="<?= h(t('explore.download_tar')) ?>">
                        .tar.gz
                    </a>
                    <a href="/api/download_folder.php?repo_id=<?= $repoId ?>&snapshot=<?= urlencode($snapshot) ?>&path=<?= urlencode($itemPath) ?>&format=zip"
                       class="btn btn-sm" style="padding:2px 5px;font-size:10px" title="<?= h(t('explore.download_zip')) ?>">
                        .zip
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach ($files as $item):
                $itemPath = (string) ($item['path'] ?? (rtrim($path, '/') . '/' . ($item['name'] ?? '')));
                $isText = isTextFile($item['name']);
                $previewAllowed = canInlinePreviewFile((string) ($item['name'] ?? ''));
                $size = $item['size'] ?? 0;
            ?>
            <div class="file-item" style="border-bottom:1px solid var(--border)">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" style="color:var(--text2)">
                    <rect x="3" y="1" width="10" height="14" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                </svg>
                <?php if ($isText && $size <= exploreMaxFileSizeBytes()): ?>
                <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=view&path=<?= urlencode($itemPath) ?>">
                    <?= h($item['name']) ?>
                </a>
                <?php if (!$previewAllowed): ?>
                <span class="badge badge-gray" style="font-size:10px"><?= h(t('explore.preview_masked_short')) ?></span>
                <?php endif; ?>
                <?php else: ?>
                <span style="color:var(--text)"><?= h($item['name']) ?></span>
                <?php if ($size > exploreMaxFileSizeBytes()): ?>
                <span class="badge badge-gray" style="font-size:10px"><?= h(t('explore.too_large_short')) ?></span>
                <?php endif; ?>
                <?php endif; ?>
                <a href="/api/download_file.php?repo_id=<?= $repoId ?>&snapshot=<?= urlencode($snapshot) ?>&path=<?= urlencode($itemPath) ?>"
                   class="btn btn-sm" style="margin-left:8px;padding:2px 6px;font-size:11px" title="<?= h(t('explore.download')) ?>">
                    ↓
                </a>
                <span class="file-size"><?= formatBytes($size) ?></span>
                <span class="file-mtime"><?= !empty($item['mtime']) ? formatDate($item['mtime']) : '' ?></span>
            </div>
            <?php endforeach; ?>

            <?php if (empty($dirs) && empty($files)): ?>
            <div class="empty-state" style="padding:24px"><?= h(t('explore.empty_folder')) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($action === 'view'):
        $filename = basename($path);
        $previewAllowed = canInlinePreviewFile($path);
        $preview = null;
        $previewContent = '';
        if ($previewAllowed) {
            $preview = $restic->dumpPreview($snapshot, $path, 8);
            $previewContent = (string) ($preview['content'] ?? '');
            Auth::log('file_view', "Lecture du fichier: $path (snapshot: $snapshot, repo: {$repo['name']})");
        } else {
            Auth::log('file_view_masked', "Apercu masque du fichier sensible: $path (snapshot: $snapshot, repo: {$repo['name']})");
        }
    ?>
    <div class="mb-4 flex items-center gap-2">
        <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=browse&path=<?= urlencode(dirname($path)) ?>" class="btn btn-sm"><?= h(t('common.back')) ?></a>
        <span style="font-family:var(--font-mono);font-size:13px;color:var(--text2)"><?= h($path) ?></span>
        <a href="/api/download_file.php?repo_id=<?= $repoId ?>&snapshot=<?= urlencode($snapshot) ?>&path=<?= urlencode($path) ?>" class="btn btn-sm btn-success" style="margin-left:auto"><?= h(t('explore.download')) ?></a>
    </div>
    <div class="card">
        <div class="card-header"><?= h($filename) ?></div>
        <div class="card-body">
            <?php if (!$previewAllowed): ?>
            <div class="alert alert-warning" style="margin-bottom:12px">
                <?= h(t('explore.preview_masked')) ?>
            </div>
            <div style="font-size:13px;color:var(--text2)"><?= h(t('explore.preview_masked_desc')) ?></div>
            <?php elseif (!$preview['success']): ?>
            <div class="alert alert-warning" style="margin-bottom:12px">
                <?= !empty($preview['timed_out']) ? h(t('explore.preview_unavailable_timeout')) : h((string) ($preview['error'] ?? t('explore.preview_unavailable'))) ?>
            </div>
            <div style="font-size:13px;color:var(--text2)"><?= h(t('explore.preview_unavailable_desc')) ?></div>
            <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:12px"><?= h(t('explore.preview_readonly')) ?></div>
            <pre class="code-viewer" style="max-height:65vh;white-space:pre-wrap;overflow:auto;margin:0"><?= h($previewContent) ?></pre>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($action === 'search'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div style="display:flex;gap:8px">
                <input type="text" id="search-query" class="form-control"
                       placeholder="<?= h(t('explore.search_placeholder')) ?>"
                       oninput="scheduleSearchFiles()"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();searchFiles()}">
                <button class="btn btn-primary" onclick="searchFiles()" style="white-space:nowrap"><?= h(t('common.search')) ?></button>
            </div>
            <div style="font-size:12px;color:var(--text2);margin-top:6px">
                <?= h(t('explore.search_help', ['max' => (string) exploreSearchMaxResults()])) ?>
            </div>
        </div>
    </div>
    <div id="search-results"></div>

    <?php elseif ($action === 'partial'):
        $allItems = SnapshotSearchIndex::listDirectory($repoId, $snapshot, $path);
        if ($allItems === null) {
            $allItems = $restic->cachedLs($snapshot, $path, true);
        }
        if ($allItems === null && !empty($snapshotData['paths'])) {
            $allItems = buildSyntheticExploreItems($snapshotData['paths'], $path);
        }
        if ($allItems === null || $allItems === []) {
            JobQueue::enqueueSnapshotFullIndex($repoId, $snapshot, 'explore_partial_missing_index', 180);
            $allItems = $restic->lsWithTimeout($snapshot, $path, 6);
        }
    ?>
    <div class="card mb-4">
        <div class="card-body" style="display:flex;align-items:center;gap:12px">
            <span style="font-size:13px;color:var(--text2)"><?= h(t('common.path')) ?> :</span>
            <div class="breadcrumb" style="flex:1">
                <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=partial&path=/"><?= h(t('common.root')) ?></a>
                <?php
                $parts = array_values(array_filter(explode('/', $path)));
                $cur = '';
                foreach ($parts as $part):
                    $cur .= '/' . $part;
                ?>
                <span>/</span>
                <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=partial&path=<?= urlencode($cur) ?>"><?= h($part) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (Auth::canRestore()): ?>
            <button class="btn btn-success btn-sm" onclick="restoreSelected()"><?= h(t('explore.partial_restore.title')) ?></button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                <input type="checkbox" id="select-all" onchange="toggleAll(this)" style="accent-color:var(--accent)">
                <?= h(t('common.select_all')) ?>
            </label>
            <span id="selected-count" style="font-size:12px;color:var(--text2)"><?= h(t('explore.partial_restore.files_selected_count', ['count' => '0'])) ?></span>
        </div>
        <div class="file-explorer" id="partial-list" data-virtualize="1" data-virtual-threshold="120" data-virtual-height="70vh">
            <?php if ($allItems === null): ?>
            <div class="empty-state" style="padding:24px"><?= h(t('explore.pending.tree')) ?></div>
            <?php elseif (is_array($allItems) && !empty($allItems['timed_out'])): ?>
            <div class="empty-state" style="padding:24px"><?= h(t('explore.pending.deferred_retry')) ?></div>
            <?php elseif (isset($allItems['error'])): ?>
            <div class="alert alert-danger"><?= h($allItems['error']) ?></div>
            <?php else:
                $pdirs = [];
                $pfiles = [];
                foreach ($allItems as $item) {
                    if (!isset($item['type'])) {
                        continue;
                    }
                    if ($item['type'] === 'dir') {
                        $pdirs[] = $item;
                    } else {
                        $pfiles[] = $item;
                    }
                }
                usort($pdirs, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
                usort($pfiles, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
                $pagination = paginateExploreItems(array_merge($pdirs, $pfiles), $page);
                $pagedItems = $pagination['items'];
                $pdirs = array_values(array_filter($pagedItems, static fn($item) => ($item['type'] ?? '') === 'dir'));
                $pfiles = array_values(array_filter($pagedItems, static fn($item) => ($item['type'] ?? '') !== 'dir'));
            ?>
            <?php renderExplorePagination($repoId, $snapshot, 'partial', $path, $pagination); ?>
            <?php if ($path !== '/'): ?>
            <?php $parent = dirname($path); if ($parent === '.') { $parent = '/'; } ?>
            <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=partial&path=<?= urlencode($parent) ?>&page=1"
               class="file-item dir" style="border-bottom:1px solid var(--border)">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h6l2 2h6v9H1z"/></svg>
                ..
            </a>
            <?php endif; ?>

            <?php foreach ($pdirs as $item):
                $itemPath = (string) ($item['path'] ?? (rtrim($path, '/') . '/' . ($item['name'] ?? '')));
            ?>
            <div class="file-item" style="border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <input type="checkbox" class="file-checkbox" value="<?= h($itemPath) ?>" onchange="updateCount()" style="accent-color:var(--accent)">
                <a href="?repo=<?= $repoId ?>&snapshot=<?= h($snapshot) ?>&action=partial&path=<?= urlencode($itemPath) ?>&page=1"
                   style="display:flex;align-items:center;gap:6px;flex:1;text-decoration:none;color:var(--accent)">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M1 3h6l2 2h6v9H1z"/></svg>
                    <?= h($item['name']) ?>/
                </a>
            </div>
            <?php endforeach; ?>

            <?php foreach ($pfiles as $item):
                $itemPath = (string) ($item['path'] ?? (rtrim($path, '/') . '/' . ($item['name'] ?? '')));
                $size = $item['size'] ?? 0;
            ?>
            <div class="file-item" style="border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <input type="checkbox" class="file-checkbox" value="<?= h($itemPath) ?>" onchange="updateCount()" style="accent-color:var(--accent)">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" style="color:var(--text2)">
                    <rect x="3" y="1" width="10" height="14" rx="1" fill="none" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <span style="flex:1"><?= h($item['name']) ?></span>
                <span class="file-size"><?= formatBytes($size) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($action === 'stats'):
        $stats = getLatestExploreRepoStats($repoId);
    ?>
    <?php if ($stats === null): ?>
    <div class="empty-state" style="padding:32px"><?= h(t('explore.stats.empty')) ?></div>
    <?php else: ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= formatBytes($stats['total_size'] ?? 0) ?></div>
            <div class="stat-label"><?= h(t('explore.stats.total_size')) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_file_count'] ?? 0) ?></div>
            <div class="stat-label"><?= h(t('explore.stats.files')) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int) ($stats['snapshot_count'] ?? 0) ?></div>
            <div class="stat-label"><?= h(t('explore.stats.snapshots_historized')) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
    <?php
}

function getLatestExploreRepoStats(int $repoId): ?array {
    $stmt = Database::getInstance()->prepare("
        SELECT snapshot_count, total_size, total_file_count, recorded_at
        FROM repo_stats_history
        WHERE repo_id = ?
        ORDER BY recorded_at DESC
        LIMIT 1
    ");
    $stmt->execute([$repoId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

