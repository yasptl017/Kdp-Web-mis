'use strict';

(() => {
    const installButtons = Array.from(document.querySelectorAll('.pwa-install-btn'));
    if (!installButtons.length) {
        return;
    }

    let deferredInstallPrompt = null;

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    const hideInstallButtons = () => {
        installButtons.forEach((button) => {
            button.classList.add('d-none');
            button.setAttribute('disabled', 'disabled');
        });
    };

    const showInstallButtons = () => {
        installButtons.forEach((button) => {
            button.classList.remove('d-none');
            button.removeAttribute('disabled');
        });
    };

    hideInstallButtons();

    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js').catch(() => {
            hideInstallButtons();
        });
    });

    if (isStandalone()) {
        hideInstallButtons();
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        showInstallButtons();
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        hideInstallButtons();
    });

    installButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (isStandalone()) {
                hideInstallButtons();
                return;
            }

            if (!deferredInstallPrompt) {
                const isiOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
                if (isiOS) {
                    alert('To install, open Share and tap "Add to Home Screen".');
                } else {
                    alert('Install option is not available right now in this browser.');
                }
                return;
            }

            deferredInstallPrompt.prompt();

            try {
                await deferredInstallPrompt.userChoice;
            } catch (error) {
                // Ignore prompt result errors and reset prompt reference.
            }

            deferredInstallPrompt = null;
            hideInstallButtons();
        });
    });
})();
