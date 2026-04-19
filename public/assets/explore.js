(function() {
    const config = window.RESTIC_EXPLORE_CONFIG || {};
    const strings = window.FULGURITE_STRINGS || {};
    const s = (key, fallback) => strings[key] || fallback;
    const repoId = config.repoId || 0;
    const repoName = config.repoName || 'repo';
    const snapshotId = config.snapshot || '';
    const currentPath = config.path || '/';
    const currentAction = config.action || 'browse';
    const currentPage = config.page || 1;
    const restoreSettings = config.restore || {};
    const hosts = Array.isArray(config.hosts) ? config.hosts : [];
    const hostMap = new Map(hosts.map((host) => [String(host.id), host]));

    let restoreMode = 'local';
    let diffData = null;
    let pendingRemoveTags = [];
    let searchDebounceTimer = null;
    let searchRequestId = 0;
    let pendingRefreshTimer = null;
    const overwriteCheckTimers = { restore: null, partial: null };
    const overwriteCheckRequestIds = { restore: 0, partial: 0 };
    let virtualExploreLists = [];
    window.__exploreMeta = { snapshots: [], current_snapshot: null };

    function getSnapshotMeta() {
        return window.__exploreMeta.current_snapshot || {};
    }

    function getOriginalRestoreMeta() {
        return window.__exploreMeta.original_restore || {};
    }

    function getOriginalConfirmationWord() {
        return String(getOriginalRestoreMeta().confirmation_word || 'confirmer').toLowerCase();
    }

    function getOriginalConfirmation(kind) {
        const input = document.getElementById(`${kind}-original-confirm-input`);
        return input ? input.value.trim().toLowerCase() : '';
    }

    function getSnapshotOriginHost() {
        const origin = getOriginalRestoreMeta().origin_host || null;
        return origin && origin.id ? origin : null;
    }

    function formatHostLabel(host) {
        if (!host) {
            return '';
        }
        const name = host.name || host.hostname || `hote #${host.id}`;
        const endpoint = host.user && host.hostname ? `${host.user}@${host.hostname}` : (host.hostname || '');
        return endpoint ? `${name} (${endpoint})` : name;
    }

    function getSelectedMode(kind) {
        const selected = document.querySelector(`input[name="${kind}_mode"]:checked`);
        return selected && selected.value === 'remote' ? 'remote' : 'local';
    }

    function getSelectedStrategy(kind) {
        const selected = document.querySelector(`input[name="${kind}_destination_mode"]:checked`);
        return selected && selected.value === 'original' ? 'original' : 'managed';
    }

    function getAppendContext(kind) {
        const checkbox = document.getElementById(`${kind}-append-context`);
        return checkbox ? checkbox.checked : !!restoreSettings.appendContextDefault;
    }

    function normalizeRestorePath(path) {
        const value = String(path || '').trim().replace(/\\/g, '/');
        if (!value) {
            return '';
        }

        if (/^[A-Za-z]:\//.test(value)) {
            return value.replace(/\/+/g, '/').replace(/\/$/, '');
        }

        const normalized = value.startsWith('/') ? value : `/${value.replace(/^\/+/, '')}`;
        return normalized === '/' ? normalized : normalized.replace(/\/+/g, '/').replace(/\/$/, '');
    }

    function joinRestorePath(base, suffix) {
        const normalizedBase = normalizeRestorePath(base) || '/';
        const normalizedSuffix = String(suffix || '').replace(/\\/g, '/').replace(/^\/+|\/+$/g, '');
        if (!normalizedSuffix) {
            return normalizedBase;
        }
        return normalizedBase === '/' ? `/${normalizedSuffix}` : `${normalizedBase}/${normalizedSuffix}`;
    }

    function slugifyRestoreLabel(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[@.]/g, '-')
            .replace(/[^a-z0-9_-]+/g, '-')
            .replace(/^-+|-+$/g, '') || '';
    }

    function resolveHost(kind) {
        const select = document.getElementById(kind === 'partial' ? 'partial-host-id' : 'restore-host');
        if (!select) {
            return null;
        }
        return hostMap.get(String(select.value || '')) || null;
    }

    function resolveContextSubdir() {
        return slugifyRestoreLabel(repoName) || 'restore-target';
    }

    function resolveManagedBaseRoot(mode, host) {
        if (mode === 'remote' && host && host.restoreManagedRoot) {
            return host.restoreManagedRoot;
        }

        return mode === 'remote'
            ? (restoreSettings.managedRemoteRoot || '/var/tmp/fulgurite-restores')
            : (restoreSettings.managedLocalRoot || '/tmp/restores');
    }

    function samplePathsForPreview(kind) {
        if (kind === 'partial') {
            const files = getSelectedFiles().slice(0, 3);
            return files.length ? files : [currentPath || '/'];
        }

        const include = (document.getElementById('restore-include')?.value || '').trim();
        if (include) {
            return [include];
        }

        const snapshotMeta = getSnapshotMeta();
        if (Array.isArray(snapshotMeta.paths) && snapshotMeta.paths.length) {
            return snapshotMeta.paths.slice(0, 3);
        }

        return ['/'];
    }

    function buildRestorePreview(kind) {
        const mode = getSelectedMode(kind);
        const strategy = getSelectedStrategy(kind);
        const host = resolveHost(kind);
        const appendContext = getAppendContext(kind);
        const originHost = getSnapshotOriginHost();
        const confirmationWord = getOriginalConfirmationWord();
        const baseRoot = resolveManagedBaseRoot(mode, host);
        const contextSubdir = appendContext ? resolveContextSubdir() : '';
        const effectiveRoot = strategy === 'original'
            ? '/'
            : joinRestorePath(baseRoot, contextSubdir);
        const previewPaths = samplePathsForPreview(kind).map((path) => {
            const normalizedPath = normalizeRestorePath(path).replace(/^\/+/, '');
            return normalizedPath ? joinRestorePath(effectiveRoot, normalizedPath) : effectiveRoot;
        });
        let hostWarning = '';
        let hostWarningLevel = 'info';

        if (mode === 'remote') {
            if (originHost && host && Number(host.id) !== Number(originHost.id)) {
                hostWarningLevel = 'warning';
                hostWarning = `Attention: l'hote cible ${formatHostLabel(host)} est different de l'hote d'origine du depot ${formatHostLabel(originHost)}. Le mode gere reste possible, mais verifiez que c'est volontaire.`;
            } else if (originHost && host) {
                hostWarning = `Hote cible conforme a l'origine connue du depot: ${formatHostLabel(originHost)}.`;
            } else {
                hostWarningLevel = 'warning';
                hostWarning = "L'hote d'origine du depot n'est pas identifie avec certitude. Le mode gere reste possible; la destination originale restera bloquee.";
            }
        }

        if (strategy === 'original') {
            if (mode === 'local') {
                return {
                    mode,
                    strategy,
                    effectiveRoot,
                    previewPaths,
                    confirmationRequired: true,
                    confirmationWord,
                    blocked: true,
                    blockedReason: s('explore_local_original_forbidden', 'Restoring to exact original location is forbidden locally. Use the managed restores folder.'),
                    hostWarning,
                    hostWarningLevel: 'danger',
                    warning: s('explore_local_original_blocked_warning', 'Blocked mode: local restore to original paths could write files outside allowed scope.'),
                };
            }

            if (mode === 'remote') {
                if (!originHost) {
                    return {
                        mode,
                        strategy,
                        effectiveRoot,
                        previewPaths,
                        confirmationRequired: true,
                        confirmationWord,
                        blocked: true,
                        blockedReason: s('explore_origin_host_unknown', 'Cannot identify snapshot origin host. Use the managed restores folder.'),
                        hostWarning,
                        hostWarningLevel: 'danger',
                        warning: s('explore_remote_original_blocked_warning', 'Original remote destination is blocked until the origin host is known.'),
                    };
                }
                if (!host || Number(host.id) !== Number(originHost.id)) {
                    return {
                        mode,
                        strategy,
                        effectiveRoot,
                        previewPaths,
                        confirmationRequired: true,
                        confirmationWord,
                        blocked: true,
                        blockedReason: s('explore_origin_host_required', 'Original remote destination must target origin host: :host.')
                            .replace(':host', formatHostLabel(originHost)),
                        hostWarning,
                        hostWarningLevel: 'danger',
                        warning: s('explore_select_origin_host', 'Select origin host :host to restore to exact location.')
                            .replace(':host', formatHostLabel(originHost)),
                    };
                }
            }

            if (getOriginalConfirmation(kind) !== confirmationWord) {
                return {
                    mode,
                    strategy,
                    effectiveRoot,
                    previewPaths,
                    confirmationRequired: true,
                    confirmationWord,
                    blocked: true,
                    blockedReason: s('explore_retype_confirmation', 'Retype ":word" to confirm potential overwrite.')
                        .replace(':word', confirmationWord),
                    hostWarning,
                    hostWarningLevel,
                    warning: s('explore_admin_confirmation_required', 'Admin confirmation required before restoring to original destination.'),
                };
            }
        }

        return {
            mode,
            strategy,
            effectiveRoot,
            previewPaths,
            blocked: false,
            blockedReason: '',
            confirmationRequired: strategy === 'original',
            confirmationWord,
            hostWarning,
            hostWarningLevel,
            warning: strategy === 'original'
                ? (mode === 'remote'
                    ? s('explore_remote_admin_mode', 'Admin mode: exact restore on origin host :host. Existing files may be overwritten.')
                        .replace(':host', formatHostLabel(originHost))
                    : s('explore_local_exact_blocked', 'Blocked mode: exact local restore forbidden.'))
                : (mode === 'remote'
                    ? s('explore_remote_recommended', 'Recommended mode: restore stays within managed remote root while preserving tree.')
                    : s('explore_local_recommended', 'Recommended mode: restore stays within managed local root while preserving tree.')),
        };
    }

    function renderRestorePreview(kind) {
        const preview = buildRestorePreview(kind);
        const rootEl = document.getElementById(`${kind}-preview-root`);
        const modeEl = document.getElementById(`${kind}-preview-mode`);
        const warningEl = document.getElementById(`${kind}-preview-warning`);
        const exampleEl = document.getElementById(`${kind}-preview-example`);
        const confirmEl = document.getElementById(`${kind}-original-confirm`);
        const confirmHelpEl = document.getElementById(`${kind}-original-confirm-help`);
        const hostSafetyEl = document.getElementById(`${kind}-host-safety`);
        const btn = document.getElementById(kind === 'partial' ? 'btn-partial-restore' : 'btn-restore');
        if (rootEl) {
            rootEl.textContent = preview.effectiveRoot;
        }
        if (modeEl) {
            modeEl.textContent = `${preview.mode} / ${preview.strategy}`;
        }
        if (warningEl) {
            warningEl.textContent = preview.warning;
        }
        if (exampleEl) {
            exampleEl.innerHTML = preview.previewPaths
                .map((path) => `<div class="restore-preview-line">${escHtml(path)}</div>`)
                .join('');
        }
        if (confirmEl) {
            confirmEl.style.display = preview.confirmationRequired ? 'flex' : 'none';
        }
        if (confirmHelpEl) {
            confirmHelpEl.textContent = preview.confirmationRequired
                ? (preview.blockedReason || s('explore_retype_confirmation', 'Retype ":word" to confirm potential overwrite.').replace(':word', preview.confirmationWord))
                : '';
        }
        if (hostSafetyEl) {
            hostSafetyEl.style.display = preview.mode === 'remote' ? 'flex' : 'none';
            hostSafetyEl.className = `restore-host-safety restore-host-safety-${preview.hostWarningLevel || 'info'}`;
            hostSafetyEl.textContent = preview.hostWarning || '';
        }
        if (btn && btn.dataset.running !== '1') {
            btn.disabled = !!preview.blocked;
            btn.title = preview.blockedReason || '';
        }
        scheduleOverwriteCheck(kind, preview);
    }

    function buildRestoreApiPayload(kind) {
        const mode = getSelectedMode(kind);
        const payload = {
            repo_id: repoId,
            snapshot: snapshotId,
            mode,
            destination_mode: getSelectedStrategy(kind),
            append_context_subdir: getAppendContext(kind),
        };

        if (kind === 'partial') {
            payload.files = getSelectedFiles().slice(0, 100);
        } else {
            payload.include = document.getElementById('restore-include')?.value || '';
            if (!payload.include) {
                payload.files = samplePathsForPreview('restore').slice(0, 20);
            }
        }

        if (mode === 'remote') {
            const select = document.getElementById(kind === 'partial' ? 'partial-host-id' : 'restore-host');
            if (select) {
                payload.host_id = parseInt(select.value, 10);
            }
        }

        return payload;
    }

    function scheduleOverwriteCheck(kind, preview) {
        const warningEl = document.getElementById(`${kind}-overwrite-warning`);
        if (!warningEl) {
            return;
        }

        window.clearTimeout(overwriteCheckTimers[kind]);
        if (!snapshotId || preview.blocked) {
            renderOverwriteWarning(kind, null);
            return;
        }

        if (kind === 'partial' && getSelectedFiles().length === 0) {
            renderOverwriteWarning(kind, null);
            return;
        }

        const requestId = ++overwriteCheckRequestIds[kind];
        overwriteCheckTimers[kind] = window.setTimeout(async () => {
            try {
                const result = await window.apiPost('/api/restore_destination_preview.php', buildRestoreApiPayload(kind));
                if (requestId !== overwriteCheckRequestIds[kind]) {
                    return;
                }
                renderOverwriteWarning(kind, result);
            } catch (error) {
                if (requestId !== overwriteCheckRequestIds[kind]) {
                    return;
                }
                renderOverwriteWarning(kind, { success: false, error: error.message || s('explore_error', 'Error') });
            }
        }, 350);
    }

    function renderOverwriteWarning(kind, result) {
        const card = document.getElementById(`${kind}-overwrite-warning`);
        const summary = document.getElementById(`${kind}-overwrite-summary`);
        const list = document.getElementById(`${kind}-overwrite-list`);
        if (!card || !summary || !list) {
            return;
        }

        if (!result) {
            card.style.display = 'none';
            summary.textContent = '';
            list.innerHTML = '';
            return;
        }

        if (!result.success) {
            card.style.display = 'flex';
            summary.textContent = result.error || s('explore_destination_exists_error', 'Unable to verify if files already exist at destination.');
            list.innerHTML = '';
            return;
        }

        const existing = Array.isArray(result.existing) ? result.existing : [];
        if (!existing.length) {
            card.style.display = 'none';
            summary.textContent = '';
            list.innerHTML = '';
            return;
        }

        const shown = existing.slice(0, 50);
        card.style.display = 'flex';
        summary.textContent = s('explore_destination_exists_summary', ':count path(s) already exist and will be overwritten or merged by restore.')
            .replace(':count', String(existing.length));
        list.innerHTML = shown
            .map((item) => `<div class="restore-overwrite-item">${escHtml(item.path || '')} <span style="color:var(--text2)">(${escHtml(item.type || 'file')})</span></div>`)
            .join('');
        if (existing.length > shown.length) {
            list.innerHTML += `<div class="restore-overwrite-item">${s('explore_other_paths', '... :count other path(s)').replace(':count', String(existing.length - shown.length))}</div>`;
        }
    }

    function syncRestoreFieldsVisibility() {
        restoreMode = getSelectedMode('restore');
        const localFields = document.getElementById('fields-local');
        const remoteFields = document.getElementById('fields-remote');
        syncOriginalOptionVisibility('restore', restoreMode);
        updateOriginalOptionState('restore');
        if (localFields) {
            localFields.style.display = restoreMode === 'local' ? 'block' : 'none';
        }
        if (remoteFields) {
            remoteFields.style.display = restoreMode === 'remote' ? 'block' : 'none';
        }
    }

    function syncPartialFieldsVisibility() {
        const mode = getSelectedMode('partial');
        const localFields = document.getElementById('partial-local-fields');
        const remoteFields = document.getElementById('partial-remote-fields');
        syncOriginalOptionVisibility('partial', mode);
        updateOriginalOptionState('partial');
        if (localFields) {
            localFields.style.display = mode === 'local' ? 'block' : 'none';
        }
        if (remoteFields) {
            remoteFields.style.display = mode === 'remote' ? 'block' : 'none';
        }
    }

    function syncOriginalOptionVisibility(kind, mode) {
        const option = document.querySelector(`[data-original-option="${kind}"]`);
        const managed = document.querySelector(`input[name="${kind}_destination_mode"][value="managed"]`);
        const original = document.querySelector(`input[name="${kind}_destination_mode"][value="original"]`);
        if (!option || !original) {
            return;
        }

        const allowOriginal = mode === 'remote';
        option.style.display = allowOriginal ? 'flex' : 'none';
        original.disabled = !allowOriginal;
        if (!allowOriginal && original.checked && managed) {
            managed.checked = true;
        }
    }

    function updateOriginalOptionState(kind) {
        const option = document.querySelector(`[data-original-option="${kind}"]`);
        const original = document.querySelector(`input[name="${kind}_destination_mode"][value="original"]`);
        const managed = document.querySelector(`input[name="${kind}_destination_mode"][value="managed"]`);
        const select = document.getElementById(kind === 'partial' ? 'partial-host-id' : 'restore-host');
        const hostSafetyEl = document.getElementById(`${kind}-host-safety`);
        if (!option || !original) return;
        // If option hidden by global config, keep it disabled
        if (option.style.display === 'none') {
            original.disabled = true;
            if (original.checked && managed) managed.checked = true;
            if (hostSafetyEl) hostSafetyEl.style.display = 'none';
            return;
        }

        if (!select) return;
        const host = hostMap.get(String(select.value || '')) || null;
        const allowed = host && host.restoreOriginalEnabled;
        if (!allowed) {
            original.disabled = true;
            if (original.checked && managed) managed.checked = true;
            if (hostSafetyEl) {
                hostSafetyEl.style.display = 'flex';
                hostSafetyEl.className = 'restore-host-safety restore-host-safety-warning';
                hostSafetyEl.textContent = s('explore_original_disabled', 'Original restore is disabled for this host (missing opt-in).');
            }
        } else {
            original.disabled = false;
            if (hostSafetyEl) {
                hostSafetyEl.style.display = 'none';
            }
        }
    }

    function bindRestoreControls() {
        document.querySelectorAll('input[name="restore_mode"], input[name="restore_destination_mode"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                syncRestoreFieldsVisibility();
                renderRestorePreview('restore');
            });
        });
        document.querySelectorAll('input[name="partial_mode"], input[name="partial_destination_mode"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                syncPartialFieldsVisibility();
                renderRestorePreview('partial');
            });
        });
        document.getElementById('restore-append-context')?.addEventListener('change', () => renderRestorePreview('restore'));
        document.getElementById('partial-append-context')?.addEventListener('change', () => renderRestorePreview('partial'));
        document.getElementById('restore-host')?.addEventListener('change', () => { updateOriginalOptionState('restore'); renderRestorePreview('restore'); });
        document.getElementById('partial-host-id')?.addEventListener('change', () => { updateOriginalOptionState('partial'); renderRestorePreview('partial'); });
        document.getElementById('restore-include')?.addEventListener('input', () => renderRestorePreview('restore'));
        document.getElementById('restore-original-confirm-input')?.addEventListener('input', () => renderRestorePreview('restore'));
        document.getElementById('partial-original-confirm-input')?.addEventListener('input', () => renderRestorePreview('partial'));
        syncRestoreFieldsVisibility();
        syncPartialFieldsVisibility();
        updateOriginalOptionState('restore');
        updateOriginalOptionState('partial');
        renderRestorePreview('restore');
        renderRestorePreview('partial');
    }

    window.addEventListener('load', () => {
        bindRestoreControls();
        loadExploreView();
    });

    async function loadExploreView() {
        if (pendingRefreshTimer) {
            window.clearTimeout(pendingRefreshTimer);
            pendingRefreshTimer = null;
        }

        const query = new URLSearchParams({
            repo: String(repoId),
            path: currentPath,
            action: currentAction,
            page: String(currentPage),
        });
        if (snapshotId) {
            query.set('snapshot', snapshotId);
        }

        try {
            const requestUrl = '/api/explore_view.php?' + query.toString();
            const payload = window.fetchJsonWithCache
                ? await window.fetchJsonWithCache(requestUrl, {
                    cacheKey: 'explore-view:' + query.toString(),
                    maxStaleMs: 45000,
                    timeoutMs: 12000,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'default',
                    shouldCache: (data) => !!data && typeof data === 'object' && typeof data.snapshots_html === 'string' && typeof data.content_html === 'string',
                    onStaleData: (cachedPayload) => {
                        try {
                            renderExplorePayload(cachedPayload);
                        } catch (error) {}
                    },
                })
                : window.fetchJsonSafe
                ? await window.fetchJsonSafe(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'default',
                    timeoutMs: 12000,
                })
                : await (async () => {
                    const response = await fetch(requestUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'default',
                    });
                    const rawBody = await response.text();
                    const parsed = rawBody ? JSON.parse(rawBody) : null;
                    if (!response.ok) {
                        throw new Error((parsed && parsed.error) || s('loading', 'Unable to load'));
                    }
                    return parsed;
                })();

            if (!payload || payload.error) {
                throw new Error((payload && payload.error) || s('loading', 'Unable to load'));
            }

            renderExplorePayload(payload);

            if (payload.status === 'pending') {
                pendingRefreshTimer = window.setTimeout(() => {
                    loadExploreView();
                }, payload.poll_after_ms || 5000);
            }
        } catch (error) {
            showExploreLoadError(error.message || s('loading', 'Unable to load'));
        }
    }

    function renderExplorePayload(payload) {
        document.getElementById('explore-snapshots-panel').innerHTML = payload.snapshots_html;
        document.getElementById('explore-main-panel').innerHTML = payload.content_html;
        hydrateExploreMeta(payload.meta || {});
        initVirtualExploreLists();
        initDiffTabs();
        renderRestorePreview('restore');
        renderRestorePreview('partial');
    }

    function showExploreLoadError(message) {
        const html = `<div class="alert alert-danger">${escHtml(s('explore_load_error', 'Unable to load explorer: :message').replace(':message', message))}</div>`;
        document.getElementById('explore-snapshots-panel').innerHTML = html;
        document.getElementById('explore-main-panel').innerHTML = html;
    }

    function initVirtualExploreLists() {
        virtualExploreLists.forEach((cleanup) => {
            if (typeof cleanup === 'function') {
                cleanup();
            }
        });
        virtualExploreLists = [];

        document.querySelectorAll('.file-explorer[data-virtualize="1"]').forEach((container) => {
            const cleanup = virtualizeExploreList(container);
            if (typeof cleanup === 'function') {
                virtualExploreLists.push(cleanup);
            }
        });
    }

    function virtualizeExploreList(container) {
        if (!container || container.dataset.virtualized === '1') {
            return null;
        }

        const children = Array.from(container.children);
        const rowIndexes = children
            .map((child, index) => ({ child, index }))
            .filter((entry) => entry.child.classList && entry.child.classList.contains('file-item'));
        const rows = rowIndexes.map((entry) => entry.child);
        const threshold = parseInt(container.dataset.virtualThreshold || '120', 10);
        if (rows.length < threshold) {
            return null;
        }

        const firstRowIndex = rowIndexes[0].index;
        const lastRowIndex = rowIndexes[rowIndexes.length - 1].index;
        const prefixNodes = children.slice(0, firstRowIndex);
        const suffixNodes = children.slice(lastRowIndex + 1);

        const sample = rows.slice(0, Math.min(12, rows.length));
        const sampleHeight = sample.reduce((total, row) => total + Math.max(28, row.getBoundingClientRect().height || 0), 0);
        const rowHeight = Math.max(36, Math.round(sampleHeight / Math.max(1, sample.length)));
        const overscan = 10;

        container.dataset.virtualized = '1';
        container.style.maxHeight = container.dataset.virtualHeight || '70vh';
        container.style.overflowY = 'auto';
        container.style.position = 'relative';
        container.style.contain = 'strict';

        const topSpacer = document.createElement('div');
        const visibleHost = document.createElement('div');
        const bottomSpacer = document.createElement('div');
        topSpacer.setAttribute('aria-hidden', 'true');
        bottomSpacer.setAttribute('aria-hidden', 'true');

        container.innerHTML = '';
        prefixNodes.forEach((node) => container.appendChild(node));
        container.appendChild(topSpacer);
        container.appendChild(visibleHost);
        container.appendChild(bottomSpacer);
        suffixNodes.forEach((node) => container.appendChild(node));

        let frameId = 0;
        let start = -1;
        let end = -1;

        const render = () => {
            frameId = 0;
            const viewportHeight = Math.max(container.clientHeight || 0, rowHeight * 8);
            const visibleCount = Math.ceil(viewportHeight / rowHeight) + (overscan * 2);
            const nextStart = Math.max(0, Math.floor(container.scrollTop / rowHeight) - overscan);
            const nextEnd = Math.min(rows.length, nextStart + visibleCount);

            topSpacer.style.height = `${nextStart * rowHeight}px`;
            bottomSpacer.style.height = `${Math.max(0, rows.length - nextEnd) * rowHeight}px`;

            if (nextStart === start && nextEnd === end) {
                return;
            }

            start = nextStart;
            end = nextEnd;
            visibleHost.replaceChildren(...rows.slice(nextStart, nextEnd));
        };

        const scheduleRender = () => {
            if (frameId) {
                return;
            }
            frameId = window.requestAnimationFrame(render);
        };

        container.addEventListener('scroll', scheduleRender, { passive: true });
        window.addEventListener('resize', scheduleRender);
        render();

        return () => {
            container.removeEventListener('scroll', scheduleRender);
            window.removeEventListener('resize', scheduleRender);
            if (frameId) {
                window.cancelAnimationFrame(frameId);
            }
        };
    }

    function hydrateExploreMeta(meta) {
        window.__exploreMeta = meta || { snapshots: [], current_snapshot: null };
        pendingRemoveTags = [];

        const current = window.__exploreMeta.current_snapshot;
        const currentId = current && current.short_id ? current.short_id : snapshotId;
        const alternateId = getAlternateSnapshotId(window.__exploreMeta.snapshots || [], currentId);

        populateSnapshotSelect('diff-snap-a', currentId, false);
        populateSnapshotSelect('diff-snap-b-file', alternateId, false);
        populateSnapshotSelect('diff-snapshot-b', alternateId, true);
        renderCurrentTags(current && current.tags ? current.tags : []);

        const restoreTitle = document.getElementById('restore-modal-snapshot');
        if (restoreTitle) {
            restoreTitle.textContent = currentId;
        }

        const diffTitle = document.getElementById('diff-current-snapshot');
        if (diffTitle) {
            diffTitle.textContent = currentId;
        }

        const tagsTitle = document.getElementById('tags-modal-snapshot');
        if (tagsTitle) {
            tagsTitle.textContent = currentId;
        }

        renderRestorePreview('restore');
        renderRestorePreview('partial');
    }

    function initDiffTabs() {
        const group = document.querySelector('.diff-tab-group');
        if (group) {
            group.setAttribute('role', 'tablist');
            group.setAttribute('aria-label', 'Filtres de comparaison');
        }

        ['all', 'added', 'removed', 'changed'].forEach((name) => {
            const element = document.getElementById('diff-tab-' + name);
            if (!element) {
                return;
            }

            element.classList.add('diff-tab');
            element.setAttribute('role', 'tab');
            element.setAttribute('aria-selected', name === 'all' ? 'true' : 'false');

            if (element.tagName !== 'BUTTON') {
                element.tabIndex = 0;
            }

            if (element.dataset.keyboardReady === '1') {
                return;
            }

            element.dataset.keyboardReady = '1';
            element.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    window.showDiffTab(name);
                }
            });
        });
    }

    function getAlternateSnapshotId(snapshots, excludedId) {
        const alternate = snapshots.find((snap) => snap.short_id !== excludedId);
        return alternate ? alternate.short_id : '';
    }

    function populateSnapshotSelect(elementId, selectedId, excludeCurrent) {
        const select = document.getElementById(elementId);
        if (!select) {
            return;
        }

        const currentId = window.__exploreMeta.current_snapshot && window.__exploreMeta.current_snapshot.short_id
            ? window.__exploreMeta.current_snapshot.short_id
            : snapshotId;
        const snapshots = (window.__exploreMeta.snapshots || []).filter((snap) => !excludeCurrent || snap.short_id !== currentId);

        if (!snapshots.length) {
            select.innerHTML = `<option value="">${escHtml(s('explore_no_snapshot_available', 'No snapshot available'))}</option>`;
            return;
        }

        select.innerHTML = snapshots.map((snap) => {
            const selected = snap.short_id === selectedId ? ' selected' : '';
            const label = `${escHtml(snap.short_id)} — ${escHtml(snap.formatted_time || snap.time || '')}`;
            return `<option value="${escHtml(snap.short_id)}"${selected}>${label}</option>`;
        }).join('');
    }

    function renderCurrentTags(tags) {
        const container = document.getElementById('current-tags');
        if (!container) {
            return;
        }

        if (!tags.length) {
            container.innerHTML = `<span style="font-size:12px;color:var(--text2)">${escHtml(s('explore_no_tag', 'No tag'))}</span>`;
            return;
        }

        container.innerHTML = '';
        tags.forEach((tag) => {
            const badge = document.createElement('span');
            badge.className = 'badge badge-blue';
            badge.style.cursor = 'pointer';
            badge.title = s('explore_click_to_remove', 'Click to remove');
            badge.textContent = tag + ' ×';
            badge.onclick = () => removeTagBadge(badge, tag);
            container.appendChild(badge);
        });
    }

    window.deleteSnapshot = async function(snapshotToDelete) {
        const confirmed = await window.confirmActionAsync(
            s('explore_delete_snapshot_confirm', 'Delete this snapshot permanently?\nThis action is irreversible.')
        );
        if (!confirmed) {
            return;
        }
        window.toast(s('explore_deleting', 'Deleting...'), 'info');
        const res = await window.apiPost('/api/delete_snapshot.php', {
            repo_id: repoId,
            snapshot_ids: [snapshotToDelete],
        });
        if (res.success) {
            window.toast(s('explore_snapshot_deleted', 'Snapshot deleted'), 'success');
            window.setTimeout(() => {
                window.location.href = '/explore.php?repo=' + repoId;
            }, 1500);
            return;
        }
        window.toast(res.output || res.error || s('explore_unknown_error', 'Unknown error'), 'error');
    };

    window.launchRestore = async function() {
        const btn = document.getElementById('btn-restore');
        const log = document.getElementById('restore-log');
        const out = document.getElementById('restore-output');
        const preview = buildRestorePreview('restore');
        if (preview.blocked) {
            window.toast(preview.blockedReason || s('explore_confirmation_required', 'Confirmation required'), 'error');
            renderRestorePreview('restore');
            return;
        }
        btn.dataset.running = '1';
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner"></span> ${escHtml(s('explore_in_progress', 'In progress...'))}`;
        out.style.display = 'block';
        log.textContent = s('explore_in_progress', 'In progress...');

        const payload = {
            repo_id: repoId,
            snapshot: snapshotId,
            include: document.getElementById('restore-include').value,
            mode: getSelectedMode('restore'),
            destination_mode: getSelectedStrategy('restore'),
            append_context_subdir: getAppendContext('restore'),
        };
        if (payload.destination_mode === 'original') {
            payload.original_confirmation = getOriginalConfirmation('restore');
        }

        if (payload.mode === 'remote') {
            const select = document.getElementById('restore-host');
            if (!select) {
                btn.dataset.running = '0';
                window.toast(s('explore_no_ssh_key', 'No SSH key'), 'error');
                btn.disabled = false;
                btn.textContent = '↩ ' + s('explore_restore_btn', 'Restore');
                return;
            }
            payload.host_id = parseInt(select.value, 10);
        }

        const res = await window.apiPost('/api/restore.php', payload);
        btn.dataset.running = '0';
        btn.disabled = false;
        btn.textContent = '↩ ' + s('explore_restore_btn', 'Restore');
        renderRestorePreview('restore');
        log.textContent = res.output || res.error || s('explore_no_output', '(no output)');
        log.style.color = res.success ? 'var(--green)' : 'var(--red)';
        window.toast(res.success ? s('explore_restore_done', 'Restore completed') : s('explore_error', 'Error'), res.success ? 'success' : 'error');
    };

    window.launchDiff = async function() {
        const select = document.getElementById('diff-snapshot-b');
        if (!select || !select.value) {
            window.toast(s('explore_select_snapshot', 'Select a snapshot'), 'error');
            return;
        }

        const out = document.getElementById('diff-output');
        out.style.display = 'block';
        document.getElementById('diff-content').textContent = s('explore_comparison_loading', 'Comparison in progress...');
        document.getElementById('diff-summary').innerHTML = '';

        const res = await window.apiPost('/api/diff_snapshots.php', {
            repo_id: repoId,
            snapshot_a: snapshotId,
            snapshot_b: select.value,
        });

        if (res.error) {
            document.getElementById('diff-content').textContent = res.error;
            return;
        }

        diffData = res;
        document.getElementById('diff-summary').innerHTML = `
            <span class="badge badge-green">+${res.summary.added} ${escHtml(s('explore_diff_added', 'added'))}</span>
            <span class="badge badge-red">-${res.summary.removed} ${escHtml(s('explore_diff_removed', 'removed'))}</span>
            <span class="badge badge-yellow">~${res.summary.changed} ${escHtml(s('explore_diff_changed', 'changed'))}</span>
        `;
        window.showDiffTab('all');
    };

    window.showDiffTab = function(tab) {
        if (!diffData) {
            return;
        }

        ['all', 'added', 'removed', 'changed'].forEach((name) => {
            const element = document.getElementById('diff-tab-' + name);
            if (element) {
                element.style.color = name === tab ? 'var(--accent)' : 'var(--text2)';
                element.setAttribute('aria-selected', name === tab ? 'true' : 'false');
            }
        });

        let lines = [];
        if (tab === 'all') {
            lines = diffData.raw.split('\n').slice(0, 500);
        } else if (tab === 'added') {
            lines = diffData.added.map((line) => '+ ' + line);
        } else if (tab === 'removed') {
            lines = diffData.removed.map((line) => '- ' + line);
        } else if (tab === 'changed') {
            lines = diffData.changed.map((line) => '~ ' + line);
        }
        document.getElementById('diff-content').textContent = lines.join('\n') || s('explore_no_difference', '(no differences)');
    };

    window.scheduleSearchFiles = function() {
        const input = document.getElementById('search-query');
        const output = document.getElementById('search-results');
        if (!input) {
            return;
        }

        window.clearTimeout(searchDebounceTimer);
        const query = input.value.trim();
        if (query.length < 2) {
            if (output) {
                output.innerHTML = '';
            }
            return;
        }

        searchDebounceTimer = window.setTimeout(() => {
            window.searchFiles();
        }, 300);
    };

    window.searchFiles = async function() {
        const input = document.getElementById('search-query');
        const out = document.getElementById('search-results');
        if (!input || !out) {
            return;
        }

        const query = input.value.trim();
        if (query.length < 2) {
            window.toast(s('explore_min_2_chars', 'Minimum 2 characters'), 'error');
            return;
        }

        const requestId = ++searchRequestId;
        out.innerHTML = `<div style="padding:16px;color:var(--text2)">${s('explore_search_loading', 'Search in progress... <span class="spinner"></span>')}</div>`;

        const res = await window.apiPost('/api/search_files.php', {
            repo_id: repoId,
            snapshot: snapshotId,
            query,
        });

        if (requestId !== searchRequestId) {
            return;
        }

        if (res.error) {
            out.innerHTML = `<div class="alert alert-danger">${escHtml(res.error)}</div>`;
            return;
        }

        if (res.status === 'pending') {
            out.innerHTML = `<div class="empty-state" style="padding:24px">${escHtml(res.message || s('explore_index_preparing', 'Index is being prepared.'))}</div>`;
            window.setTimeout(() => {
                if (input.value.trim() === query) {
                    window.searchFiles();
                }
            }, 5000);
            return;
        }

        if (!res.results || res.results.length === 0) {
            out.innerHTML = `<div class="empty-state" style="padding:32px">${escHtml(s('explore_no_file_found', 'No file found'))}</div>`;
            return;
        }

        let html = `<div class="card"><div class="card-header">${escHtml(s('explore_search_results', 'Results'))} <span class="badge badge-blue">${res.count}</span></div><div class="file-explorer">`;
        res.results.forEach((item) => {
            if (item.type === 'dir') {
                html += `<a href="?repo=${repoId}&snapshot=${encodeURIComponent(snapshotId)}&action=browse&path=${encodeURIComponent(item.path)}" class="file-item dir" style="border-bottom:1px solid var(--border)">${escHtml(item.path)}</a>`;
            } else {
                html += `<div class="file-item" style="border-bottom:1px solid var(--border)">
                    <a href="?repo=${repoId}&snapshot=${encodeURIComponent(snapshotId)}&action=view&path=${encodeURIComponent(item.path)}">${escHtml(item.path)}</a>
                    <a href="/api/download_file.php?repo_id=${repoId}&snapshot=${encodeURIComponent(snapshotId)}&path=${encodeURIComponent(item.path)}" class="btn btn-sm" style="margin-left:8px;padding:2px 6px;font-size:11px">↓</a>
                    <span class="file-size">${item.size ? formatBytesJS(item.size) : ''}</span>
                </div>`;
            }
        });
        html += '</div></div>';
        out.innerHTML = html;
    };

    window.checkRepo = async function() {
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = s('explore_checking', 'Checking...');
        const out = document.getElementById('repo-action-output');
        const log = document.getElementById('repo-action-log');
        out.style.display = 'block';
        log.textContent = s('explore_check_in_progress', 'Check in progress (can take several minutes)...');

        const res = await window.apiPost('/api/check_repo.php', { repo_id: repoId });
        btn.disabled = false;
        btn.textContent = s('explore_check_integrity', 'Check integrity');
        log.textContent = res.output || res.error;
        log.style.color = res.success ? 'var(--green)' : 'var(--red)';
        window.toast(res.success ? s('explore_integrity_ok', 'Integrity OK') : s('explore_issue_detected', 'Issue detected'), res.success ? 'success' : 'error');
    };

    window.initRepo = async function() {
        const confirmed = await window.confirmActionAsync(
            s('explore_init_repo_confirm', 'Initialize this restic repository?\nOnly if the repository does not exist yet.')
        );
        if (!confirmed) {
            return;
        }
        const btn = event.target;
        btn.disabled = true;
        btn.textContent = s('explore_initializing', 'Initializing...');
        const out = document.getElementById('repo-action-output');
        const log = document.getElementById('repo-action-log');
        out.style.display = 'block';
        log.textContent = s('explore_init_in_progress', 'Initialization in progress...');

        const res = await window.apiPost('/api/init_repo.php', { repo_id: repoId });
        btn.disabled = false;
        btn.textContent = s('explore_init_repo', 'Initialize repository');
        log.textContent = res.output || res.error;
        log.style.color = res.success ? 'var(--green)' : 'var(--red)';
        window.toast(res.success ? s('explore_repo_initialized', 'Repository initialized') : s('explore_error', 'Error'), res.success ? 'success' : 'error');
    };

    window.toggleAll = function(master) {
        document.querySelectorAll('.file-checkbox').forEach((checkbox) => {
            checkbox.checked = master.checked;
        });
        window.updateCount();
    };

    window.updateCount = function() {
        const count = document.querySelectorAll('.file-checkbox:checked').length;
        const element = document.getElementById('selected-count');
        if (element) {
            element.textContent = s('explore_files_selected', ':count file(s) selected').replace(':count', String(count));
        }
    };

    function getSelectedFiles() {
        return Array.from(document.querySelectorAll('.file-checkbox:checked')).map((checkbox) => checkbox.value);
    }

    window.restoreSelected = function() {
        const files = getSelectedFiles();
        if (files.length === 0) {
            window.toast(s('explore_no_file_selected', 'No file selected'), 'error');
            return;
        }
        const countEl = document.getElementById('partial-file-count');
        if (countEl) {
            countEl.textContent = files.length;
        }
        renderRestorePreview('partial');
        document.getElementById('modal-partial-restore').classList.add('show');
    };

    window.launchPartialRestore = async function() {
        const files = getSelectedFiles();
        if (files.length === 0) {
            window.toast(s('explore_no_file_selected', 'No file selected'), 'error');
            return;
        }

        const mode = document.querySelector('input[name="partial_mode"]:checked')?.value || 'local';
        const btn = document.getElementById('btn-partial-restore');
        const out = document.getElementById('partial-restore-output');
        const log = document.getElementById('partial-restore-log');
        const preview = buildRestorePreview('partial');
        if (preview.blocked) {
            window.toast(preview.blockedReason || s('explore_confirmation_required', 'Confirmation required'), 'error');
            renderRestorePreview('partial');
            return;
        }
        btn.dataset.running = '1';
        btn.disabled = true;
        btn.textContent = s('explore_in_progress', 'In progress...');
        out.style.display = 'block';
        log.textContent = s('explore_restore_in_progress', 'Restore in progress...');

        const payload = {
            repo_id: repoId,
            snapshot: snapshotId,
            files,
            mode,
            destination_mode: getSelectedStrategy('partial'),
            append_context_subdir: getAppendContext('partial'),
        };
        if (payload.destination_mode === 'original') {
            payload.original_confirmation = getOriginalConfirmation('partial');
        }

        if (mode === 'remote') {
            const select = document.getElementById('partial-host-id');
            if (select) {
                payload.host_id = parseInt(select.value, 10);
            }
        }

        const res = await window.apiPost('/api/restore_partial.php', payload);
        btn.dataset.running = '0';
        renderRestorePreview('partial');
        btn.disabled = false;
        btn.textContent = '↩ ' + s('explore_restore_btn', 'Restore');
        log.textContent = res.output || res.error;
        log.style.color = res.success ? 'var(--green)' : 'var(--red)';
        window.toast(res.success ? s('explore_restore_done', 'Restore completed') : s('explore_error', 'Error'), res.success ? 'success' : 'error');
    };

    window.launchFileDiff = async function() {
        const snapA = document.getElementById('diff-snap-a').value;
        const snapB = document.getElementById('diff-snap-b-file').value;
        const filePath = document.getElementById('diff-file-path').value.trim();

        if (!filePath) {
            window.toast(s('explore_enter_file_path', 'Enter a file path'), 'error');
            return;
        }
        if (!snapA || !snapB || snapA === snapB) {
            window.toast(s('explore_select_two_snapshots', 'Select two different snapshots'), 'error');
            return;
        }

        const out = document.getElementById('file-diff-output');
        const content = document.getElementById('file-diff-content');
        const stats = document.getElementById('diff-file-stats');
        out.style.display = 'block';
        content.textContent = s('explore_comparison_loading', 'Comparison in progress...');
        content.innerHTML = '';
        stats.textContent = '';

        const res = await window.apiPost('/api/diff_file.php', {
            repo_id: repoId,
            snapshot_a: snapA,
            snapshot_b: snapB,
            file_path: filePath,
        });

        if (res.error) {
            content.textContent = res.error;
            return;
        }

        const added = res.diff.filter((line) => line.type === 'add').length;
        const removed = res.diff.filter((line) => line.type === 'remove').length;
        stats.innerHTML = '<span class="badge badge-blue">' + escHtml(snapA) + '</span> vs <span class="badge badge-blue">' + escHtml(snapB) + '</span> &nbsp;'
            + '<span style="color:var(--green)">+' + added + '</span> &nbsp;'
            + '<span style="color:var(--red)">-' + removed + '</span>';

        content.innerHTML = '';
        res.diff.forEach((line) => {
            const div = document.createElement('div');
            div.style.cssText = 'display:flex;gap:8px;padding:1px 4px;border-radius:2px';

            if (line.type === 'add') {
                div.style.background = 'rgba(63,185,80,.15)';
                div.innerHTML = '<span style="color:var(--green);min-width:16px">+</span>'
                    + '<span style="color:var(--text2);min-width:32px;font-size:10px">' + (line.line_b || '') + '</span>'
                    + '<span>' + escHtml(line.content) + '</span>';
            } else if (line.type === 'remove') {
                div.style.background = 'rgba(248,81,73,.15)';
                div.innerHTML = '<span style="color:var(--red);min-width:16px">-</span>'
                    + '<span style="color:var(--text2);min-width:32px;font-size:10px">' + (line.line_a || '') + '</span>'
                    + '<span style="text-decoration:line-through;opacity:.7">' + escHtml(line.content) + '</span>';
            } else if (line.type === 'ellipsis') {
                div.style.cssText += ';color:var(--text2);font-style:italic;font-size:11px';
                div.textContent = s('explore_identical_lines', '... :count identical lines ...').replace(':count', String(line.count || 0));
            } else if (line.type === 'info') {
                div.style.cssText += ';color:var(--yellow)';
                div.textContent = line.content;
            } else {
                div.innerHTML = '<span style="min-width:16px;color:var(--text2)"> </span>'
                    + '<span style="color:var(--text2);min-width:32px;font-size:10px">' + (line.line_a || '') + '</span>'
                    + '<span style="color:var(--text2)">' + escHtml(line.content) + '</span>';
            }
            content.appendChild(div);
        });
    };

    function formatBytesJS(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' Go';
        }
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' Mo';
        }
        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' Ko';
        }
        return bytes + ' o';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    window.removeTagBadge = function(element, tag) {
        pendingRemoveTags.push(tag);
        element.style.opacity = '0.3';
        element.style.textDecoration = 'line-through';
        element.onclick = null;
    };

    window.addTagBadge = function() {
        const input = document.getElementById('new-tag-input');
        const tag = input.value.trim();
        if (!tag) {
            return;
        }

        const container = document.getElementById('current-tags');
        if (container.textContent.includes(s('explore_no_tag', 'No tag'))) {
            container.innerHTML = '';
        }

        const badge = document.createElement('span');
        badge.className = 'badge badge-green';
        badge.style.cursor = 'pointer';
        badge.dataset.newTag = tag;
        badge.textContent = tag + ' ×';
        badge.onclick = () => { badge.remove(); };
        container.appendChild(badge);
        input.value = '';
    };

    window.saveTags = async function() {
        const btn = document.getElementById('btn-save-tags');
        const out = document.getElementById('tags-output');
        const log = document.getElementById('tags-log');
        btn.disabled = true;
        out.style.display = 'block';
        log.textContent = s('explore_saving', 'Saving...');

        const newTags = [];
        document.querySelectorAll('#current-tags [data-new-tag]').forEach((element) => {
            newTags.push(element.dataset.newTag);
        });

        let success = true;
        if (newTags.length > 0) {
            const res = await window.apiPost('/api/manage_tags.php', {
                repo_id: repoId,
                snapshot_id: snapshotId,
                action: 'add',
                tags: newTags,
            });
            if (!res.success) {
                success = false;
                log.textContent += s('explore_add_error', '\nAdd error: ') + (res.output || res.error);
            }
        }

        if (pendingRemoveTags.length > 0) {
            const res = await window.apiPost('/api/manage_tags.php', {
                repo_id: repoId,
                snapshot_id: snapshotId,
                action: 'remove',
                tags: pendingRemoveTags,
            });
            if (!res.success) {
                success = false;
                log.textContent += s('explore_remove_error', '\nRemove error: ') + (res.output || res.error);
            }
        }

        btn.disabled = false;
        log.textContent = success ? s('explore_tags_updated', 'Tags updated') : log.textContent;
        log.style.color = success ? 'var(--green)' : 'var(--red)';
        window.toast(success ? s('explore_tags_updated', 'Tags updated') : s('explore_error', 'Error'), success ? 'success' : 'error');
        if (success) {
            window.setTimeout(() => window.location.reload(), 1500);
        }
    };

    window.applyRetention = async function(dryRun) {
        const out = document.getElementById('retention-output');
        const log = document.getElementById('retention-log');
        out.style.display = 'block';
        log.textContent = dryRun ? s('explore_simulation_in_progress', 'Simulation in progress...') : s('explore_apply_in_progress', 'Applying...');

        const res = await window.apiPost('/api/apply_retention.php', {
            repo_id: repoId,
            keep_last: parseInt(document.getElementById('ret-keep_last').value, 10) || 0,
            keep_daily: parseInt(document.getElementById('ret-keep_daily').value, 10) || 0,
            keep_weekly: parseInt(document.getElementById('ret-keep_weekly').value, 10) || 0,
            keep_monthly: parseInt(document.getElementById('ret-keep_monthly').value, 10) || 0,
            keep_yearly: parseInt(document.getElementById('ret-keep_yearly').value, 10) || 0,
            prune: document.getElementById('ret-prune').checked,
            dry_run: dryRun,
        });

        log.textContent = res.output || res.error || s('explore_no_output', '(no output)');
        log.style.color = res.success ? 'var(--green)' : 'var(--red)';
        window.toast(
            dryRun ? s('explore_simulation_done', 'Simulation completed') : (res.success ? s('explore_retention_applied', 'Retention applied') : s('explore_error', 'Error')),
            res.success ? 'success' : 'error'
        );
        if (!dryRun && res.success) {
            window.setTimeout(() => window.location.reload(), 2000);
        }
    };
})();
