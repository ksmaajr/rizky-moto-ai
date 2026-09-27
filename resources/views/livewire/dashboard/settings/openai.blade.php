<div class="rms-settings-page rms-content-enter">
    <div class="rms-settings-page-head">
        <div>
            <span class="section-kicker">SYSTEM / SETTINGS</span>
            <h1>OpenAI API</h1>
            <p>Kelola koneksi creative engine yang digunakan oleh Product Generator.</p>
        </div>
        <div class="rms-settings-head-badge">
            <i></i>
            INTERNAL CONFIGURATION
        </div>
    </div>

<section class="rms-api-settings-card">
                        <div class="rms-api-settings-card-head">
                            <div class="rms-api-settings-brand">
                                <span class="rms-api-settings-mark">AI</span>
                                <div>
                                    <span>CREATIVE ENGINE</span>
                                    <strong>OpenAI API</strong>
                                    <small>Connection & generation configuration</small>
                                </div>
                            </div>

                            <div class="rms-api-connection-badge">
                                <i></i>
                                Ready to configure
                            </div>
                        </div>

                        <div class="rms-api-settings-divider"></div>

                        <div class="rms-api-settings-grid">
                            <label class="rms-api-field">
                                <span>API Key</span>
                                <div class="rms-api-input-wrap">
                                    <input type="password" value="sk-proj-••••••••••••••••••••" readonly>
                                    <button type="button" aria-label="Show API key">View</button>
                                </div>
                                <small>API key akan digunakan oleh server untuk proses generate gambar.</small>
                            </label>

                            <label class="rms-api-field">
                                <span>Image Model</span>
                                <select>
                                    <option>OpenAI Image Generation</option>
                                </select>
                                <small>Model yang digunakan Product Generator.</small>
                            </label>
                        </div>

                        <div class="rms-api-info-strip">
                            <div>
                                <span class="rms-api-info-icon">✓</span>
                                <div>
                                    <strong>API configuration</strong>
                                    <small>UI sudah disiapkan untuk koneksi OpenAI. Penyimpanan credential kita sambungkan pada tahap backend.</small>
                                </div>
                            </div>

                            <button type="button" class="rms-api-test-button">
                                Test Connection
                                <span>→</span>
                            </button>
                        </div>
                    </section>
                <div class="rms-openai-logs">
            <div class="rms-openai-logs-head">
                <div class="rms-openai-logs-title">
                    <span class="rms-openai-logs-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="m8 9 3 3-3 3"/>
                            <path d="M13 15h4"/>
                            <rect x="3" y="4" width="18" height="16" rx="3"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Connection Logs</strong>
                        <small>Aktivitas test koneksi OpenAI akan tampil di panel ini.</small>
                    </div>
                </div>
                <div class="rms-openai-log-actions">
                    <span class="rms-openai-log-status"><i></i> WAITING FOR TEST</span>
                    <button type="button" class="rms-openai-log-clear">Clear</button>
                </div>
            </div>
            <div class="rms-openai-log-console">
                <div class="rms-openai-log-empty">
                    <span class="rms-openai-log-empty-icon">⌁</span>
                    <strong>Belum ada test koneksi</strong>
                    <small>Jalankan <b>Test Connection</b> untuk melihat proses, response, dan status koneksi OpenAI secara realtime.</small>
                </div>
            </div>
            <div class="rms-openai-log-footer">
                <span><i></i> SERVER CONNECTION MONITOR</span>
                <span>Awaiting backend integration</span>
            </div>
        </div>
</section>
</div>
