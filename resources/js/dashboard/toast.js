(() => {
    if (window.RMS?.toast) return;

    const state = { host: null, timer: null, progressTimer: null };

    const tones = {
        success: { icon: '✓', accent: '#16a34a' },
        error: { icon: '!', accent: '#dc2626' },
        warning: { icon: '!', accent: '#d97706' },
        info: { icon: 'i', accent: '#2563eb' },
        loading: { icon: '↻', accent: '#18181b' },
    };

    function ensureHost() {
        if (state.host) return state.host;
        const host = document.createElement('div');
        host.id = 'rms-global-toast-host';
        host.setAttribute('aria-live', 'polite');
        host.innerHTML = `
            <div class="rms-global-toast" hidden>
                <div class="rms-global-toast-icon"></div>
                <div class="rms-global-toast-copy"><strong></strong><span></span></div>
                <button type="button" class="rms-global-toast-close" aria-label="Tutup">×</button>
                <div class="rms-global-toast-progress"><i></i></div>
            </div>`;
        document.body.appendChild(host);
        state.host = host;
        host.querySelector('.rms-global-toast-close').addEventListener('click', hide);
        return host;
    }

    function show(detail = {}) {
        const host = ensureHost();
        const toast = host.querySelector('.rms-global-toast');
        const tone = tones[detail.type] || tones.success;
        clearTimeout(state.timer);
        clearInterval(state.progressTimer);
        toast.hidden = false;
        toast.dataset.type = detail.type || 'success';
        toast.querySelector('.rms-global-toast-icon').textContent = tone.icon;
        toast.querySelector('.rms-global-toast-copy strong').textContent = detail.title || 'Berhasil';
        toast.querySelector('.rms-global-toast-copy span').textContent = detail.message || '';
        toast.querySelector('.rms-global-toast-progress i').style.width = '100%';
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        const duration = Number(detail.duration ?? 4500);
        if (duration <= 0) return;
        const started = performance.now();
        state.progressTimer = setInterval(() => {
            const pct = Math.max(0, 100 - ((performance.now() - started) / duration) * 100);
            toast.querySelector('.rms-global-toast-progress i').style.width = `${pct}%`;
        }, 40);
        state.timer = setTimeout(hide, duration);
    }

    function hide() {
        clearTimeout(state.timer);
        clearInterval(state.progressTimer);
        if (!state.host) return;
        const toast = state.host.querySelector('.rms-global-toast');
        toast.classList.remove('is-visible');
        setTimeout(() => { toast.hidden = true; }, 220);
    }

    window.RMS = window.RMS || {};
    window.RMS.toast = {
        success: (title, message = '') => show({ type: 'success', title, message }),
        error: (title, message = '') => show({ type: 'error', title, message }),
        warning: (title, message = '') => show({ type: 'warning', title, message }),
        info: (title, message = '') => show({ type: 'info', title, message }),
        loading: (title, message = '') => show({ type: 'loading', title, message, duration: 0 }),
        close: hide,
    };

    function handleEvent(event) {
        show(event.detail || {});
    }

    document.addEventListener('livewire:init', () => {
        if (window.Livewire?.on) {
            window.Livewire.on('toast', (detail) => show(detail || {}));
        }
    });

    window.addEventListener('toast', handleEvent);
})();
