(function() {
    const config = window.FULGURITE_CONFIG || {};
    const timezoneConfig = config.timezone || {};
    const localeCode = config.locale || 'fr';
    const sessionConfig = config.session || {};
    const notificationsConfig = config.notifications || {};
    const csrfToken = config.csrfToken || '';
    const strings = window.FULGURITE_STRINGS || {};
    const s = (key, fallback) => strings[key] || fallback;

    window.CSRF_TOKEN = csrfToken;

    window.parseAppDateTime = function(value) {
        if (!value) {
            return null;
        }

        const raw = String(value).trim();
        if (!raw) {
            return null;
        }

        let candidate = raw;
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(raw)) {
            candidate = raw.replace(' ', 'T');
        } else if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
            candidate = raw + 'T00:00:00';
        }

        if (!/(Z|[+\-]\d{2}:\d{2}|[+\-]\d{4})$/.test(candidate)) {
            candidate += 'Z';
        }

        const parsed = new Date(candidate);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    };

    window.formatAppDateTime = function(value, options = {}) {
        const parsed = window.parseAppDateTime(value);
        if (!parsed) {
            return value ? String(value) : '';
        }

        const formatter = new Intl.DateTimeFormat(localeCode, {
            timeZone: timezoneConfig.name || undefined,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            ...options,
        });

        return formatter.format(parsed);
    };

    window.toast = function(msg, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) {
            return;
        }

        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${type}`;
        toastEl.textContent = msg;
        container.appendChild(toastEl);
        window.setTimeout(() => toastEl.remove(), 3500);
    };

    window.showToast = function(msg, type = 'info') {
        const normalizedType = type === 'danger' ? 'error' : type;
        window.toast(msg, normalizedType);
    };

    window.apiPost = async function(url, data = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({ ...data, csrf_token: csrfToken }),
        });

        return response.json();
    };

    function base64UrlToUint8Array(value) {
        const normalized = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
        const padded = normalized + '='.repeat((4 - normalized.length % 4) % 4);
        const binary = atob(padded);
        return Uint8Array.from(binary, (char) => char.charCodeAt(0));
    }

    function arrayBufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        bytes.forEach((byte) => {
            binary += String.fromCharCode(byte);
        });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    async function performWebAuthnStepUp(operation) {
        if (!window.PublicKeyCredential || !navigator.credentials?.get) {
            throw new Error(window.FULGURITE_STRINGS?.webauthn_unavailable_browser || 'WebAuthn unavailable');
        }
        if (!window.isSecureContext) {
            throw new Error(window.FULGURITE_STRINGS?.webauthn_requires_https || 'WebAuthn requires HTTPS');
        }

        const optionsData = await window.apiPost('/api/webauthn_auth_options.php', {
            mode: 'reauth',
            operation,
        });
        if (!optionsData.success) {
            throw new Error(optionsData.error || (window.FULGURITE_STRINGS?.webauthn_prepare_error || 'Unable to prepare WebAuthn'));
        }

        const options = optionsData.options || {};
        options.challenge = base64UrlToUint8Array(options.challenge || '');
        options.allowCredentials = (options.allowCredentials || []).map((credential) => ({
            ...credential,
            id: base64UrlToUint8Array(credential.id),
        }));

        const assertion = await navigator.credentials.get({ publicKey: options });
        const verifyData = await window.apiPost('/api/webauthn_auth_verify.php', {
            assertion: {
                id: assertion.id,
                rawId: arrayBufferToBase64Url(assertion.rawId),
                clientDataJSON: arrayBufferToBase64Url(assertion.response.clientDataJSON),
                authenticatorData: arrayBufferToBase64Url(assertion.response.authenticatorData),
                signature: arrayBufferToBase64Url(assertion.response.signature),
                userHandle: assertion.response.userHandle ? arrayBufferToBase64Url(assertion.response.userHandle) : '',
            },
        });

        if (!verifyData.success) {
            throw new Error(verifyData.error || (window.FULGURITE_STRINGS?.webauthn_verify_error || 'Verification failed'));
        }

        return verifyData;
    }

    window.startWebAuthnStepUp = function(operation = 'generic.sensitive') {
        return performWebAuthnStepUp(operation);
    };

    window.toggleNotificationPolicyMode = function(prefix, isCustom) {
        const target = document.getElementById(`${prefix}-notification-custom`);
        if (target) {
            target.style.display = isCustom ? 'block' : 'none';
        }
    };

    window.applyNotificationPolicyToEditor = function(prefix, policy = {}) {
        const inherit = !policy || policy.inherit !== false;
        const modeSelector = `input[name="${prefix}_notification_mode"]`;
        document.querySelectorAll(modeSelector).forEach((input) => {
            input.checked = input.value === (inherit ? 'inherit' : 'custom');
        });

        document.querySelectorAll(`input[name^="${prefix}_notify_"]`).forEach((input) => {
            input.checked = false;
        });

        const events = policy && policy.events ? policy.events : {};
        Object.entries(events).forEach(([eventKey, channels]) => {
            (channels || []).forEach((channelKey) => {
                const selector = `input[name="${prefix}_notify_${eventKey}_${channelKey}"]`;
                const checkbox = document.querySelector(selector);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        });

        window.toggleNotificationPolicyMode(prefix, !inherit);
    };

    window.toggleRetryPolicyMode = function(prefix, isCustom) {
        const target = document.getElementById(`${prefix}-retry-custom`);
        if (target) {
            target.style.display = isCustom ? 'block' : 'none';
        }
        window.toggleRetryPolicyEnabled(prefix);
    };

    window.toggleRetryPolicyEnabled = function(prefix) {
        const target = document.getElementById(`${prefix}-retry-enabled-options`);
        const checkbox = document.querySelector(`input[name="${prefix}_retry_enabled"]`);
        if (target && checkbox) {
            target.style.display = checkbox.checked ? 'block' : 'none';
        }
    };

    window.applyRetryPolicyToEditor = function(prefix, policy = {}) {
        const inherit = !policy || policy.inherit !== false;
        const modeSelector = `input[name="${prefix}_retry_mode"]`;
        document.querySelectorAll(modeSelector).forEach((input) => {
            input.checked = input.value === (inherit ? 'inherit' : 'custom');
        });

        const enabledCheckbox = document.querySelector(`input[name="${prefix}_retry_enabled"]`);
        if (enabledCheckbox) {
            enabledCheckbox.checked = !policy || policy.enabled !== false;
        }

        const maxRetriesInput = document.querySelector(`input[name="${prefix}_retry_max_retries"]`);
        if (maxRetriesInput) {
            maxRetriesInput.value = policy && typeof policy.max_retries !== 'undefined' ? policy.max_retries : 1;
        }

        const delayInput = document.querySelector(`input[name="${prefix}_retry_delay_seconds"]`);
        if (delayInput) {
            delayInput.value = policy && typeof policy.delay_seconds !== 'undefined' ? policy.delay_seconds : 20;
        }

        document.querySelectorAll(`input[name^="${prefix}_retry_on_"]`).forEach((input) => {
            input.checked = false;
        });

        const retryOn = Array.isArray(policy && policy.retry_on) ? policy.retry_on : [];
        retryOn.forEach((category) => {
            const checkbox = document.querySelector(`input[name="${prefix}_retry_on_${category}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });

        window.toggleRetryPolicyMode(prefix, !inherit);
    };

    window.testSavedNotificationPolicy = async function(profile, event, policy, contextName = '') {
        try {
            const result = await window.apiPost('/api/test_notification.php', {
                profile,
                event,
                policy,
                context_name: contextName,
            });
            window.toast(
                result.output || (result.success
                    ? s('notification_test_sent', 'Test notification sent')
                    : s('notification_test_failed', 'Test notification failed')),
                result.success ? 'success' : 'error'
            );
        } catch (error) {
            window.toast(error.message || s('notification_test_error', 'Error while testing notification'), 'error');
        }
    };

    window.fetchJsonSafe = async function(url, options = {}) {
        const {
            timeoutMs = 15000,
            headers = {},
            ...fetchOptions
        } = options;

        const controller = new AbortController();
        const timer = window.setTimeout(() => controller.abort(), timeoutMs);
        const method = String(fetchOptions.method || 'GET').toUpperCase();
        const finalHeaders = new Headers(headers || {});
        if (csrfToken && !['GET', 'HEAD', 'OPTIONS'].includes(method) && !finalHeaders.has('X-CSRF-Token')) {
            finalHeaders.set('X-CSRF-Token', csrfToken);
        }

        try {
            const response = await fetch(url, {
                ...fetchOptions,
                headers: finalHeaders,
                credentials: fetchOptions.credentials || 'same-origin',
                signal: controller.signal,
            });
            const rawBody = await response.text();
            const payload = rawBody ? JSON.parse(rawBody) : null;

            if (!response.ok) {
                throw new Error((payload && payload.error) || `HTTP ${response.status}`);
            }

            return payload;
        } catch (error) {
            if (error && error.name === 'AbortError') {
                throw new Error('Delai depasse');
            }
            throw error;
        } finally {
            window.clearTimeout(timer);
        }
    };

    function getJsonCacheStore(storageMode) {
        try {
            return storageMode === 'local' ? window.localStorage : window.sessionStorage;
        } catch (error) {
            return null;
        }
    }

    function getJsonCacheKey(key) {
        return `fulgurite:json-cache:${key}`;
    }

    function readJsonCache(key, storageMode) {
        const store = getJsonCacheStore(storageMode);
        if (!store) {
            return null;
        }

        try {
            const raw = store.getItem(getJsonCacheKey(key));
            if (!raw) {
                return null;
            }

            const decoded = JSON.parse(raw);
            if (!decoded || typeof decoded !== 'object' || !('payload' in decoded) || !decoded.savedAt) {
                return null;
            }

            return decoded;
        } catch (error) {
            return null;
        }
    }

    function writeJsonCache(key, payload, storageMode) {
        const store = getJsonCacheStore(storageMode);
        if (!store) {
            return;
        }

        try {
            store.setItem(getJsonCacheKey(key), JSON.stringify({
                savedAt: Date.now(),
                payload,
            }));
        } catch (error) {
            // ignore cache write issues
        }
    }

    function removeJsonCache(key, storageMode) {
        const store = getJsonCacheStore(storageMode);
        if (!store) {
            return;
        }

        try {
            store.removeItem(getJsonCacheKey(key));
        } catch (error) {
            // ignore cache cleanup issues
        }
    }

    function withCacheMeta(payload, source, ageMs, networkError = null) {
        if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
            return payload;
        }

        return {
            ...payload,
            __cache: {
                source,
                ageMs,
                networkError: networkError ? String(networkError) : '',
            },
        };
    }

    window.fetchJsonWithCache = async function(url, options = {}) {
        const {
            cacheKey = url,
            storage = 'session',
            maxStaleMs = 60000,
            onStaleData = null,
            shouldCache = () => true,
            ...fetchOptions
        } = options;

        const cached = readJsonCache(cacheKey, storage);
        const cacheAgeMs = cached ? Math.max(0, Date.now() - Number(cached.savedAt || 0)) : Infinity;
        const cacheUsable = !!cached && cacheAgeMs <= maxStaleMs;

        if (cacheUsable && typeof onStaleData === 'function') {
            try {
                onStaleData(withCacheMeta(cached.payload, 'stale', cacheAgeMs));
            } catch (error) {
                // ignore render callback errors
            }
        }

        try {
            const payload = await window.fetchJsonSafe(url, fetchOptions);
            if (shouldCache(payload)) {
                writeJsonCache(cacheKey, payload, storage);
            } else {
                removeJsonCache(cacheKey, storage);
            }
            return withCacheMeta(payload, 'network', 0);
        } catch (error) {
            if (cacheUsable) {
                return withCacheMeta(cached.payload, 'stale-fallback', cacheAgeMs, error.message || String(error));
            }
            throw error;
        }
    };

    window.registerVisibilityAwareInterval = function(callback, intervalMs, options = {}) {
        const settings = {
            runImmediately: false,
            skipWhenHidden: true,
            ...options,
        };

        const runner = () => {
            if (settings.skipWhenHidden && document.hidden) {
                return;
            }
            callback();
        };

        if (settings.runImmediately) {
            runner();
        }

        return window.setInterval(runner, intervalMs);
    };

    window.loadScriptOnce = function(src) {
        window.__fulguriteScripts = window.__fulguriteScripts || {};
        if (window.__fulguriteScripts[src]) {
            return window.__fulguriteScripts[src];
        }

        window.__fulguriteScripts[src] = new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                if (typeof window.Chart !== 'undefined') {
                    resolve();
                    return;
                }
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', () => reject(new Error(`Unable to load ${src}`)), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Unable to load ${src}`));
            document.head.appendChild(script);
        });

        return window.__fulguriteScripts[src];
    };

    window.ensureChartJs = function() {
        return window.loadScriptOnce('/assets/chart.umd.min.js');
    };

    function setNotificationBadge(count) {
        const badge = document.getElementById('global-notifications-badge');
        if (!badge) {
            return;
        }

        const total = Math.max(0, Number(count || 0));
        badge.textContent = String(total);
        badge.style.display = total > 0 ? 'inline-flex' : 'none';
    }

    function getNotificationStorageKey() {
        return `fulgurite:notifications:last-browser-id:${notificationsConfig.userId || 0}`;
    }

    function readLastBrowserNotificationId() {
        try {
            const raw = window.localStorage.getItem(getNotificationStorageKey());
            return Math.max(0, Number(raw || 0));
        } catch (error) {
            return 0;
        }
    }

    function writeLastBrowserNotificationId(id) {
        try {
            window.localStorage.setItem(getNotificationStorageKey(), String(Math.max(0, Number(id || 0))));
        } catch (error) {
            // ignore storage issues
        }
    }

    function openBrowserNotificationTarget(url) {
        const target = String(url || notificationsConfig.centerUrl || '/notifications').trim();
        if (target) {
            window.location.href = target;
        }
    }

    function showBrowserNotification(item) {
        if (!notificationsConfig.browserChannelEnabled || !('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        const notification = new Notification(String(item.title || 'Fulgurite'), {
            body: String(item.body || ''),
            tag: `fulgurite-notification-${item.id}`,
        });

        notification.onclick = () => {
            window.focus();
            openBrowserNotificationTarget(item.link_url || notificationsConfig.centerUrl || '/notifications');
            notification.close();
        };
    }

    window.enableBrowserNotifications = async function() {
        if (!('Notification' in window)) {
            window.toast(s('browser_notifications_unavailable', 'Browser notifications are unavailable in this environment'), 'error');
            return 'unsupported';
        }

        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            window.toast(s('browser_notifications_enabled', 'Browser notifications enabled'), 'success');
        } else if (permission === 'denied') {
            window.toast(s('browser_notifications_denied', 'Browser notifications were denied'), 'error');
        }

        return permission;
    };

    if (notificationsConfig.enabled && Number(notificationsConfig.userId || 0) > 0 && notificationsConfig.feedUrl) {
        let notificationFeedInitialized = false;
        let notificationPollInFlight = false;

        const pollNotifications = async () => {
            if (notificationPollInFlight) {
                return;
            }

            notificationPollInFlight = true;
            try {
                const afterId = readLastBrowserNotificationId();
                const separator = notificationsConfig.feedUrl.includes('?') ? '&' : '?';
                const url = `${notificationsConfig.feedUrl}${separator}after_id=${encodeURIComponent(afterId)}&limit=10`;
                const payload = await window.fetchJsonSafe(url, {
                    timeoutMs: 10000,
                    headers: { 'X-Requested-With': 'fetch' },
                });

                const unreadCount = Number(payload && payload.unread_count ? payload.unread_count : 0);
                const latestId = Math.max(0, Number(payload && payload.latest_id ? payload.latest_id : 0));
                const browserItems = Array.isArray(payload && payload.browser_items) ? payload.browser_items : [];
                setNotificationBadge(unreadCount);

                if (!notificationFeedInitialized && afterId === 0) {
                    if (latestId > 0) {
                        writeLastBrowserNotificationId(latestId);
                    }
                    notificationFeedInitialized = true;
                    return;
                }

                let highestId = afterId;
                browserItems.forEach((item) => {
                    const itemId = Math.max(0, Number(item && item.id ? item.id : 0));
                    if (itemId > highestId) {
                        highestId = itemId;
                    }
                    if (itemId > afterId) {
                        showBrowserNotification(item || {});
                    }
                });

                if (highestId > afterId) {
                    writeLastBrowserNotificationId(highestId);
                } else if (latestId > afterId && browserItems.length === 0) {
                    writeLastBrowserNotificationId(latestId);
                }

                notificationFeedInitialized = true;
            } catch (error) {
                // keep silent to avoid poll noise in the UI
            } finally {
                notificationPollInFlight = false;
            }
        };

        const refreshNotificationsNow = () => {
            if (document.hidden) {
                return;
            }
            void pollNotifications();
        };

        window.registerVisibilityAwareInterval(() => {
            void pollNotifications();
        }, Math.max(5000, Number(notificationsConfig.pollIntervalMs || 20000)), {
            runImmediately: true,
            skipWhenHidden: true,
        });

        document.addEventListener('visibilitychange', refreshNotificationsNow);
        window.addEventListener('focus', refreshNotificationsNow);
    }

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        'summary',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');
    const modalStates = new WeakMap();
    let sidebarReturnFocus = null;

    function isVisibleForFocus(element) {
        return !!element && !element.hasAttribute('disabled') && element.getAttribute('aria-hidden') !== 'true'
            && (element.offsetParent !== null || element.getClientRects().length > 0);
    }

    function focusElement(element) {
        if (element && typeof element.focus === 'function') {
            element.focus({ preventScroll: false });
        }
    }

    function getFocusableElements(container) {
        if (!container) {
            return [];
        }

        return Array.from(container.querySelectorAll(focusableSelector)).filter((element) => isVisibleForFocus(element));
    }

    function getTopmostShownModal() {
        const overlays = Array.from(document.querySelectorAll('.modal-overlay.show'));
        return overlays.length ? overlays[overlays.length - 1] : null;
    }

    function ensureModalAccessibility(overlay) {
        if (!overlay) {
            return null;
        }

        const modal = overlay.querySelector('.modal');
        if (!modal) {
            return null;
        }

        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        if (!modal.hasAttribute('tabindex')) {
            modal.setAttribute('tabindex', '-1');
        }

        const title = modal.querySelector('.modal-title');
        if (title) {
            if (!title.id) {
                title.id = `${overlay.id || 'modal'}-title`;
            }
            modal.setAttribute('aria-labelledby', title.id);
        }

        return modal;
    }

    function syncBodyModalState() {
        document.body.classList.toggle('modal-open', !!getTopmostShownModal());
    }

    function handleModalOpened(overlay) {
        const modal = ensureModalAccessibility(overlay);
        if (!modal) {
            return;
        }

        const previous = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const currentState = modalStates.get(overlay) || {};
        modalStates.set(overlay, {
            ...currentState,
            isOpen: true,
            previousFocus: currentState.previousFocus || previous,
        });
        syncBodyModalState();

        const autoFocusTarget = modal.querySelector('[autofocus], [data-modal-autofocus]');
        const fallbackTarget = getFocusableElements(modal)[0] || modal;
        window.requestAnimationFrame(() => {
            focusElement(autoFocusTarget || fallbackTarget);
        });
    }

    function handleModalClosed(overlay) {
        const state = modalStates.get(overlay) || {};
        modalStates.set(overlay, { ...state, isOpen: false });
        syncBodyModalState();

        if (!getTopmostShownModal() && state.previousFocus && state.previousFocus.isConnected) {
            window.requestAnimationFrame(() => {
                focusElement(state.previousFocus);
            });
        }
    }

    function syncModalState(overlay) {
        if (!overlay || !overlay.classList || !overlay.classList.contains('modal-overlay')) {
            return;
        }

        const state = modalStates.get(overlay) || { isOpen: false, previousFocus: null };
        const isOpen = overlay.classList.contains('show');
        if (isOpen && !state.isOpen) {
            handleModalOpened(overlay);
        } else if (!isOpen && state.isOpen) {
            handleModalClosed(overlay);
        } else if (isOpen) {
            ensureModalAccessibility(overlay);
        }
    }

    window.openModal = function(target) {
        const overlay = typeof target === 'string' ? document.getElementById(target) : target;
        if (!overlay) {
            return;
        }

        const activeElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        const state = modalStates.get(overlay) || {};
        modalStates.set(overlay, {
            ...state,
            previousFocus: activeElement,
        });
        overlay.classList.add('show');
        syncModalState(overlay);
    };

    window.closeModal = function(target) {
        const overlay = typeof target === 'string' ? document.getElementById(target) : target;
        if (!overlay) {
            return;
        }

        if (overlay.id === 'modal-confirm' && typeof window.finishConfirmDialog === 'function') {
            window.finishConfirmDialog(false);
            return;
        }

        if (overlay.id === 'modal-reauth' && typeof window.closeReauth === 'function') {
            window.closeReauth();
            return;
        }

        overlay.classList.remove('show');
        syncModalState(overlay);
    };

    let confirmDialogState = null;

    function getConfirmDialogElements() {
        return {
            overlay: document.getElementById('modal-confirm'),
            title: document.getElementById('modal-confirm-title'),
            message: document.getElementById('modal-confirm-message'),
            cancel: document.getElementById('modal-confirm-cancel'),
            confirm: document.getElementById('modal-confirm-submit'),
        };
    }

    window.finishConfirmDialog = function(accepted) {
        if (!confirmDialogState) {
            return;
        }

        const { overlay, resolve } = confirmDialogState;
        confirmDialogState = null;

        if (overlay) {
            overlay.classList.remove('show');
            syncModalState(overlay);
        }

        resolve(Boolean(accepted));
    };

    window.confirmActionAsync = function(message, options = {}) {
        const elements = getConfirmDialogElements();
        if (!elements.overlay || !elements.title || !elements.message || !elements.cancel || !elements.confirm) {
            return Promise.resolve(false);
        }

        if (confirmDialogState) {
            window.finishConfirmDialog(false);
        }

        elements.title.textContent = options.title || s('confirmation_required', 'Confirmation required');
        elements.message.textContent = message || s('confirm_action', 'Confirm this action.');
        elements.cancel.textContent = options.cancelLabel || s('cancel', 'Cancel');
        elements.confirm.textContent = options.confirmLabel || s('confirm', 'Confirm');
        elements.confirm.className = options.confirmClass || 'btn btn-danger';
        elements.cancel.className = options.cancelClass || 'btn';

        return new Promise((resolve) => {
            confirmDialogState = {
                overlay: elements.overlay,
                resolve,
            };
            window.openModal(elements.overlay);
        });
    };

    window.confirmAction = function(message, callback, options = {}) {
        void window.confirmActionAsync(message, options).then((accepted) => {
            if (accepted && typeof callback === 'function') {
                callback();
            }
        });
    };

    function getEnhancementNodes(root, selector) {
        const nodes = [];
        if (!root) {
            return nodes;
        }

        if (root.nodeType === 1 && root.matches(selector)) {
            nodes.push(root);
        }

        if (typeof root.querySelectorAll === 'function') {
            root.querySelectorAll(selector).forEach((node) => nodes.push(node));
        }

        return nodes;
    }

    function enhanceTables(root) {
        getEnhancementNodes(root, '.table').forEach((table) => {
            if (table.closest('.table-wrap')) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'table-wrap';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }

    function enhanceInlineLayouts(root) {
        getEnhancementNodes(root, '[style]').forEach((element) => {
            const style = (element.getAttribute('style') || '').toLowerCase();
            if (!style) {
                return;
            }

            if (style.includes('grid-template-columns')) {
                element.dataset.responsiveGrid = 'stack';
            }

            if (style.includes('display:flex')) {
                element.dataset.responsiveFlex = 'wrap';
            }
        });
    }

    window.enhanceResponsiveLayout = function(root = document) {
        enhanceTables(root);
        enhanceInlineLayouts(root);
    };

    const appShell = document.getElementById('app-shell');
    const sidebarEl = document.getElementById('app-sidebar');
    const mobileBreakpoint = 900;

    function syncSidebarButtons(isOpen) {
        document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function getSidebarFocusTarget() {
        if (!sidebarEl) {
            return null;
        }

        return sidebarEl.querySelector('.sidebar-close')
            || sidebarEl.querySelector('.nav-item.active')
            || sidebarEl.querySelector('.nav-item, .nav-group-toggle');
    }

    function closeNavGroups(except = null) {
        document.querySelectorAll('.nav-group[open]').forEach((group) => {
            if (group !== except) {
                group.open = false;
            }
        });
    }

    function setSidebarOpen(isOpen) {
        if (!appShell) {
            return;
        }

        const wasOpen = appShell.classList.contains('sidebar-open');
        if (isOpen && !wasOpen && document.activeElement instanceof HTMLElement && (!sidebarEl || !sidebarEl.contains(document.activeElement))) {
            sidebarReturnFocus = document.activeElement;
        }

        appShell.classList.toggle('sidebar-open', isOpen);
        document.body.classList.toggle('sidebar-open', isOpen);
        syncSidebarButtons(isOpen);

        if (isOpen && !wasOpen) {
            window.requestAnimationFrame(() => {
                focusElement(getSidebarFocusTarget());
            });
        }

        if (!isOpen && wasOpen) {
            const returnTarget = sidebarReturnFocus;
            sidebarReturnFocus = null;
            if (returnTarget && returnTarget.isConnected) {
                window.requestAnimationFrame(() => {
                    focusElement(returnTarget);
                });
            }
        }
    }

    window.closeSidebar = function() {
        setSidebarOpen(false);
    };

    window.toggleSidebar = function(forceOpen) {
        const nextState = typeof forceOpen === 'boolean'
            ? forceOpen
            : !(appShell && appShell.classList.contains('sidebar-open'));
        setSidebarOpen(nextState);
    };

    document.addEventListener('click', (event) => {
        const confirmCancel = event.target.closest('[data-confirm-dismiss]');
        if (confirmCancel) {
            event.preventDefault();
            window.finishConfirmDialog(false);
            return;
        }

        const confirmAccept = event.target.closest('[data-confirm-accept]');
        if (confirmAccept) {
            event.preventDefault();
            window.finishConfirmDialog(true);
            return;
        }

        const skipLink = event.target.closest('.skip-link');
        if (skipLink) {
            const target = document.getElementById('main-content');
            if (target) {
                window.requestAnimationFrame(() => {
                    focusElement(target);
                });
            }
            return;
        }

        const toggle = event.target.closest('[data-sidebar-toggle]');
        if (toggle) {
            event.preventDefault();
            window.toggleSidebar();
            return;
        }

        const close = event.target.closest('[data-sidebar-close]');
        if (close) {
            event.preventDefault();
            window.closeSidebar();
            return;
        }

        const navGroupToggle = event.target.closest('.nav-group > .nav-group-toggle');
        if (navGroupToggle) {
            const navGroup = navGroupToggle.parentElement;
            if (navGroup && !navGroup.hasAttribute('open')) {
                closeNavGroups(navGroup);
            }
            return;
        }

        const navItem = event.target.closest('.sidebar .nav-item');
        if (navItem) {
            closeNavGroups();
            if (window.innerWidth <= mobileBreakpoint) {
                window.closeSidebar();
            }
            return;
        }

        if (!event.target.closest('.nav-group')) {
            closeNavGroups();
        }

        const modalOverlay = event.target.closest('.modal-overlay.show');
        if (modalOverlay && event.target === modalOverlay) {
            window.closeModal(modalOverlay);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const message = submitter?.dataset.confirmMessage || form.dataset.confirmMessage || '';
        if (!message) {
            return;
        }

        if (submitter?.dataset.confirmVerified === '1') {
            delete submitter.dataset.confirmVerified;
            return;
        }

        if (form.dataset.confirmVerified === '1') {
            delete form.dataset.confirmVerified;
            return;
        }

        event.preventDefault();
        const options = {
            title: submitter?.dataset.confirmTitle || form.dataset.confirmTitle || undefined,
            confirmLabel: submitter?.dataset.confirmLabel || form.dataset.confirmLabel || undefined,
            cancelLabel: submitter?.dataset.confirmCancelLabel || form.dataset.confirmCancelLabel || undefined,
            confirmClass: submitter?.dataset.confirmClass || form.dataset.confirmClass || undefined,
            cancelClass: submitter?.dataset.confirmCancelClass || form.dataset.confirmCancelClass || undefined,
        };

        void window.confirmActionAsync(message, options).then((accepted) => {
            if (!accepted) {
                return;
            }

            if (submitter) {
                submitter.dataset.confirmVerified = '1';
            } else {
                form.dataset.confirmVerified = '1';
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter || undefined);
                return;
            }

            if (submitter && typeof submitter.click === 'function') {
                submitter.click();
                return;
            }

            form.submit();
        });
    }, true);

    // --- Unsaved changes guard ---
    (function () {
        let dirtyCount = 0;
        const dirtySet = new WeakSet();

        function markDirty(form) {
            if (!dirtySet.has(form)) {
                dirtySet.add(form);
                dirtyCount++;
            }
        }

        function markClean(form) {
            if (dirtySet.has(form)) {
                dirtySet.delete(form);
                dirtyCount--;
            }
        }

        function hasDirty() {
            return dirtyCount > 0;
        }

        function isTrackable(form) {
            if (form.closest('.modal-overlay')) return false;
            if ('noUnsavedCheck' in form.dataset) return false;
            return form.querySelector('input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=reset]):not([type=image]):not([type=checkbox]):not([type=radio]), input[type=checkbox], input[type=radio], textarea, select') !== null;
        }

        function attachTracking(form) {
            if (form._unsavedTracked) return;
            form._unsavedTracked = true;
            form.addEventListener('input', () => markDirty(form));
            form.addEventListener('change', () => markDirty(form));
            form.addEventListener('submit', () => markClean(form));
        }

        function initTracking() {
            document.querySelectorAll('.content form').forEach((form) => {
                if (isTrackable(form)) attachTracking(form);
            });
        }

        // Intercept same-origin navigation links when a form is dirty
        document.addEventListener('click', (event) => {
            if (!hasDirty()) return;

            const link = event.target.closest('a[href]');
            if (!link) return;
            if (link.target === '_blank' || link.target === '_new') return;
            if (link.closest('.modal-overlay')) return;

            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            let url;
            try {
                url = new URL(href, window.location.origin);
            } catch (_) {
                return;
            }
            if (url.origin !== window.location.origin) return;
            // Skip if same page + same query (pure hash change)
            if (url.pathname + url.search === window.location.pathname + window.location.search) return;

            event.preventDefault();

            const destination = url.href;
            void window.confirmActionAsync(
                s('unsaved_changes_message', 'Unsaved changes will be lost if you leave this page without saving.'),
                {
                    title: s('unsaved_changes_title', 'Leave without saving?'),
                    confirmLabel: s('leave_page', 'Leave page'),
                    cancelLabel: s('stay', 'Stay'),
                    confirmClass: 'btn btn-warning',
                }
            ).then((accepted) => {
                if (accepted) {
                    dirtyCount = 0;
                    window.location.href = destination;
                }
            });
        }, true);

        // Native dialog for refresh / tab-close / direct URL change
        window.addEventListener('beforeunload', (event) => {
            if (hasDirty()) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // Expose hook so pages can mark a form clean after an AJAX save
        window.markFormClean = function (form) {
            if (form instanceof HTMLFormElement) markClean(form);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTracking);
        } else {
            initTracking();
        }
    }());
    // --- End unsaved changes guard ---

    document.addEventListener('keydown', (event) => {
        const topModal = getTopmostShownModal();
        if (topModal) {
            if (event.key === 'Escape') {
                event.preventDefault();
                window.closeModal(topModal);
                return;
            }

            if (event.key === 'Tab') {
                const modal = topModal.querySelector('.modal');
                if (!modal) {
                    return;
                }

                const focusable = getFocusableElements(modal);
                if (!focusable.length) {
                    event.preventDefault();
                    focusElement(modal);
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                const activeElement = document.activeElement;

                if (event.shiftKey) {
                    if (activeElement === first || !modal.contains(activeElement)) {
                        event.preventDefault();
                        focusElement(last);
                    }
                } else if (activeElement === last || !modal.contains(activeElement)) {
                    event.preventDefault();
                    focusElement(first);
                }
                return;
            }
        }

        if (event.key === 'Escape') {
            const openNavGroups = Array.from(document.querySelectorAll('.nav-group[open]'));
            if (openNavGroups.length) {
                event.preventDefault();
                openNavGroups.forEach((group) => {
                    group.open = false;
                });
                return;
            }
        }

        if (event.key === 'Tab' && appShell && appShell.classList.contains('sidebar-open') && sidebarEl && window.innerWidth <= mobileBreakpoint) {
            const focusable = getFocusableElements(sidebarEl);
            if (!focusable.length) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const activeElement = document.activeElement;

            if (event.shiftKey) {
                if (activeElement === first || !sidebarEl.contains(activeElement)) {
                    event.preventDefault();
                    focusElement(last);
                }
            } else if (activeElement === last || !sidebarEl.contains(activeElement)) {
                event.preventDefault();
                focusElement(first);
            }
            return;
        }

        if (event.key === 'Escape' && appShell && appShell.classList.contains('sidebar-open')) {
            window.closeSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > mobileBreakpoint) {
            window.closeSidebar();
        }
    });

    window.enhanceResponsiveLayout(document);
    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        ensureModalAccessibility(overlay);
        syncModalState(overlay);
    });
    if (document.body && typeof MutationObserver !== 'undefined') {
        const responsiveObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class' && mutation.target.nodeType === 1) {
                    syncModalState(mutation.target);
                }

                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        window.enhanceResponsiveLayout(node);
                        if (node.matches('.modal-overlay')) {
                            ensureModalAccessibility(node);
                            syncModalState(node);
                        }
                        node.querySelectorAll?.('.modal-overlay').forEach((overlay) => {
                            ensureModalAccessibility(overlay);
                            syncModalState(overlay);
                        });
                    }
                });
            });
        });

        responsiveObserver.observe(document.body, {
            attributes: true,
            attributeFilter: ['class'],
            childList: true,
            subtree: true,
        });
    }

    const warningEl = document.getElementById('session-warning');
    const countdownEl = document.getElementById('session-countdown');

    if (warningEl && countdownEl && sessionConfig.inactivityMs && sessionConfig.warningMs) {
        let lastActivity = Date.now();
        let warningShown = false;
        let warningInterval = null;

        const updateCountdown = (msLeft) => {
            const secs = Math.ceil(msLeft / 1000);
            const mins = Math.floor(secs / 60);
            const secRemainder = secs % 60;
            countdownEl.textContent = mins > 0 ? `${mins}m ${secRemainder}s` : `${secRemainder}s`;
        };

        const hideWarning = () => {
            warningEl.style.display = 'none';
            if (warningInterval) {
                window.clearInterval(warningInterval);
                warningInterval = null;
            }
            warningShown = false;
        };

        const showWarning = (msLeft) => {
            warningEl.style.display = 'block';
            updateCountdown(msLeft);
            if (warningInterval) {
                window.clearInterval(warningInterval);
            }

            warningInterval = window.setInterval(() => {
                const remaining = sessionConfig.inactivityMs - (Date.now() - lastActivity);
                if (remaining <= 0) {
                    window.clearInterval(warningInterval);
                    warningInterval = null;
                    window.location.href = '/login.php?expired=1&reason=inactivity';
                    return;
                }

                updateCountdown(remaining);
            }, 1000);
        };

        const checkSessionInactivity = () => {
            const idle = Date.now() - lastActivity;
            const remaining = sessionConfig.inactivityMs - idle;

            if (remaining <= 0) {
                window.location.href = '/login.php?expired=1&reason=inactivity';
            } else if (remaining <= sessionConfig.warningMs && !warningShown) {
                warningShown = true;
                showWarning(remaining);
            } else if (remaining > sessionConfig.warningMs && warningShown) {
                hideWarning();
            }
        };

        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((eventName) => {
            document.addEventListener(eventName, () => {
                lastActivity = Date.now();
            }, { passive: true });
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                checkSessionInactivity();
            }
        });

        window.registerVisibilityAwareInterval(checkSessionInactivity, 10000, { skipWhenHidden: true });
        window.registerVisibilityAwareInterval(async () => {
            const idle = Date.now() - lastActivity;
            if (idle < 60000) {
                await fetch('/api/keepalive.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                });
            }
        }, 300000, { skipWhenHidden: true });

        window.extendSession = async function() {
            await fetch('/api/keepalive.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken },
            });
            lastActivity = Date.now();
            hideWarning();
            window.toast(s('session_extended', 'Session extended'), 'success');
        };
    }

    // ── Onboarding wizard ────────────────────────────────────────────────────
    (function () {
        const STORAGE_KEY = 'fulgurite_onboarding_v1_seen';
        const TOTAL_STEPS = 6;
        let currentStep = 0;

        function getOverlay() { return document.getElementById('onboarding-wizard'); }

        function updateUI() {
            const overlay = getOverlay();
            if (!overlay) return;

            const panels   = overlay.querySelectorAll('.onboarding-panel');
            const navItems = overlay.querySelectorAll('.onboarding-step-item');
            const fillEl   = document.getElementById('onboarding-progress-fill');
            const currentEl = document.getElementById('onboarding-step-current');
            const prevBtn  = document.getElementById('onboarding-btn-prev');
            const nextBtn  = document.getElementById('onboarding-btn-next');
            const finishBtn = document.getElementById('onboarding-btn-finish');

            // Panels
            panels.forEach((panel, i) => {
                panel.hidden = i !== currentStep;
            });

            // Nav items
            navItems.forEach((item, i) => {
                item.classList.toggle('active', i === currentStep);
                item.classList.toggle('done',   i < currentStep);
            });

            // Progress
            const pct = TOTAL_STEPS > 1 ? Math.round((currentStep / (TOTAL_STEPS - 1)) * 100) : 100;
            if (fillEl) fillEl.style.width = pct + '%';
            if (currentEl) currentEl.textContent = currentStep + 1;

            // Buttons
            if (prevBtn)   prevBtn.style.display   = currentStep === 0 ? 'none' : '';
            if (nextBtn)   nextBtn.style.display   = currentStep === TOTAL_STEPS - 1 ? 'none' : '';
            if (finishBtn) finishBtn.style.display = currentStep === TOTAL_STEPS - 1 ? '' : 'none';
        }

        window.openOnboardingWizard = function (step) {
            const overlay = getOverlay();
            if (!overlay) return;
            currentStep = (typeof step === 'number') ? Math.max(0, Math.min(step, TOTAL_STEPS - 1)) : 0;
            updateUI();
            overlay.classList.add('show');
            document.body.classList.add('modal-open');
            overlay.focus();
        };

        window.closeOnboardingWizard = function () {
            const overlay = getOverlay();
            if (!overlay) return;
            overlay.classList.remove('show');
            document.body.classList.remove('modal-open');
            try { localStorage.setItem(STORAGE_KEY, '1'); } catch (_) {}
        };

        window.onboardingNext = function () {
            if (currentStep < TOTAL_STEPS - 1) {
                currentStep++;
                updateUI();
            }
        };

        window.onboardingPrev = function () {
            if (currentStep > 0) {
                currentStep--;
                updateUI();
            }
        };

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            const overlay = getOverlay();
            if (overlay && overlay.classList.contains('show') && e.key === 'Escape') {
                window.closeOnboardingWizard();
            }
        });

        // Close on backdrop click (outside shell)
        document.addEventListener('click', function (e) {
            const overlay = getOverlay();
            if (overlay && overlay.classList.contains('show') && e.target === overlay) {
                window.closeOnboardingWizard();
            }
        });

        // Auto-show on first login
        window.addEventListener('load', function () {
            let seen = false;
            try { seen = localStorage.getItem(STORAGE_KEY) === '1'; } catch (_) {}
            if (!seen && document.getElementById('onboarding-wizard')) {
                // Small delay so the page is fully rendered
                setTimeout(function () { window.openOnboardingWizard(0); }, 400);
            }
        });
    }());
})();
