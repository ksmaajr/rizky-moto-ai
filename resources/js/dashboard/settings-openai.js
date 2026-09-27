document.addEventListener('click', (event) => {
    const clear = event.target.closest('.rms-openai-log-clear');
    if (!clear) return;
    const consoleBox = clear.closest('.rms-openai-logs')?.querySelector('.rms-openai-log-console');
    if (consoleBox) {
        consoleBox.innerHTML = '<div class="rms-openai-log-empty"><span class="rms-openai-log-empty-icon">⌁</span><strong>Belum ada test koneksi</strong><small>Log dibersihkan. Jalankan Test Connection untuk memulai pemeriksaan.</small></div>';
    }
});
