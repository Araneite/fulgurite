(function() {
    const config = window.FULGURITE_CONFIG || {};
    const reauthConfig = config.reauth || {};
    const strings = window.FULGURITE_STRINGS || {};
    const s = (key, fallback) => strings[key] || fallback;

    let reauthCallback = null;
    let reauthOperation = 'generic.sensitive';

    window.requireReauth = function(callback, message = 'Confirmez votre identite pour continuer.', operation = 'generic.sensitive') {
        reauthCallback = callback;
        reauthOperation = operation;

        const messageEl = document.getElementById('reauth-message');
        const passwordEl = document.getElementById('reauth-password');
        const totpEl = document.getElementById('reauth-totp');
        const errorEl = document.getElementById('reauth-error');
        const totpGroupEl = document.getElementById('reauth-totp-group');
        const webauthnHelpEl = document.getElementById('reauth-webauthn-help');
        const webauthnButtonEl = document.getElementById('reauth-webauthn-button');
        const modalEl = document.getElementById('modal-reauth');
        const buttons = modalEl ? modalEl.querySelectorAll('button[onclick="submitReauth()"]') : [];

        if (!messageEl || !passwordEl || !totpEl || !errorEl || !totpGroupEl || !webauthnHelpEl || !webauthnButtonEl || !modalEl) {
            return;
        }

        messageEl.textContent = message;
        passwordEl.value = '';
        totpEl.value = '';
        errorEl.textContent = '';
        totpGroupEl.style.display = reauthConfig.primaryFactor === 'classic_2fa' && reauthConfig.totpEnabled ? 'block' : 'none';
        webauthnHelpEl.style.display = reauthConfig.primaryFactor === 'webauthn' ? 'block' : 'none';
        webauthnHelpEl.textContent = reauthConfig.primaryFactor === 'webauthn'
            ? s('reauth_webauthn_help', 'Your primary factor is WebAuthn. Enter your password, then confirm with your security key.')
            : '';
        webauthnButtonEl.textContent = s('reauth_webauthn_confirm', 'Confirm with WebAuthn');
        buttons.forEach((button) => {
            button.style.display = button.id === 'reauth-webauthn-button'
                ? (reauthConfig.primaryFactor === 'webauthn' ? '' : 'none')
                : (reauthConfig.primaryFactor === 'webauthn' ? 'none' : '');
        });
        modalEl.classList.add('show');
        window.setTimeout(() => passwordEl.focus(), 100);
    };

    window.closeReauth = function() {
        const modalEl = document.getElementById('modal-reauth');
        if (modalEl) {
            modalEl.classList.remove('show');
        }
        reauthCallback = null;
        reauthOperation = 'generic.sensitive';
    };

    window.submitReauth = async function() {
        const passwordEl = document.getElementById('reauth-password');
        const totpEl = document.getElementById('reauth-totp');
        const errorEl = document.getElementById('reauth-error');
        const modalEl = document.getElementById('modal-reauth');

        if (!passwordEl || !totpEl || !errorEl || !modalEl) {
            return;
        }

        if (!passwordEl.value) {
            errorEl.textContent = s('reauth_password_required', 'Password required.');
            return;
        }

        const response = await window.apiPost('/api/reauth.php', {
            password: passwordEl.value,
            totp_code: totpEl.value,
            operation: reauthOperation,
        });

        if (response.success && response.completed === false && response.next_factor === 'webauthn') {
            try {
                if (typeof window.startWebAuthnStepUp !== 'function') {
                    throw new Error(s('reauth_webauthn_unavailable', 'WebAuthn unavailable.'));
                }
                await window.startWebAuthnStepUp(reauthOperation);
                if (reauthCallback) {
                    const callback = reauthCallback;
                    window.closeReauth();
                    callback();
                } else {
                    window.closeReauth();
                }
                return;
            } catch (error) {
                errorEl.textContent = error.message || s('reauth_webauthn_failed', 'WebAuthn authentication failed.');
                passwordEl.value = '';
                passwordEl.focus();
                return;
            }
        }

        if (response.success) {
            if (reauthCallback) {
                const callback = reauthCallback;
                window.closeReauth();
                callback();
            } else {
                window.closeReauth();
            }
            return;
        }

        errorEl.textContent = response.error || s('reauth_failed', 'Authentication failed.');
        passwordEl.value = '';
        passwordEl.focus();
    };

    document.addEventListener('keydown', (event) => {
        const modalEl = document.getElementById('modal-reauth');
        if (!modalEl) {
            return;
        }

        if (event.key === 'Escape') {
            window.closeReauth();
        }

        if (event.key === 'Enter' && modalEl.classList.contains('show')) {
            window.submitReauth();
        }
    });
})();
