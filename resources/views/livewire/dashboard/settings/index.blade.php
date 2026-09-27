<div class="rms-settings-page" x-data="{ showApiKey: @entangle('showApiKey') }">
    <div class="rms-settings-page-head">
        <div>
            <span class="section-kicker">SYSTEM / SETTINGS</span>
            <h1 x-text="$wire.activeTab === 'openai' ? 'OpenAI API' : 'General Settings'"></h1>
            <p x-text="$wire.activeTab === 'openai'
                ? 'Kelola koneksi creative engine yang digunakan oleh Product Generator.'
                : 'Kelola preferensi dasar workspace internal Rizky Moto Shop.'"></p>
        </div>

        <div class="rms-settings-head-badge">
            <i></i>
            INTERNAL CONFIGURATION
        </div>
    </div>

    <div class="rms-settings-layout">
        <aside class="rms-settings-tabs" aria-label="Settings navigation">
            <div class="rms-settings-tabs-head">
                <span>SETTINGS</span>
                <small>Workspace configuration</small>
            </div>

            <button
                type="button"
                class="rms-settings-tab"
                :class="{ 'is-active': $wire.activeTab === 'general' }"
                wire:click="selectTab('general')"
            >
                <span class="rms-settings-tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.5v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.8-1.8.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6.5v-2.5h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.8-1.8.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V4h2.5v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.2v2.5h-.2a1.7 1.7 0 0 0-1.5 1.4Z"/>
                    </svg>
                </span>
                <span>
                    <strong>General</strong>
                    <small>Workspace preferences</small>
                </span>
                <b>→</b>
            </button>

            <button
                type="button"
                class="rms-settings-tab"
                :class="{ 'is-active': $wire.activeTab === 'openai' }"
                wire:click="selectTab('openai')"
            >
                <span class="rms-settings-tab-icon rms-settings-ai">AI</span>
                <span>
                    <strong>OpenAI API</strong>
                    <small>Creative engine connection</small>
                </span>
                <i class="rms-settings-tab-status"></i>
            </button>

            <div class="rms-settings-tabs-footer">
                <span><i></i> Internal workspace</span>
                <span>BUILD 01.00</span>
            </div>
        </aside>

        <section class="rms-settings-content">
            @if ($activeTab === 'openai')
                <div class="rms-settings-panel rms-settings-panel-enter" wire:key="settings-openai-panel">
                    <div class="rms-settings-panel-head">
                        <div class="rms-settings-panel-brand">
                            <span class="rms-settings-panel-mark">AI</span>
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

                    <div class="rms-settings-divider"></div>

                    <div class="rms-settings-form">
                        <label class="rms-settings-field rms-settings-field-wide">
                            <span>API Key</span>
                            <div class="rms-api-input-wrap">
                                <input
                                    :type="showApiKey ? 'text' : 'password'"
                                    wire:model.live="apiKey"
                                    placeholder="sk-proj-••••••••••••••••"
                                    autocomplete="off"
                                >
                                <button
                                    type="button"
                                    @click="showApiKey = !showApiKey"
                                    x-text="showApiKey ? 'Hide' : 'Show'"
                                ></button>
                            </div>
                            <small>API key akan digunakan server untuk proses generate gambar.</small>
                        </label>

                        <label class="rms-settings-field">
                            <span>Image Model</span>
                            <div class="rms-settings-select-wrap">
                                <select wire:model.live="imageModel">
                                    <option>OpenAI Image Generation</option>
                                    <option>GPT Image</option>
                                </select>
                                <span>⌄</span>
                            </div>
                            <small>Model image generation yang digunakan Product Generator.</small>
                        </label>

                        <label class="rms-settings-field">
                            <span>Default Aspect Ratio</span>
                            <div class="rms-settings-select-wrap">
                                <select wire:model.live="defaultAspectRatio">
                                    <option value="1:1">1:1 — Square</option>
                                    <option value="4:5">4:5 — Portrait</option>
                                    <option value="3:4">3:4 — Portrait</option>
                                    <option value="16:9">16:9 — Landscape</option>
                                    <option value="9:16">9:16 — Vertical</option>
                                </select>
                                <span>⌄</span>
                            </div>
                            <small>Nilai awal pada Product Generator.</small>
                        </label>

                        <label class="rms-settings-field">
                            <span>Default Quality</span>
                            <div class="rms-settings-select-wrap">
                                <select wire:model.live="defaultQuality">
                                    <option value="standard">Standard Quality</option>
                                    <option value="high">High Quality</option>
                                </select>
                                <span>⌄</span>
                            </div>
                            <small>Kualitas output default untuk generation baru.</small>
                        </label>
                    </div>

                    <div class="rms-settings-notice">
                        <div>
                            <span class="rms-settings-notice-icon">✓</span>
                            <div>
                                <strong>Secure configuration</strong>
                                <small>Credential akan disimpan melalui konfigurasi server pada tahap backend. Jangan menaruh API key langsung di source code.</small>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rms-settings-dark-button"
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            wire:target="testConnection"
                        >
                            <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                            <span wire:loading wire:target="testConnection">Testing...</span>
                            <b>→</b>
                        </button>
                    </div>

                    <div class="rms-settings-panel-footer">
                        <span>OpenAI connection</span>
                        <button
                            type="button"
                            class="rms-settings-save"
                            wire:click="saveOpenAi"
                        >
                            Save Configuration
                            <span>✓</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="rms-settings-panel rms-settings-panel-enter" wire:key="settings-general-panel">
                    <div class="rms-general-hero">
                        <div>
                            <span>WORKSPACE PREFERENCES</span>
                            <h2>Keep your creative workspace consistent.</h2>
                            <p>Pengaturan dasar dashboard internal Rizky Moto Shop. Konfigurasi engine AI tersedia pada OpenAI API.</p>
                        </div>
                        <div class="rms-general-orbit">
                            <span>RMS</span>
                        </div>
                    </div>

                    <div class="rms-settings-preference-grid">
                        <article>
                            <span class="rms-preference-icon">⌘</span>
                            <div>
                                <strong>Single Workspace</strong>
                                <small>Semua fitur berjalan di dashboard tanpa pindah halaman.</small>
                            </div>
                            <em>ON</em>
                        </article>

                        <article>
                            <span class="rms-preference-icon">AI</span>
                            <div>
                                <strong>Template Based</strong>
                                <small>Generator menggunakan Store dan Template sebagai sumber konfigurasi.</small>
                            </div>
                            <em>READY</em>
                        </article>

                        <article>
                            <span class="rms-preference-icon">●</span>
                            <div>
                                <strong>System Status</strong>
                                <small>Workspace siap digunakan untuk konfigurasi berikutnya.</small>
                            </div>
                            <em>READY</em>
                        </article>
                    </div>

                    <div class="rms-settings-panel-footer">
                        <span>Workspace preferences</span>
                        <button type="button" class="rms-settings-save" wire:click="saveGeneral">
                            Save Preferences
                            <span>✓</span>
                        </button>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
