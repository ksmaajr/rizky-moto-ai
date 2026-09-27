/*
 * RMS Dashboard — Mobile Sidebar V4
 *
 * Important:
 * Livewire can morph/replace the sidebar after wire:click.
 * Therefore this script NEVER keeps stale references to the sidebar,
 * overlay, or hamburger button.
 */

(() => {
    const MOBILE_QUERY = '(max-width: 900px)';

    const getSidebar = () => document.getElementById('dashboardSidebar');
    const getOverlay = () => document.getElementById('mobileOverlay');
    const getMenu = () => document.querySelector('.mobile-menu');

    const isMobile = () => window.matchMedia(MOBILE_QUERY).matches;

    const syncButton = (open) => {
        const menu = getMenu();

        if (!menu) return;

        menu.classList.toggle('is-sidebar-open', open);
        menu.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.setAttribute('aria-label', open ? 'Close sidebar' : 'Open sidebar');
    };

    const setOpenState = (open) => {
        const sidebar = getSidebar();
        const overlay = getOverlay();

        if (!sidebar || !overlay) return;

        if (open && isMobile()) {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('mobile-visible');
            document.documentElement.classList.add('rms-sidebar-lock');
            document.body.classList.add('rms-sidebar-lock');
            syncButton(true);
            return;
        }

        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('mobile-visible');
        document.documentElement.classList.remove('rms-sidebar-lock');
        document.body.classList.remove('rms-sidebar-lock');
        syncButton(false);
    };

    const open = () => setOpenState(true);
    const close = () => setOpenState(false);
    const toggle = () => {
        const sidebar = getSidebar();
        if (!sidebar) return;
        setOpenState(!sidebar.classList.contains('mobile-open'));
    };

    // Keep compatibility with the existing Blade onclick attributes.
    window.openMobileSidebar = open;
    window.closeMobileSidebar = close;

    // Event delegation survives Livewire DOM morphing.
    document.addEventListener('click', (event) => {
        const menu = event.target.closest?.('.mobile-menu');
        const closeButton = event.target.closest?.('.mobile-close');
        const overlay = event.target.closest?.('#mobileOverlay');

        // Settings navigation is completely independent from the drawer
        // toggle. Clicking it must never open/close the drawer.
        if (event.target.closest?.('.rms-settings-nav')) {
            return;
        }

        if (menu) {
            event.preventDefault();
            event.stopPropagation();
            toggle();
            return;
        }

        if (closeButton) {
            event.preventDefault();
            event.stopPropagation();
            close();
            return;
        }

        if (overlay) {
            close();
        }
    }, true);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) close();
    });

    const restoreAfterLivewire = () => {
        // Read the CURRENT DOM after morphing, not a stale pre-morph node.
        const sidebar = getSidebar();

        if (sidebar?.dataset.rmsKeepOpen === '1' && isMobile()) {
            requestAnimationFrame(() => {
                setOpenState(true);
            });
        }
    };

    // Livewire event emitted by dashboard navigation methods.
    const registerLivewire = () => {
        if (!window.Livewire || window.__rmsMobileSidebarV5) return;

        window.__rmsMobileSidebarV5 = true;

        /*
         * Explicit KEEP event:
         * Used by top-level navigation and the Settings parent button.
         */
        Livewire.on('mobile-sidebar-keep-open', () => {
            const sidebar = getSidebar();

            if (sidebar) {
                sidebar.dataset.rmsKeepOpen = '1';
            }

            requestAnimationFrame(() => {
                if (isMobile()) {
                    setOpenState(true);
                }
            });
        });

        /*
         * Explicit CLOSE event:
         * Used by Settings sub-items. The selected workspace can change,
         * but the drawer itself should close on mobile.
         */
        Livewire.on('mobile-sidebar-close', () => {
            const sidebar = getSidebar();

            if (sidebar) {
                sidebar.dataset.rmsKeepOpen = '0';
            }

            requestAnimationFrame(() => {
                setOpenState(false);
            });
        });

        /*
         * Preserve the drawer only when the current DOM explicitly carries
         * data-rms-keep-open="1". Do NOT infer "keep open" merely because
         * the drawer happened to be open before a Livewire request.
         */
        Livewire.hook('commit', ({ succeed }) => {
            const sidebarBefore = getSidebar();
            const shouldKeepOpen = sidebarBefore?.dataset.rmsKeepOpen === '1';

            succeed(() => {
                requestAnimationFrame(() => {
                    const sidebarAfter = getSidebar();

                    if (shouldKeepOpen && isMobile()) {
                        if (sidebarAfter) {
                            sidebarAfter.dataset.rmsKeepOpen = '1';
                        }

                        setOpenState(true);
                    } else if (isMobile()) {
                        if (sidebarAfter) {
                            sidebarAfter.dataset.rmsKeepOpen = '0';
                        }

                        setOpenState(false);
                    }
                });
            });
        });
    };

    if (window.Livewire) {
        registerLivewire();
    } else {
        document.addEventListener('livewire:init', registerLivewire, { once: true });
    }

    // Initial state: closed on mobile.
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = getSidebar();

        if (sidebar) {
            sidebar.dataset.rmsKeepOpen = '0';
        }

        setOpenState(false);
    }, { once: true });
})();
