<div class="rms-settings-page" x-data="{ showApiKey: @entangle('showApiKey') }">
    <div class="rms-settings-page-head">
        <div>
            <span class="section-kicker">SYSTEM / SETTINGS</span>
            <h1 x-text="$wire.activeTab === 'provider' ? 'AI Provider Configuration' : 'General Settings'"></h1>
            <p x-text="$wire.activeTab === 'provider'
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
                :class="{ 'is-active': $wire.activeTab === 'provider' }"
                wire:click="selectTab('provider')"
            >
                <span class="rms-settings-tab-icon rms-settings-ai">AI</span>
                <span>
                    <strong>AI Provider</strong>
                    <small>Engine &amp; credentials</small>
                </span>
                <i class="rms-settings-tab-status"></i>
            </button>

            <button
                type="button"
                class="rms-settings-tab"
                :class="{ 'is-active': $wire.activeTab === 'activity' }"
                wire:click="selectTab('activity')"
            >
                <span class="rms-settings-tab-icon rms-settings-activity-tab-icon">◷</span>
                <span>
                    <strong>Activity Log</strong>
                    <small>Global system audit</small>
                </span>
                <i class="rms-settings-tab-status"></i>
            </button>

            <div class="rms-settings-tabs-footer">
                <span><i></i> Internal workspace</span>
                <span>BUILD 01.00</span>
            </div>
        </aside>

        <section class="rms-settings-content">
            @if (in_array($activeTab, ['provider', 'activity'], true))
                <div
                    class="rms-settings-panel rms-settings-panel-enter"
                    wire:key="settings-openai-panel"
                    x-data="{
                    }"
                >

                    @if ($activeTab === 'provider')

                    {{-- AI PROVIDER HEADER --}}
                    <div class="rms-settings-panel-head rms-openai-head-upgraded">
                        <div class="rms-settings-panel-brand">
                            <span class="rms-settings-panel-mark rms-openai-mark">AI</span>

                            <div>
                                <span>AI PROVIDER</span>
                                <strong>AI Provider Configuration</strong>
                                <small>Provider, credentials &amp; failover configuration</small>
                            </div>
                        </div>

                        <div class="rms-api-connection-badge">
                            <i></i>
                            Ready to configure
                        </div>
                    </div>

                    <div class="rms-settings-divider"></div>

                    {{-- CONFIGURATION --}}
                    <div class="rms-settings-form rms-openai-form-upgraded">


                        {{-- VERCEL GATEWAY KEY POOL --}}
                        @php
                            $vercelGatewayKeys = is_iterable($this->vercelGatewayKeys ?? null)
                                ? $this->vercelGatewayKeys
                                : [];
                        @endphp

                        <div class="rms-vg-key-manager-root" x-data="{ addOpen: false, showNewKey: false, deleteOpen: false, deleteId: null, deleteName: '', deleting: false, confirmDelete(id, name) { this.deleteId = id; this.deleteName = name || 'API key ini'; this.deleteOpen = true; }, closeDelete() { if (!this.deleting) this.deleteOpen = false; }, async deleteKey() { if (!this.deleteId || this.deleting) return; this.deleting = true; try { await $wire.removeVercelGatewayKey(this.deleteId); this.deleteOpen = false; } catch (e) { console.error(e); } finally { this.deleting = false; this.deleteId = null; } } }" @keydown.escape.window="closeDelete()">
                        <section class="rms-vg-key-manager">
                            <div class="rms-vg-section-head">
                                <div class="rms-vg-section-title">
                                    <span class="rms-vg-section-icon">KEY</span>
                                    <div>
                                        <strong>Vercel API Key Pool</strong>
                                        <small>Tambahkan beberapa key. Generator akan memilih key yang tersedia secara otomatis.</small>
                                    </div>
                                </div>

                                <div class="rms-vg-pool-badge">
                                    <i></i>
                                    <span>{{ count($vercelGatewayKeys) }} KEY{{ count($vercelGatewayKeys) === 1 ? '' : 'S' }}</span>
                                </div>
                            </div>

                            <div class="rms-vg-key-notice">
                                <span class="rms-vg-notice-mark">↻</span>
                                <div>
                                    <strong>Automatic failover enabled</strong>
                                    <small>
                                        Jika sebuah key terkena credit, budget, rate limit, atau authentication error,
                                        key tersebut dapat ditandai otomatis dan generator mencoba key berikutnya.
                                    </small>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="rms-vg-add-toggle"
                                @click="addOpen = !addOpen"
                                :class="{ 'is-open': addOpen }"
                            >
                                <span class="rms-vg-add-toggle-icon">+</span>
                                <span>
                                    <strong>Add Vercel API Key</strong>
                                    <small>Tambahkan credential baru ke pool</small>
                                </span>
                                <b x-text="addOpen ? '−' : '+'"></b>
                            </button>

                            <div
                                x-show="addOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-250"
                                x-transition:enter-start="opacity-0 -translate-y-3 scale-[.985]"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 -translate-y-2 scale-[.985]"
                                class="rms-vg-add-panel"
                            >
                                <div class="rms-vg-add-grid">
                                    <label>
                                        <span>KEY NAME</span>
                                        <input
                                            type="text"
                                            wire:model.live="newVercelKeyName"
                                            placeholder="Contoh: Vercel Main"
                                            autocomplete="off"
                                        >
                                    </label>

                                    <label class="rms-vg-key-input-field">
                                        <span>VERCEL API KEY</span>
                                        <div>
                                            <input
                                                :type="showNewKey ? 'text' : 'password'"
                                                wire:model.live="newVercelApiKey"
                                                placeholder="vck_••••••••••••••••"
                                                autocomplete="new-password"
                                                spellcheck="false"
                                            >
                                            <button type="button" @click="showNewKey = !showNewKey">
                                                <span x-text="showNewKey ? 'Hide' : 'Show'"></span>
                                            </button>
                                        </div>
                                    </label>
                                </div>

                                <div class="rms-vg-add-footer">
                                    <small>
                                        <i></i>
                                        Credential terenkripsi di backend. Raw API key tidak pernah ditulis ke activity log.
                                    </small>

                                    <button
                                        type="button"
                                        class="rms-vg-save-key"
                                        wire:click="addVercelGatewayKey"
                                        wire:loading.attr="disabled"
                                        wire:target="addVercelGatewayKey"
                                    >
                                        <span wire:loading.remove wire:target="addVercelGatewayKey">
                                            <b>+</b> Add &amp; Save Key
                                        </span>
                                        <span wire:loading wire:target="addVercelGatewayKey" class="rms-vg-button-loading">
                                            <i></i> Saving key...
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="rms-vg-key-list">
                                @forelse ($vercelGatewayKeys as $key)
                                    @php
                                        $keyId = $key['id'] ?? $key->id ?? null;
                                        $keyName = $key['name'] ?? $key->name ?? ('Vercel Key #' . $keyId);
                                        $keyStatus = $key['status'] ?? $key->status ?? 'active';
                                        $keyMasked = $key['masked_key'] ?? $key->masked_key ?? 'vck_••••••••';
                                        $keyRequests = (int) ($key['request_count'] ?? $key->request_count ?? 0);
                                        $keySuccess = (int) ($key['success_count'] ?? $key->success_count ?? 0);
                                        $keyFailure = (int) ($key['failure_count'] ?? $key->failure_count ?? 0);
                                        $keyLastUsed = $key['last_used_at'] ?? $key->last_used_at ?? null;
                                    @endphp

                                    <article
                                        class="rms-vg-key-card is-{{ $keyStatus }}"
                                        wire:key="vercel-gateway-key-{{ $keyId }}"
                                        x-data="{ actionsOpen: false }"
                                        @click.outside="actionsOpen = false"
                                    >
                                        <div class="rms-vg-key-card-main">
                                            <div class="rms-vg-key-avatar">
                                                <span>AI</span>
                                                <i></i>
                                            </div>

                                            <div class="rms-vg-key-copy">
                                                <div class="rms-vg-key-name-row">
                                                    <strong>{{ $keyName }}</strong>
                                                    <span class="rms-vg-status rms-vg-status-{{ $keyStatus }}">
                                                        <i></i>
                                                        {{ strtoupper(str_replace('_', ' ', $keyStatus)) }}
                                                    </span>
                                                </div>

                                                <code>{{ $keyMasked }}</code>

                                                <div class="rms-vg-key-meta">
                                                    <span>{{ $keyRequests }} requests</span>
                                                    <span>{{ $keySuccess }} success</span>
                                                    <span>{{ $keyFailure }} failed</span>
                                                    @if ($keyLastUsed)
                                                        <span>Last used {{ $keyLastUsed }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="rms-vg-key-actions">
                                                <button
                                                    type="button"
                                                    class="rms-vg-delete-key"
                                                    @click.stop="confirmDelete({{ $keyId }}, @js($keyName))"
                                                    wire:loading.attr="disabled"
                                                    wire:target="removeVercelGatewayKey({{ $keyId }})"
                                                    title="Remove API key"
                                                >
                                                    <span wire:loading.remove wire:target="removeVercelGatewayKey({{ $keyId }})">×</span>
                                                    <span wire:loading wire:target="removeVercelGatewayKey({{ $keyId }})" class="rms-vg-mini-spinner"></span>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="rms-vg-test-key"
                                                    wire:click.stop="testVercelGatewayKey({{ $keyId }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="testVercelGatewayKey({{ $keyId }})"
                                                >
                                                    <span wire:loading.remove wire:target="testVercelGatewayKey({{ $keyId }})">Test</span>
                                                    <span wire:loading wire:target="testVercelGatewayKey({{ $keyId }})"><i></i></span>
                                                </button>

                                                <div class="rms-vg-more-wrap">
                                                    <button
                                                        type="button"
                                                        class="rms-vg-key-more"
                                                        @click.stop="actionsOpen = !actionsOpen"
                                                        :aria-expanded="actionsOpen"
                                                        title="Key actions"
                                                    >⋯</button>

                                                    <div
                                                        x-show="actionsOpen"
                                                        x-cloak
                                                        x-transition:enter="transition ease-out duration-150"
                                                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                        x-transition:leave="transition ease-in duration-100"
                                                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                                        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                                        class="rms-vg-key-menu"
                                                    >
                                                        <button type="button" @click="actionsOpen = false" wire:click="toggleVercelGatewayKey({{ $keyId }})">
                                                            {{ $keyStatus === 'disabled' ? 'Enable key' : 'Disable key' }}
                                                        </button>
                                                        <button type="button" @click="actionsOpen = false" wire:click="resetVercelGatewayKey({{ $keyId }})">
                                                            Reset status
                                                        </button>
                                                        <button type="button" class="is-danger" @click="actionsOpen = false; confirmDelete({{ $keyId }}, @js($keyName))">
                                                            Delete key
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if (!empty($key['last_error'] ?? $key->last_error ?? null))
                                            <div class="rms-vg-key-error">
                                                <span>!</span>
                                                <div>
                                                    <strong>{{ $key['last_error_type'] ?? $key->last_error_type ?? 'Connection issue' }}</strong>
                                                    <small>{{ $key['last_error'] ?? $key->last_error }}</small>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="rms-vg-key-progress">
                                            <span style="width: {{ $keyRequests > 0 ? min(100, round(($keySuccess / max(1, $keyRequests)) * 100)) : 0 }}%"></span>
                                        </div>
                                    </article>
                                @empty
                                    <div class="rms-vg-empty-keys">
                                        <button type="button" class="rms-vg-empty-orbit" @click="addOpen = true">
                                            <span>+</span>
                                        </button>
                                        <strong>No Vercel API key configured</strong>
                                        <p>Tambahkan key pertama untuk mengaktifkan automatic key pool.</p>
                                    </div>
                                @endforelse
                            </div>

                    <template x-teleport="body">
                        <div x-show="deleteOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="rms-vg-delete-overlay" @keydown.escape.window="closeDelete()">
                        <div class="rms-vg-delete-backdrop" @click="closeDelete()"></div>
                        <div class="rms-vg-delete-modal" x-show="deleteOpen" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-90 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2" @click.stop>
                            <div class="rms-vg-delete-orb"><span></span></div>
                            <div class="rms-vg-delete-copy">
                                <span class="rms-vg-delete-eyebrow">REMOVE API CREDENTIAL</span>
                                <h3>Hapus API key ini?</h3>
                                <p>Key <strong x-text="deleteName"></strong> akan dihapus dari pool dan tidak akan dipakai lagi oleh generator.</p>
                            </div>
                            <div class="rms-vg-delete-warning"><span>!</span><div><strong>Tindakan ini tidak dapat dibatalkan</strong><small>Riwayat activity tetap tersimpan untuk audit.</small></div></div>
                            <div class="rms-vg-delete-actions">
                                <button type="button" class="rms-vg-delete-cancel" @click="closeDelete()" :disabled="deleting">Batal</button>
                                <button type="button" class="rms-vg-delete-confirm" @click="deleteKey()" :disabled="deleting"><span x-show="!deleting">Hapus API Key</span><span x-show="deleting" x-cloak class="rms-vg-delete-loading"><i></i> Menghapus...</span></button>
                            </div>
                        </div>
                    </div>
                        </div>
                    </template>
                        </section>



                    {{-- TEST ALL VERCEL KEYS --}}
                    <div class="rms-vg-test-all-card rms-vg-test-all-card-full">
                        <div class="rms-vg-test-all-copy">
                            <div class="rms-vg-test-all-icon">
                                <span></span>
                                <i></i>
                            </div>
                            <div>
                                <strong>Gateway health check</strong>
                                <small>Test seluruh Vercel API key yang aktif sekaligus. Setiap hasil akan dicatat ke API Key Activity dan status key diperbarui otomatis.</small>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rms-settings-dark-button rms-test-connection-upgraded rms-vg-test-all-button"
                            wire:click="testAllVercelGatewayKeys"
                            wire:loading.attr="disabled"
                            wire:target="testAllVercelGatewayKeys"
                        >
                            <span wire:loading.remove wire:target="testAllVercelGatewayKeys">Test All Keys</span>
                            <span wire:loading wire:target="testAllVercelGatewayKeys" class="rms-vg-test-loading"><i></i> Testing keys...</span>
                            <b wire:loading.remove wire:target="testAllVercelGatewayKeys">→</b>
                        </button>
                    </div>



                        </div>


                    {{-- AGENT AI CONFIGURATION SHELL --}}
                    <section class="rms-ai-provider-section rms-agent-provider-card">
                        <div class="rms-ai-provider-section-head">
                            <div class="rms-ai-provider-section-title">
                                <span class="rms-ai-provider-icon rms-agent-icon">AG</span>
                                <div>
                                    <strong>Agent AI</strong>
                                    <small>GPT Image 2.5 melalui Agent backend. Credential Pool akan ditambahkan pada Stage 5.</small>
                                </div>
                            </div>
                            <span class="rms-ai-provider-status is-pending"><i></i> Pool setup next</span>
                        </div>

                        <div class="rms-ai-agent-grid">
                            <div class="rms-ai-agent-readiness">
                                <div class="rms-ai-readiness-orb"><span></span></div>
                                <div>
                                    <strong>Agent Credential Pool</strong>
                                    <p>Siapkan beberapa credential Agent AI agar sistem dapat melakukan rotation, cooldown, dan automatic failover tanpa konfigurasi ulang saat runtime.</p>
                                </div>
                            </div>

                            <div class="rms-ai-agent-actions">
                                <div class="rms-ai-agent-meta">
                                    <span><b>Credential Pool</b><em>Stage 5</em></span>
                                    <span><b>Connection Test</b><em>Stage 7</em></span>
                                    <span><b>Auto Rotation</b><em>Stage 8</em></span>
                                </div>
                                <button type="button" class="rms-ai-secondary-button" disabled>
                                    <span>Configure Agent Credentials</span>
                                    <small>Coming in next stage</small>
                                </button>
                            </div>
                        </div>
                    </section>

                    {{-- PROVIDER FAILOVER CONFIGURATION --}}
                    <section class="rms-ai-provider-section rms-failover-card">
                        <div class="rms-ai-provider-section-head">
                            <div class="rms-ai-provider-section-title">
                                <span class="rms-ai-provider-icon">↗</span>
                                <div>
                                    <strong>Provider Failover</strong>
                                    <small>Fondasi konfigurasi fallback untuk menjaga generation tetap siap 24/7.</small>
                                </div>
                            </div>
                            <span class="rms-ai-provider-status"><i></i> Ready to configure</span>
                        </div>

                        <div class="rms-ai-failover-grid">
                            <label class="rms-ai-config-field">
                                <span>PRIMARY PROVIDER</span>
                                <select wire:model.live="activeProvider">
                                    <option value="vercel">Vercel AI Gateway</option>
                                    <option value="agentkit">Agent AI</option>
                                </select>
                            </label>

                            <label class="rms-ai-config-field">
                                <span>FALLBACK PROVIDER</span>
                                <select wire:model.live="fallbackProvider">
                                    <option value="">No automatic fallback</option>
                                    <option value="vercel">Vercel AI Gateway</option>
                                    <option value="agentkit">Agent AI</option>
                                </select>
                            </label>

                            <label class="rms-ai-toggle-field">
                                <input type="checkbox" wire:model.live="allowProviderFallback">
                                <span class="rms-ai-toggle-ui"></span>
                                <div>
                                    <strong>Allow provider fallback</strong>
                                    <small>Gunakan provider cadangan jika seluruh credential provider utama tidak tersedia.</small>
                                </div>
                            </label>

                            <label class="rms-ai-toggle-field">
                                <input type="checkbox" wire:model.live="emergencyFallback">
                                <span class="rms-ai-toggle-ui"></span>
                                <div>
                                    <strong>Emergency fallback</strong>
                                    <small>Prioritaskan availability untuk kebutuhan mendadak ketika provider utama unavailable.</small>
                                </div>
                            </label>
                        </div>

                        <div class="rms-ai-provider-save-row">
                            <div>
                                <span class="rms-ai-live-dot"></span>
                                <span>Configuration is stored server-side and applied to new generations.</span>
                            </div>
                            <button type="button" class="rms-ai-save-button" wire:click="saveAiProviderConfiguration" wire:loading.attr="disabled" wire:target="saveAiProviderConfiguration">
                                <span wire:loading.remove wire:target="saveAiProviderConfiguration">Save AI Configuration <b>→</b></span>
                                <span wire:loading wire:target="saveAiProviderConfiguration"><i></i> Saving...</span>
                            </button>
                        </div>
                    </section>

                    @endif

                    {{-- ACTIVITY LOG PAGE --}}
                    @if ($activeTab === 'activity')
                        <div class="rms-settings-activity-head">
                            <div class="rms-settings-activity-mark">◷</div>
                            <div>
                                <span>OBSERVABILITY</span>
                                <strong>Global Activity Log</strong>
                                <small>Audit terpusat untuk worker, provider, credential, generation, failover, storage, dan system events.</small>
                            </div>
                            <div class="rms-settings-activity-live"><i></i> LIVE</div>
                        </div>
                    @endif

                    @if ($activeTab === 'activity')
                    {{-- GLOBAL ACTIVITY LOGS --}}
                    <section
                        class="rms-openai-logs rms-vg-activity rms-global-activity"
                        wire:poll.3s="refreshActivityLogs"
                        x-data="{
                            selected: [],
                            copied: false,
                            get checkboxes() {
                                return Array.from(this.$root.querySelectorAll('.global-log-select'));
                            },
                            get visibleIds() {
                                return this.checkboxes.map(el => String(el.value));
                            },
                            toggleAll() {
                                const ids = this.visibleIds;
                                const allSelected = ids.length > 0 && ids.every(id => this.selected.includes(id));
                                this.selected = allSelected
                                    ? this.selected.filter(id => !ids.includes(id))
                                    : [...new Set([...this.selected, ...ids])];
                            },
                            toggle(id) {
                                id = String(id);
                                this.selected = this.selected.includes(id)
                                    ? this.selected.filter(item => item !== id)
                                    : [...this.selected, id];
                            },
                            async copyLogs(mode = 'selected') {
                                const wanted = mode === 'all'
                                    ? this.checkboxes
                                    : this.checkboxes.filter(el => this.selected.includes(String(el.value)));

                                if (!wanted.length) return;

                                const text = wanted
                                    .map(el => {
                                        try {
                                            const bytes = Uint8Array.from(atob(el.dataset.copy), char => char.charCodeAt(0));
                                            return JSON.parse(new TextDecoder().decode(bytes));
                                        } catch (_) {
                                            return '';
                                        }
                                    })
                                    .filter(Boolean)
                                    .join('\n\n------------------------------------------------------------\n\n');

                                try {
                                    await navigator.clipboard.writeText(text);
                                    this.copied = true;
                                    window.setTimeout(() => this.copied = false, 1800);
                                } catch (_) {
                                    const area = document.createElement('textarea');
                                    area.value = text;
                                    area.style.position = 'fixed';
                                    area.style.opacity = '0';
                                    document.body.appendChild(area);
                                    area.select();
                                    document.execCommand('copy');
                                    area.remove();
                                    this.copied = true;
                                    window.setTimeout(() => this.copied = false, 1800);
                                }
                            }
                        }"
                    >
                        <div class="rms-openai-logs-head">
                            <div class="rms-openai-logs-title">
                                <span class="rms-openai-terminal">&gt;_</span>
                                <div>
                                    <strong>Global Activity</strong>
                                    <small>Audit terpusat untuk worker, provider, API key, generation, failover, dan error dari seluruh workspace.</small>
                                </div>
                            </div>

                            <div class="rms-openai-log-actions">
                                <span class="rms-global-scope-badge">GLOBAL</span>
                                <span class="rms-openai-log-counter {{ $this->activityLogCount > 0 ? 'has-events' : '' }}">
                                    <i></i>
                                    <span>
                                        {{ $this->activityLogCount }} EVENT{{ $this->activityLogCount === 1 ? '' : 'S' }}
                                    </span>
                                </span>

                                <button
                                    type="button"
                                    class="rms-global-copy-button"
                                    @click="copyLogs('selected')"
                                    :disabled="selected.length === 0"
                                    :class="{ 'is-disabled': selected.length === 0 }"
                                    title="Copy selected logs"
                                >
                                    <span x-show="!copied">Copy Selected (<span x-text="selected.length"></span>)</span>
                                    <span x-show="copied">Copied ✓</span>
                                </button>

                                <button
                                    type="button"
                                    class="rms-global-copy-all"
                                    @click="copyLogs('all')"
                                    :disabled="checkboxes.length === 0"
                                    title="Copy all visible logs"
                                >
                                    Copy Visible
                                </button>

                                <button
                                    type="button"
                                    wire:click="clearActivityLogs"
                                    wire:confirm="Hapus seluruh global activity log? Tindakan ini tidak dapat dibatalkan."
                                    wire:loading.attr="disabled"
                                    wire:target="clearActivityLogs"
                                    @disabled($this->activityLogCount === 0)
                                    class="{{ $this->activityLogCount === 0 ? 'is-disabled' : '' }}"
                                >
                                    <span wire:loading.remove wire:target="clearActivityLogs">Clear</span>
                                    <span wire:loading wire:target="clearActivityLogs">Clearing...</span>
                                </button>
                            </div>
                        </div>

                        <div class="rms-global-activity-toolbar">
                            <label>
                                <span>SEARCH</span>
                                <input type="search" wire:model.live.debounce.350ms="activitySearch" placeholder="Search activity..." autocomplete="off">
                            </label>

                            <label>
                                <span>CATEGORY</span>
                                <select wire:model.live="activityCategory">
                                    <option value="all">All</option>
                                    <option value="worker">Worker</option>
                                    <option value="api">API</option>
                                    <option value="generation">Generation</option>
                                    <option value="system">System</option>
                                </select>
                            </label>

                            <label>
                                <span>STATUS</span>
                                <select wire:model.live="activityStatus">
                                    <option value="all">All</option>
                                    <option value="success">Success</option>
                                    <option value="info">Info</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                </select>
                            </label>

                            <label>
                                <span>RANGE</span>
                                <select wire:model.live="activityTimeframe">
                                    <option value="all">All time</option>
                                    <option value="today">Today</option>
                                    <option value="7d">7 days</option>
                                    <option value="30d">30 days</option>
                                </select>
                            </label>

                            <div class="rms-global-select-actions">
                                <button type="button" @click="toggleAll()" :disabled="checkboxes.length === 0">
                                    <span x-text="visibleIds.length > 0 && visibleIds.every(id => selected.includes(id)) ? 'Deselect Visible' : 'Select Visible'"></span>
                                </button>
                                <span><strong x-text="selected.length"></strong> selected</span>
                            </div>
                        </div>

                        <div class="rms-vg-activity-list rms-global-activity-list">
                            @forelse ($this->activityLogs as $log)
                                @php
                                    $logStatus = $log['status'] ?? 'info';
                                    $logCategory = $log['category'] ?? 'system';
                                    $logTitle = $log['title'] ?? ($log['action'] ?? 'Activity');
                                    $logDescription = $log['description'] ?? '';
                                    $logUser = $log['user_name'] ?? 'System';
                                @endphp

                                <article class="rms-openai-log-item is-{{ $logStatus }}" wire:key="global-activity-{{ $log['id'] ?? $loop->index }}">
                                    <label class="rms-global-log-select-wrap" title="Select log">
                                        <input
                                            type="checkbox"
                                            class="global-log-select"
                                            value="{{ $log['id'] ?? $loop->index }}"
                                            data-copy="{{ base64_encode(json_encode($log['copy_text'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
                                            @change="toggle($event.target.value)"
                                        >
                                        <span class="rms-global-log-checkbox"></span>
                                    </label>

                                    <span class="rms-openai-log-dot"></span>

                                    <div class="rms-openai-log-content">
                                        <div class="rms-openai-log-top">
                                            <span class="rms-global-log-category">{{ strtoupper($logCategory) }}</span>
                                            <span class="rms-global-log-user">{{ $logUser }}</span>
                                            <time>{{ $log['created_at'] ?? '-' }}</time>
                                        </div>

                                        <strong>{{ $logTitle }}</strong>

                                        @if ($logDescription !== '')
                                            <small>{{ $logDescription }}</small>
                                        @endif

                                        <div class="rms-vg-log-meta">
                                            @if (!empty($log['action'])) <span>{{ $log['action'] }}</span> @endif
                                            @if (!empty($log['http_status'])) <span>HTTP {{ $log['http_status'] }}</span> @endif
                                            @if (!empty($log['duration_ms'])) <span>{{ $log['duration_ms'] }} ms</span> @endif
                                            @if (!empty($log['entity_type'])) <span>{{ $log['entity_type'] }} #{{ $log['entity_id'] }}</span> @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rms-vg-log-empty">
                                    <div class="rms-vg-empty-terminal">&gt;_</div>
                                    <strong>No global activity yet.</strong>
                                    <p>Worker lifecycle, API activity, generation, dan provider events akan muncul di sini.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="rms-openai-logs-footer">
                            <span><i></i> Global activity monitor · {{ $this->filteredActivityLogCount }} shown</span>
                            <span>All users · All providers</span>
                        </div>
                    </section>

                    @endif

                    {{-- LEGACY LOG SAFETY --}}
                    @if (count($vercelGatewayLogs) === 0 && count($openAiLogs) > 0)
                        <div class="rms-vg-legacy-note">
                            <span>i</span>
                            <small>Riwayat koneksi lama tetap dipertahankan untuk kompatibilitas data sebelumnya.</small>
                        </div>
                    @endif

                    @if ($activeTab === 'activity')
                        <div class="rms-settings-activity-note">
                            <span>●</span>
                            <div><strong>Global audit enabled</strong><small>Semua provider dan credential event tetap masuk ke global activity, termasuk failover dan connection test.</small></div>
                        </div>
                    @endif

                    {{-- FOOTER --}}
                    <div class="rms-settings-panel-footer rms-openai-footer-upgraded rms-vg-footer">
                        <span><i></i> AI provider configuration is server-managed</span>
                        <strong>Encrypted credentials · Automatic failover ready</strong>
                    </div>


                </div>
            @else
                <div class="rms-settings-panel rms-settings-panel-enter" wire:key="settings-general-panel">
                    <div class="rms-general-hero">
                        <div>
                            <span>WORKSPACE PREFERENCES</span>
                            <h2>Keep your creative workspace consistent.</h2>
                            <p>Pengaturan dasar dashboard internal Rizky Moto Shop. Konfigurasi engine AI tersedia pada AI Provider Configuration.</p>
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

<style>
.rms-openai-head-upgraded{min-height:104px}
.rms-openai-mark{display:grid;place-items:center;width:50px;height:50px;flex:0 0 50px;border-radius:15px;background:#18181b;color:#ef3030;box-shadow:0 10px 22px rgba(24,24,27,.12);font-size:11px;font-weight:850;letter-spacing:.04em}
.rms-openai-form-upgraded{row-gap:25px}
.rms-api-input-upgraded{min-height:48px}
.rms-openai-notice-upgraded{min-height:72px;gap:18px}
.rms-openai-notice-copy{display:flex;align-items:center;gap:12px;min-width:0}
.rms-openai-notice-copy>div{min-width:0}
.rms-openai-notice-copy strong{display:block;font-size:11px;line-height:1.25;font-weight:750;color:#27272a}
.rms-openai-notice-copy small{display:block;max-width:680px;margin-top:4px;color:#a1a1aa;font-size:9px;line-height:1.5}
.rms-test-connection-upgraded{flex:0 0 auto;min-width:142px}

.rms-custom-select{position:relative;width:100%;z-index:40}
.rms-custom-select-trigger{position:relative;display:flex;align-items:center;width:100%;min-height:52px;padding:8px 11px;gap:10px;border:1px solid #dcdce1;border-radius:12px;background:#fff;color:#18181b;text-align:left;cursor:pointer;transition:border-color .18s ease,box-shadow .18s ease,background .18s ease,transform .18s ease}
.rms-custom-select-trigger:hover{border-color:#c7c7cc;background:#fcfcfc}
.rms-custom-select-trigger:active{transform:scale(.998)}
.rms-custom-select-trigger[aria-expanded="true"]{border-color:#ef3030;box-shadow:0 0 0 3px rgba(239,48,48,.07)}
.rms-custom-select-preview{display:grid;place-items:center;width:32px;height:32px;flex:0 0 32px;border:1px solid #e7e7e9;border-radius:8px;background:#f7f7f8}
.rms-model-preview,.rms-quality-preview{color:#52525b;font-size:7px;line-height:1;font-weight:850;letter-spacing:.04em}
.rms-ratio-preview{display:block;border:1.5px solid #52525b;border-radius:3px;background:linear-gradient(135deg,rgba(239,48,48,.12),rgba(24,24,27,.03))}
.ratio-square{width:15px;height:15px}.ratio-portrait{width:12px;height:16px}.ratio-landscape{width:18px;height:11px}.ratio-vertical{width:10px;height:18px}
.rms-custom-select-value{display:flex;flex:1;min-width:0;flex-direction:column;justify-content:center}
.rms-custom-select-value strong{display:block;overflow:hidden;color:#27272a;font-size:11px;line-height:1.25;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
.rms-custom-select-value small{display:block;margin-top:2px;color:#a1a1aa;font-size:8px;line-height:1;font-weight:550}
.rms-custom-select-chevron{display:grid;place-items:center;width:27px;height:27px;flex:0 0 27px;border-radius:7px;color:#a1a1aa;transition:transform .18s ease,background .18s ease,color .18s ease}
.rms-custom-select-chevron svg{width:14px;height:14px}
.rms-custom-select-chevron.is-open{transform:rotate(180deg);background:#fafafa;color:#52525b}
.rms-custom-select-menu{position:absolute;top:calc(100% + 7px);left:0;right:0;z-index:9999;overflow:hidden;border:1px solid #e4e4e7;border-radius:14px;background:rgba(255,255,255,.98);box-shadow:0 18px 45px rgba(0,0,0,.11),0 4px 12px rgba(0,0,0,.05);backdrop-filter:blur(14px)}
.rms-custom-select-menu-head{padding:11px 13px 9px;border-bottom:1px solid #f1f1f3;background:linear-gradient(180deg,#fff,#fafafa)}
.rms-custom-select-menu-head span{display:block;color:#a1a1aa;font-size:8px;line-height:1;letter-spacing:.13em;font-weight:800}
.rms-custom-select-menu-head small{display:block;margin-top:4px;color:#b0b0b7;font-size:8px;line-height:1.2}
.rms-custom-select-options{padding:5px}
.rms-custom-select-option{display:flex;align-items:center;width:100%;min-height:51px;padding:7px 9px;gap:10px;border:0;border-radius:9px;background:transparent;text-align:left;cursor:pointer;transition:background .15s ease,transform .15s ease}
.rms-custom-select-option:hover{background:#f7f7f8}
.rms-custom-select-option:active{transform:scale(.99)}
.rms-custom-select-option.is-selected{background:rgba(239,48,48,.055)}
.rms-option-preview{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border:1px solid #e8e8ea;border-radius:8px;background:#fafafa}
.rms-model-option-preview,.rms-quality-option-preview{color:#71717a;font-size:7px;line-height:1;font-weight:850;letter-spacing:.03em}
.rms-custom-select-option:hover .rms-option-preview,.rms-custom-select-option.is-selected .rms-option-preview{border-color:rgba(239,48,48,.18);background:#fff}
.rms-option-copy{display:flex;flex:1;min-width:0;flex-direction:column}
.rms-option-copy strong{overflow:hidden;color:#3f3f46;font-size:10px;line-height:1.3;font-weight:700;text-overflow:ellipsis;white-space:nowrap}
.rms-custom-select-option.is-selected .rms-option-copy strong{color:#18181b}
.rms-option-copy small{display:flex;gap:5px;margin-top:3px;color:#a1a1aa;font-size:8px;line-height:1.2}
.rms-option-copy small span:first-child{color:#71717a;font-weight:650}
.rms-option-check{display:grid;place-items:center;width:22px;height:22px;flex:0 0 22px;border-radius:7px;background:#ef3030;color:#fff;box-shadow:0 4px 10px rgba(239,48,48,.18)}
.rms-option-check svg{width:12px;height:12px}

.rms-openai-logs{margin-top:22px;overflow:hidden;border:1px solid #e7e7eb;border-radius:16px;background:#fff;box-shadow:0 8px 26px rgba(0,0,0,.035)}
.rms-openai-logs-head{display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:70px;padding:15px 18px;border-bottom:1px solid #f0f0f2;background:linear-gradient(180deg,#fff,#fcfcfd)}
.rms-openai-logs-title{display:flex;align-items:center;gap:11px;min-width:0}
.rms-openai-terminal{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border:1px solid #e4e4e7;border-radius:9px;background:#fafafa;color:#18181b;font-family:"SFMono-Regular",Consolas,monospace;font-size:11px;font-weight:800}
.rms-openai-logs-title strong{display:block;color:#18181b;font-size:12px;line-height:1.2;font-weight:750}
.rms-openai-logs-title small{display:block;margin-top:4px;color:#a1a1aa;font-size:9px;line-height:1.35}
.rms-openai-log-actions{display:flex;align-items:center;gap:10px}
.rms-openai-log-counter{display:inline-flex;align-items:center;gap:6px;color:#a1a1aa;font-size:8px;line-height:1;letter-spacing:.08em;font-weight:800;white-space:nowrap}
.rms-openai-log-counter i{width:6px;height:6px;border-radius:999px;background:#d4d4d8}
.rms-openai-log-counter.has-events{color:#52525b}
.rms-openai-log-counter.has-events i{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.10)}
.rms-openai-log-actions button{height:29px;padding:0 11px;border:1px solid #e4e4e7;border-radius:8px;background:#fff;color:#52525b;font-size:9px;font-weight:700;transition:background .18s ease,border-color .18s ease,color .18s ease,opacity .18s ease}
.rms-openai-log-actions button:hover:not(.is-disabled){border-color:#18181b;background:#18181b;color:#fff}
.rms-openai-log-actions button.is-disabled{cursor:not-allowed;opacity:.38}
.rms-openai-log-empty{display:flex;align-items:center;justify-content:center;flex-direction:column;min-height:165px;padding:28px 20px;text-align:center}
.rms-openai-empty-icon{display:grid;place-items:center;width:40px;height:40px;margin-bottom:10px;border-radius:11px;background:#18181b;color:#fff;font-family:"SFMono-Regular",Consolas,monospace;font-size:11px;font-weight:800;box-shadow:0 8px 20px rgba(0,0,0,.12)}
.rms-openai-log-empty strong{color:#3f3f46;font-size:11px;line-height:1.3;font-weight:750}
.rms-openai-log-empty p{max-width:420px;margin:5px auto 0;color:#a1a1aa;font-size:9px;line-height:1.55}
.rms-openai-log-empty p b{color:#71717a;font-weight:750}
.rms-openai-log-list{max-height:260px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#d4d4d8 transparent}
.rms-openai-log-item{display:grid;grid-template-columns:8px minmax(0,1fr);gap:12px;padding:13px 18px;border-bottom:1px solid #f4f4f5;animation:rmsOpenAiLogIn .22s ease both}
.rms-openai-log-item:last-child{border-bottom:0}
.rms-openai-log-dot{width:7px;height:7px;margin-top:5px;border-radius:999px;background:#a1a1aa}
.rms-openai-log-item.is-info .rms-openai-log-dot{background:#3b82f6}
.rms-openai-log-item.is-success .rms-openai-log-dot{background:#22c55e}
.rms-openai-log-item.is-warning .rms-openai-log-dot{background:#f59e0b}
.rms-openai-log-item.is-error .rms-openai-log-dot{background:#ef4444}
.rms-openai-log-content{min-width:0}
.rms-openai-log-top{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.rms-openai-log-type{display:inline-flex;align-items:center;justify-content:center;min-width:47px;height:17px;padding:0 6px;border-radius:5px;background:#f4f4f5;color:#71717a;font-size:7px;line-height:1;font-weight:850;letter-spacing:.08em}
.rms-openai-log-item.is-info .rms-openai-log-type{background:#eff6ff;color:#2563eb}
.rms-openai-log-item.is-success .rms-openai-log-type{background:#f0fdf4;color:#16a34a}
.rms-openai-log-item.is-warning .rms-openai-log-type{background:#fffbeb;color:#d97706}
.rms-openai-log-item.is-error .rms-openai-log-type{background:#fef2f2;color:#dc2626}
.rms-openai-log-top time{color:#a1a1aa;font-family:"SFMono-Regular",Consolas,monospace;font-size:8px}
.rms-openai-log-content>strong{display:block;overflow:hidden;color:#52525b;font-size:10px;line-height:1.4;font-weight:650;text-overflow:ellipsis;white-space:nowrap}
.rms-openai-log-content>small{display:block;margin-top:3px;color:#a1a1aa;font-size:8px;line-height:1.45}
.rms-openai-logs-footer{display:flex;align-items:center;justify-content:space-between;min-height:38px;padding:0 18px;border-top:1px solid #f0f0f2;background:#fafafa;color:#a1a1aa;font-size:8px}
.rms-openai-logs-footer span{display:inline-flex;align-items:center;gap:6px}
.rms-openai-logs-footer span:first-child i{width:5px;height:5px;border-radius:999px;background:#22c55e}
.rms-openai-footer-upgraded{min-height:65px}
@keyframes rmsOpenAiLogIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:760px){
    .rms-settings-panel-head.rms-openai-head-upgraded{align-items:flex-start;flex-direction:column;gap:14px}
    .rms-api-connection-badge{margin-left:62px}
    .rms-openai-notice-upgraded{align-items:stretch;flex-direction:column}
    .rms-test-connection-upgraded{width:100%}
    .rms-openai-logs-head{align-items:flex-start;flex-direction:column}
    .rms-openai-log-actions{width:100%;justify-content:space-between}
}

/* =========================================================
   VERCEL MULTI-KEY — FULL WIDTH LAYOUT
   Keep the pool and health check on separate horizontal rows.
   ========================================================= */
.rms-settings-form.rms-openai-form-upgraded > .rms-vg-key-manager,
.rms-settings-form.rms-openai-form-upgraded > .rms-vg-test-all-card,
.rms-settings-form.rms-openai-form-upgraded > .rms-vg-activity {
    grid-column: 1 / -1;
    width: 100%;
    min-width: 0;
}

.rms-vg-key-manager{
    display:block;
}

.rms-vg-test-all-card{
    width:100%;
    box-sizing:border-box;
}

.rms-vg-test-all-copy{
    flex:1 1 auto;
}

.rms-vg-key-list{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:9px;
}

.rms-vg-empty-keys{
    grid-column:1 / -1;
}

.rms-vg-activity{
    width:100%;
}

@media(max-width:900px){
    .rms-vg-key-list{
        grid-template-columns:1fr;
    }
}

/* =========================================================
   VERCEL MULTI-KEY HEALTH CHECK
   ========================================================= */
.rms-vg-test-all-card{
    position:relative;display:flex;align-items:center;justify-content:space-between;gap:20px;
    margin-top:22px;padding:16px 18px;border:1px solid #f0d7d7;border-radius:16px;
    background:linear-gradient(135deg,#fff,#fffafa 62%,#fef4f4);
    overflow:hidden;
}
.rms-vg-test-all-card:before{content:"";position:absolute;inset:0;background:linear-gradient(100deg,transparent 20%,rgba(239,48,48,.055) 50%,transparent 80%);transform:translateX(-100%);animation:rmsVgScan 5s ease-in-out infinite;pointer-events:none}
.rms-vg-test-all-copy{position:relative;z-index:1;display:flex;align-items:center;gap:12px;min-width:0}
.rms-vg-test-all-copy>div:last-child{min-width:0}
.rms-vg-test-all-copy strong{display:block;color:#27272a;font-size:11px;line-height:1.25;font-weight:750}
.rms-vg-test-all-copy small{display:block;max-width:670px;margin-top:4px;color:#a1a1aa;font-size:8px;line-height:1.5}
.rms-vg-test-all-icon{position:relative;display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;border:1px solid #ead7d7;border-radius:11px;background:#fff;color:#ef3030;box-shadow:0 6px 16px rgba(239,48,48,.08)}
.rms-vg-test-all-icon:before{content:"";position:absolute;width:18px;height:18px;border:1.5px solid rgba(239,48,48,.22);border-top-color:#ef3030;border-radius:999px;animation:rmsVgSpin 1.8s linear infinite}
.rms-vg-test-all-icon span{width:5px;height:5px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.10);animation:rmsVgPulse 1.5s ease-in-out infinite}
.rms-vg-test-all-icon i{position:absolute;inset:0;border-radius:11px;box-shadow:0 0 0 0 rgba(239,48,48,.16);animation:rmsVgRing 2.2s ease-out infinite}
.rms-vg-test-all-button{position:relative;z-index:1;min-width:156px}
.rms-vg-test-loading{display:inline-flex;align-items:center;gap:7px}
.rms-vg-test-loading i{width:10px;height:10px;border:1.5px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:999px;animation:rmsVgSpin .7s linear infinite}
.rms-vg-footer{justify-content:space-between}
.rms-vg-footer>span{display:inline-flex;align-items:center;gap:7px}
.rms-vg-footer>span i{width:5px;height:5px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08);animation:rmsVgPulse 1.7s ease-in-out infinite}
.rms-vg-footer strong{color:#a1a1aa;font-size:8px;font-weight:650}
@keyframes rmsVgScan{0%,30%{transform:translateX(-100%)}70%,100%{transform:translateX(100%)}}
@keyframes rmsVgSpin{to{transform:rotate(360deg)}}
@keyframes rmsVgPulse{0%,100%{transform:scale(.85);opacity:.65}50%{transform:scale(1.12);opacity:1}}
@keyframes rmsVgRing{0%{box-shadow:0 0 0 0 rgba(239,48,48,.16);opacity:.8}70%,100%{box-shadow:0 0 0 9px rgba(239,48,48,0);opacity:0}}
@media(max-width:760px){
    .rms-vg-test-all-card{align-items:stretch;flex-direction:column}
    .rms-vg-test-all-button{width:100%}
    .rms-vg-footer{align-items:flex-start;flex-direction:column;gap:6px}
}
</style>



<style>
/* =========================================================
   VERCEL AI GATEWAY — MULTI KEY POOL
   ========================================================= */
.rms-vg-key-manager{margin-top:4px;border:1px solid #e7e7eb;border-radius:16px;background:#fff;box-shadow:0 8px 26px rgba(0,0,0,.035);overflow:hidden}
.rms-vg-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px 18px;border-bottom:1px solid #f0f0f2;background:linear-gradient(180deg,#fff,#fcfcfd)}
.rms-vg-section-title{display:flex;align-items:center;gap:11px;min-width:0}
.rms-vg-section-icon{display:grid;place-items:center;width:35px;height:35px;border-radius:10px;background:#18181b;color:#fff;font-size:7px;font-weight:900;letter-spacing:.08em;box-shadow:0 8px 18px rgba(0,0,0,.10)}
.rms-vg-section-title strong{display:block;color:#18181b;font-size:12px;line-height:1.2;font-weight:780}
.rms-vg-section-title small{display:block;margin-top:4px;color:#a1a1aa;font-size:8.5px;line-height:1.4}
.rms-vg-pool-badge{display:inline-flex;align-items:center;gap:7px;height:26px;padding:0 9px;border:1px solid #e4e4e7;border-radius:8px;color:#71717a;font-size:8px;font-weight:800;letter-spacing:.08em;white-space:nowrap}
.rms-vg-pool-badge i{width:5px;height:5px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.10);animation:rmsVgPulse 1.8s ease-in-out infinite}
.rms-vg-key-notice{display:flex;align-items:center;gap:10px;margin:14px 18px 10px;padding:10px 12px;border:1px solid #ececef;border-radius:10px;background:linear-gradient(90deg,#fafafa,#fff)}
.rms-vg-notice-mark{display:grid;place-items:center;width:28px;height:28px;flex:0 0 28px;border-radius:8px;background:#f4f4f5;color:#18181b;font-size:13px;font-weight:800}
.rms-vg-key-notice strong{display:block;color:#52525b;font-size:9px;font-weight:750}
.rms-vg-key-notice small{display:block;margin-top:3px;color:#a1a1aa;font-size:8px;line-height:1.45}
.rms-vg-add-toggle{display:flex;align-items:center;width:calc(100% - 36px);margin:0 18px 10px;padding:10px 11px;gap:10px;border:1px solid #e4e4e7;border-radius:11px;background:#fff;color:#18181b;text-align:left;cursor:pointer;transition:border-color .2s ease,background .2s ease,transform .2s ease,box-shadow .2s ease}
.rms-vg-add-toggle:hover{border-color:#c7c7cc;background:#fcfcfc;box-shadow:0 5px 16px rgba(0,0,0,.04)}
.rms-vg-add-toggle:active{transform:scale(.997)}
.rms-vg-add-toggle.is-open{border-color:#dedee2;background:#fafafa}
.rms-vg-add-toggle-icon{display:grid;place-items:center;width:29px;height:29px;border-radius:8px;background:#18181b;color:#fff;font-size:18px;font-weight:300;line-height:1;transition:transform .25s ease}
.rms-vg-add-toggle.is-open .rms-vg-add-toggle-icon{transform:rotate(90deg)}
.rms-vg-add-toggle>span:nth-child(2){display:flex;flex:1;min-width:0;flex-direction:column}
.rms-vg-add-toggle strong{font-size:10px;line-height:1.25;font-weight:750}
.rms-vg-add-toggle small{margin-top:3px;color:#a1a1aa;font-size:8px}
.rms-vg-add-toggle>b{color:#a1a1aa;font-size:16px;font-weight:400}
.rms-vg-add-panel{margin:0 18px 13px;padding:14px;border:1px solid #e7e7eb;border-radius:13px;background:linear-gradient(180deg,#fafafa,#fff);animation:rmsVgPanelIn .25s ease both}
.rms-vg-add-grid{display:grid;grid-template-columns:minmax(170px,.6fr) minmax(0,1.4fr);gap:11px}
.rms-vg-add-grid label{display:block}
.rms-vg-add-grid label>span{display:block;margin:0 0 6px;color:#a1a1aa;font-size:7px;letter-spacing:.12em;font-weight:850}
.rms-vg-add-grid input{width:100%;height:42px;padding:0 11px;border:1px solid #dedee2;border-radius:9px;background:#fff;color:#27272a;outline:none;font-size:10px;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
.rms-vg-add-grid input::placeholder{color:#c4c4c9}
.rms-vg-add-grid input:focus{border-color:#18181b;box-shadow:0 0 0 3px rgba(24,24,27,.05)}
.rms-vg-key-input-field>div{position:relative}
.rms-vg-key-input-field input{padding-right:62px;font-family:"SFMono-Regular",Consolas,monospace;letter-spacing:.02em}
.rms-vg-key-input-field button{position:absolute;right:5px;top:5px;height:32px;padding:0 9px;border:0;border-radius:7px;background:#f4f4f5;color:#71717a;font-size:8px;font-weight:750;cursor:pointer}
.rms-vg-key-input-field button:hover{background:#18181b;color:#fff}
.rms-vg-add-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:11px}
.rms-vg-add-footer>small{display:flex;align-items:center;gap:6px;color:#a1a1aa;font-size:7.5px;line-height:1.4}
.rms-vg-add-footer>small i{width:5px;height:5px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.09)}
.rms-vg-save-key{min-height:36px;padding:0 13px;border:0;border-radius:9px;background:#18181b;color:#fff;font-size:9px;font-weight:750;cursor:pointer;box-shadow:0 7px 16px rgba(24,24,27,.12);transition:transform .18s ease,box-shadow .18s ease,background .18s ease}
.rms-vg-save-key:hover{background:#ef3030;box-shadow:0 9px 20px rgba(239,48,48,.18);transform:translateY(-1px)}
.rms-vg-save-key:active{transform:translateY(0) scale(.985)}
.rms-vg-button-loading{display:inline-flex;align-items:center;gap:7px}
.rms-vg-button-loading i{width:11px;height:11px;border:1.5px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:rmsVgSpin .7s linear infinite}
.rms-vg-key-list{display:flex;flex-direction:column;gap:8px;padding:0 18px 15px}
.rms-vg-key-card{position:relative;overflow:hidden;border:1px solid #e8e8eb;border-radius:12px;background:#fff;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease;animation:rmsVgCardIn .35s cubic-bezier(.2,.75,.2,1) both}
.rms-vg-key-card:hover{border-color:#d5d5d9;box-shadow:0 9px 24px rgba(0,0,0,.055);transform:translateY(-1px)}
.rms-vg-key-card.is-exhausted{background:linear-gradient(180deg,#fff,#fffafa);border-color:#f1d4d4}
.rms-vg-key-card.is-disabled{opacity:.62}
.rms-vg-key-card-main{display:flex;align-items:center;gap:11px;padding:12px 13px}
.rms-vg-key-avatar{position:relative;display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;border:1px solid #e6e6e9;border-radius:10px;background:#fafafa;color:#52525b;font-size:7px;font-weight:900}
.rms-vg-key-avatar i{position:absolute;right:-2px;bottom:-2px;width:7px;height:7px;border:2px solid #fff;border-radius:50%;background:#22c55e}
.rms-vg-key-card.is-exhausted .rms-vg-key-avatar i{background:#ef4444}
.rms-vg-key-copy{flex:1;min-width:0}
.rms-vg-key-name-row{display:flex;align-items:center;gap:7px;min-width:0}
.rms-vg-key-name-row>strong{overflow:hidden;color:#27272a;font-size:10px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}
.rms-vg-status{display:inline-flex;align-items:center;gap:4px;flex:0 0 auto;height:17px;padding:0 6px;border-radius:5px;font-size:6.5px;line-height:1;font-weight:850;letter-spacing:.06em}
.rms-vg-status i{width:4px;height:4px;border-radius:50%;background:currentColor}
.rms-vg-status-active{background:#f0fdf4;color:#16a34a}.rms-vg-status-exhausted{background:#fef2f2;color:#dc2626}.rms-vg-status-rate_limited{background:#fffbeb;color:#d97706}.rms-vg-status-invalid{background:#fef2f2;color:#dc2626}.rms-vg-status-disabled{background:#f4f4f5;color:#71717a}
.rms-vg-key-copy code{display:block;margin-top:5px;color:#71717a;font-family:"SFMono-Regular",Consolas,monospace;font-size:8px}
.rms-vg-key-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;color:#a1a1aa;font-size:7px}
.rms-vg-key-meta span+span:before{content:"·";margin-right:8px;color:#d4d4d8}
.rms-vg-key-actions{display:flex;align-items:center;gap:5px;flex:0 0 auto}
.rms-vg-test-key{display:grid;place-items:center;min-width:54px;height:29px;padding:0 10px;border:1px solid #e1e1e4;border-radius:8px;background:#fff;color:#52525b;font-size:8px;font-weight:750;cursor:pointer;transition:all .18s ease}
.rms-vg-test-key:hover{border-color:#18181b;background:#18181b;color:#fff;transform:translateY(-1px)}
.rms-vg-test-key i{display:block;width:11px;height:11px;border:1.5px solid currentColor;border-right-color:transparent;border-radius:50%;animation:rmsVgSpin .65s linear infinite}
.rms-vg-key-more{display:grid;place-items:center;width:29px;height:29px;border:1px solid #e7e7eb;border-radius:8px;background:#fff;color:#a1a1aa;font-size:16px;line-height:1;cursor:pointer}
.rms-vg-key-more:hover{color:#18181b;background:#fafafa}
.rms-vg-more-wrap{position:relative}
.rms-vg-key-more{transition:transform .18s ease,background .18s ease,border-color .18s ease}
.rms-vg-key-more[aria-expanded="true"]{background:#18181b;color:#fff;border-color:#18181b;transform:rotate(90deg)}
.rms-vg-key-menu{position:absolute;right:0;top:calc(100% + 7px);z-index:80;min-width:145px;padding:5px;border:1px solid #e7e7eb;border-radius:10px;background:#fff;box-shadow:0 14px 30px rgba(0,0,0,.10)}
.rms-vg-key-menu button{display:block;width:100%;padding:8px 9px;border:0;border-radius:7px;background:transparent;color:#52525b;text-align:left;font-size:8px;font-weight:700;cursor:pointer}
.rms-vg-key-menu button:hover{background:#f4f4f5;color:#18181b}
.rms-vg-key-menu button.is-danger{color:#dc2626}
.rms-vg-key-menu button.is-danger:hover{background:#fef2f2;color:#b91c1c}
.rms-vg-key-error{display:flex;gap:8px;margin:0 13px 10px;padding:8px 9px;border-radius:8px;background:#fff7f7;border:1px solid #f8dddd}
.rms-vg-key-error>span{display:grid;place-items:center;width:17px;height:17px;flex:0 0 17px;border-radius:50%;background:#ef4444;color:#fff;font-size:8px;font-weight:850}
.rms-vg-key-error strong{display:block;color:#b91c1c;font-size:7.5px;font-weight:800}
.rms-vg-key-error small{display:block;margin-top:2px;color:#a1a1aa;font-size:7.5px;line-height:1.35}
.rms-vg-key-progress{height:2px;background:#f4f4f5}
.rms-vg-key-progress span{display:block;height:100%;background:#22c55e;transition:width .5s ease}
.rms-vg-empty-keys{display:flex;align-items:center;justify-content:center;flex-direction:column;min-height:145px;padding:20px;text-align:center;border:1px dashed #dedee2;border-radius:12px;background:#fcfcfd}
.rms-vg-empty-orbit{display:grid;place-items:center;width:38px;height:38px;margin-bottom:9px;border:1px solid #e4e4e7;border-radius:11px;background:#fff;color:#18181b;font-size:17px;box-shadow:0 6px 16px rgba(0,0,0,.05);animation:rmsVgFloat 2.6s ease-in-out infinite}
.rms-vg-empty-keys strong{color:#52525b;font-size:10px;font-weight:750}.rms-vg-empty-keys p{margin:4px 0 0;color:#a1a1aa;font-size:8px}
.rms-vg-activity{margin-top:22px}
.rms-global-activity{position:relative}
.rms-global-activity .rms-openai-logs-head{align-items:center}
.rms-global-activity .rms-openai-log-actions{flex-wrap:wrap;justify-content:flex-end}
.rms-global-copy-button,.rms-global-copy-all{height:22px;display:inline-flex;align-items:center;justify-content:center;padding:0 8px;border:1px solid #e4e4e7;border-radius:6px;background:#fff;color:#52525b;font-size:6.5px;font-weight:850;cursor:pointer;transition:all .18s ease}
.rms-global-copy-button:hover,.rms-global-copy-all:hover{border-color:#a1a1aa;background:#fafafa;color:#18181b}
.rms-global-copy-button:disabled,.rms-global-copy-all:disabled{opacity:.45;cursor:not-allowed}
.rms-global-copy-button:not(:disabled){border-color:#d4d4d8;background:#18181b;color:#fff}
.rms-global-select-actions{grid-column:1/-1;display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:-1px}
.rms-global-select-actions button{height:23px;padding:0 8px;border:1px solid #e4e4e7;border-radius:6px;background:#fff;color:#52525b;font-size:6.5px;font-weight:850;cursor:pointer}
.rms-global-select-actions button:hover:not(:disabled){background:#f4f4f5;color:#18181b}
.rms-global-select-actions button:disabled{opacity:.4;cursor:not-allowed}
.rms-global-select-actions>span{color:#a1a1aa;font-size:6.5px}
.rms-global-select-actions strong{color:#18181b;font-weight:900}
.rms-global-log-select-wrap{position:relative;display:grid;place-items:center;width:20px;height:20px;flex:0 0 20px;margin-right:1px;cursor:pointer}
.rms-global-log-select-wrap input{position:absolute;opacity:0;pointer-events:none}
.rms-global-log-checkbox{width:13px;height:13px;border:1px solid #d4d4d8;border-radius:4px;background:#fff;box-shadow:inset 0 0 0 2px #fff;transition:all .16s ease}
.rms-global-log-select-wrap:hover .rms-global-log-checkbox{border-color:#a1a1aa}
.rms-global-log-select-wrap input:checked + .rms-global-log-checkbox{border-color:#18181b;background:#18181b;box-shadow:inset 0 0 0 3px #18181b}
.rms-global-log-select-wrap input:checked + .rms-global-log-checkbox:after{content:"";display:block;width:6px;height:3px;margin:3px 0 0 3px;border-left:1.5px solid #fff;border-bottom:1.5px solid #fff;transform:rotate(-45deg)}
.rms-global-activity .rms-openai-log-item{display:flex;align-items:flex-start;gap:7px}
.rms-global-activity .rms-openai-log-content{min-width:0;flex:1}
.rms-global-activity .rms-openai-log-item:has(.global-log-select:checked){background:#fafafa}
@media(max-width:760px){
    .rms-global-activity .rms-openai-logs-head{align-items:flex-start}
    .rms-global-activity .rms-openai-log-actions{width:100%;justify-content:flex-start}
    .rms-global-copy-button,.rms-global-copy-all{height:26px;font-size:7px}
    .rms-global-select-actions{justify-content:flex-start}
}
.rms-global-activity{position:relative}
.rms-global-scope-badge{display:inline-flex;align-items:center;height:22px;padding:0 7px;border:1px solid #e4e4e7;border-radius:6px;background:#fafafa;color:#52525b;font-size:6.5px;font-weight:900;letter-spacing:.09em}
.rms-global-activity-toolbar{display:grid;grid-template-columns:minmax(180px,1.6fr) repeat(3,minmax(95px,.55fr));gap:8px;padding:11px 18px;border-top:1px solid #f0f0f2;border-bottom:1px solid #f0f0f2;background:#fafafa}
.rms-global-activity-toolbar label{display:grid;gap:5px;min-width:0}
.rms-global-activity-toolbar label>span{font-size:6.5px;line-height:1;color:#a1a1aa;letter-spacing:.12em;font-weight:900}
.rms-global-activity-toolbar input,.rms-global-activity-toolbar select{width:100%;height:32px;min-width:0;padding:0 9px;border:1px solid #e4e4e7;border-radius:8px;background:#fff;color:#3f3f46;outline:none;font-family:inherit;font-size:8px}
.rms-global-activity-toolbar input:focus,.rms-global-activity-toolbar select:focus{border-color:#18181b;box-shadow:0 0 0 3px rgba(24,24,27,.04)}
.rms-global-activity-toolbar input::placeholder{color:#c4c4c9}
.rms-global-activity-list{max-height:390px}
.rms-global-log-category{display:inline-flex;align-items:center;height:17px;padding:0 5px;border-radius:5px;background:#f4f4f5;color:#52525b;font-size:6px;font-weight:900;letter-spacing:.06em}
.rms-global-log-user{max-width:145px;overflow:hidden;color:#a1a1aa;font-size:7px;text-overflow:ellipsis;white-space:nowrap}
.rms-global-activity .rms-openai-log-item.is-success .rms-global-log-category{background:#f0fdf4;color:#15803d}
.rms-global-activity .rms-openai-log-item.is-error .rms-global-log-category{background:#fef2f2;color:#b91c1c}
.rms-global-activity .rms-openai-log-item.is-warning .rms-global-log-category{background:#fffbeb;color:#a16207}
.rms-global-activity .rms-openai-log-item.is-info .rms-global-log-category{background:#eff6ff;color:#1d4ed8}
.rms-global-activity .rms-openai-log-top{gap:7px}
@media(max-width:760px){
    .rms-global-activity-toolbar{grid-template-columns:1fr 1fr}
    .rms-global-activity-toolbar label:first-child{grid-column:1/-1}
    .rms-global-log-user{max-width:90px}
}

.rms-vg-activity-list{max-height:330px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:#d4d4d8 transparent}
.rms-vg-log-key{overflow:hidden;max-width:230px;color:#71717a;font-family:"SFMono-Regular",Consolas,monospace;font-size:7px;text-overflow:ellipsis;white-space:nowrap}
.rms-vg-log-meta{display:flex;flex-wrap:wrap;gap:10px;margin-top:7px;color:#a1a1aa;font-size:7px}
.rms-vg-log-meta span+span:before{content:"·";margin-right:10px;color:#d4d4d8}
.rms-vg-log-empty{display:flex;align-items:center;justify-content:center;flex-direction:column;min-height:150px;padding:25px;text-align:center}
.rms-vg-empty-terminal{display:grid;place-items:center;width:40px;height:40px;margin-bottom:9px;border-radius:11px;background:#18181b;color:#fff;font-family:"SFMono-Regular",Consolas,monospace;font-size:11px;font-weight:850;box-shadow:0 8px 20px rgba(0,0,0,.12)}
.rms-vg-log-empty strong{color:#3f3f46;font-size:10px;font-weight:750}.rms-vg-log-empty p{margin:4px 0 0;color:#a1a1aa;font-size:8px}
.rms-vg-legacy-note{display:flex;align-items:center;gap:7px;margin-top:8px;color:#a1a1aa;font-size:8px}.rms-vg-legacy-note>span{display:grid;place-items:center;width:16px;height:16px;border-radius:50%;background:#f4f4f5;color:#71717a;font-weight:800}
@keyframes rmsVgSpin{to{transform:rotate(360deg)}}@keyframes rmsVgPulse{0%,100%{opacity:.55;transform:scale(.9)}50%{opacity:1;transform:scale(1.15)}}@keyframes rmsVgPanelIn{from{opacity:0;transform:translateY(-7px) scale(.985)}to{opacity:1;transform:none}}@keyframes rmsVgCardIn{from{opacity:0;transform:translateY(9px) scale(.985)}to{opacity:1;transform:none}}@keyframes rmsVgFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
@media(max-width:760px){.rms-vg-section-head{align-items:flex-start;flex-direction:column}.rms-vg-add-grid{grid-template-columns:1fr}.rms-vg-add-footer{align-items:stretch;flex-direction:column}.rms-vg-save-key{width:100%}.rms-vg-key-card-main{align-items:flex-start}.rms-vg-key-actions{margin-left:auto}.rms-vg-key-meta{gap:5px}.rms-vg-key-name-row{align-items:flex-start;flex-direction:column;gap:5px}.rms-vg-log-key{max-width:130px}}
</style>

<style>
/* =========================================================
   CUSTOM SELECT — STACKING / OVERLAP FIX
   ========================================================= */

.rms-settings-form-upgraded{
    position:relative;
    isolation:isolate;
}

.rms-settings-field.rms-custom-select-field{
    position:relative;
    z-index:1;
}

.rms-settings-field.rms-custom-select-field.rms-select-field-open{
    z-index:500;
}

.rms-settings-field.rms-custom-select-field .rms-custom-select{
    position:relative;
    z-index:1;
}

.rms-settings-field.rms-custom-select-field.rms-select-field-open .rms-custom-select{
    z-index:1000;
}

.rms-settings-field.rms-custom-select-field .rms-custom-select-menu{
    z-index:99999;
}

/* The opened dropdown gets a little more breathing room from the next row. */
.rms-settings-field.rms-select-field-open{
    margin-bottom:2px;
}

/* Keep the dropdown visually above the secure configuration block too. */
.rms-openai-notice-upgraded{
    position:relative;
    z-index:1;
}

/* =========================================================
   VERCEL KEY MANAGER — FINAL INTERACTION PASS
   ========================================================= */
.rms-vg-key-manager{position:relative;overflow:hidden}
.rms-vg-add-toggle{transition:transform .2s ease,background .2s ease,border-color .2s ease,box-shadow .2s ease}
.rms-vg-add-toggle:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(0,0,0,.045)}
.rms-vg-add-toggle.is-open .rms-vg-add-toggle-icon{transform:rotate(90deg);background:#ef3030;box-shadow:0 8px 18px rgba(239,48,48,.18)}
.rms-vg-add-toggle-icon{transition:transform .25s cubic-bezier(.2,.8,.2,1),background .2s ease,box-shadow .2s ease}
.rms-vg-add-panel{animation:rmsVgPanelIn .28s cubic-bezier(.2,.8,.2,1) both}
.rms-vg-key-card{animation:rmsVgCardIn .32s cubic-bezier(.2,.8,.2,1) both}
.rms-vg-key-card:nth-child(2){animation-delay:.035s}.rms-vg-key-card:nth-child(3){animation-delay:.07s}.rms-vg-key-card:nth-child(4){animation-delay:.105s}
.rms-vg-delete-key{display:grid;place-items:center;width:32px;height:32px;border:1px solid #ececef;border-radius:9px;background:#fff;color:#a1a1aa;font-size:18px;line-height:1;cursor:pointer;transition:all .18s ease}
.rms-vg-delete-key:hover{border-color:#fecaca;background:#fef2f2;color:#dc2626;transform:scale(1.05)}
.rms-vg-delete-key:active{transform:scale(.94)}
.rms-vg-mini-spinner{width:11px;height:11px;border:1.5px solid #d4d4d8;border-top-color:#ef3030;border-radius:999px;animation:rmsVgSpin .65s linear infinite}
.rms-vg-test-all-card-full{width:100%;grid-column:1/-1}
.rms-vg-test-all-card-full:has(button[wire\:loading]){transition:box-shadow .25s ease,border-color .25s ease}
.rms-vg-test-all-button{position:relative;overflow:hidden}
.rms-vg-test-all-button::before{content:"";position:absolute;inset:0;transform:translateX(-110%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.13),transparent);transition:transform .6s ease}
.rms-vg-test-all-button:hover::before{transform:translateX(110%)}
.rms-vg-test-loading i{display:inline-block;width:11px;height:11px;margin-right:5px;border:1.5px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:999px;vertical-align:-1px;animation:rmsVgSpin .65s linear infinite}
.rms-vg-key-input-field input{transition:border-color .18s ease,box-shadow .18s ease}
.rms-vg-key-input-field input:focus{border-color:#ef3030!important;box-shadow:0 0 0 3px rgba(239,48,48,.07)}
@keyframes rmsVgSpin{to{transform:rotate(360deg)}}
@keyframes rmsVgPanelIn{from{opacity:0;transform:translateY(-8px) scale(.985);filter:blur(2px)}to{opacity:1;transform:translateY(0) scale(1);filter:blur(0)}}
@keyframes rmsVgCardIn{from{opacity:0;transform:translateY(8px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}
@media(max-width:760px){.rms-vg-add-grid{grid-template-columns:1fr!important}.rms-vg-test-all-card-full{width:100%}}
</style>


<style>
.rms-vg-delete-overlay{position:fixed;inset:0;z-index:2147483000;display:grid;place-items:center;padding:22px}.rms-vg-delete-backdrop{position:absolute;inset:0;background:rgba(9,9,11,.48);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px)}.rms-vg-delete-modal{position:relative;width:min(430px,calc(100vw - 30px));padding:26px;border:1px solid #e4e4e7;border-radius:22px;background:rgba(255,255,255,.98);box-shadow:0 30px 90px rgba(0,0,0,.22);overflow:hidden}.rms-vg-delete-modal:before{content:"";position:absolute;left:0;right:0;top:0;height:3px;background:linear-gradient(90deg,#ef3030,#f87171,#ef3030);background-size:200% 100%;animation:rmsVgDeleteShimmer 2s linear infinite}.rms-vg-delete-orb{width:58px;height:58px;margin:0 auto 15px;border-radius:18px;background:#fff1f2;border:1px solid #fecdd3;display:grid;place-items:center;position:relative;animation:rmsVgDeleteFloat 2.4s ease-in-out infinite}.rms-vg-delete-orb:before,.rms-vg-delete-orb:after{content:"";position:absolute;border:1px solid rgba(239,48,48,.18);border-radius:50%;inset:7px;animation:rmsVgDeletePulse 1.8s ease-out infinite}.rms-vg-delete-orb:after{inset:0;animation-delay:.45s}.rms-vg-delete-orb span{width:9px;height:9px;border-radius:50%;background:#ef3030;box-shadow:0 0 0 5px rgba(239,48,48,.09),0 0 18px rgba(239,48,48,.3);position:relative;z-index:2}.rms-vg-delete-copy{text-align:center}.rms-vg-delete-eyebrow{font-size:8px;font-weight:850;letter-spacing:.14em;color:#ef3030}.rms-vg-delete-copy h3{margin:7px 0 6px;color:#18181b;font-size:20px;line-height:1.15;font-weight:850}.rms-vg-delete-copy p{margin:0 auto;color:#71717a;font-size:10px;line-height:1.6;max-width:340px}.rms-vg-delete-copy p strong{color:#27272a;font-weight:800}.rms-vg-delete-warning{display:flex;align-items:center;gap:10px;margin:18px 0;padding:11px 12px;border:1px solid #f4d5d8;border-radius:12px;background:#fff8f8}.rms-vg-delete-warning>span{display:grid;place-items:center;width:23px;height:23px;flex:0 0 23px;border-radius:8px;background:#ef3030;color:#fff;font-size:11px;font-weight:900}.rms-vg-delete-warning strong{display:block;color:#9f1239;font-size:9px;font-weight:800}.rms-vg-delete-warning small{display:block;margin-top:2px;color:#a1a1aa;font-size:8px}.rms-vg-delete-actions{display:grid;grid-template-columns:1fr 1.25fr;gap:9px}.rms-vg-delete-actions button{height:42px;border-radius:11px;font-size:9px;font-weight:800;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,background .18s ease}.rms-vg-delete-cancel{border:1px solid #e4e4e7;background:#fff;color:#52525b}.rms-vg-delete-cancel:hover{background:#f4f4f5;color:#18181b}.rms-vg-delete-confirm{border:1px solid #ef3030;background:#ef3030;color:#fff;box-shadow:0 8px 20px rgba(239,48,48,.18)}.rms-vg-delete-confirm:hover{background:#dc2626;box-shadow:0 11px 25px rgba(239,48,48,.24);transform:translateY(-1px)}.rms-vg-delete-loading{display:inline-flex;align-items:center;gap:6px}.rms-vg-delete-loading i{width:11px;height:11px;border:1.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:rmsVgSpin .65s linear infinite}@keyframes rmsVgDeletePulse{0%{transform:scale(.7);opacity:.7}100%{transform:scale(1.6);opacity:0}}@keyframes rmsVgDeleteFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}@keyframes rmsVgDeleteShimmer{0%{background-position:0 0}100%{background-position:200% 0}}@media(max-width:520px){.rms-vg-delete-modal{padding:22px;border-radius:18px}.rms-vg-delete-actions{grid-template-columns:1fr}.rms-vg-delete-confirm{order:-1}}
</style>

<style>
/* VERCEL DELETE MODAL — BODY TELEPORT / TOP LAYER */
.rms-vg-delete-overlay{position:fixed!important;inset:0!important;z-index:2147483647!important;align-items:center!important;justify-content:center!important;padding:24px!important;width:100vw!important;height:100dvh!important;box-sizing:border-box!important;isolation:isolate!important}
.rms-vg-delete-backdrop{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;background:rgba(9,9,11,.48)!important;backdrop-filter:blur(7px)!important;-webkit-backdrop-filter:blur(7px)!important}
.rms-vg-delete-modal{position:relative!important;z-index:2!important;opacity:1!important;width:min(430px,calc(100vw - 32px))!important;max-height:calc(100dvh - 48px)!important;overflow:auto!important}
[x-cloak]{display:none!important}
</style>

</div>

<style>
.rms-vg-key-manager-root{position:relative;overflow:visible!important}
.rms-vg-key-manager,.rms-vg-key-list,.rms-vg-key-card{overflow:visible!important}
.rms-vg-more-wrap{position:relative!important;z-index:100!important}
.rms-vg-key-menu{position:absolute!important;right:0!important;top:calc(100% + 8px)!important;z-index:2147483000!important;min-width:190px!important;background:#fff!important;box-shadow:0 20px 45px rgba(15,23,42,.18)!important;border:1px solid #e5e7eb!important;border-radius:14px!important;padding:6px!important}
.rms-vg-delete-overlay{position:fixed!important;inset:0!important;z-index:2147483647!important;align-items:center!important;justify-content:center!important;padding:24px!important;width:100vw!important;height:100dvh!important;box-sizing:border-box!important;isolation:isolate!important}
.rms-vg-delete-backdrop{position:absolute!important;inset:0!important;background:rgba(8,12,20,.52)!important;backdrop-filter:blur(9px)!important}
.rms-vg-delete-modal{position:relative!important;z-index:2!important;width:min(470px,calc(100vw - 32px))!important;max-height:calc(100vh - 48px)!important;overflow:auto!important;background:#fff!important;border-radius:24px!important;box-shadow:0 35px 100px rgba(0,0,0,.30)!important}

/* FINAL HORIZONTAL OVERRIDE — VERCEL KEY POOL ROOT */
.rms-settings-form.rms-openai-form-upgraded > .rms-vg-key-manager-root,
.rms-settings-form.rms-openai-form-upgraded > div.rms-vg-key-manager-root {
    grid-column: 1 / -1 !important;
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;
    flex: 1 1 100% !important;
}
.rms-vg-key-manager-root {
    width: 100% !important;
    max-width: none !important;
    min-width: 0 !important;
    display: block !important;
}
.rms-vg-key-manager-root > .rms-vg-key-manager {
    width: 100% !important;
    max-width: none !important;
}
.rms-vg-key-manager-root > .rms-vg-delete-overlay {
    width: 100vw !important;
}
</style>

<style>
/* STAGE 4 — AI PROVIDER CONFIGURATION / SMOOTH UX */
.rms-ai-provider-section{margin-top:18px;border:1px solid #e8e8ec;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#fcfcfd 100%);box-shadow:0 10px 30px rgba(17,24,39,.035);overflow:hidden;transition:transform .28s cubic-bezier(.2,.8,.2,1),box-shadow .28s ease,border-color .28s ease}
.rms-ai-provider-section:hover{transform:translateY(-1px);box-shadow:0 16px 38px rgba(17,24,39,.06);border-color:#dedee4}
.rms-ai-provider-section-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 18px;border-bottom:1px solid #f0f0f2}
.rms-ai-provider-section-title{display:flex;align-items:center;gap:11px;min-width:0}
.rms-ai-provider-section-title>div{min-width:0}
.rms-ai-provider-section-title strong{display:block;color:#18181b;font-size:11px;font-weight:850;letter-spacing:-.01em}
.rms-ai-provider-section-title small{display:block;margin-top:3px;color:#a1a1aa;font-size:7.8px;line-height:1.45}
.rms-ai-provider-icon{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border:1px solid #e4e4e7;border-radius:10px;background:#fafafa;color:#18181b;font-size:10px;font-weight:900}
.rms-agent-icon{background:#18181b;color:#fff;border-color:#18181b;letter-spacing:-.06em}
.rms-ai-provider-status{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;height:24px;padding:0 8px;border:1px solid #e4e4e7;border-radius:999px;background:#fff;color:#71717a;font-size:6.5px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}
.rms-ai-provider-status i{width:6px;height:6px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.08)}
.rms-ai-provider-status.is-pending i{background:#f59e0b;box-shadow:0 0 0 4px rgba(245,158,11,.08)}
.rms-ai-agent-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:14px;padding:17px 18px}
.rms-ai-agent-readiness{display:flex;align-items:center;gap:13px;padding:15px;border:1px solid #ededf0;border-radius:13px;background:#fff}
.rms-ai-readiness-orb{display:grid;place-items:center;width:44px;height:44px;flex:0 0 44px;border-radius:13px;background:radial-gradient(circle at 50% 50%,#fff 0 22%,#f4f4f5 23% 100%);border:1px solid #e4e4e7;position:relative;animation:rmsAiFloat 3.2s ease-in-out infinite}
.rms-ai-readiness-orb:before,.rms-ai-readiness-orb:after{content:"";position:absolute;inset:5px;border:1px solid #e4e4e7;border-radius:50%;animation:rmsAiPulse 2.4s ease-out infinite}
.rms-ai-readiness-orb:after{inset:-2px;animation-delay:.7s}
.rms-ai-readiness-orb span{width:7px;height:7px;border-radius:50%;background:#f59e0b;box-shadow:0 0 0 5px rgba(245,158,11,.08),0 0 16px rgba(245,158,11,.18);z-index:2}
.rms-ai-agent-readiness strong{display:block;color:#27272a;font-size:9px;font-weight:850}
.rms-ai-agent-readiness p{margin:4px 0 0;color:#a1a1aa;font-size:7.5px;line-height:1.55}
.rms-ai-agent-actions{display:grid;gap:10px}
.rms-ai-agent-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.rms-ai-agent-meta span{display:grid;gap:3px;padding:9px;border:1px solid #ededf0;border-radius:10px;background:#fff}
.rms-ai-agent-meta b{font-size:6.5px;color:#71717a;font-weight:800}
.rms-ai-agent-meta em{font-style:normal;font-size:6.5px;color:#b0b0b7}
.rms-ai-secondary-button{display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;min-height:42px;padding:0 12px;border:1px solid #e4e4e7;border-radius:10px;background:#f7f7f8;color:#a1a1aa;cursor:not-allowed}
.rms-ai-secondary-button span{font-size:8px;font-weight:800}.rms-ai-secondary-button small{font-size:6.5px}
.rms-ai-failover-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:17px 18px}
.rms-ai-config-field{display:grid;gap:6px}
.rms-ai-config-field>span{font-size:6.5px;color:#a1a1aa;letter-spacing:.12em;font-weight:900}
.rms-ai-config-field select{height:38px;padding:0 10px;border:1px solid #e4e4e7;border-radius:10px;background:#fff;color:#3f3f46;font:inherit;font-size:8px;outline:none;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}
.rms-ai-config-field select:focus{border-color:#18181b;box-shadow:0 0 0 3px rgba(24,24,27,.045)}
.rms-ai-toggle-field{display:flex;align-items:center;gap:9px;padding:11px;border:1px solid #ededf0;border-radius:10px;background:#fff;cursor:pointer;transition:background .2s ease,border-color .2s ease,transform .2s ease}
.rms-ai-toggle-field:hover{background:#fcfcfd;border-color:#dedee4;transform:translateY(-1px)}
.rms-ai-toggle-field input{position:absolute;opacity:0;pointer-events:none}
.rms-ai-toggle-ui{position:relative;width:28px;height:17px;flex:0 0 28px;border-radius:999px;background:#e4e4e7;transition:background .25s cubic-bezier(.2,.8,.2,1)}
.rms-ai-toggle-ui:after{content:"";position:absolute;top:3px;left:3px;width:11px;height:11px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.12);transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.rms-ai-toggle-field input:checked+.rms-ai-toggle-ui{background:#18181b}.rms-ai-toggle-field input:checked+.rms-ai-toggle-ui:after{transform:translateX(11px)}
.rms-ai-toggle-field strong{display:block;color:#52525b;font-size:7.5px;font-weight:850}.rms-ai-toggle-field small{display:block;margin-top:2px;color:#a1a1aa;font-size:6.7px;line-height:1.35}
.rms-ai-provider-save-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;border-top:1px solid #f0f0f2;background:#fafafa}
.rms-ai-provider-save-row>div{display:flex;align-items:center;gap:7px;color:#a1a1aa;font-size:6.8px}.rms-ai-live-dot{width:6px;height:6px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.08)}
.rms-ai-save-button{height:34px;padding:0 12px;border:1px solid #18181b;border-radius:9px;background:#18181b;color:#fff;font-size:7.5px;font-weight:850;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,background .2s ease}
.rms-ai-save-button:hover{transform:translateY(-1px);box-shadow:0 9px 20px rgba(24,24,27,.14);background:#27272a}.rms-ai-save-button:active{transform:translateY(0) scale(.98)}
.rms-ai-save-button i{display:inline-block;width:10px;height:10px;margin-right:5px;border:1.5px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;vertical-align:-2px;animation:rmsAiSpin .65s linear infinite}
.rms-settings-activity-head{display:flex;align-items:center;gap:12px;padding:4px 0 20px;animation:rmsSettingsIn .35s cubic-bezier(.2,.8,.2,1) both}
.rms-settings-activity-mark{display:grid;place-items:center;width:42px;height:42px;border:1px solid #e4e4e7;border-radius:12px;background:#18181b;color:#fff;font-size:18px;box-shadow:0 10px 24px rgba(0,0,0,.08)}
.rms-settings-activity-head>div:nth-child(2){min-width:0;flex:1}.rms-settings-activity-head span{display:block;color:#a1a1aa;font-size:6.5px;font-weight:900;letter-spacing:.14em}.rms-settings-activity-head strong{display:block;margin-top:3px;color:#18181b;font-size:16px;font-weight:850;letter-spacing:-.02em}.rms-settings-activity-head small{display:block;margin-top:4px;color:#a1a1aa;font-size:7.5px;line-height:1.45}
.rms-settings-activity-live{display:inline-flex!important;align-items:center;gap:6px;flex:0 0 auto;padding:6px 8px;border:1px solid #dcfce7;border-radius:999px;background:#f0fdf4;color:#15803d!important;font-size:6px!important;font-weight:900!important;letter-spacing:.08em!important}.rms-settings-activity-live i{width:5px;height:5px;border-radius:50%;background:#22c55e;animation:rmsAiBlink 1.8s ease-in-out infinite}
.rms-settings-activity-note{display:flex;gap:8px;align-items:center;margin-top:14px;padding:10px 12px;border:1px solid #e4e4e7;border-radius:10px;background:#fafafa}.rms-settings-activity-note>span{color:#22c55e;font-size:8px}.rms-settings-activity-note strong{display:block;color:#52525b;font-size:7.5px}.rms-settings-activity-note small{display:block;margin-top:2px;color:#a1a1aa;font-size:6.8px}
@keyframes rmsAiSpin{to{transform:rotate(360deg)}}@keyframes rmsAiPulse{0%{transform:scale(.72);opacity:.65}100%{transform:scale(1.4);opacity:0}}@keyframes rmsAiFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}@keyframes rmsAiBlink{0%,100%{opacity:.45}50%{opacity:1;transform:scale(1.2)}}@keyframes rmsSettingsIn{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:none}}
@media(max-width:760px){.rms-ai-agent-grid,.rms-ai-failover-grid{grid-template-columns:1fr}.rms-ai-agent-meta{grid-template-columns:1fr}.rms-ai-provider-save-row{align-items:stretch;flex-direction:column}.rms-ai-save-button{width:100%}.rms-settings-activity-head{align-items:flex-start}.rms-settings-activity-live{margin-left:auto}}
@media(prefers-reduced-motion:reduce){.rms-ai-provider-section,.rms-ai-save-button,.rms-ai-toggle-field,.rms-ai-readiness-orb,.rms-settings-activity-head,.rms-settings-activity-live{animation:none!important;transition:none!important}}
</style>
