(() => {
    'use strict';

    if (window.__RMS_UI_SYSTEM__) return;
    window.__RMS_UI_SYSTEM__ = true;

    const $ = (selector, root = document) => root.querySelector(selector);

    const state = {
        confirmResolver: null,
        toastId: 0,
    };

    function setExpanded(button, expanded) {
        if (button) button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    function closeNotification() {
        const menu = $('#notificationMenu');
        const dropdown = $('#notificationDropdown');
        menu?.classList.remove('is-open');
        setExpanded($('#notificationButton'), false);
        dropdown?.setAttribute('aria-hidden', 'true');
    }

    function closeProfile() {
        const menu = $('#profileMenu');
        const dropdown = $('#profileDropdown');
        menu?.classList.remove('is-open');
        setExpanded($('#profileButton'), false);
        dropdown?.setAttribute('aria-hidden', 'true');
    }

    function openNotification() {
        closeProfile();
        const menu = $('#notificationMenu');
        const dropdown = $('#notificationDropdown');
        menu?.classList.add('is-open');
        setExpanded($('#notificationButton'), true);
        dropdown?.setAttribute('aria-hidden', 'false');
    }

    function openProfile() {
        closeNotification();
        const menu = $('#profileMenu');
        const dropdown = $('#profileDropdown');
        menu?.classList.add('is-open');
        setExpanded($('#profileButton'), true);
        dropdown?.setAttribute('aria-hidden', 'false');
    }

    function closeMenus() {
        closeNotification();
        closeProfile();
    }

    function syncMobileScrollLock() {
        const sidebar = $('#dashboardSidebar');
        const overlay = $('#mobileOverlay');
        const isMobile = window.innerWidth <= 900;
        const isSidebarOpen = isMobile && sidebar?.classList.contains('mobile-open');
        const isOverlayVisible = isMobile && overlay?.classList.contains('mobile-visible');
        const shouldLock = Boolean(isSidebarOpen || isOverlayVisible);

        document.body.classList.toggle('rms-mobile-scroll-lock', shouldLock);
        document.documentElement.classList.toggle('rms-mobile-scroll-lock', shouldLock);

        if (!shouldLock) {
            document.body.style.removeProperty('overflow');
            document.documentElement.style.removeProperty('overflow');
        }
    }

    function openMobileSidebar() {
        $('#dashboardSidebar')?.classList.add('mobile-open');
        $('#mobileOverlay')?.classList.add('mobile-visible');
        syncMobileScrollLock();
    }

    function closeMobileSidebar() {
        $('#dashboardSidebar')?.classList.remove('mobile-open');
        $('#mobileOverlay')?.classList.remove('mobile-visible');
        syncMobileScrollLock();
    }

    window.openMobileSidebar = openMobileSidebar;
    window.closeMobileSidebar = closeMobileSidebar;

    function ensureToastStack() {
        let stack = $('#rmsToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'rmsToastStack';
            stack.className = 'rms-toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function toast(type, title, message = '', duration = 3200) {
        const stack = ensureToastStack();
        const id = ++state.toastId;
        const item = document.createElement('article');
        item.className = `rms-toast ${type}`;
        item.dataset.toastId = String(id);
        item.innerHTML = `
            <div class="rms-toast-icon" aria-hidden="true"></div>
            <div class="rms-toast-copy"><strong></strong><span></span></div>
            <button type="button" class="rms-toast-close" aria-label="Tutup">×</button>
            <div class="rms-toast-progress"><i></i></div>
        `;
        const icon = $('.rms-toast-icon', item);
        icon.textContent = type === 'success' ? '✓' : type === 'error' ? '!' : type === 'warning' ? '!' : type === 'loading' ? '' : 'i';
        $('strong', item).textContent = title;
        $('span', item).textContent = message;
        const close = () => {
            if (!item.isConnected) return;
            item.classList.add('is-leaving');
            setTimeout(() => item.remove(), 300);
        };
        $('.rms-toast-close', item).addEventListener('click', close, { once: true });
        stack.appendChild(item);
        if (duration > 0) {
            item.style.setProperty('--rms-toast-duration', `${duration}ms`);
            setTimeout(close, duration);
        }
        return { close };
    }

    function ensureConfirm() {
        let root = $('#rmsConfirm');
        if (root) return root;
        root = document.createElement('div');
        root.id = 'rmsConfirm';
        root.className = 'rms-confirm-root';
        root.innerHTML = `
            <div class="rms-confirm-card" role="dialog" aria-modal="true" aria-labelledby="rmsConfirmTitle">
                <div class="rms-confirm-badge">!</div>
                <h3 id="rmsConfirmTitle"></h3>
                <p></p>
                <div class="rms-confirm-actions">
                    <button type="button" class="rms-confirm-cancel">Batal</button>
                    <button type="button" class="rms-confirm-ok">Konfirmasi</button>
                </div>
            </div>
        `;
        document.body.appendChild(root);
        return root;
    }

    function closeConfirm(value = false) {
        const root = $('#rmsConfirm');
        root?.classList.remove('is-open');
        const resolver = state.confirmResolver;
        state.confirmResolver = null;
        resolver?.(value);
    }

    function confirm(options = {}) {
        const root = ensureConfirm();
        $('h3', root).textContent = options.title || 'Konfirmasi';
        $('p', root).textContent = options.message || 'Lanjutkan tindakan ini?';
        $('.rms-confirm-ok', root).textContent = options.confirmText || 'Konfirmasi';
        $('.rms-confirm-cancel', root).textContent = options.cancelText || 'Batal';
        root.classList.add('is-open');
        return new Promise(resolve => { state.confirmResolver = resolve; });
    }

    window.RMS = window.RMS || {};
    window.RMS.toast = {
        success: (t, m, d) => toast('success', t, m, d),
        error: (t, m, d) => toast('error', t, m, d),
        warning: (t, m, d) => toast('warning', t, m, d),
        info: (t, m, d) => toast('info', t, m, d),
        loading: (t, m) => toast('loading', t, m, 0),
    };
    window.RMS.confirm = Object.assign((options) => confirm(options), { close: () => closeConfirm(false) });

    document.addEventListener('click', event => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        if (target.closest('#notificationButton')) {
            event.preventDefault();
            event.stopPropagation();
            $('#notificationMenu')?.classList.contains('is-open') ? closeNotification() : openNotification();
            return;
        }

        if (target.closest('#profileButton')) {
            event.preventDefault();
            event.stopPropagation();
            $('#profileMenu')?.classList.contains('is-open') ? closeProfile() : openProfile();
            return;
        }

        if (target.closest('#notificationClose')) {
            event.preventDefault();
            closeNotification();
            return;
        }

        if (target.closest('#notificationMarkRead')) {
            event.preventDefault();
            document.querySelectorAll('#notificationDropdown .notification-unread-dot').forEach(el => el.remove());
            $('.notification-dot')?.classList.add('is-hidden');
            $('.notification-count')?.classList.add('is-hidden');
            RMS.toast.info('Notifikasi', 'Semua notifikasi ditandai sudah dibaca.');
            return;
        }

        if (target.closest('.nav-item')) {
            // Selecting a workspace on mobile closes the drawer immediately.
            // This prevents the body from staying scroll-locked after Livewire morphs the content.
            if (window.innerWidth <= 900) closeMobileSidebar();
            return;
        }

        if (target.closest('.mobile-menu')) {
            event.preventDefault();
            openMobileSidebar();
            return;
        }

        if (target.closest('.mobile-close')) {
            event.preventDefault();
            closeMobileSidebar();
            return;
        }

        if (target.closest('#mobileOverlay')) {
            closeMobileSidebar();
            return;
        }

        if (target.closest('#rmsConfirm .rms-confirm-cancel')) return closeConfirm(false);
        if (target.closest('#rmsConfirm .rms-confirm-ok')) return closeConfirm(true);
        if (target.id === 'rmsConfirm') return closeConfirm(false);

        if (target.closest('[data-store-action="add"]')) {
            RMS.toast.info('Store Workspace', 'Form Add Store akan kita aktifkan pada tahap CRUD.');
            return;
        }

        if (target.closest('[data-store-action="manage"]')) {
            RMS.toast.info('Store Preview', 'Detail Store akan kita aktifkan pada tahap berikutnya.');
            return;
        }

        if (target.closest('[data-store-action="settings"]')) {
            RMS.toast.info('Store Settings', 'Pengaturan Store akan kita aktifkan pada tahap berikutnya.');
            return;
        }

        if (!target.closest('#notificationMenu') && !target.closest('#profileMenu')) closeMenus();
    });

    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            const search = document.querySelector('.top-search input');
            if (search) {
                search.focus();
                search.select();
            }
            return;
        }

        if (event.key === 'Escape') {
            closeMenus();
            closeMobileSidebar();
        }
    });

    document.addEventListener('livewire:navigated', () => {
        closeMenus();
        closeMobileSidebar();
        document.querySelectorAll('.rms-content-enter').forEach(el => {
            el.classList.remove('rms-content-enter');
            void el.offsetWidth;
            el.classList.add('rms-content-enter');
        });
    });

    function refreshContentAnimation() {
        document.querySelectorAll('.rms-content-enter').forEach(el => {
            el.classList.remove('rms-content-enter');
            void el.offsetWidth;
            el.classList.add('rms-content-enter');
        });
    }

    document.addEventListener('livewire:init', () => {
        if (window.Livewire?.hook) {
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    // Livewire can morph the DOM without firing livewire:navigated.
                    // Always restore the mobile scroll state after a workspace change.
                    if (window.innerWidth <= 900) {
                        closeMobileSidebar();
                    } else {
                        syncMobileScrollLock();
                    }

                    refreshContentAnimation();
                });
            });
        }

        syncMobileScrollLock();
    });

    window.addEventListener('resize', syncMobileScrollLock);
    window.addEventListener('pageshow', syncMobileScrollLock);
    window.addEventListener('orientationchange', () => {
        setTimeout(syncMobileScrollLock, 50);
    });

    // Safety net for Livewire DOM replacement.
    const rmsObserver = new MutationObserver(() => {
        syncMobileScrollLock();
    });

    rmsObserver.observe(document.body, { childList: true, subtree: true });
})();
