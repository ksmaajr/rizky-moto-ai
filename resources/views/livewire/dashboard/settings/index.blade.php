<div class="rms-settings-page" x-data="{ showApiKey: @entangle('showApiKey') }">
    <div class="rms-settings-page-head">
        <div>
            <span class="section-kicker">SYSTEM / SETTINGS</span>
            <h1 x-text="$wire.activeTab === 'provider' ? 'AI Provider Configuration' : 'General Settings'"></h1>
            <p x-text="$wire.activeTab === 'provider'
                ? 'Kelola provider AI, credential, failover, dan readiness untuk Product Generator.'
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


                        <section class="rms-provider-block rms-provider-block-vercel">
                            <div class="rms-provider-block-head">
                                <div class="rms-provider-block-icon rms-provider-icon-vercel" aria-hidden="true"><span></span></div>
                                <div class="rms-provider-block-copy">
                                    <span>PROVIDER 01 · GATEWAY</span>
                                    <strong>Vercel AI Gateway</strong>
                                    <small>Credential pool, key health, automatic rotation, dan gateway connectivity.</small>
                                </div>
                                <div class="rms-provider-block-state is-ready"><i></i><span>Primary gateway</span></div>
                            </div>

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
                        </section>


                        <section class="rms-provider-block rms-provider-block-agent">
                            <div class="rms-provider-block-head">
                                <div class="rms-provider-block-icon rms-provider-icon-agent" aria-hidden="true"><span>✦</span></div>
                                <div class="rms-provider-block-copy">
                                    <span>PROVIDER 02 · BACKEND</span>
                                    <strong>Agent AI</strong>
                                    <small>GPT Image 2.5 melalui AgentKit backend dengan shared credential pool dan dedicated worker queue.</small>
                                </div>
                                <div class="rms-provider-block-state {{ $agentAiActiveCredentialCount > 0 ? 'is-ready' : 'is-pending' }}">
                                    <i></i><span>{{ $agentAiActiveCredentialCount > 0 ? 'Configured' : 'Not configured' }}</span>
                                </div>
                            </div>

                            <section class="rms-ai-provider-section rms-agent-provider-card" x-data="{ credentialFormOpen: @entangle('showAgentCredentialForm').live, credentialDeleteOpen: false, credentialDeleteId: null, credentialDeleteName: '', credentialDeleting: false, confirmCredentialDelete(id, name) { this.credentialDeleteId = id; this.credentialDeleteName = name || 'credential Agent ini'; this.credentialDeleteOpen = true; }, closeCredentialDelete() { if (!this.credentialDeleting) this.credentialDeleteOpen = false; }, async deleteCredential() { if (!this.credentialDeleteId || this.credentialDeleting) return; this.credentialDeleting = true; try { await $wire.deleteAgentAiCredential(this.credentialDeleteId); this.credentialDeleteOpen = false; } catch (e) { console.error(e); } finally { this.credentialDeleting = false; this.credentialDeleteId = null; } } }" @keydown.escape.window="closeCredentialDelete()">
                                <div class="rms-ai-provider-section-head">
                                    <div class="rms-ai-provider-section-title">
                                        <span class="rms-ai-provider-icon rms-agent-icon">AG</span>
                                        <div>
                                            <strong>Agent Credential Pool</strong>
                                            <small>Token disimpan terenkripsi di server. Semua AgentKit worker dapat memakai credential sehat yang sama.</small>
                                        </div>
                                    </div>
                                    <span class="rms-ai-provider-status {{ $agentAiActiveCredentialCount > 0 ? '' : 'is-pending' }}">
                                        <i></i> {{ $agentAiActiveCredentialCount }}/{{ $agentAiCredentialCount }} active
                                    </span>
                                </div>

                                <div class="rms-agent-credential-toolbar">
                                    <div>
                                        <strong>Shared credentials</strong>
                                        <small>Rotation, cooldown, rate-limit handling, dan failover berjalan di server-side pool.</small>
                                    </div>
                                    <button
                                        type="button"
                                        class="rms-ai-secondary-button rms-agent-add-button"
                                        wire:click="beginCodexLogin"
                                        wire:loading.attr="disabled"
                                        wire:target="beginCodexLogin"
                                        @disabled($codexLoginInProgress)
                                        aria-busy="{{ $codexLoginInProgress ? 'true' : 'false' }}"
                                        aria-controls="agent-codex-login-bridge"
                                    >
                                        <span class="rms-agent-add-icon" aria-hidden="true">
                                            @if ($codexLoginInProgress)
                                                <span class="rms-agent-login-spinner"></span>
                                            @else
                                                <svg viewBox="0 0 24 24" fill="none" focusable="false">
                                                    <circle cx="9" cy="8" r="3.25" stroke="currentColor" stroke-width="1.7"/>
                                                    <path d="M3.8 19c.45-3.05 2.3-4.7 5.2-4.7 2.1 0 3.65.9 4.55 2.55" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                                    <path d="M17.5 11.5v7M14 15h7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                                </svg>
                                            @endif
                                        </span>
                                        <span class="rms-agent-add-label">
                                            @if ($codexLoginInProgress)
                                                {{ $codexLoginState === 'importing' ? 'Menyimpan akun...' : 'Menunggu login...' }}
                                            @else
                                                Add Account
                                            @endif
                                        </span>
                                        <svg class="rms-agent-add-chevron" wire:loading.remove wire:target="beginCodexLogin" viewBox="0 0 20 20" fill="none" aria-hidden="true" @if($codexLoginInProgress) style="display:none" @endif>
                                            <path d="m7.5 4.5 5.5 5.5-5.5 5.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="rms-agent-login-hint">
                                    <span class="rms-agent-login-hint-icon" aria-hidden="true">i</span>
                                    <div class="rms-agent-login-hint-copy">
                                        <strong>Login Codex melalui Add Account</strong>
                                        <span>{{ $codexCliAvailable ? 'Codex CLI terdeteksi. Login akan dibuka melalui PowerShell di development Windows.' : 'Codex CLI belum terdeteksi oleh proses web. Pastikan CLI tersedia di host AgentKit.' }}</span>
                                    </div>
                                    <span class="rms-agent-login-hint-state {{ $codexCliAvailable ? 'is-ready' : 'is-pending' }}"><i></i>{{ $codexCliAvailable ? 'CLI detected' : 'CLI not detected' }}</span>
                                </div>
                                @if ($codexLoginState !== 'idle')
                                    <div wire:poll.3s="refreshCodexLoginStatus" class="rms-agent-login-progress" role="status" aria-live="polite">
                                        @if ($codexLoginInProgress)
                                            <span class="rms-agent-login-progress-dot is-running"></span>
                                            <div><strong>{{ $codexLoginState === 'importing' ? 'Menyimpan credential' : 'Menunggu autentikasi Codex' }}</strong><small>{{ $codexLoginMessage ?: 'Selesaikan login pada jendela PowerShell yang terbuka.' }}</small></div>
                                        @elseif ($codexLoginState === 'completed')
                                            <span class="rms-agent-login-progress-dot is-success"></span>
                                            <div><strong>Login selesai</strong><small>{{ $codexLoginMessage ?: 'Credential diimpor dan menunggu validasi AgentKit.' }}</small></div>
                                        @elseif ($codexLoginState === 'failed')
                                            <span class="rms-agent-login-progress-dot is-failed"></span>
                                            <div><strong>Login gagal</strong><small>{{ $codexLoginMessage ?: 'Periksa pesan pada jendela PowerShell.' }}</small></div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Worker controls live in the dedicated sidebar page. Keep this panel focused on credentials. --}}

                                <div class="rms-agent-credential-list" wire:poll.10s="loadAgentAiCredentials">
                                    @forelse ($agentAiCredentials as $credential)
                                        @php
                                            $credentialStatus = (string) ($credential['status'] ?? 'active');
                                            $credentialActive = (bool) ($credential['is_active'] ?? false);
                                            $credentialStatusLabel = match ($credentialStatus) {
                                                'cooldown' => 'Cooldown',
                                                'invalid' => 'Invalid',
                                                'exhausted' => 'Exhausted',
                                                'error' => 'Error',
                                                'disabled' => 'Disabled',
                                                'pending_validation' => 'Pending Validation',
                                                default => $credentialActive ? 'Active' : 'Disabled',
                                            };
                                            $credentialStatusClass = match ($credentialStatus) {
                                                'cooldown' => 'is-cooldown',
                                                'invalid', 'error', 'exhausted' => 'is-error',
                                                'disabled' => 'is-disabled',
                                                'pending_validation' => 'is-pending',
                                                default => 'is-active',
                                            };
                                        @endphp
                                        <article class="rms-agent-credential-card {{ $credentialActive ? 'is-enabled' : 'is-disabled' }}">
                                            <div class="rms-agent-credential-main">
                                                <div class="rms-agent-credential-icon">AG</div>
                                                <div class="rms-agent-credential-copy">
                                                    <div class="rms-agent-credential-title">
                                                        <strong>{{ $credential['name'] }}</strong>
                                                        <span class="rms-agent-status {{ $credentialStatusClass }}"><i></i>{{ $credentialStatusLabel }}</span>
                                                    </div>
                                                    <code>••••••••••••••••••••</code>
                                                    <small>{{ $credential['request_count'] }} requests · {{ $credential['success_count'] }} success · {{ $credential['failure_count'] }} failed</small>
                                                </div>
                                            </div>

                                            <div class="rms-agent-credential-meta">
                                                <span><b>Last used</b><em>{{ $credential['last_used_at'] ?: 'Never' }}</em></span>
                                                <span><b>Last success</b><em>{{ $credential['last_success_at'] ?: 'Never' }}</em></span>
                                            </div>

                                            @if ($credential['cooldown_until'])
                                                @php
                                                    $credentialCooldownUntil = \Illuminate\Support\Carbon::parse($credential['cooldown_until']);
                                                    $credentialCooldownActive = $credentialCooldownUntil->isFuture();
                                                @endphp
                                                @if ($credentialCooldownActive)
                                                    <div class="rms-agent-cooldown">
                                                        <span>Cooldown until {{ $credentialCooldownUntil->format('d M Y, H:i:s') }}</span>
                                                        <strong
                                                            x-data="{ remaining: {{ max(0, $credentialCooldownUntil->timestamp - now()->timestamp) }} }"
                                                            x-init="setInterval(() => remaining = Math.max(0, remaining - 1), 1000)"
                                                            x-text="(Math.floor(remaining / 86400) > 0 ? Math.floor(remaining / 86400) + 'd ' : '') + [Math.floor((remaining % 86400) / 3600), Math.floor((remaining % 3600) / 60), remaining % 60].map(value => String(value).padStart(2, '0')).join(':')"
                                                        ></strong>
                                                    </div>
                                                @else
                                                    <div class="rms-agent-cooldown is-expired">Cooldown elapsed · {{ $credentialCooldownUntil->format('d M Y, H:i:s') }}</div>
                                                @endif
                                            @endif

                                            <div class="rms-agent-credential-actions">
                                                <button type="button" class="rms-agent-monitor-button" wire:click="openAgentCredentialMonitoring({{ (int) $credential['id'] }})" wire:loading.attr="disabled" wire:target="openAgentCredentialMonitoring({{ (int) $credential['id'] }})">
                                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7"/></svg>
                                                    <span>Monitoring</span>
                                                </button>
                                                <button type="button" class="rms-agent-test-button" wire:click="testAgentAiCredential({{ (int) $credential['id'] }})" wire:loading.attr="disabled" wire:target="testAgentAiCredential({{ (int) $credential['id'] }})">
                                                    <span class="rms-agent-action-icon" aria-hidden="true">↗</span>
                                                    <span wire:loading.remove wire:target="testAgentAiCredential({{ (int) $credential['id'] }})">Test token</span>
                                                    <span wire:loading wire:target="testAgentAiCredential({{ (int) $credential['id'] }})">Testing…</span>
                                                </button>
                                                <button type="button" class="rms-agent-toggle-button {{ $credentialActive ? 'is-enabled' : 'is-disabled' }}" wire:click="toggleAgentAiCredential({{ (int) $credential['id'] }})" wire:loading.attr="disabled" wire:target="toggleAgentAiCredential({{ (int) $credential['id'] }})">
                                                    <span class="rms-agent-action-icon" aria-hidden="true">{{ $credentialActive ? 'Ⅱ' : '▶' }}</span>
                                                    <span wire:loading.remove wire:target="toggleAgentAiCredential({{ (int) $credential['id'] }})">{{ $credentialActive ? 'Disable' : 'Enable' }}</span>
                                                    <span wire:loading wire:target="toggleAgentAiCredential({{ (int) $credential['id'] }})">Updating…</span>
                                                </button>
                                                <button type="button" class="rms-agent-delete-button" @click.stop="confirmCredentialDelete({{ (int) $credential['id'] }}, @js($credential['name']))" title="Remove credential" aria-label="Remove credential">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                        </article>
                                    @empty
                                        <div class="rms-agent-empty">
                                            <span>✦</span>
                                            <div>
                                                <strong>Belum ada Agent credential</strong>
                                                <small>Browser login worker belum terhubung. Setelah Codex login di host yang sama, importer file-based eksperimental dapat dipakai bila format sesi kompatibel.</small>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>

                                <template x-teleport="body">
                                    <div
                                        x-show="credentialDeleteOpen"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="rms-agent-delete-overlay"
                                        @keydown.escape.window="closeCredentialDelete()"
                                    >
                                        <div class="rms-agent-delete-backdrop" @click="closeCredentialDelete()"></div>
                                        <div
                                            class="rms-agent-delete-modal"
                                            x-show="credentialDeleteOpen"
                                            x-transition:enter="transition ease-out duration-250"
                                            x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                                            @click.stop
                                        >
                                            <div class="rms-agent-delete-orb"><span></span></div>
                                            <div class="rms-agent-delete-copy">
                                                <span>REMOVE AGENT CREDENTIAL</span>
                                                <h3>Hapus credential ini?</h3>
                                                <p>Credential <strong x-text="credentialDeleteName"></strong> akan dihapus dari pool dan tidak dapat digunakan lagi oleh AgentKit worker.</p>
                                            </div>
                                            <div class="rms-agent-delete-warning">
                                                <span>!</span>
                                                <div>
                                                    <strong>Tindakan ini tidak dapat dibatalkan</strong>
                                                    <small>Riwayat activity tetap tersimpan untuk audit.</small>
                                                </div>
                                            </div>
                                            <div class="rms-agent-delete-actions">
                                                <button type="button" class="rms-agent-delete-cancel" @click="closeCredentialDelete()" :disabled="credentialDeleting">Batal</button>
                                                <button type="button" class="rms-agent-delete-confirm" @click="deleteCredential()" :disabled="credentialDeleting">
                                                    <span x-show="!credentialDeleting">Hapus Credential</span>
                                                    <span x-show="credentialDeleting" x-cloak class="rms-agent-delete-loading"><i></i>Menghapus...</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                @teleport('body')
                                    <div class="rms-agent-monitor-overlay" x-show="$wire.agentCredentialMonitoringOpen" x-cloak wire:poll.15s.visible="refreshAgentCredentialMonitoring" role="dialog" aria-modal="true" aria-label="AgentKit account monitoring">
                                        @if ($agentCredentialMonitoringOpen && !empty($selectedAgentCredentialMonitoring))
                                        <button type="button" class="rms-agent-monitor-backdrop" wire:click="closeAgentCredentialMonitoring" aria-label="Close monitoring"></button>
                                        <section class="rms-agent-monitor-drawer">
                                            <header class="rms-agent-monitor-header">
                                                <div class="rms-agent-monitor-heading">
                                                    <span class="rms-agent-monitor-eyebrow">ACCOUNT MONITORING</span>
                                                    <h2>{{ $selectedAgentCredentialMonitoring['name'] }}</h2>
                                                    <p>Health, request metrics, authentication signals, and recent activity for this account.</p>
                                                </div>
                                                <button type="button" class="rms-agent-monitor-close" wire:click="closeAgentCredentialMonitoring" aria-label="Close drawer">×</button>
                                            </header>
                                            @php
                                                $monitorStatus = (string) ($selectedAgentCredentialMonitoring['status'] ?? 'unknown');
                                                $monitorErrorType = strtolower((string) ($selectedAgentCredentialMonitoring['last_error_type'] ?? ''));
                                                $monitorCooldownUntil = !empty($selectedAgentCredentialMonitoring['cooldown_until'])
                                                    ? \Illuminate\Support\Carbon::parse($selectedAgentCredentialMonitoring['cooldown_until'])
                                                    : null;
                                                $monitorCooldownActive = $monitorCooldownUntil !== null && $monitorCooldownUntil->isFuture();
                                                $monitorRateLimited = in_array($monitorErrorType, ['rate_limited', 'rate_limit', 'quota_exceeded'], true)
                                                    || ($monitorStatus === 'cooldown' && $monitorErrorType === 'rate_limited');
                                                $monitorStatusLabel = $monitorRateLimited
                                                    ? ($monitorCooldownActive ? 'Rate limited · cooldown active' : ($monitorCooldownUntil ? 'Rate limit · cooldown elapsed' : 'Rate limit detected'))
                                                    : match ($monitorStatus) {
                                                        'cooldown' => 'Cooldown',
                                                        'invalid' => 'Invalid authentication',
                                                        'exhausted' => 'Limit / exhausted',
                                                        'error' => 'Error',
                                                        'disabled' => 'Disabled',
                                                        'pending_validation' => 'Pending validation',
                                                        default => !empty($selectedAgentCredentialMonitoring['is_active']) ? 'Enabled · health unverified' : 'Disabled',
                                                    };
                                                $monitorSuccessRate = (int) ($selectedAgentCredentialMonitoring['request_count'] ?? 0) > 0
                                                    ? round(((int) ($selectedAgentCredentialMonitoring['success_count'] ?? 0) / (int) $selectedAgentCredentialMonitoring['request_count']) * 100)
                                                    : null;
                                            @endphp
                                            <div class="rms-agent-monitor-scroll">
                                                <div class="rms-agent-monitor-status-row">
                                                    <span class="rms-agent-monitor-status-dot {{ $monitorRateLimited || in_array($monitorStatus, ['cooldown','pending_validation'], true) ? 'is-warning' : ($monitorStatus === 'active' && !empty($selectedAgentCredentialMonitoring['is_active']) && $monitorErrorType === '' ? 'is-healthy' : 'is-problem') }}"></span>
                                                    <div><strong>{{ $monitorStatusLabel }}</strong><small>Current stored credential state</small></div>
                                                    <button type="button" wire:click="refreshAgentCredentialMonitoring({{ (int) $selectedAgentCredentialMonitoring['id'] }})" wire:loading.attr="disabled" wire:target="refreshAgentCredentialMonitoring" class="rms-agent-monitor-refresh"><span wire:loading.remove wire:target="refreshAgentCredentialMonitoring">↻ Refresh</span><span wire:loading wire:target="refreshAgentCredentialMonitoring">Refreshing…</span></button>
                                                </div>
                                                <div class="rms-agent-monitor-metrics">
                                                    <article><span>Total requests</span><strong>{{ number_format((int) ($selectedAgentCredentialMonitoring['request_count'] ?? 0)) }}</strong><small>Recorded invocations</small></article>
                                                    <article><span>Success rate</span><strong>{{ $monitorSuccessRate === null ? '—' : $monitorSuccessRate . '%' }}</strong><small>{{ number_format((int) ($selectedAgentCredentialMonitoring['success_count'] ?? 0)) }} successful</small></article>
                                                    <article><span>Failed requests</span><strong>{{ number_format((int) ($selectedAgentCredentialMonitoring['failure_count'] ?? 0)) }}</strong><small>Recorded failures</small></article>
                                                    <article><span>Cooldown remaining</span>
                                                        @if ($monitorCooldownActive)
                                                            <strong x-data="{ remaining: {{ max(0, $monitorCooldownUntil->timestamp - now()->timestamp) }} }" x-init="setInterval(() => remaining = Math.max(0, remaining - 1), 1000)" x-text="(Math.floor(remaining / 86400) > 0 ? Math.floor(remaining / 86400) + 'd ' : '') + [Math.floor((remaining % 86400) / 3600), Math.floor((remaining % 3600) / 60), remaining % 60].map(value => String(value).padStart(2, '0')).join(':')"></strong>
                                                            <small>Deadline {{ $monitorCooldownUntil->format('d M Y, H:i:s') }} server time</small>
                                                        @else
                                                            <strong>{{ $monitorRateLimited ? 'Not set' : 'Not active' }}</strong>
                                                            <small>{{ $monitorRateLimited ? 'Rate limit recorded; set a cooldown below' : 'No active cooldown' }}</small>
                                                        @endif
                                                    </article>
                                                </div>
                                                <section class="rms-agent-monitor-cooldown-control">
                                                    <div>
                                                        <span class="rms-agent-monitor-control-eyebrow">AUTO COOLDOWN POLICY</span>
                                                        <strong>Atur durasi saat rate limit terdeteksi</strong>
                                                        <p>Durasi ini diterapkan otomatis saat akun terkena rate limit. Countdown dimulai dari waktu limit terdeteksi, bukan saat pengaturan disimpan. Maksimal 7 hari.</p>
                                                    </div>
                                                    <div class="rms-agent-monitor-cooldown-form">
                                                        <label><span>Durasi (menit)</span><input type="number" min="1" max="10080" step="1" wire:model="agentCredentialCooldownMinutes" /></label>
                                                        <button type="button" wire:click="saveAgentCredentialCooldownPreference" wire:loading.attr="disabled" wire:target="saveAgentCredentialCooldownPreference"><span wire:loading.remove wire:target="saveAgentCredentialCooldownPreference">Simpan durasi</span><span wire:loading wire:target="saveAgentCredentialCooldownPreference">Menyimpan…</span></button>
                                                    </div>
                                                </section>
                                                <section class="rms-agent-monitor-section">
                                                    <div class="rms-agent-monitor-section-head"><div><span>01 / HEALTH &amp; AUTH</span><h3>Account health</h3></div></div>
                                                    <div class="rms-agent-monitor-details">
                                                        <div><span>Last activity</span><strong>{{ !empty($selectedAgentCredentialMonitoring['last_used_at']) ? \Illuminate\Support\Carbon::parse($selectedAgentCredentialMonitoring['last_used_at'])->format('d M Y, H:i:s') : 'Never' }}</strong></div>
                                                        <div><span>Last success</span><strong>{{ !empty($selectedAgentCredentialMonitoring['last_success_at']) ? \Illuminate\Support\Carbon::parse($selectedAgentCredentialMonitoring['last_success_at'])->format('d M Y, H:i:s') : 'Never' }}</strong></div>
                                                        <div><span>Last failure</span><strong>{{ !empty($selectedAgentCredentialMonitoring['last_failure_at']) ? \Illuminate\Support\Carbon::parse($selectedAgentCredentialMonitoring['last_failure_at'])->format('d M Y, H:i:s') : 'Never' }}</strong></div>
                                                        <div><span>Last error type</span><strong>{{ $selectedAgentCredentialMonitoring['last_error_type'] ?: 'None recorded' }}</strong></div>
                                                        <div><span>Last exit code</span><strong>{{ $selectedAgentCredentialMonitoring['last_exit_code'] ?? '—' }}</strong></div>
                                                        <div><span>Account added</span><strong>{{ !empty($selectedAgentCredentialMonitoring['created_at']) ? \Illuminate\Support\Carbon::parse($selectedAgentCredentialMonitoring['created_at'])->format('d M Y, H:i') : '—' }}</strong></div>
                                                    </div>
                                                    @if (!empty($selectedAgentCredentialMonitoring['last_error']))
                                                        <div class="rms-agent-monitor-error"><strong>Latest error detail</strong><p>{{ $selectedAgentCredentialMonitoring['last_error'] }}</p></div>
                                                    @endif
                                                    <p class="rms-agent-monitor-footnote">Quota and reset time are not shown unless AgentKit exposes verified provider data. Status reflects recorded telemetry, not a guaranteed live quota check.</p>
                                                </section>
                                                <section class="rms-agent-monitor-section">
                                                    <div class="rms-agent-monitor-section-head"><div><span>02 / REQUEST TELEMETRY</span><h3>Recent activity</h3></div><small>Latest 25 events</small></div>
                                                    @forelse ($agentCredentialMonitoringLogs as $monitorLog)
                                                        <article class="rms-agent-monitor-log">
                                                            <span class="rms-agent-monitor-log-mark {{ in_array($monitorLog['status'], ['success','ok'], true) ? 'is-success' : (in_array($monitorLog['status'], ['error','failed'], true) ? 'is-error' : 'is-info') }}"><i></i></span>
                                                            <div class="rms-agent-monitor-log-copy"><strong>{{ $monitorLog['title'] }}</strong><p>{{ $monitorLog['description'] ?: $monitorLog['action'] }}</p><small>{{ !empty($monitorLog['created_at']) ? \Illuminate\Support\Carbon::parse($monitorLog['created_at'])->format('d M Y, H:i:s') : 'Timestamp unavailable' }}@if (!empty($monitorLog['http_status'])) · HTTP {{ $monitorLog['http_status'] }}@endif @if (!empty($monitorLog['duration_ms'])) · {{ $monitorLog['duration_ms'] }} ms @endif @if (!is_null($monitorLog['exit_code'])) · Exit {{ $monitorLog['exit_code'] }}@endif</small></div>
                                                        </article>
                                                    @empty
                                                        <div class="rms-agent-monitor-empty"><span>◷</span><strong>No account activity yet</strong><p>Events will appear here when this credential is tested or used by a generation request.</p></div>
                                                    @endforelse
                                                </section>
                                            </div>
                                            <footer class="rms-agent-monitor-footer"><span><i></i> Account-specific telemetry</span><button type="button" wire:click="closeAgentCredentialMonitoring">Done</button></footer>
                                        </section>
                                        @endif
                                    </div>
                                @endteleport

                                <div class="rms-agent-test-note">
                                    <span>!</span>
                                    <div>
                                        <strong>Test Token menjalankan live smoke test</strong>
                                        <small>AgentKit tidak menyediakan auth-only endpoint; test ini melakukan satu live image invocation dan dapat menggunakan quota/limit akun.</small>
                                    </div>
                                </div>
                            </section>
                        </section>

                        <section class="rms-provider-block rms-provider-block-failover">
                            <div class="rms-provider-block-head">
                                <div class="rms-provider-block-icon rms-provider-icon-failover" aria-hidden="true"><span>↗</span></div>
                                <div class="rms-provider-block-copy">
                                    <span>RUNTIME POLICY · FAILOVER</span>
                                    <strong>Provider Failover</strong>
                                    <small>Aturan fallback server-side untuk menjaga generation tetap tersedia saat provider utama bermasalah.</small>
                                </div>
                                <div class="rms-provider-block-state is-ready"><i></i><span>Configurable</span></div>
                            </div>

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
                            <div class="rms-ai-config-field" x-data="{ open: false, value: @entangle('activeProvider').live, options: [{value:'vercel',label:'Vercel AI Gateway',desc:'Primary generation gateway',icon:'V'}, {value:'agentkit',label:'Agent AI',desc:'GPT Image 2.5 backend',icon:'✦'}] }" @click.outside="open = false">
                                <span>PRIMARY PROVIDER</span>
                                <div class="rms-custom-select" :class="{ 'is-open': open }">
                                    <button type="button" class="rms-custom-select-trigger" @click="open = !open" :aria-expanded="open">
                                        <span class="rms-custom-select-leading rms-leading-primary" x-text="value === 'agentkit' ? '✦' : 'V'"></span>
                                        <span class="rms-custom-select-value">
                                            <strong x-text="value === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway'"></strong>
                                            <small x-text="value === 'agentkit' ? 'GPT Image 2.5 backend' : 'Primary generation gateway'"></small>
                                        </span>
                                        <span class="rms-custom-select-arrow">⌄</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-180" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.985]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-120" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-1 scale-[.985]" class="rms-custom-select-menu">
                                        <div class="rms-custom-select-label">SELECT PRIMARY PROVIDER</div>
                                        <template x-for="option in options" :key="option.value">
                                            <button type="button" class="rms-custom-select-option" :class="{ 'is-selected': value === option.value }" @click="value = option.value; open = false">
                                                <span class="rms-custom-option-icon" x-text="option.icon"></span>
                                                <span><strong x-text="option.label"></strong><small x-text="option.desc"></small></span>
                                                <span class="rms-custom-option-check" x-show="value === option.value">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="rms-ai-config-field" x-data="{ open: false, value: @entangle('fallbackProvider').live, options: [{value:'',label:'No automatic fallback',desc:'Keep generation on the primary provider',icon:'—'}, {value:'vercel',label:'Vercel AI Gateway',desc:'Use Vercel as backup provider',icon:'V'}, {value:'agentkit',label:'Agent AI',desc:'Use Agent AI as backup provider',icon:'✦'}] }" @click.outside="open = false">
                                <span>FALLBACK PROVIDER</span>
                                <div class="rms-custom-select" :class="{ 'is-open': open }">
                                    <button type="button" class="rms-custom-select-trigger" @click="open = !open" :aria-expanded="open">
                                        <span class="rms-custom-select-leading rms-leading-fallback" x-text="value === 'agentkit' ? '✦' : (value === 'vercel' ? 'V' : '—')"></span>
                                        <span class="rms-custom-select-value">
                                            <strong x-text="value === 'agentkit' ? 'Agent AI' : (value === 'vercel' ? 'Vercel AI Gateway' : 'No automatic fallback')"></strong>
                                            <small x-text="value === 'agentkit' ? 'Use Agent AI as backup provider' : (value === 'vercel' ? 'Use Vercel as backup provider' : 'Keep generation on the primary provider')"></small>
                                        </span>
                                        <span class="rms-custom-select-arrow">⌄</span>
                                    </button>
                                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-180" x-transition:enter-start="opacity-0 -translate-y-1 scale-[.985]" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-120" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-1 scale-[.985]" class="rms-custom-select-menu">
                                        <div class="rms-custom-select-label">SELECT FALLBACK PROVIDER</div>
                                        <template x-for="option in options" :key="option.value">
                                            <button type="button" class="rms-custom-select-option" :class="{ 'is-selected': value === option.value }" @click="value = option.value; open = false">
                                                <span class="rms-custom-option-icon" x-text="option.icon"></span>
                                                <span><strong x-text="option.label"></strong><small x-text="option.desc"></small></span>
                                                <span class="rms-custom-option-check" x-show="value === option.value">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="rms-ai-toggle-field {{ $allowProviderFallback ? 'is-enabled' : '' }}"
                                wire:click="$toggle('allowProviderFallback')"
                                wire:loading.attr="disabled"
                                wire:target="$toggle('allowProviderFallback')"
                                aria-pressed="{{ $allowProviderFallback ? 'true' : 'false' }}"
                            >
                                <span class="rms-ai-toggle-ui"></span>
                                <span class="rms-ai-toggle-copy">
                                    <strong>Allow provider fallback</strong>
                                    <small>Gunakan provider cadangan jika provider utama gagal.</small>
                                </span>
                                <span class="rms-ai-toggle-state">{{ $allowProviderFallback ? 'ON' : 'OFF' }}</span>
                            </button>

                            <button
                                type="button"
                                class="rms-ai-toggle-field {{ $emergencyFallback ? 'is-enabled' : '' }}"
                                wire:click="$toggle('emergencyFallback')"
                                wire:loading.attr="disabled"
                                wire:target="$toggle('emergencyFallback')"
                                aria-pressed="{{ $emergencyFallback ? 'true' : 'false' }}"
                            >
                                <span class="rms-ai-toggle-ui"></span>
                                <span class="rms-ai-toggle-copy">
                                    <strong>Emergency fallback</strong>
                                    <small>Prioritaskan availability saat provider utama unavailable.</small>
                                </span>
                                <span class="rms-ai-toggle-state">{{ $emergencyFallback ? 'ON' : 'OFF' }}</span>
                            </button>
                        </div>

                        <div class="rms-ai-provider-save-row">
                            <div>
                                <span class="rms-ai-live-dot"></span>
                                <span>Configuration is stored server-side and applied to new generations.</span>
                            </div>
                            <button type="button" class="rms-ai-save-button" wire:click="saveAiProviderConfiguration" wire:loading.attr="disabled" wire:target="saveAiProviderConfiguration">
                                <span class="rms-ai-save-label" wire:loading.class="rms-ai-save-hidden" wire:target="saveAiProviderConfiguration">Save AI Configuration</span>
                                <b class="rms-ai-save-check" wire:loading.class="rms-ai-save-hidden" wire:target="saveAiProviderConfiguration" aria-hidden="true">✓</b>
                                <span class="rms-ai-save-loading" wire:loading.class="rms-ai-save-visible" wire:target="saveAiProviderConfiguration">Saving...</span>
                            </button>
                        </div>
                    </section>
                        </section>

                    @endif

                    {{-- ACTIVITY LOG PAGE --}}
                    @if ($activeTab === 'activity')
                        <div class="rms-settings-activity-hero">
                            <div class="rms-settings-activity-hero-main">
                                <div class="rms-settings-activity-mark">◷</div>
                                <div class="rms-settings-activity-copy">
                                    <div class="rms-settings-activity-eyebrow">
                                        <span>OBSERVABILITY</span>
                                        <b>GLOBAL AUDIT</b>
                                    </div>
                                    <h2>Global Activity Log</h2>
                                    <p>Audit terpusat untuk worker, provider, credential, generation, failover, storage, dan system events.</p>
                                </div>
                            </div>
                            <div class="rms-settings-activity-live"><i></i><span>LIVE</span><small>Auto refresh 3s</small></div>
                        </div>
                    @endif

                    @if ($activeTab === 'activity')
                    <div class="rms-activity-section-divider">
                        <span>ACTIVITY STREAM</span>
                        <i></i>
                    </div>
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

                        <div class="rms-global-activity-toolbar" x-data="{ filtersOpen: false }">
                            <label>
                                <span>SEARCH</span>
                                <input type="search" wire:model.live.debounce.350ms="activitySearch" placeholder="Search activity..." autocomplete="off">
                            </label>

                            <button type="button" class="rms-mobile-filter-toggle" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()">
                                <span class="rms-mobile-filter-toggle-main">
                                    <span class="rms-mobile-filter-icon">⌄</span>
                                    <span>
                                        <strong>Filters</strong>
                                        <small>Category, status &amp; range</small>
                                    </span>
                                </span>
                                <span class="rms-mobile-filter-toggle-state" x-text="filtersOpen ? 'Hide' : 'Show'"></span>
                            </button>

                            <div class="rms-mobile-filter-group" :class="{ 'is-collapsed': !filtersOpen }">
                                <div class="rms-filter-field">
                                    <span class="rms-filter-label">CATEGORY</span>
                                <div class="rms-activity-select" x-data="{
                                    open: false,
                                    value: @js($activityCategory),
                                    options: [
                                        { value: 'all', label: 'All', desc: 'All activity categories' },
                                        { value: 'worker', label: 'Worker', desc: 'Queue & worker lifecycle' },
                                        { value: 'api', label: 'API', desc: 'Gateway & credential events' },
                                        { value: 'generation', label: 'Generation', desc: 'Image generation events' },
                                        { value: 'system', label: 'System', desc: 'System & application events' }
                                    ],
                                    get current() { return this.options.find(o => o.value === this.value) || this.options[0] },
                                    choose(option) { this.value = option.value; this.open = false; $wire.set('activityCategory', option.value) }
                                }" @click.outside="open = false" @keydown.escape.window="open = false">
                                    <button type="button" class="rms-activity-select-trigger" @click="open = !open" :aria-expanded="open.toString()">
                                        <span class="rms-activity-select-copy">
                                            <strong x-text="current.label"></strong>
                                            <small x-text="current.desc"></small>
                                        </span>
                                        <span class="rms-activity-select-chevron" :class="{ 'is-open': open }" aria-hidden="true"></span>
                                    </button>
                                    <div class="rms-activity-select-menu" x-cloak x-show="open" x-transition:enter="rms-select-enter" x-transition:enter-start="rms-select-enter-start" x-transition:enter-end="rms-select-enter-end" x-transition:leave="rms-select-leave" x-transition:leave-start="rms-select-leave-start" x-transition:leave-end="rms-select-leave-end">
                                        <div class="rms-activity-select-menu-head">FILTER BY CATEGORY</div>
                                        <div class="rms-activity-select-options">
                                            <template x-for="option in options" :key="option.value">
                                                <button type="button" class="rms-activity-select-option" :class="{ 'is-selected': value === option.value }" @click="choose(option)">
                                                    <span class="rms-filter-option-dot"></span>
                                                    <span class="rms-filter-option-copy"><strong x-text="option.label"></strong><small x-text="option.desc"></small></span>
                                                    <span class="rms-filter-option-check" x-show="value === option.value">✓</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rms-filter-field">
                                <span class="rms-filter-label">STATUS</span>
                                <div class="rms-activity-select" x-data="{
                                    open: false,
                                    value: @js($activityStatus),
                                    options: [
                                        { value: 'all', label: 'All', desc: 'Every event status' },
                                        { value: 'success', label: 'Success', desc: 'Completed successfully' },
                                        { value: 'info', label: 'Info', desc: 'Informational events' },
                                        { value: 'warning', label: 'Warning', desc: 'Attention may be required' },
                                        { value: 'error', label: 'Error', desc: 'Failed or blocked events' }
                                    ],
                                    get current() { return this.options.find(o => o.value === this.value) || this.options[0] },
                                    choose(option) { this.value = option.value; this.open = false; $wire.set('activityStatus', option.value) }
                                }" @click.outside="open = false" @keydown.escape.window="open = false">
                                    <button type="button" class="rms-activity-select-trigger" @click="open = !open" :aria-expanded="open.toString()">
                                        <span class="rms-activity-select-copy"><strong x-text="current.label"></strong><small x-text="current.desc"></small></span>
                                        <span class="rms-activity-select-chevron" :class="{ 'is-open': open }" aria-hidden="true"></span>
                                    </button>
                                    <div class="rms-activity-select-menu" x-cloak x-show="open" x-transition:enter="rms-select-enter" x-transition:enter-start="rms-select-enter-start" x-transition:enter-end="rms-select-enter-end" x-transition:leave="rms-select-leave" x-transition:leave-start="rms-select-leave-start" x-transition:leave-end="rms-select-leave-end">
                                        <div class="rms-activity-select-menu-head">FILTER BY STATUS</div>
                                        <div class="rms-activity-select-options">
                                            <template x-for="option in options" :key="option.value">
                                                <button type="button" class="rms-activity-select-option" :class="{ 'is-selected': value === option.value }" @click="choose(option)">
                                                    <span class="rms-filter-option-dot"></span><span class="rms-filter-option-copy"><strong x-text="option.label"></strong><small x-text="option.desc"></small></span><span class="rms-filter-option-check" x-show="value === option.value">✓</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rms-filter-field">
                                <span class="rms-filter-label">RANGE</span>
                                <div class="rms-activity-select" x-data="{
                                    open: false,
                                    value: @js($activityTimeframe),
                                    options: [
                                        { value: 'all', label: 'All time', desc: 'Entire activity history' },
                                        { value: 'today', label: 'Today', desc: 'Events from today' },
                                        { value: '7d', label: '7 days', desc: 'Last 7 days' },
                                        { value: '30d', label: '30 days', desc: 'Last 30 days' }
                                    ],
                                    get current() { return this.options.find(o => o.value === this.value) || this.options[0] },
                                    choose(option) { this.value = option.value; this.open = false; $wire.set('activityTimeframe', option.value) }
                                }" @click.outside="open = false" @keydown.escape.window="open = false">
                                    <button type="button" class="rms-activity-select-trigger" @click="open = !open" :aria-expanded="open.toString()">
                                        <span class="rms-activity-select-copy"><strong x-text="current.label"></strong><small x-text="current.desc"></small></span>
                                        <span class="rms-activity-select-chevron" :class="{ 'is-open': open }" aria-hidden="true"></span>
                                    </button>
                                    <div class="rms-activity-select-menu" x-cloak x-show="open" x-transition:enter="rms-select-enter" x-transition:enter-start="rms-select-enter-start" x-transition:enter-end="rms-select-enter-end" x-transition:leave="rms-select-leave" x-transition:leave-start="rms-select-leave-start" x-transition:leave-end="rms-select-leave-end">
                                        <div class="rms-activity-select-menu-head">FILTER BY RANGE</div>
                                        <div class="rms-activity-select-options">
                                            <template x-for="option in options" :key="option.value">
                                                <button type="button" class="rms-activity-select-option" :class="{ 'is-selected': value === option.value }" @click="choose(option)">
                                                    <span class="rms-filter-option-dot"></span><span class="rms-filter-option-copy"><strong x-text="option.label"></strong><small x-text="option.desc"></small></span><span class="rms-filter-option-check" x-show="value === option.value">✓</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>

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
                        <div class="rms-settings-activity-note rms-settings-activity-note-upgraded">
                            <div class="rms-settings-activity-note-icon">✓</div>
                            <div class="rms-settings-activity-note-copy">
                                <span>GLOBAL AUDIT ENABLED</span>
                                <strong>Every provider and credential event is recorded.</strong>
                                <small>Termasuk generation, worker lifecycle, failover, connection test, storage, dan system events.</small>
                            </div>
                            <div class="rms-settings-activity-note-badge"><i></i> ALL PROVIDERS</div>
                        </div>

                        <div class="rms-settings-activity-footer">
                            <span><i></i> Activity stream is live and server-managed</span>
                            <strong>All users · All providers · Auto refresh</strong>
                        </div>
                    @else
                        {{-- FOOTER --}}
                        <div class="rms-settings-panel-footer rms-openai-footer-upgraded rms-vg-footer">
                            <span><i></i> AI provider configuration is server-managed</span>
                            <strong>Encrypted credentials · Automatic failover ready</strong>
                        </div>
                    @endif


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
.rms-ai-toggle-field{position:relative;width:100%;border:0;text-align:left;font:inherit;color:inherit}
.rms-ai-toggle-field input{position:absolute;opacity:0;pointer-events:none}
.rms-ai-toggle-ui{position:relative;width:28px;height:17px;flex:0 0 28px;border-radius:999px;background:#e4e4e7;transition:background .25s cubic-bezier(.2,.8,.2,1)}
.rms-ai-toggle-ui:after{content:"";position:absolute;top:3px;left:3px;width:11px;height:11px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.12);transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.rms-ai-toggle-field.is-enabled{border-color:#b9e4c8;background:#f4fbf6}
.rms-ai-toggle-field.is-enabled .rms-ai-toggle-ui{background:#27ae60}
.rms-ai-toggle-field.is-enabled .rms-ai-toggle-ui:after{transform:translateX(11px)}
.rms-ai-toggle-copy{display:flex;min-width:0;flex:1;flex-direction:column;gap:2px}
.rms-ai-toggle-state{margin-left:auto;min-width:27px;text-align:center;font-size:7px;font-weight:900;letter-spacing:.08em;color:#a1a1aa}
.rms-ai-toggle-field.is-enabled .rms-ai-toggle-state{color:#1f9d55}

<style>
/* =========================================================
   FINAL SETTINGS UX PASS — VISUAL SEPARATION + ACTIVITY LOG
   ========================================================= */
.rms-settings-content{min-width:0}
.rms-settings-panel.rms-settings-panel-enter{min-width:0}
.rms-openai-form-upgraded{display:grid;grid-template-columns:minmax(0,1fr);gap:0!important}
.rms-openai-form-upgraded>.rms-provider-section-divider,
.rms-openai-form-upgraded>.rms-vg-key-manager-root,
.rms-openai-form-upgraded>.rms-vg-test-all-card,
.rms-openai-form-upgraded>.rms-ai-provider-section,
.rms-openai-form-upgraded>.rms-activity-section-divider,
.rms-openai-form-upgraded>.rms-openai-logs{grid-column:1/-1!important;width:100%;min-width:0}

.rms-provider-section-divider{
    display:grid;
    grid-template-columns:34px auto minmax(30px,1fr);
    align-items:center;
    gap:10px;
    margin:24px 0 11px;
    padding:0 2px;
}
.rms-provider-section-divider>span{
    display:grid;place-items:center;
    width:27px;height:22px;
    border:1px solid #e4e4e7;border-radius:7px;
    background:#fafafa;color:#71717a;
    font-size:7px;font-weight:900;letter-spacing:.08em;
}
.rms-provider-section-divider>div{min-width:0}
.rms-provider-section-divider strong{
    display:block;color:#71717a;font-size:7px;font-weight:900;letter-spacing:.14em;
}
.rms-provider-section-divider small{
    display:block;margin-top:3px;color:#b0b0b7;font-size:7px;line-height:1.3;
}
.rms-provider-section-divider>i{
    height:1px;background:linear-gradient(90deg,#e4e4e7,transparent);
}
.rms-provider-section-divider.rms-provider-divider-after-vercel{margin-top:28px}

.rms-vg-key-manager-root{margin-top:0!important}
.rms-vg-test-all-card{margin-top:12px!important}
.rms-ai-provider-section{
    margin-top:0!important;
    border-color:#e5e7eb;
    box-shadow:0 10px 28px rgba(15,23,42,.035);
}
.rms-ai-provider-section+.rms-provider-section-divider{margin-top:24px}
.rms-ai-provider-section-head{padding:17px 18px}
.rms-ai-agent-grid{grid-template-columns:minmax(0,1.15fr) minmax(300px,.85fr)}
.rms-ai-failover-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.rms-ai-provider-save-row{
    display:flex;align-items:center;justify-content:space-between;
    gap:16px;padding:14px 18px;
    border-top:1px solid #f0f0f2;background:#fafafa;
}
.rms-ai-provider-save-row>div{display:flex;align-items:center;gap:7px;min-width:0}
.rms-ai-provider-save-row>div>span:last-child{
    color:#8a8a93;font-size:8px;line-height:1.45;
}

.rms-activity-section-divider{
    display:flex;align-items:center;gap:10px;
    margin:25px 0 11px;padding:0 2px;
}
.rms-activity-section-divider span{
    color:#71717a;font-size:7px;font-weight:900;letter-spacing:.14em;white-space:nowrap;
}
.rms-activity-section-divider i{
    height:1px;flex:1;background:linear-gradient(90deg,#e4e4e7,transparent);
}

.rms-global-activity{
    margin-top:0!important;
    border:1px solid #e1e1e6!important;
    border-radius:18px!important;
    box-shadow:0 12px 34px rgba(15,23,42,.055)!important;
    background:#fff!important;
}
.rms-global-activity .rms-openai-logs-head{
    min-height:78px;
    padding:16px 18px;
    border-bottom:1px solid #ececf0;
    background:#fff;
}
.rms-global-activity .rms-openai-logs-title{gap:12px}
.rms-global-activity .rms-openai-terminal{
    width:38px;height:38px;flex-basis:38px;border-radius:11px;
    background:#18181b;color:#fff;border-color:#18181b;
}
.rms-global-activity .rms-openai-logs-title strong{font-size:13px}
.rms-global-activity .rms-openai-logs-title small{font-size:8.5px;max-width:600px}
.rms-global-activity .rms-openai-log-actions{gap:7px;flex-wrap:wrap}
.rms-global-scope-badge,.rms-openai-log-counter{height:25px!important}
.rms-global-copy-button,.rms-global-copy-all,
.rms-global-activity .rms-openai-log-actions>button{
    height:31px!important;padding:0 10px!important;border-radius:8px!important;font-size:8px!important;
}
.rms-global-activity .rms-openai-log-actions>button:last-child{
    border-color:#18181b;background:#18181b;color:#fff;
}
.rms-global-activity .rms-openai-log-actions>button:last-child:hover{background:#ef3030;border-color:#ef3030}

.rms-global-activity-toolbar{
    grid-template-columns:minmax(220px,1.8fr) repeat(3,minmax(110px,.65fr));
    gap:10px;
    padding:14px 18px;
    background:#fafafa;
    border-top:0;
    border-bottom:1px solid #ececf0;
}
.rms-global-activity-toolbar label{gap:6px}
.rms-global-activity-toolbar label>span{font-size:7px}
.rms-global-activity-toolbar input,
.rms-global-activity-toolbar select{
    height:36px;padding:0 10px;border-radius:9px;font-size:9px;
}
.rms-global-select-actions{
    grid-column:1/-1;
    margin:2px 0 0;
    padding-top:10px;
    border-top:1px solid #ededf0;
    justify-content:space-between;
}
.rms-global-select-actions button{height:27px;padding:0 9px;border-radius:7px;font-size:7px}
.rms-global-select-actions>span{font-size:7px}

.rms-global-activity-list{
    max-height:430px!important;
    padding:0!important;
    background:#fff;
}
.rms-global-activity .rms-openai-log-item{
    min-height:74px;
    display:grid!important;
    grid-template-columns:24px 7px minmax(0,1fr);
    gap:10px;
    align-items:start;
    padding:14px 18px;
    border-bottom:1px solid #f0f0f2;
    transition:background .16s ease;
}
.rms-global-activity .rms-openai-log-item:hover{background:#fafafa}
.rms-global-log-select-wrap{margin-top:2px}
.rms-global-activity .rms-openai-log-dot{margin-top:7px}
.rms-global-activity .rms-openai-log-content{min-width:0}
.rms-global-activity .rms-openai-log-top{
    display:flex;align-items:center;flex-wrap:wrap;gap:6px;
    margin-bottom:5px;
}
.rms-global-log-category{height:18px!important;padding:0 6px!important;font-size:6.5px!important}
.rms-global-log-user{font-size:7.5px}
.rms-global-activity .rms-openai-log-top time{margin-left:auto;font-size:7.5px}
.rms-global-activity .rms-openai-log-content>strong{
    color:#27272a;font-size:10px;line-height:1.45;font-weight:750;white-space:normal;
}
.rms-global-activity .rms-openai-log-content>small{
    max-width:900px;margin-top:4px;color:#8f8f98;font-size:8.5px;line-height:1.5;
}
.rms-global-activity .rms-vg-log-meta{
    gap:8px;margin-top:7px;font-size:7.5px;
}
.rms-global-activity .rms-vg-log-meta span{
    padding:3px 6px;border:1px solid #ededf0;border-radius:5px;background:#fafafa;
}
.rms-global-activity .rms-vg-log-meta span+span:before{display:none}
.rms-global-activity .rms-openai-logs-footer{
    min-height:42px;padding:0 18px;font-size:7.5px;background:#fafafa;
}

@media(max-width:900px){
    .rms-ai-agent-grid{grid-template-columns:1fr}
    .rms-global-activity-toolbar{grid-template-columns:1fr 1fr}
    .rms-global-activity-toolbar label:first-child{grid-column:1/-1}
}
@media(max-width:760px){
    .rms-provider-section-divider{grid-template-columns:30px auto 1fr;margin-top:20px}
    .rms-ai-failover-grid{grid-template-columns:1fr}
    .rms-ai-provider-save-row{align-items:stretch;flex-direction:column}
    .rms-ai-provider-save-row>button{width:100%}
    .rms-global-activity .rms-openai-logs-head{padding:14px}
    .rms-global-activity .rms-openai-log-actions{width:100%;justify-content:flex-start}
    .rms-global-activity-toolbar{grid-template-columns:1fr}
    .rms-global-activity-toolbar label:first-child{grid-column:auto}
    .rms-global-activity .rms-openai-log-item{
        grid-template-columns:22px 7px minmax(0,1fr);
        padding:13px 14px;
    }
    .rms-global-activity .rms-openai-log-top time{margin-left:0;width:100%}
}
@media(prefers-reduced-motion:reduce){
    .rms-ai-provider-section,.rms-global-activity .rms-openai-log-item{transition:none;animation:none}
}
</style>

<style>
/* FINAL ACTIVITY PRESENTATION — clear hierarchy, highlights and separators */
.rms-settings-activity-hero{
    display:flex;align-items:center;justify-content:space-between;gap:22px;
    margin:0 0 4px;padding:18px 20px;
    border:1px solid #e4e4e7;border-radius:18px;
    background:linear-gradient(135deg,#fff 0%,#fcfcfd 68%,#fff7f7 100%);
    box-shadow:0 10px 30px rgba(15,23,42,.045);
}
.rms-settings-activity-hero-main{display:flex;align-items:center;gap:14px;min-width:0}
.rms-settings-activity-mark{
    display:grid;place-items:center;width:46px;height:46px;flex:0 0 46px;
    border:1px solid #fecaca;border-radius:13px;background:#fff5f5;color:#ef3030;
    font-size:18px;font-weight:800;box-shadow:0 6px 18px rgba(239,48,48,.08);
}
.rms-settings-activity-copy{min-width:0}
.rms-settings-activity-eyebrow{display:flex;align-items:center;gap:7px;margin-bottom:5px}
.rms-settings-activity-eyebrow span,
.rms-settings-activity-eyebrow b{
    display:inline-flex;align-items:center;height:19px;padding:0 7px;border-radius:6px;
    font-size:6.5px;line-height:1;font-weight:900;letter-spacing:.11em;
}
.rms-settings-activity-eyebrow span{background:#18181b;color:#fff}
.rms-settings-activity-eyebrow b{border:1px solid #e4e4e7;background:#fff;color:#71717a}
.rms-settings-activity-copy h2{
    margin:0;color:#18181b;font-size:17px;line-height:1.2;font-weight:850;letter-spacing:-.025em;
}
.rms-settings-activity-copy p{
    margin:5px 0 0;max-width:720px;color:#8b8b94;font-size:8.5px;line-height:1.5;
}
.rms-settings-activity-live{
    display:grid;grid-template-columns:auto auto;grid-template-rows:auto auto;
    align-items:center;column-gap:7px;flex:0 0 auto;
    padding:9px 11px;border:1px solid #bbf7d0;border-radius:11px;background:#f0fdf4;
}
.rms-settings-activity-live i{
    grid-row:1/3;width:7px;height:7px;border-radius:50%;background:#22c55e;
    box-shadow:0 0 0 4px rgba(34,197,94,.10);
}
.rms-settings-activity-live span{color:#15803d;font-size:7px;font-weight:900;letter-spacing:.1em}
.rms-settings-activity-live small{margin-top:2px;color:#65a30d;font-size:6.5px}
.rms-settings-activity-note-upgraded{
    display:flex!important;align-items:center;gap:12px;
    margin:14px 0 0!important;padding:13px 15px!important;
    border:1px solid #dbeafe!important;border-radius:13px!important;
    background:linear-gradient(90deg,#f8fbff,#fff)!important;
}
.rms-settings-activity-note-icon{
    display:grid;place-items:center;width:30px;height:30px;flex:0 0 30px;
    border-radius:9px;background:#eff6ff;color:#2563eb;font-size:11px;font-weight:900;
}
.rms-settings-activity-note-copy{display:flex;flex:1;min-width:0;flex-direction:column}
.rms-settings-activity-note-copy>span{color:#2563eb;font-size:6.5px;font-weight:900;letter-spacing:.12em}
.rms-settings-activity-note-copy strong{margin-top:3px;color:#27272a;font-size:9px;font-weight:800}
.rms-settings-activity-note-copy small{margin-top:2px;color:#8b8b94;font-size:7.5px;line-height:1.4}
.rms-settings-activity-note-badge{
    display:inline-flex;align-items:center;gap:6px;height:24px;padding:0 8px;
    border:1px solid #e4e4e7;border-radius:7px;background:#fff;color:#71717a;
    font-size:6.5px;font-weight:900;letter-spacing:.08em;white-space:nowrap;
}
.rms-settings-activity-note-badge i{width:5px;height:5px;border-radius:50%;background:#22c55e}
.rms-settings-activity-footer{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    min-height:48px;margin-top:9px;padding:0 15px;border-top:1px solid #ececf0;
    color:#9a9aa2;font-size:7.5px;
}
.rms-settings-activity-footer span{display:inline-flex;align-items:center;gap:7px}
.rms-settings-activity-footer span i{width:5px;height:5px;border-radius:50%;background:#22c55e}
.rms-settings-activity-footer strong{font-size:7px;font-weight:700;color:#a1a1aa}
@media(max-width:700px){
    .rms-settings-activity-hero{align-items:stretch;flex-direction:column;padding:15px}
    .rms-settings-activity-live{align-self:flex-start}
    .rms-settings-activity-copy h2{font-size:15px}
    .rms-settings-activity-note-upgraded{align-items:flex-start!important;flex-wrap:wrap}
    .rms-settings-activity-note-copy{min-width:calc(100% - 44px)}
    .rms-settings-activity-note-badge{margin-left:42px}
    .rms-settings-activity-footer{align-items:flex-start;flex-direction:column;justify-content:center;padding:10px 0;gap:5px}
}
</style>

<style>
/* FINAL TYPOGRAPHY PASS — readable desktop, tablet and mobile */
.rms-settings-panel,
.rms-settings-panel *{box-sizing:border-box}
.rms-settings-activity-copy h2{font-size:clamp(20px,1.55vw,25px)!important;line-height:1.15!important}
.rms-settings-activity-copy p{font-size:clamp(11px,.78vw,13px)!important;line-height:1.55!important}
.rms-settings-activity-eyebrow span,
.rms-settings-activity-eyebrow b{font-size:9px!important;height:23px!important;padding:0 9px!important}
.rms-settings-activity-live span{font-size:9px!important}
.rms-settings-activity-live small{font-size:8px!important}
.rms-settings-activity-note-copy>span{font-size:8px!important}
.rms-settings-activity-note-copy strong{font-size:12px!important;line-height:1.35!important}
.rms-settings-activity-note-copy small{font-size:10px!important;line-height:1.45!important}
.rms-settings-activity-note-badge{font-size:8px!important;height:29px!important}
.rms-settings-activity-footer{font-size:10px!important}
.rms-settings-activity-footer strong{font-size:9px!important}

.rms-global-activity .rms-openai-logs-title strong{font-size:15px!important}
.rms-global-activity .rms-openai-logs-title small{font-size:10px!important;line-height:1.45!important}
.rms-global-scope-badge{font-size:8px!important;height:29px!important}
.rms-global-activity .rms-openai-log-counter{font-size:9px!important}
.rms-global-copy-button,
.rms-global-copy-all,
.rms-global-activity .rms-openai-log-actions>button{font-size:9px!important;height:34px!important;padding:0 12px!important}
.rms-global-activity-toolbar label>span{font-size:8px!important}
.rms-global-activity-toolbar input,
.rms-global-activity-toolbar select{height:40px!important;font-size:10px!important}
.rms-global-select-actions button{height:30px!important;font-size:8px!important}
.rms-global-select-actions>span{font-size:8px!important}

.rms-global-activity .rms-openai-log-item{min-height:88px!important;padding:16px 18px!important}
.rms-global-log-category{font-size:8px!important;height:21px!important;padding:0 7px!important}
.rms-global-log-user{font-size:9px!important}
.rms-global-activity .rms-openai-log-top time{font-size:9px!important}
.rms-global-activity .rms-openai-log-content>strong{font-size:12px!important;line-height:1.45!important}
.rms-global-activity .rms-openai-log-content>small{font-size:10px!important;line-height:1.5!important}
.rms-global-activity .rms-vg-log-meta{font-size:9px!important}
.rms-global-activity .rms-vg-log-meta span{padding:4px 7px!important}
.rms-global-activity .rms-openai-logs-footer{font-size:9px!important;min-height:46px!important}

@media(max-width:900px){
 .rms-settings-activity-hero{gap:16px}
 .rms-global-activity .rms-openai-log-actions{gap:7px}
 .rms-global-activity .rms-openai-log-item{min-height:0!important}
}
@media(max-width:640px){
 .rms-settings-activity-hero{padding:16px!important;border-radius:15px!important}
 .rms-settings-activity-hero-main{align-items:flex-start}
 .rms-settings-activity-mark{width:42px!important;height:42px!important;flex-basis:42px!important}
 .rms-settings-activity-copy h2{font-size:19px!important}
 .rms-settings-activity-copy p{font-size:11px!important}
 .rms-settings-activity-eyebrow span,.rms-settings-activity-eyebrow b{font-size:7.5px!important;height:21px!important}
 .rms-settings-activity-live{width:max-content}
 .rms-global-activity .rms-openai-logs-head{padding:15px!important}
 .rms-global-activity .rms-openai-logs-title strong{font-size:14px!important}
 .rms-global-activity .rms-openai-logs-title small{font-size:9px!important}
 .rms-global-activity .rms-openai-log-actions{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));width:100%!important}
 .rms-global-activity .rms-openai-log-actions .rms-global-scope-badge,
 .rms-global-activity .rms-openai-log-actions .rms-openai-log-counter{justify-self:start}
 .rms-global-copy-button,.rms-global-copy-all,
 .rms-global-activity .rms-openai-log-actions>button{width:100%!important}
 .rms-global-activity .rms-openai-log-item{
   grid-template-columns:22px 7px minmax(0,1fr)!important;
   padding:15px 13px!important;
 }
 .rms-global-activity .rms-openai-log-content>strong{font-size:11px!important}
 .rms-global-activity .rms-openai-log-content>small{font-size:9.5px!important}
 .rms-global-activity .rms-openai-log-top time{margin-left:0!important;width:100%!important;font-size:8.5px!important}
 .rms-settings-activity-note-upgraded{padding:13px!important}
 .rms-settings-activity-note-copy strong{font-size:11px!important}
 .rms-settings-activity-note-copy small{font-size:9px!important}
 .rms-settings-activity-note-badge{margin-left:42px!important}
 .rms-settings-activity-footer{font-size:9px!important}
}
@media(max-width:420px){
 .rms-settings-activity-copy h2{font-size:17px!important}
 .rms-settings-activity-copy p{font-size:10px!important}
 .rms-settings-activity-eyebrow{flex-wrap:wrap}
 .rms-global-activity .rms-openai-log-item{grid-template-columns:20px 6px minmax(0,1fr)!important;padding:14px 10px!important}
 .rms-global-activity .rms-openai-log-content>strong{font-size:10.5px!important}
 .rms-global-activity .rms-openai-log-content>small{font-size:9px!important}
 .rms-global-activity .rms-vg-log-meta{font-size:8px!important}
}
@media(prefers-reduced-motion:reduce){
 .rms-settings-activity-hero,.rms-settings-activity-note-upgraded{animation:none!important}
}
</style>
<style>
/* CUSTOM ACTIVITY FILTERS — premium animated select */
[x-cloak]{display:none!important}
.rms-global-activity-toolbar{
    grid-template-columns:minmax(250px,1.8fr) repeat(3,minmax(165px,.72fr))!important;
    align-items:end!important;
}
.rms-filter-field{display:grid;gap:7px;min-width:0}
.rms-filter-label{
    display:block;color:#71717a;font-size:8px;font-weight:900;letter-spacing:.13em;
}
.rms-activity-select{position:relative;width:100%;z-index:20}
.rms-activity-select:focus-within{z-index:100}
.rms-activity-select-trigger{
    display:flex;align-items:center;justify-content:space-between;gap:10px;width:100%;height:46px;
    padding:0 12px;border:1px solid #dedee4;border-radius:11px;background:#fff;color:#27272a;
    text-align:left;cursor:pointer;outline:none;
    box-shadow:0 2px 6px rgba(15,23,42,.025);
    transition:border-color .2s ease,box-shadow .25s ease,transform .2s ease,background .2s ease;
}
.rms-activity-select-trigger:hover{border-color:#c7c7ce;background:#fcfcfd;transform:translateY(-1px)}
.rms-activity-select-trigger:focus-visible{border-color:#18181b;box-shadow:0 0 0 4px rgba(24,24,27,.06)}
.rms-activity-select-copy{display:flex;min-width:0;flex-direction:column}
.rms-activity-select-copy strong{overflow:hidden;color:#3f3f46;font-size:10px;font-weight:800;text-overflow:ellipsis;white-space:nowrap}
.rms-activity-select-copy small{overflow:hidden;margin-top:3px;color:#a1a1aa;font-size:7.5px;line-height:1.1;text-overflow:ellipsis;white-space:nowrap}
.rms-activity-select-chevron{
    display:grid;place-items:center;width:25px;height:25px;flex:0 0 25px;border-radius:7px;
    color:#71717a;font-size:15px;line-height:1;transition:transform .25s cubic-bezier(.2,.8,.2,1),background .2s ease,color .2s ease;
}
.rms-activity-select-chevron.is-open{transform:rotate(180deg);background:#f4f4f5;color:#18181b}
.rms-activity-select-menu{
    position:absolute;top:calc(100% + 8px);left:0;width:100%;min-width:220px;overflow:hidden;
    border:1px solid #e2e2e7;border-radius:14px;background:rgba(255,255,255,.98);
    box-shadow:0 20px 55px rgba(15,23,42,.14),0 4px 12px rgba(15,23,42,.05);
    backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    transform-origin:top center;z-index:99999;
}
.rms-activity-select-menu-head{
    padding:10px 12px;border-bottom:1px solid #f0f0f2;background:linear-gradient(180deg,#fff,#fafafa);
    color:#a1a1aa;font-size:7px;font-weight:900;letter-spacing:.13em;
}
.rms-activity-select-options{padding:5px}
.rms-activity-select-option{
    display:flex;align-items:center;gap:9px;width:100%;min-height:48px;padding:7px 9px;
    border:0;border-radius:9px;background:transparent;color:#3f3f46;text-align:left;cursor:pointer;
    transition:background .16s ease,transform .16s ease;
}
.rms-activity-select-option:hover{background:#f7f7f8;transform:translateX(2px)}
.rms-activity-select-option.is-selected{background:#fff1f2}
.rms-filter-option-dot{width:7px;height:7px;flex:0 0 7px;border-radius:50%;background:#d4d4d8;transition:transform .2s ease,background .2s ease,box-shadow .2s ease}
.rms-activity-select-option.is-selected .rms-filter-option-dot{background:#ef3030;box-shadow:0 0 0 4px rgba(239,48,48,.09);transform:scale(1.05)}
.rms-filter-option-copy{display:flex;flex:1;min-width:0;flex-direction:column}
.rms-filter-option-copy strong{font-size:9px;font-weight:800;color:#3f3f46}
.rms-filter-option-copy small{margin-top:2px;color:#a1a1aa;font-size:7px;line-height:1.3}
.rms-filter-option-check{display:grid;place-items:center;width:21px;height:21px;border-radius:6px;background:#ef3030;color:#fff;font-size:10px;font-weight:900;box-shadow:0 4px 10px rgba(239,48,48,.16)}
.rms-select-enter{transition:opacity .2s ease,transform .24s cubic-bezier(.2,.8,.2,1)}
.rms-select-enter-start{opacity:0;transform:translateY(-7px) scale(.975)}
.rms-select-enter-end{opacity:1;transform:translateY(0) scale(1)}
.rms-select-leave{transition:opacity .14s ease,transform .14s ease}
.rms-select-leave-start{opacity:1;transform:translateY(0) scale(1)}
.rms-select-leave-end{opacity:0;transform:translateY(-4px) scale(.985)}
.rms-global-activity-toolbar>label:first-child input{
    height:46px!important;border-radius:11px!important;font-size:10px!important;padding:0 12px!important;
}
@media(max-width:1100px){
 .rms-global-activity-toolbar{grid-template-columns:minmax(200px,1.5fr) repeat(3,minmax(135px,1fr))!important}
}
@media(max-width:900px){
 .rms-global-activity-toolbar{grid-template-columns:1fr 1fr!important}
 .rms-global-activity-toolbar>label:first-child{grid-column:1/-1}
 .rms-filter-field{min-width:0}
}
@media(max-width:640px){
 .rms-global-activity-toolbar{grid-template-columns:1fr!important;padding:13px!important}
 .rms-global-activity-toolbar>label:first-child{grid-column:auto}
 .rms-activity-select-trigger{height:44px}
 .rms-activity-select-menu{min-width:0;width:100%}
}
</style>
<style>
/* FINAL ACTIVITY LAYOUT — aligned actions + compact responsive mobile */
.rms-global-activity .rms-openai-logs-head{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center!important;
    gap:20px!important;
}
.rms-global-activity .rms-openai-log-actions{
    display:grid!important;
    grid-template-columns:auto auto repeat(3,minmax(104px,auto));
    align-items:center!important;
    justify-content:end!important;
    gap:8px!important;
    width:auto!important;
    flex-wrap:nowrap!important;
}
.rms-global-activity .rms-openai-log-actions>button{
    width:auto!important;
    min-width:104px!important;
    height:38px!important;
    margin:0!important;
    white-space:nowrap;
}
.rms-global-activity .rms-global-scope-badge,
.rms-global-activity .rms-openai-log-counter{
    height:38px!important;
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    white-space:nowrap;
}
.rms-global-activity .rms-openai-log-counter{padding:0 9px!important}
.rms-global-activity-toolbar{
    grid-template-columns:minmax(230px,1.7fr) repeat(3,minmax(160px,.75fr))!important;
    align-items:end!important;
    gap:10px!important;
}
.rms-global-activity-toolbar .rms-filter-field{min-width:0}
.rms-global-select-actions{
    grid-column:1/-1!important;
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    min-height:34px;
    margin-top:0!important;
    padding-top:10px!important;
    border-top:1px solid #ededf0;
}
.rms-global-select-actions button{height:31px!important;font-size:8px!important;padding:0 10px!important}

/* Tablet */
@media(max-width:1100px){
    .rms-global-activity .rms-openai-logs-head{grid-template-columns:1fr!important;gap:14px!important}
    .rms-global-activity .rms-openai-log-actions{
        width:100%!important;
        grid-template-columns:auto auto repeat(3,minmax(0,1fr))!important;
        justify-content:stretch!important;
    }
    .rms-global-activity .rms-openai-log-actions>button{width:100%!important;min-width:0!important}
    .rms-global-activity-toolbar{grid-template-columns:minmax(200px,1.5fr) repeat(3,minmax(130px,1fr))!important}
}

/* Mobile: keep it compact instead of creating a very tall control stack */
@media(max-width:640px){
    .rms-global-activity .rms-openai-logs-head{
        padding:15px!important;
        gap:12px!important;
    }
    .rms-global-activity .rms-openai-log-actions{
        display:grid!important;
        grid-template-columns:1fr 1fr!important;
        width:100%!important;
        gap:7px!important;
    }
    .rms-global-activity .rms-openai-log-actions .rms-global-scope-badge{
        justify-self:start!important;
        width:max-content!important;
        min-width:0!important;
        height:32px!important;
    }
    .rms-global-activity .rms-openai-log-actions .rms-openai-log-counter{
        justify-self:end!important;
        width:max-content!important;
        height:32px!important;
    }
    .rms-global-activity .rms-openai-log-actions>button{
        min-width:0!important;
        height:38px!important;
    }
    .rms-global-activity .rms-openai-log-actions>button:last-child{
        grid-column:1/-1!important;
    }

    .rms-global-activity-toolbar{
        grid-template-columns:1fr 1fr!important;
        gap:9px!important;
        padding:12px!important;
    }
    .rms-global-activity-toolbar>label:first-child{
        grid-column:1/-1!important;
    }
    .rms-global-activity-toolbar .rms-filter-field{
        width:100%;
    }
    .rms-activity-select-trigger{height:43px!important}
    .rms-global-activity-toolbar>label:first-child input{
        height:43px!important;
    }
    .rms-global-select-actions{
        grid-column:1/-1!important;
        min-height:35px!important;
        padding-top:9px!important;
    }
}

/* Very small phones */
@media(max-width:390px){
    .rms-global-activity .rms-openai-logs-head{padding:13px!important}
    .rms-global-activity .rms-openai-log-actions{gap:6px!important}
    .rms-global-activity .rms-openai-log-actions>button{height:36px!important;font-size:8px!important}
    .rms-global-activity-toolbar{gap:8px!important;padding:10px!important}
    .rms-global-activity-toolbar .rms-filter-field .rms-filter-label{font-size:7px!important}
    .rms-activity-select-trigger{padding:0 9px!important}
    .rms-activity-select-copy strong{font-size:9px!important}
    .rms-activity-select-copy small{font-size:7px!important}
}
</style>
<style>
/* MOBILE FILTER DRAWER + DROPDOWN CONTAINMENT */
.rms-mobile-filter-toggle{display:none}
.rms-mobile-filter-group{display:contents}
.rms-activity-select-menu{
    width:100%!important;
    min-width:0!important;
    max-width:min(300px,calc(100vw - 32px))!important;
}
.rms-filter-field:last-child .rms-activity-select-menu{
    left:auto!important;
    right:0!important;
}
@media(max-width:640px){
    .rms-mobile-filter-toggle{
        display:flex;
        align-items:center;
        justify-content:space-between;
        width:100%;
        min-height:48px;
        padding:0 12px;
        border:1px solid #e4e4e7;
        border-radius:11px;
        background:#fff;
        color:#27272a;
        cursor:pointer;
        text-align:left;
        box-shadow:0 2px 8px rgba(15,23,42,.025);
        transition:border-color .2s ease,background .2s ease,box-shadow .25s ease,transform .2s ease;
    }
    .rms-mobile-filter-toggle:hover{border-color:#c7c7ce;background:#fcfcfd;transform:translateY(-1px)}
    .rms-mobile-filter-toggle[aria-expanded="true"]{border-color:#d4d4d8;background:#fafafa;box-shadow:0 4px 14px rgba(15,23,42,.04)}
    .rms-mobile-filter-toggle-main{display:flex;align-items:center;gap:10px}
    .rms-mobile-filter-icon{
        display:grid;place-items:center;width:27px;height:27px;
        border-radius:8px;background:#f4f4f5;color:#52525b;font-size:14px;
        transition:transform .3s cubic-bezier(.2,.8,.2,1),background .2s ease;
    }
    .rms-mobile-filter-toggle[aria-expanded="true"] .rms-mobile-filter-icon{
        transform:rotate(180deg);background:#fff1f2;color:#ef3030;
    }
    .rms-mobile-filter-toggle-main strong{display:block;font-size:10px;font-weight:850;color:#27272a}
    .rms-mobile-filter-toggle-main small{display:block;margin-top:2px;font-size:7.5px;color:#a1a1aa}
    .rms-mobile-filter-toggle-state{
        padding:5px 8px;border:1px solid #e4e4e7;border-radius:7px;background:#fff;
        color:#71717a;font-size:7px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;
    }
    .rms-mobile-filter-group{
        display:grid!important;
        grid-template-columns:1fr 1fr;
        gap:9px;
        min-width:0;
        max-height:600px;
        overflow:visible;
        opacity:1;
        transform:translateY(0);
        transition:max-height .32s cubic-bezier(.2,.8,.2,1),opacity .2s ease,transform .32s cubic-bezier(.2,.8,.2,1);
    }
    .rms-mobile-filter-group.is-collapsed{
        max-height:0!important;
        opacity:0;
        transform:translateY(-8px);
        overflow:hidden!important;
        pointer-events:none;
        margin-top:-2px;
    }
    .rms-mobile-filter-group .rms-filter-field:first-child{grid-column:1/-1}
    .rms-mobile-filter-group .rms-filter-field:last-child{grid-column:1/-1}
    .rms-global-activity-toolbar{grid-template-columns:1fr!important}
    .rms-global-activity-toolbar>label:first-child{grid-column:auto!important}
    .rms-global-activity-toolbar .rms-mobile-filter-toggle{grid-column:1/-1}
    .rms-global-activity-toolbar .rms-global-select-actions{grid-column:1/-1}
    .rms-activity-select-menu{
        width:100%!important;
        max-width:calc(100vw - 28px)!important;
        right:auto!important;
        left:0!important;
    }
    .rms-mobile-filter-group .rms-filter-field:last-child .rms-activity-select-menu{
        right:0!important;
        left:auto!important;
    }
}
@media(min-width:641px){
    .rms-mobile-filter-group{display:contents!important}
}
@media(max-width:390px){
    .rms-mobile-filter-toggle{min-height:45px}
    .rms-mobile-filter-group{gap:8px}
}
</style>
<style>
/* FINAL FILTER MICRO-UX — centered chevrons + equal mobile fields */
.rms-activity-select-trigger{min-height:46px}
.rms-activity-select-chevron{
    position:relative;
    display:grid!important;
    place-items:center!important;
    width:28px!important;
    height:28px!important;
    flex:0 0 28px!important;
    border:1px solid #ececf0;
    border-radius:8px;
    background:#fafafa;
    color:transparent!important;
    font-size:0!important;
    line-height:0!important;
    transition:transform .28s cubic-bezier(.2,.8,.2,1),background .2s ease,border-color .2s ease,box-shadow .2s ease;
}
.rms-activity-select-chevron::before{
    content:"";
    width:6px;
    height:6px;
    margin-top:-3px;
    border-right:1.8px solid #71717a;
    border-bottom:1.8px solid #71717a;
    transform:rotate(45deg);
    transition:transform .28s cubic-bezier(.2,.8,.2,1),border-color .2s ease;
}
.rms-activity-select-trigger:hover .rms-activity-select-chevron{
    background:#f4f4f5;border-color:#e4e4e7;
}
.rms-activity-select-chevron.is-open{
    transform:rotate(180deg);
    background:#fff1f2!important;
    border-color:#fecaca!important;
    box-shadow:0 3px 10px rgba(239,48,48,.08);
}
.rms-activity-select-chevron.is-open::before{border-color:#ef3030}

/* Keep every mobile filter the same width */
@media(max-width:640px){
    .rms-mobile-filter-group{
        grid-template-columns:1fr!important;
        gap:9px!important;
    }
    .rms-mobile-filter-group .rms-filter-field,
    .rms-mobile-filter-group .rms-filter-field:first-child,
    .rms-mobile-filter-group .rms-filter-field:last-child{
        grid-column:1/-1!important;
        width:100%!important;
        min-width:0!important;
    }
    .rms-mobile-filter-group .rms-activity-select,
    .rms-mobile-filter-group .rms-activity-select-trigger{
        width:100%!important;
    }
    .rms-activity-select-trigger{height:46px!important}
}
@media(max-width:390px){
    .rms-activity-select-trigger{height:44px!important}
    .rms-activity-select-chevron{width:27px!important;height:27px!important;flex-basis:27px!important}
}
</style>
<style>
/* MOBILE FILTER TOGGLE — refined chevron + hidden by default */
@media(max-width:640px){
    .rms-mobile-filter-toggle{
        min-height:52px!important;
        padding:0 13px!important;
        border-radius:12px!important;
        background:linear-gradient(180deg,#fff,#fafafa)!important;
        box-shadow:0 3px 12px rgba(15,23,42,.035)!important;
    }
    .rms-mobile-filter-toggle-main{gap:11px!important}
    .rms-mobile-filter-icon{
        position:relative;
        width:30px!important;height:30px!important;
        flex:0 0 30px!important;
        border:1px solid #e4e4e7;
        background:#f7f7f8!important;
        color:transparent!important;
        font-size:0!important;
        transform:none!important;
        transition:background .22s ease,border-color .22s ease,box-shadow .22s ease;
    }
    .rms-mobile-filter-icon::before{
        content:"";
        width:7px;height:7px;
        border-right:1.8px solid #71717a;
        border-bottom:1.8px solid #71717a;
        transform:rotate(45deg);
        margin-top:-4px;
        transition:transform .3s cubic-bezier(.2,.8,.2,1),border-color .22s ease;
    }
    .rms-mobile-filter-toggle[aria-expanded="true"] .rms-mobile-filter-icon{
        background:#fff1f2!important;
        border-color:#fecaca!important;
        box-shadow:0 3px 10px rgba(239,48,48,.08);
    }
    .rms-mobile-filter-toggle[aria-expanded="true"] .rms-mobile-filter-icon::before{
        transform:rotate(225deg);
        margin-top:4px;
        border-color:#ef3030;
    }
    .rms-mobile-filter-toggle-main strong{
        font-size:11px!important;
        letter-spacing:-.01em;
    }
    .rms-mobile-filter-toggle-main small{font-size:8px!important}
    .rms-mobile-filter-toggle-state{
        min-width:44px;
        height:25px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:0 8px!important;
        border-radius:8px!important;
        font-size:7px!important;
        background:#fff!important;
    }
}
</style>
<style>
/* FINAL MOBILE DROPDOWN FIX — menus flow with content instead of covering logs */
.rms-global-activity{
    margin-top:14px!important;
    margin-bottom:16px!important;
}
.rms-activity-section-divider{
    margin-top:16px!important;
    margin-bottom:10px!important;
}
@media(max-width:640px){
    .rms-global-activity{
        margin-top:12px!important;
        margin-bottom:14px!important;
    }
    .rms-activity-section-divider{
        margin-top:14px!important;
        margin-bottom:9px!important;
    }
    /* The mobile menu must participate in document flow. */
    .rms-mobile-filter-group .rms-activity-select-menu{
        position:relative!important;
        top:auto!important;
        left:auto!important;
        right:auto!important;
        width:100%!important;
        min-width:0!important;
        max-width:none!important;
        margin-top:7px!important;
        z-index:30!important;
        transform-origin:top center!important;
    }
    .rms-mobile-filter-group .rms-filter-field:last-child .rms-activity-select-menu{
        left:auto!important;
        right:auto!important;
    }
    .rms-mobile-filter-group .rms-activity-select{
        z-index:20!important;
    }
    .rms-mobile-filter-group .rms-filter-field:has(.rms-activity-select-menu[style*="display: none"]){
        z-index:auto!important;
    }
    /* Keep the filter toggle arrow perfectly centered. */
    .rms-mobile-filter-icon{
        display:grid!important;
        place-items:center!important;
        align-content:center!important;
    }
    .rms-mobile-filter-icon::before{
        display:block;
        flex:0 0 auto;
    }
}
</style>
<style>
/* GLOBAL ACTIVITY — inset card inside the settings container */
.rms-global-activity{
    width:calc(100% - 28px)!important;
    max-width:calc(100% - 28px)!important;
    margin:14px 14px 18px!important;
    border:1px solid #dedee5!important;
    border-radius:18px!important;
    overflow:hidden!important;
    box-shadow:0 10px 28px rgba(15,23,42,.055)!important;
}
@media(max-width:640px){
    .rms-global-activity{
        width:calc(100% - 20px)!important;
        max-width:calc(100% - 20px)!important;
        margin:10px 10px 14px!important;
        border-radius:15px!important;
    }
}
</style>
<style>
/* ACTIVITY STREAM DIVIDER — align with the inset Global Activity card */
.rms-activity-section-divider{
    width:calc(100% - 28px)!important;
    margin:16px 14px 10px!important;
    box-sizing:border-box!important;
}
.rms-activity-section-divider span{
    white-space:nowrap;
}
.rms-activity-section-divider i{
    min-width:0;
}
@media(max-width:640px){
    .rms-activity-section-divider{
        width:calc(100% - 20px)!important;
        margin:14px 10px 9px!important;
    }
}
</style>
<style>
/* AI PROVIDER — independent section containers */
.rms-openai-form-upgraded{
    gap:0!important;
}
.rms-provider-block{
    position:relative;
    margin:0 0 22px;
    padding:0;
    border:1px solid #e4e4e8;
    border-radius:22px;
    background:linear-gradient(180deg,#fff 0%,#fcfcfd 100%);
    box-shadow:0 12px 34px rgba(15,23,42,.045);
    overflow:hidden;
    isolation:isolate;
    transition:transform .3s cubic-bezier(.2,.8,.2,1),box-shadow .3s ease,border-color .3s ease;
}
.rms-provider-block::before{
    content:"";
    position:absolute;
    inset:0 0 auto;
    height:3px;
    background:linear-gradient(90deg,#18181b 0%,#ef3030 48%,#22c55e 100%);
    opacity:.9;
}
.rms-provider-block:hover{
    transform:translateY(-1px);
    border-color:#d9d9df;
    box-shadow:0 18px 44px rgba(15,23,42,.065);
}
.rms-provider-block-head{
    display:grid;
    grid-template-columns:42px minmax(0,1fr) auto;
    align-items:center;
    gap:14px;
    padding:20px 22px 18px;
    border-bottom:1px solid #ededf0;
    background:linear-gradient(180deg,rgba(250,250,251,.9),rgba(255,255,255,.96));
}
.rms-provider-block-index{
    display:grid;
    place-items:center;
    width:40px;height:40px;
    border:1px solid #dedee4;
    border-radius:12px;
    background:#fff;
    color:#27272a;
    font-size:10px;
    font-weight:900;
    letter-spacing:.08em;
    box-shadow:0 5px 14px rgba(15,23,42,.045);
}
.rms-provider-block-copy{min-width:0}
.rms-provider-block-copy span{
    display:inline-flex;
    align-items:center;
    margin-bottom:4px;
    color:#ef3030;
    font-size:7px;
    font-weight:900;
    letter-spacing:.14em;
    text-transform:uppercase;
}
.rms-provider-block-copy strong{
    display:block;
    color:#18181b;
    font-size:17px;
    line-height:1.15;
    font-weight:850;
    letter-spacing:-.025em;
}
.rms-provider-block-copy small{
    display:block;
    margin-top:5px;
    color:#8f8f98;
    font-size:9px;
    line-height:1.5;
}
.rms-provider-block-state{
    display:inline-flex;
    align-items:center;
    gap:7px;
    min-height:30px;
    padding:0 10px;
    border:1px solid #e4e4e7;
    border-radius:999px;
    background:#fff;
    color:#71717a;
    font-size:7px;
    font-weight:850;
    letter-spacing:.06em;
    text-transform:uppercase;
    white-space:nowrap;
}
.rms-provider-block-state i{
    width:7px;height:7px;border-radius:50%;
    background:#22c55e;
    box-shadow:0 0 0 4px rgba(34,197,94,.08);
}
.rms-provider-block-state.is-pending i{
    background:#f59e0b;
    box-shadow:0 0 0 4px rgba(245,158,11,.08);
}
.rms-provider-block .rms-vg-key-manager-root,
.rms-provider-block .rms-ai-provider-section{
    margin:18px 20px 20px!important;
}
.rms-provider-block .rms-vg-test-all-card{
    margin:0 20px 20px!important;
}
.rms-provider-block-agent .rms-ai-provider-section,
.rms-provider-block-failover .rms-ai-provider-section{
    box-shadow:none!important;
    border-color:#e8e8ec!important;
}
.rms-provider-block-agent .rms-ai-provider-section-head,
.rms-provider-block-failover .rms-ai-provider-section-head{
    padding:16px 18px!important;
}
.rms-provider-block-agent .rms-ai-provider-section-head .rms-ai-provider-icon,
.rms-provider-block-failover .rms-ai-provider-section-head .rms-ai-provider-icon{
    width:38px;height:38px;
}
@media(max-width:760px){
    .rms-provider-block{
        margin-bottom:16px;
        border-radius:17px;
    }
    .rms-provider-block-head{
        grid-template-columns:36px minmax(0,1fr);
        padding:16px 15px;
        gap:11px;
    }
    .rms-provider-block-index{
        width:36px;height:36px;border-radius:10px;font-size:9px;
    }
    .rms-provider-block-copy strong{font-size:14px}
    .rms-provider-block-copy small{font-size:8px;max-width:100%}
    .rms-provider-block-state{
        grid-column:2;
        justify-self:start;
        min-height:26px;
        font-size:6.5px;
    }
    .rms-provider-block .rms-vg-key-manager-root,
    .rms-provider-block .rms-ai-provider-section{
        margin:12px 11px 12px!important;
    }
    .rms-provider-block .rms-vg-test-all-card{
        margin:0 11px 12px!important;
    }
}
@media(max-width:390px){
    .rms-provider-block-head{padding:14px 12px}
    .rms-provider-block-copy strong{font-size:13px}
    .rms-provider-block-copy small{font-size:7.5px}
}
@media(prefers-reduced-motion:reduce){
    .rms-provider-block{transition:none}
}
</style><style>
/* Provider block entrance + decorative micro-interactions */
.rms-provider-block{
    animation:rmsProviderBlockIn .48s cubic-bezier(.2,.8,.2,1) both;
}
.rms-provider-block:nth-of-type(2){animation-delay:.05s}
.rms-provider-block:nth-of-type(3){animation-delay:.1s}
.rms-provider-block-head::after{
    content:"";
    position:absolute;
    top:16px;
    right:22px;
    width:44px;
    height:44px;
    border-radius:50%;
    border:1px solid rgba(239,48,48,.07);
    box-shadow:0 0 0 8px rgba(239,48,48,.025);
    pointer-events:none;
}
.rms-provider-block-agent .rms-provider-block-head::after{
    border-color:rgba(245,158,11,.1);
    box-shadow:0 0 0 8px rgba(245,158,11,.025);
}
.rms-provider-block-failover .rms-provider-block-head::after{
    border-color:rgba(34,197,94,.1);
    box-shadow:0 0 0 8px rgba(34,197,94,.025);
}
@keyframes rmsProviderBlockIn{
    from{opacity:0;transform:translateY(8px)}
    to{opacity:1;transform:translateY(0)}
}
@media(max-width:760px){
    .rms-provider-block-head::after{right:12px;top:13px;width:36px;height:36px}
}
@media(prefers-reduced-motion:reduce){
    .rms-provider-block{animation:none}
}
</style><style>
/* FINAL PROVIDER CONTAINERS — distinct but intentionally muted */
.rms-provider-block{
    box-sizing:border-box!important;
    width:100%!important;
    max-width:100%!important;
    margin:0 0 24px!important;
    padding:0 0 2px!important;
    border-width:1px!important;
}
.rms-provider-block-vercel{
    background:linear-gradient(180deg,#fffafa 0%,#fff 38%,#fcfcfd 100%)!important;
    border-color:#eadfe0!important;
}
.rms-provider-block-agent{
    background:linear-gradient(180deg,#fffdf8 0%,#fff 38%,#fcfcfd 100%)!important;
    border-color:#ebe5d8!important;
}
.rms-provider-block-failover{
    background:linear-gradient(180deg,#f9fcfa 0%,#fff 38%,#fcfcfd 100%)!important;
    border-color:#dfe9e2!important;
}
.rms-provider-block-vercel::before{background:linear-gradient(90deg,#27272a,#e6b5b8,#70c98b)!important}
.rms-provider-block-agent::before{background:linear-gradient(90deg,#27272a,#e6c67a,#e9b84f)!important}
.rms-provider-block-failover::before{background:linear-gradient(90deg,#27272a,#a8cdb2,#62b97a)!important}

/* Keep the Vercel contents fully inside its own boundary. */
.rms-provider-block-vercel .rms-vg-key-manager-root{
    width:auto!important;
    max-width:none!important;
    margin:18px 20px 0!important;
}
.rms-provider-block-vercel .rms-vg-test-all-card{
    width:auto!important;
    max-width:none!important;
    margin:12px 20px 20px!important;
    box-sizing:border-box!important;
}
.rms-provider-block-vercel .rms-vg-key-manager{
    width:100%!important;
    box-sizing:border-box!important;
}
.rms-provider-block-vercel .rms-vg-key-list{
    padding-bottom:16px!important;
}
.rms-provider-block-vercel .rms-vg-key-manager-root + .rms-vg-test-all-card{
    margin-top:12px!important;
}
.rms-provider-block-agent .rms-ai-provider-section,
.rms-provider-block-failover .rms-ai-provider-section{
    background:rgba(255,255,255,.76)!important;
}
.rms-provider-block-agent .rms-ai-agent-readiness,
.rms-provider-block-agent .rms-ai-agent-meta span,
.rms-provider-block-failover .rms-ai-toggle-field{
    background:rgba(255,255,255,.84)!important;
}
.rms-provider-block-failover .rms-ai-provider-save-row{
    background:rgba(247,251,248,.9)!important;
}
@media(max-width:760px){
    .rms-provider-block{
        margin-bottom:16px!important;
        border-radius:18px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-manager-root{
        margin:12px 11px 0!important;
    }
    .rms-provider-block-vercel .rms-vg-test-all-card{
        margin:10px 11px 12px!important;
    }
}
</style><style>
/* PROVIDER IDENTITY ICONS */
.rms-provider-block-icon{
    position:relative;
    display:grid;
    place-items:center;
    width:48px;height:48px;
    flex:0 0 48px;
    border:1px solid #e3e3e7;
    border-radius:15px;
    background:rgba(255,255,255,.82);
    box-shadow:0 8px 20px rgba(15,23,42,.06);
    overflow:hidden;
}
.rms-provider-block-icon::after{
    content:"";
    position:absolute;
    inset:5px;
    border-radius:11px;
    border:1px solid rgba(24,24,27,.06);
}
.rms-provider-icon-vercel{color:#18181b}
.rms-provider-icon-vercel span{
    width:0;height:0;
    border-left:9px solid transparent;
    border-right:9px solid transparent;
    border-bottom:18px solid #18181b;
    transform:translateY(-1px);
}
.rms-provider-icon-agent{
    color:#d99000;
    background:linear-gradient(145deg,#fffdf8,#fff8e8);
    border-color:#eee3c9;
}
.rms-provider-icon-agent span{font-size:22px;line-height:1;font-weight:900}
.rms-provider-icon-failover{
    color:#21864a;
    background:linear-gradient(145deg,#f9fdf9,#eef9f1);
    border-color:#dcebdd;
}
.rms-provider-icon-failover span{font-size:22px;line-height:1;font-weight:800;transform:translateY(-1px)}

.rms-provider-block-head{
    grid-template-columns:48px minmax(0,1fr) auto!important;
}
.rms-provider-block-copy span{font-size:7.5px!important}
.rms-provider-block-copy strong{font-size:19px!important}

/* CUSTOM PROVIDER SELECT */
.rms-custom-select{position:relative;width:100%;z-index:10}
.rms-custom-select-trigger{
    position:relative;
    display:flex;
    align-items:center;
    width:100%;
    min-height:52px;
    gap:10px;
    padding:7px 10px;
    border:1px solid #e1e1e5;
    border-radius:12px;
    background:#fff;
    color:#27272a;
    text-align:left;
    cursor:pointer;
    box-shadow:0 3px 10px rgba(15,23,42,.025);
    transition:border-color .22s ease,box-shadow .22s ease,transform .22s cubic-bezier(.2,.8,.2,1);
}
.rms-custom-select-trigger:hover{border-color:#cfcfd5;box-shadow:0 7px 18px rgba(15,23,42,.05)}
.rms-custom-select.is-open .rms-custom-select-trigger{
    border-color:#c9c9cf;
    box-shadow:0 0 0 4px rgba(24,24,27,.035),0 8px 22px rgba(15,23,42,.06);
}
.rms-custom-select-leading{
    display:grid;place-items:center;
    width:32px;height:32px;flex:0 0 32px;
    border:1px solid #e5e5e8;border-radius:9px;
    background:#fafafa;
    color:#27272a;
    font-size:9px;font-weight:900;
}
.rms-leading-primary{background:#f7f7f8}
.rms-leading-fallback{background:#f7fdf9;color:#21864a}
.rms-custom-select-value{display:grid;min-width:0;flex:1;gap:2px}
.rms-custom-select-value strong{font-size:10px;font-weight:800;color:#3f3f46;line-height:1.2}
.rms-custom-select-value small{font-size:7.5px;color:#a1a1aa;line-height:1.2}
.rms-custom-select-arrow{
    position:relative;
    display:grid;
    place-items:center;
    width:31px;height:31px;flex:0 0 31px;
    margin:0;
    border:1px solid #e7e7eb;
    border-radius:9px;
    background:#fafafa;
    color:#71717a;
    font-size:0;
    line-height:0;
    transform:none;
    transition:background .22s ease,color .22s ease,border-color .22s ease,box-shadow .22s ease;
}
.rms-custom-select-arrow::before{
    content:"";
    width:7px;
    height:7px;
    border-right:1.7px solid currentColor;
    border-bottom:1.7px solid currentColor;
    transform:translateY(-2px) rotate(45deg);
    transition:transform .28s cubic-bezier(.2,.8,.2,1);
}
.rms-custom-select.is-open .rms-custom-select-arrow{
    background:#fff5f5;
    color:#ef3030;
    border-color:#ffd1d1;
    box-shadow:0 3px 10px rgba(239,48,48,.08);
}
.rms-custom-select.is-open .rms-custom-select-arrow::before{
    transform:translateY(2px) rotate(225deg);
}
.rms-custom-select-menu{
    position:absolute;
    left:0;right:0;
    top:calc(100% + 8px);
    padding:7px;
    border:1px solid #e2e2e6;
    border-radius:14px;
    background:rgba(255,255,255,.98);
    box-shadow:0 22px 50px rgba(15,23,42,.14),0 4px 12px rgba(15,23,42,.05);
    backdrop-filter:blur(12px);
    z-index:100;
    transform-origin:top center;
}
.rms-custom-select-label{
    padding:7px 9px 6px;
    color:#a1a1aa;
    font-size:6.5px;
    font-weight:900;
    letter-spacing:.13em;
}
.rms-custom-select-option{
    display:grid;
    grid-template-columns:32px minmax(0,1fr) 24px;
    align-items:center;
    gap:9px;
    width:100%;
    min-height:53px;
    padding:7px 8px;
    border:0;
    border-radius:10px;
    background:transparent;
    text-align:left;
    cursor:pointer;
    transition:background .2s ease,transform .2s ease;
}
.rms-custom-select-option:hover{background:#fafafa;transform:translateX(2px)}
.rms-custom-select-option.is-selected{background:#fff2f2}
.rms-custom-option-icon{
    display:grid;place-items:center;
    width:30px;height:30px;
    border:1px solid #e5e5e8;border-radius:9px;
    background:#fff;
    color:#52525b;
    font-size:9px;font-weight:900;
}
.rms-custom-select-option.is-selected .rms-custom-option-icon{
    border-color:#ffd1d1;color:#ef3030;background:#fffafa;
}
.rms-custom-select-option>span:nth-child(2){display:grid;gap:2px;min-width:0}
.rms-custom-select-option strong{font-size:9px;color:#3f3f46;font-weight:800}
.rms-custom-select-option small{font-size:7px;color:#a1a1aa}
.rms-custom-option-check{
    display:grid;place-items:center;
    width:22px;height:22px;
    border-radius:7px;
    background:#ef3030;color:#fff;
    font-size:11px;font-weight:900;
    animation:rmsSelectCheckIn .18s ease both;
}
@keyframes rmsSelectCheckIn{from{opacity:0;transform:scale(.7)}to{opacity:1;transform:scale(1)}}
@media(max-width:760px){
    .rms-provider-block-head{grid-template-columns:42px minmax(0,1fr)!important}
    .rms-provider-block-icon{width:42px;height:42px;flex-basis:42px;border-radius:13px}
    .rms-provider-block-copy strong{font-size:16px!important}
    .rms-provider-block-state{grid-column:2;justify-self:start}
    .rms-custom-select-menu{max-height:280px;overflow:auto}
}
@media(prefers-reduced-motion:reduce){
    .rms-custom-select-trigger,.rms-custom-select-arrow,.rms-custom-select-option{transition:none}
}
</style><style>
/* CUSTOM PROVIDER SELECT — allow menu to escape inner cards without escaping its provider container */
.rms-provider-block-failover,
.rms-provider-block-failover .rms-ai-provider-section,
.rms-provider-block-failover .rms-ai-failover-grid{
    overflow:visible!important;
}
.rms-provider-block-failover .rms-ai-provider-section{
    position:relative;
}
.rms-provider-block-failover .rms-ai-config-field{
    position:relative;
    z-index:2;
}
.rms-provider-block-failover .rms-custom-select{
    z-index:10;
}
.rms-provider-block-failover .rms-custom-select.is-open{
    z-index:1000;
}
.rms-provider-block-failover .rms-custom-select-menu{
    z-index:1100!important;
}
.rms-provider-block-failover .rms-ai-toggle-field,
.rms-provider-block-failover .rms-ai-provider-save-row{
    position:relative;
    z-index:1;
}
@media(max-width:760px){
    .rms-provider-block-failover .rms-custom-select-menu{
        max-width:100%;
    }
}
</style><style>
/* SAVE CONFIGURATION — clean primary action */
.rms-provider-block-failover .rms-ai-save-button{
    display:inline-flex!important;
    flex-direction:row!important;
    flex-wrap:nowrap!important;
    align-items:center!important;
    justify-content:center!important;
    gap:8px!important;
    min-width:165px!important;
    height:44px!important;
    padding:0 16px!important;
    border:0!important;
    border-radius:11px!important;
    background:#ef3030!important;
    color:#fff!important;
    font-size:9px!important;
    font-weight:800!important;
    line-height:1!important;
    white-space:nowrap!important;
    cursor:pointer;
    box-shadow:0 7px 16px rgba(239,48,48,.18);
    transition:transform .18s ease,box-shadow .18s ease,background .18s ease;
}
.rms-provider-block-failover .rms-ai-save-button:hover{background:#df2424!important;transform:translateY(-1px);box-shadow:0 10px 22px rgba(239,48,48,.22)}
.rms-provider-block-failover .rms-ai-save-button:active{transform:translateY(0) scale(.985)}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-label{
    display:inline!important;
    flex:0 0 auto!important;
    width:auto!important;
    margin:0!important;
    padding:0!important;
    white-space:nowrap!important;
    font-size:9px!important;
    line-height:1!important;
}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-check{
    display:grid!important;
    place-items:center!important;
    flex:0 0 21px!important;
    width:21px!important;
    height:21px!important;
    margin:0!important;
    padding:0!important;
    border-radius:7px!important;
    background:rgba(255,255,255,.13)!important;
    color:#fff!important;
    font-size:13px!important;
    line-height:1!important;
}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-loading{
    white-space:nowrap!important;
    margin:0!important;
    padding:0!important;
    font-size:9px!important;
    line-height:1!important;
}
.rms-provider-block-failover .rms-ai-save-button[disabled]{cursor:wait;opacity:.8}
@media(max-width:760px){
    .rms-provider-block-failover .rms-ai-save-button{width:100%!important;min-width:0!important;height:46px!important}
}
@media(prefers-reduced-motion:reduce){
    .rms-provider-block-failover .rms-ai-save-button{transition:none}
}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-hidden{
    display:none!important;
}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-loading{
    display:none!important;
}
.rms-provider-block-failover .rms-provider-block-failover .rms-ai-save-button .rms-ai-save-visible{
    display:inline!important;
}
.rms-provider-block-failover .rms-ai-save-button .rms-ai-save-visible{
    display:inline!important;
}
</style><style>
/* =========================================================
   AI PROVIDER — MOBILE-FIRST FINAL PASS
   Optimized for 320–767px screens without changing desktop.
   ========================================================= */
@media (max-width: 760px){
    .rms-provider-block{
        width:100%!important;
        max-width:100%!important;
        box-sizing:border-box!important;
        margin:0 0 18px!important;
        border-radius:18px!important;
        overflow:hidden!important;
    }

    .rms-provider-block-head{
        display:grid!important;
        grid-template-columns:42px minmax(0,1fr)!important;
        align-items:start!important;
        gap:11px!important;
        padding:17px 14px 15px!important;
    }
    .rms-provider-block-icon{
        width:42px!important;
        height:42px!important;
        flex:0 0 42px!important;
        border-radius:13px!important;
    }
    .rms-provider-block-copy{
        min-width:0!important;
        padding-top:1px!important;
    }
    .rms-provider-block-copy span{
        margin-bottom:4px!important;
        font-size:7px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-copy strong{
        font-size:16px!important;
        line-height:1.15!important;
        letter-spacing:-.02em!important;
    }
    .rms-provider-block-copy small{
        margin-top:6px!important;
        max-width:100%!important;
        font-size:9px!important;
        line-height:1.45!important;
    }
    .rms-provider-block-state{
        grid-column:2!important;
        justify-self:start!important;
        min-height:27px!important;
        margin-top:2px!important;
        padding:0 9px!important;
        font-size:7px!important;
    }

    /* Vercel key pool */
    .rms-provider-block .rms-vg-key-manager-root,
    .rms-provider-block .rms-ai-provider-section{
        width:auto!important;
        margin:12px 10px!important;
    }
    .rms-vg-key-manager{
        border-radius:15px!important;
    }
    .rms-vg-section-head{
        padding:13px 12px!important;
        gap:9px!important;
    }
    .rms-vg-section-title{
        min-width:0!important;
    }
    .rms-vg-section-title strong{
        font-size:12px!important;
    }
    .rms-vg-section-title small{
        font-size:8px!important;
        line-height:1.4!important;
    }
    .rms-vg-section-icon{
        width:32px!important;
        height:32px!important;
        flex:0 0 32px!important;
    }
    .rms-vg-pool-badge{
        min-height:25px!important;
        padding:0 8px!important;
        font-size:7px!important;
    }
    .rms-vg-key-notice{
        margin:9px 10px!important;
        padding:10px!important;
        gap:8px!important;
    }
    .rms-vg-key-notice strong{
        font-size:8px!important;
    }
    .rms-vg-key-notice small{
        font-size:7.5px!important;
        line-height:1.4!important;
    }
    .rms-vg-add-toggle{
        margin:0 10px 9px!important;
        min-height:48px!important;
        padding:8px 10px!important;
    }
    .rms-vg-add-toggle strong{font-size:9px!important}
    .rms-vg-add-toggle small{font-size:7.5px!important}

    .rms-vg-key-list{
        gap:8px!important;
        padding:0 10px 11px!important;
    }
    .rms-vg-key-card{
        border-radius:12px!important;
    }
    .rms-vg-key-card-main{
        display:grid!important;
        grid-template-columns:36px minmax(0,1fr)!important;
        align-items:start!important;
        gap:9px!important;
        padding:11px!important;
    }
    .rms-vg-key-avatar{
        width:36px!important;
        height:36px!important;
        flex-basis:36px!important;
        border-radius:9px!important;
    }
    .rms-vg-key-copy{
        min-width:0!important;
    }
    .rms-vg-key-name-row{
        align-items:center!important;
        flex-wrap:wrap!important;
        gap:5px!important;
    }
    .rms-vg-key-name-row>strong{
        max-width:100%!important;
        font-size:10px!important;
    }
    .rms-vg-status{
        height:18px!important;
        font-size:6.5px!important;
    }
    .rms-vg-key-copy code{
        margin-top:5px!important;
        font-size:7.5px!important;
    }
    .rms-vg-key-meta{
        gap:5px!important;
        margin-top:6px!important;
        font-size:7px!important;
        line-height:1.4!important;
    }
    .rms-vg-key-meta span+span:before{
        margin-right:5px!important;
    }
    .rms-vg-key-actions{
        grid-column:1/-1!important;
        width:100%!important;
        display:grid!important;
        grid-template-columns:38px 1fr 38px!important;
        gap:6px!important;
        margin-top:1px!important;
        padding-top:8px!important;
        border-top:1px solid #f0f0f2!important;
    }
    .rms-vg-delete-key,
    .rms-vg-key-more{
        width:38px!important;
        height:34px!important;
        border-radius:9px!important;
    }
    .rms-vg-test-key{
        width:100%!important;
        min-width:0!important;
        height:34px!important;
        font-size:8px!important;
    }
    .rms-vg-key-menu{
        right:0!important;
        top:calc(100% + 7px)!important;
        min-width:170px!important;
    }
    .rms-provider-block .rms-vg-test-all-card{
        width:auto!important;
        margin:0 10px 12px!important;
        padding:12px!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:stretch!important;
        gap:11px!important;
    }
    .rms-vg-test-all-copy{
        align-items:flex-start!important;
    }
    .rms-vg-test-all-copy strong{font-size:10px!important}
    .rms-vg-test-all-copy small{
        font-size:7.5px!important;
        line-height:1.45!important;
    }
    .rms-vg-test-all-button{
        width:100%!important;
        min-height:40px!important;
        font-size:8px!important;
    }

    /* Agent AI */
    .rms-provider-block-agent .rms-ai-provider-section,
    .rms-provider-block-failover .rms-ai-provider-section{
        border-radius:14px!important;
    }
    .rms-ai-provider-section-head{
        display:flex!important;
        flex-direction:column!important;
        align-items:flex-start!important;
        gap:9px!important;
        padding:13px!important;
    }
    .rms-ai-provider-section-title{
        width:100%!important;
        min-width:0!important;
    }
    .rms-ai-provider-section-title strong{
        font-size:11px!important;
    }
    .rms-ai-provider-section-title small{
        font-size:7.5px!important;
        line-height:1.45!important;
    }
    .rms-ai-provider-status{
        align-self:flex-start!important;
        font-size:6.5px!important;
    }
    .rms-ai-agent-grid{
        display:grid!important;
        grid-template-columns:1fr!important;
        gap:9px!important;
        padding:10px!important;
    }
    .rms-ai-agent-readiness{
        min-height:0!important;
        padding:12px!important;
        border-radius:12px!important;
    }
    .rms-ai-agent-readiness strong{
        font-size:9px!important;
    }
    .rms-ai-agent-readiness p{
        font-size:7.5px!important;
        line-height:1.5!important;
    }
    .rms-ai-readiness-orb{
        width:38px!important;
        height:38px!important;
        flex:0 0 38px!important;
    }
    .rms-ai-agent-actions{
        min-width:0!important;
    }
    .rms-ai-agent-meta{
        display:grid!important;
        grid-template-columns:1fr 1fr 1fr!important;
        gap:6px!important;
    }
    .rms-ai-agent-meta span{
        min-width:0!important;
        padding:9px 7px!important;
        border-radius:9px!important;
    }
    .rms-ai-agent-meta b{
        display:block!important;
        font-size:6.5px!important;
        line-height:1.25!important;
    }
    .rms-ai-agent-meta em{
        display:block!important;
        margin-top:3px!important;
        font-size:6px!important;
    }
    .rms-ai-secondary-button{
        width:100%!important;
        min-height:40px!important;
        margin-top:7px!important;
        font-size:8px!important;
    }

    /* Provider failover */
    .rms-ai-failover-grid{
        display:grid!important;
        grid-template-columns:1fr!important;
        gap:10px!important;
        padding:12px!important;
    }
    .rms-ai-config-field{
        gap:5px!important;
    }
    .rms-ai-config-field>span{
        font-size:7px!important;
    }
    .rms-custom-select-trigger{
        min-height:50px!important;
        padding:7px 9px!important;
        border-radius:11px!important;
    }
    .rms-custom-select-leading{
        width:32px!important;
        height:32px!important;
        flex-basis:32px!important;
    }
    .rms-custom-select-value strong{
        font-size:9.5px!important;
    }
    .rms-custom-select-value small{
        font-size:7px!important;
    }
    .rms-custom-select-arrow{
        width:30px!important;
        height:30px!important;
        flex-basis:30px!important;
    }
    .rms-ai-toggle-field{
        align-items:flex-start!important;
        gap:9px!important;
        padding:12px!important;
        border-radius:11px!important;
    }
    .rms-ai-toggle-field strong{
        font-size:10px!important;
        line-height:1.25!important;
    }
    .rms-ai-toggle-field span:not(.rms-ai-toggle){
        font-size:8px!important;
        line-height:1.45!important;
    }
    .rms-ai-provider-save-row{
        align-items:stretch!important;
        flex-direction:column!important;
        gap:10px!important;
        padding:12px!important;
    }
    .rms-ai-provider-save-row>div>span:last-child{
        font-size:7.5px!important;
        line-height:1.45!important;
    }
    .rms-provider-block-failover .rms-ai-save-button{
        width:100%!important;
        min-width:0!important;
        height:46px!important;
    }
}

@media(max-width:390px){
    .rms-provider-block-head{
        padding:15px 12px 14px!important;
    }
    .rms-provider-block-copy strong{font-size:15px!important}
    .rms-provider-block-copy small{font-size:8.5px!important}
    .rms-provider-block .rms-vg-key-manager-root,
    .rms-provider-block .rms-ai-provider-section{
        margin-left:8px!important;
        margin-right:8px!important;
    }
    .rms-provider-block .rms-vg-test-all-card{
        margin-left:8px!important;
        margin-right:8px!important;
    }
    .rms-ai-agent-meta{
        grid-template-columns:1fr!important;
    }
    .rms-ai-agent-meta span{
        display:flex!important;
        align-items:center!important;
        justify-content:space-between!important;
        gap:8px!important;
    }
    .rms-ai-agent-meta em{
        margin-top:0!important;
    }
    .rms-ai-toggle-field strong{font-size:9.5px!important}
    .rms-ai-toggle-field span:not(.rms-ai-toggle){font-size:7.5px!important}
}
</style><style>
/* MOBILE HOTFIX — Vercel key cards + provider select stacking */
@media (max-width:760px){
    /* Never clip the custom dropdown inside the failover card. */
    .rms-provider-block-failover,
    .rms-provider-block-failover .rms-ai-provider-section,
    .rms-provider-block-failover .rms-ai-failover-grid,
    .rms-provider-block-failover .rms-ai-config-field,
    .rms-provider-block-failover .rms-custom-select{
        overflow:visible!important;
    }

    .rms-provider-block-failover{
        position:relative!important;
        z-index:20!important;
    }

    .rms-provider-block-failover .rms-ai-provider-section{
        position:relative!important;
        z-index:20!important;
    }

    .rms-provider-block-failover .rms-ai-failover-grid{
        position:relative!important;
        z-index:30!important;
    }

    .rms-provider-block-failover .rms-ai-config-field{
        position:relative!important;
        z-index:31!important;
        isolation:isolate;
    }

    .rms-provider-block-failover .rms-ai-config-field:has(.rms-custom-select.is-open){
        z-index:5000!important;
    }

    .rms-provider-block-failover .rms-custom-select{
        position:relative!important;
        z-index:100!important;
    }

    .rms-provider-block-failover .rms-custom-select.is-open{
        z-index:6000!important;
    }

    .rms-provider-block-failover .rms-custom-select-menu{
        position:absolute!important;
        left:0!important;
        right:0!important;
        top:calc(100% + 7px)!important;
        z-index:99999!important;
        width:100%!important;
        max-width:none!important;
        max-height:260px!important;
        overflow-y:auto!important;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
    }

    /* Keep the second select from ever painting over an opened first select. */
    .rms-provider-block-failover .rms-ai-config-field + .rms-ai-config-field{
        z-index:30!important;
    }
    .rms-provider-block-failover .rms-ai-config-field:has(.rms-custom-select.is-open) + .rms-ai-config-field{
        z-index:1!important;
    }

    /* Cleaner touch target + centered chevron. */
    .rms-provider-block-failover .rms-custom-select-trigger{
        min-height:50px!important;
        display:flex!important;
        align-items:center!important;
    }
    .rms-provider-block-failover .rms-custom-select-arrow{
        display:grid!important;
        place-items:center!important;
        align-self:center!important;
        margin-left:auto!important;
        transform:none!important;
    }
    .rms-provider-block-failover .rms-custom-select-arrow::before{
        display:block!important;
        margin:0!important;
        transform:translateY(-1px) rotate(45deg)!important;
    }
    .rms-provider-block-failover .rms-custom-select.is-open .rms-custom-select-arrow::before{
        transform:translateY(1px) rotate(225deg)!important;
    }

    /* Vercel API key: information first, actions always form one clean row. */
    .rms-provider-block-vercel .rms-vg-key-card-main{
        display:grid!important;
        grid-template-columns:34px minmax(0,1fr)!important;
        align-items:start!important;
        gap:9px!important;
        padding:11px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-avatar{
        width:34px!important;
        height:34px!important;
        flex-basis:34px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-copy{
        width:100%!important;
        min-width:0!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row{
        width:100%!important;
        display:flex!important;
        align-items:center!important;
        flex-wrap:nowrap!important;
        gap:5px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row>strong{
        min-width:0!important;
        max-width:calc(100% - 55px)!important;
        overflow:hidden!important;
        text-overflow:ellipsis!important;
        white-space:nowrap!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta{
        display:flex!important;
        flex-wrap:wrap!important;
        align-items:center!important;
        column-gap:5px!important;
        row-gap:3px!important;
        max-width:100%!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta span{
        white-space:nowrap!important;
    }
    .rms-provider-block-vercel .rms-vg-key-actions{
        grid-column:1/-1!important;
        width:100%!important;
        margin:2px 0 0!important;
        padding-top:8px!important;
        display:grid!important;
        grid-template-columns:36px minmax(0,1fr) 36px!important;
        align-items:center!important;
        gap:6px!important;
        border-top:1px solid #f0f0f2!important;
    }
    .rms-provider-block-vercel .rms-vg-delete-key,
    .rms-provider-block-vercel .rms-vg-key-more{
        width:36px!important;
        height:33px!important;
        min-width:36px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-vercel .rms-vg-test-key{
        width:100%!important;
        min-width:0!important;
        height:33px!important;
        padding:0 8px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-progress{
        height:2px!important;
    }
}

@media(max-width:390px){
    .rms-provider-block-vercel .rms-vg-key-card-main{
        padding:10px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta{
        font-size:6.5px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-actions{
        grid-template-columns:34px minmax(0,1fr) 34px!important;
        gap:5px!important;
    }
    .rms-provider-block-vercel .rms-vg-delete-key,
    .rms-provider-block-vercel .rms-vg-key-more{
        width:34px!important;
        min-width:34px!important;
    }
}
</style><style>
/* API KEY ACTION MENU — mobile layering hotfix */
.rms-provider-block-vercel .rms-vg-key-list,
.rms-provider-block-vercel .rms-vg-key-card,
.rms-provider-block-vercel .rms-vg-key-card-main,
.rms-provider-block-vercel .rms-vg-more-wrap{
    overflow:visible!important;
}
.rms-provider-block-vercel .rms-vg-key-card{
    position:relative!important;
    z-index:1;
}
.rms-provider-block-vercel .rms-vg-key-card:has(.rms-vg-key-more[aria-expanded="true"]){
    z-index:5000!important;
}
.rms-provider-block-vercel .rms-vg-more-wrap{
    position:relative!important;
    z-index:5001!important;
}
.rms-provider-block-vercel .rms-vg-key-menu{
    position:absolute!important;
    right:0!important;
    top:calc(100% + 7px)!important;
    z-index:99999!important;
    min-width:145px!important;
    max-width:min(210px,calc(100vw - 40px))!important;
    transform-origin:top right!important;
}
@media(max-width:760px){
    .rms-provider-block-vercel .rms-vg-key-card{
        overflow:visible!important;
    }
    .rms-provider-block-vercel .rms-vg-key-card:has(.rms-vg-key-more[aria-expanded="true"]){
        margin-bottom:52px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-menu{
        min-width:155px!important;
        max-width:calc(100vw - 56px)!important;
        padding:5px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-menu button{
        min-height:38px!important;
        padding:9px 10px!important;
        font-size:8px!important;
    }
}
</style><style>
/* MOBILE API KEY — compact information hierarchy */
@media(max-width:760px){
    .rms-provider-block-vercel .rms-vg-key-card-main{
        display:grid!important;
        grid-template-columns:36px minmax(0,1fr)!important;
        grid-template-rows:auto auto!important;
        column-gap:9px!important;
        row-gap:0!important;
        align-items:start!important;
        text-align:left!important;
    }
    .rms-provider-block-vercel .rms-vg-key-copy{
        min-width:0!important;
        width:100%!important;
        text-align:left!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row{
        display:flex!important;
        flex-direction:row!important;
        align-items:center!important;
        justify-content:flex-start!important;
        flex-wrap:nowrap!important;
        gap:6px!important;
        width:100%!important;
        min-width:0!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row>strong{
        display:block!important;
        flex:0 1 auto!important;
        min-width:0!important;
        max-width:calc(100% - 60px)!important;
        overflow:hidden!important;
        text-overflow:ellipsis!important;
        white-space:nowrap!important;
        text-align:left!important;
        font-size:10px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-vercel .rms-vg-status{
        display:inline-flex!important;
        flex:0 0 auto!important;
        align-items:center!important;
        justify-content:center!important;
        white-space:nowrap!important;
        height:18px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-copy code{
        display:block!important;
        width:100%!important;
        margin-top:6px!important;
        text-align:left!important;
        font-size:7.5px!important;
        line-height:1.25!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta{
        display:flex!important;
        align-items:center!important;
        justify-content:flex-start!important;
        flex-wrap:wrap!important;
        width:100%!important;
        margin-top:6px!important;
        gap:3px 5px!important;
        text-align:left!important;
        font-size:6.8px!important;
        line-height:1.35!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta span{
        white-space:nowrap!important;
    }
}
@media(max-width:390px){
    .rms-provider-block-vercel .rms-vg-key-name-row>strong{
        max-width:calc(100% - 54px)!important;
        font-size:9.5px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-meta{
        font-size:6.4px!important;
    }
}
</style><style>
/* MOBILE API KEY — status pinned to top-right */
@media(max-width:760px){
    .rms-provider-block-vercel .rms-vg-key-card-main{
        position:relative!important;
    }
    .rms-provider-block-vercel .rms-vg-key-copy{
        position:relative!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row{
        position:relative!important;
        display:block!important;
        width:100%!important;
        min-height:18px!important;
        padding-right:58px!important;
    }
    .rms-provider-block-vercel .rms-vg-key-name-row>strong{
        display:block!important;
        width:100%!important;
        max-width:none!important;
        padding-right:0!important;
        white-space:nowrap!important;
        overflow:hidden!important;
        text-overflow:ellipsis!important;
    }
    .rms-provider-block-vercel .rms-vg-status{
        position:absolute!important;
        top:0!important;
        right:0!important;
        margin:0!important;
        height:18px!important;
    }
}
@media(max-width:390px){
    .rms-provider-block-vercel .rms-vg-key-name-row{
        padding-right:54px!important;
    }
}
</style><style>
/* =========================================================
   AGENT AI — ALL DEVICE POLISH
   ========================================================= */
.rms-provider-block-agent{
    overflow:hidden!important;
}
.rms-provider-block-agent .rms-ai-provider-section{
    margin:14px 14px 16px!important;
    overflow:hidden!important;
}
.rms-provider-block-agent .rms-ai-provider-section-head{
    min-height:72px;
}
.rms-provider-block-agent .rms-ai-provider-section-title{
    flex:1 1 auto;
}
.rms-provider-block-agent .rms-ai-provider-section-title small{
    max-width:620px;
}
.rms-provider-block-agent .rms-ai-agent-grid{
    align-items:stretch;
}
.rms-provider-block-agent .rms-ai-agent-readiness{
    min-width:0;
}
.rms-provider-block-agent .rms-ai-agent-readiness>div:last-child{
    min-width:0;
}
.rms-provider-block-agent .rms-ai-agent-readiness p{
    max-width:680px;
}
.rms-provider-block-agent .rms-ai-agent-meta span{
    min-width:0;
    transition:transform .22s ease,border-color .22s ease,box-shadow .22s ease,background .22s ease;
}
.rms-provider-block-agent .rms-ai-agent-meta span:hover{
    transform:translateY(-2px);
    border-color:#e2dfd4;
    background:#fffdfa;
    box-shadow:0 8px 18px rgba(15,23,42,.045);
}
.rms-provider-block-agent .rms-ai-secondary-button{
    transition:transform .22s ease,border-color .22s ease,background .22s ease;
}
.rms-provider-block-agent .rms-ai-secondary-button:not(:disabled):hover{
    transform:translateY(-1px);
}

/* Large desktop */
@media(min-width:1280px){
    .rms-provider-block-agent .rms-ai-provider-section{
        margin-left:16px!important;
        margin-right:16px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-grid{
        grid-template-columns:minmax(0,1.35fr) minmax(360px,.85fr);
        gap:16px;
        padding:18px 20px;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness{
        padding:18px;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness strong{
        font-size:10px;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness p{
        font-size:8px;
    }
}

/* Laptop / tablet landscape */
@media(min-width:761px) and (max-width:1100px){
    .rms-provider-block-agent .rms-ai-provider-section-head{
        align-items:flex-start;
    }
    .rms-provider-block-agent .rms-ai-provider-status{
        margin-top:2px;
    }
    .rms-provider-block-agent .rms-ai-agent-grid{
        grid-template-columns:minmax(0,1fr) minmax(250px,.72fr);
        gap:11px;
        padding:14px;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness{
        padding:13px;
        gap:11px;
    }
    .rms-provider-block-agent .rms-ai-readiness-orb{
        width:40px;height:40px;flex-basis:40px;
    }
    .rms-provider-block-agent .rms-ai-agent-meta{
        grid-template-columns:1fr 1fr 1fr;
    }
}

/* Tablet / mobile */
@media(max-width:760px){
    .rms-provider-block-agent{
        border-radius:17px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section{
        margin:10px 8px 12px!important;
        border-radius:14px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-head{
        min-height:0!important;
        padding:13px!important;
        gap:10px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title{
        width:100%!important;
        align-items:flex-start!important;
        gap:9px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-icon{
        width:32px!important;
        height:32px!important;
        flex-basis:32px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title strong{
        font-size:11px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title small{
        max-width:none!important;
        font-size:7.5px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-status{
        align-self:flex-start!important;
        height:23px!important;
        font-size:6px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-grid{
        grid-template-columns:1fr!important;
        gap:9px!important;
        padding:10px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness{
        display:grid!important;
        grid-template-columns:38px minmax(0,1fr)!important;
        gap:10px!important;
        align-items:center!important;
        padding:11px!important;
        border-radius:11px!important;
    }
    .rms-provider-block-agent .rms-ai-readiness-orb{
        width:38px!important;
        height:38px!important;
        flex-basis:38px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness strong{
        font-size:9px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness p{
        max-width:none!important;
        font-size:7.2px!important;
        line-height:1.5!important;
    }
    .rms-provider-block-agent .rms-ai-agent-actions{
        gap:8px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta{
        grid-template-columns:repeat(3,minmax(0,1fr))!important;
        gap:6px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta span{
        min-width:0!important;
        padding:9px 7px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta b{
        font-size:6.2px!important;
        line-height:1.25!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta em{
        font-size:6.2px!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button{
        min-height:42px!important;
        padding:0 11px!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button span{
        font-size:7.5px!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button small{
        font-size:6px!important;
    }
}

/* Small phones */
@media(max-width:390px){
    .rms-provider-block-agent .rms-ai-provider-section{
        margin-left:7px!important;
        margin-right:7px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-grid{
        padding:8px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta{
        grid-template-columns:1fr!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta span{
        display:flex!important;
        align-items:center!important;
        justify-content:space-between!important;
        gap:8px!important;
        padding:8px 9px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta em{
        margin-top:0!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button{
        flex-direction:column!important;
        justify-content:center!important;
        gap:3px!important;
        min-height:46px!important;
    }
}

/* Reduced motion */
@media(prefers-reduced-motion:reduce){
    .rms-provider-block-agent .rms-ai-agent-meta span,
    .rms-provider-block-agent .rms-ai-secondary-button{
        transition:none!important;
    }
}
</style>
<style>
/* =========================================================
   AGENT AI — MOBILE PREMIUM REFINEMENT
   Improve mobile readability without changing the locked
   Vercel Gateway layout.
   ========================================================= */
@media(max-width:760px){
    .rms-provider-block-agent .rms-provider-block-head{
        padding:17px 14px 16px!important;
        gap:11px!important;
    }
    .rms-provider-block-agent .rms-provider-block-icon{
        width:42px!important;
        height:42px!important;
        flex:0 0 42px!important;
        border-radius:12px!important;
    }
    .rms-provider-block-agent .rms-provider-block-copy strong{
        font-size:16px!important;
        line-height:1.12!important;
    }
    .rms-provider-block-agent .rms-provider-block-copy small{
        margin-top:4px!important;
        font-size:9px!important;
        line-height:1.45!important;
    }
    .rms-provider-block-agent .rms-provider-block-state{
        flex:0 0 auto!important;
        margin-left:auto!important;
        white-space:nowrap!important;
    }

    .rms-provider-block-agent .rms-ai-provider-section-head{
        padding:15px!important;
        gap:11px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-icon{
        width:34px!important;
        height:34px!important;
        flex-basis:34px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title strong{
        font-size:12px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title small{
        margin-top:3px!important;
        font-size:8px!important;
        line-height:1.45!important;
    }
    .rms-provider-block-agent .rms-ai-provider-status{
        height:24px!important;
        padding:0 8px!important;
        font-size:6.2px!important;
    }

    .rms-provider-block-agent .rms-ai-agent-grid{
        gap:10px!important;
        padding:11px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness{
        grid-template-columns:42px minmax(0,1fr)!important;
        gap:11px!important;
        padding:13px!important;
        border-radius:12px!important;
    }
    .rms-provider-block-agent .rms-ai-readiness-orb{
        width:42px!important;
        height:42px!important;
        flex-basis:42px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness strong{
        font-size:10px!important;
        line-height:1.25!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness p{
        margin-top:4px!important;
        font-size:8px!important;
        line-height:1.55!important;
    }

    .rms-provider-block-agent .rms-ai-agent-meta{
        gap:7px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta span{
        padding:10px 8px!important;
        border-radius:10px!important;
        min-height:52px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta b{
        font-size:6.8px!important;
        line-height:1.3!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta em{
        margin-top:4px!important;
        font-size:6.8px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button{
        min-height:46px!important;
        padding:0 12px!important;
        border-radius:10px!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button span{
        font-size:8px!important;
    }
    .rms-provider-block-agent .rms-ai-secondary-button small{
        font-size:6.5px!important;
    }
}

@media(max-width:390px){
    .rms-provider-block-agent .rms-provider-block-head{
        padding:15px 12px 14px!important;
    }
    .rms-provider-block-agent .rms-provider-block-icon{
        width:40px!important;
        height:40px!important;
        flex-basis:40px!important;
    }
    .rms-provider-block-agent .rms-provider-block-copy strong{
        font-size:15px!important;
    }
    .rms-provider-block-agent .rms-provider-block-copy small{
        font-size:8.5px!important;
    }

    .rms-provider-block-agent .rms-ai-provider-section-head{
        padding:13px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title strong{
        font-size:11.5px!important;
    }
    .rms-provider-block-agent .rms-ai-provider-section-title small{
        font-size:7.7px!important;
    }

    .rms-provider-block-agent .rms-ai-agent-grid{
        padding:9px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness{
        grid-template-columns:40px minmax(0,1fr)!important;
        gap:10px!important;
        padding:12px!important;
    }
    .rms-provider-block-agent .rms-ai-readiness-orb{
        width:40px!important;
        height:40px!important;
        flex-basis:40px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness strong{
        font-size:9.5px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-readiness p{
        font-size:7.7px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta span{
        min-height:0!important;
        padding:9px 10px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta b{
        font-size:6.6px!important;
    }
    .rms-provider-block-agent .rms-ai-agent-meta em{
        font-size:6.6px!important;
    }
}
</style>
<style>
/* =========================================================
   PROVIDER FAILOVER — ALL DEVICE PREMIUM POLISH
   Scoped only to the failover block. Vercel + Agent remain locked.
   ========================================================= */
.rms-provider-block-failover{
    position:relative!important;
    min-width:0!important;
}
.rms-provider-block-failover .rms-ai-provider-section{
    min-width:0!important;
    margin:14px 14px 16px!important;
    overflow:visible!important;
}
.rms-provider-block-failover .rms-ai-provider-section-head{
    min-height:72px;
    align-items:center;
}
.rms-provider-block-failover .rms-ai-provider-section-title{
    min-width:0;
    flex:1 1 auto;
}
.rms-provider-block-failover .rms-ai-provider-section-title>div{
    min-width:0;
}
.rms-provider-block-failover .rms-ai-provider-section-title small{
    max-width:620px;
}
.rms-provider-block-failover .rms-ai-provider-status{
    flex:0 0 auto;
    white-space:nowrap;
}
.rms-provider-block-failover .rms-ai-failover-grid{
    align-items:stretch;
}
.rms-provider-block-failover .rms-ai-config-field{
    min-width:0;
}
.rms-provider-block-failover .rms-ai-config-field>span{
    padding-left:2px;
}
.rms-provider-block-failover .rms-custom-select{
    min-width:0;
}
.rms-provider-block-failover .rms-custom-select-value{
    min-width:0;
}
.rms-provider-block-failover .rms-custom-select-value strong,
.rms-provider-block-failover .rms-custom-select-value small{
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.rms-provider-block-failover .rms-ai-toggle-field{
    min-width:0;
    min-height:66px;
    align-items:flex-start;
}
.rms-provider-block-failover .rms-ai-toggle-field>div{
    min-width:0;
}
.rms-provider-block-failover .rms-ai-toggle-field strong,
.rms-provider-block-failover .rms-ai-toggle-field small{
    display:block;
}
.rms-provider-block-failover .rms-ai-toggle-field small{
    max-width:100%;
}
.rms-provider-block-failover .rms-ai-provider-save-row{
    min-width:0;
}
.rms-provider-block-failover .rms-ai-provider-save-row>div{
    min-width:0;
}
.rms-provider-block-failover .rms-ai-provider-save-row>div>span:last-child{
    max-width:680px;
}
.rms-provider-block-failover .rms-ai-save-button{
    flex:0 0 auto;
    min-width:185px;
    transition:transform .22s ease,box-shadow .22s ease,background .22s ease;
}
.rms-provider-block-failover .rms-ai-save-button:not(:disabled):hover{
    transform:translateY(-1px);
}

/* Large desktop */
@media(min-width:1280px){
    .rms-provider-block-failover .rms-ai-provider-section{
        margin-left:16px!important;
        margin-right:16px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-head{
        padding:18px 20px;
    }
    .rms-provider-block-failover .rms-ai-failover-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:14px;
        padding:18px 20px;
    }
    .rms-provider-block-failover .rms-ai-config-field>span{
        font-size:7px;
    }
    .rms-provider-block-failover .rms-ai-toggle-field{
        min-height:72px;
        padding:13px;
    }
    .rms-provider-block-failover .rms-ai-toggle-field strong{
        font-size:8.5px;
    }
    .rms-provider-block-failover .rms-ai-toggle-field small{
        font-size:7px;
        line-height:1.5;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row{
        padding:15px 20px;
    }
}

/* Laptop / tablet landscape */
@media(min-width:761px) and (max-width:1100px){
    .rms-provider-block-failover .rms-ai-provider-section-head{
        padding:15px;
        align-items:flex-start;
    }
    .rms-provider-block-failover .rms-ai-provider-status{
        margin-top:2px;
    }
    .rms-provider-block-failover .rms-ai-failover-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
        padding:14px;
    }
    .rms-provider-block-failover .rms-custom-select-trigger{
        min-height:50px;
    }
    .rms-provider-block-failover .rms-ai-toggle-field{
        min-height:62px;
        padding:10px;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row{
        padding:12px 14px;
        gap:12px;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row>div>span:last-child{
        font-size:7px;
    }
}

/* Tablet / mobile */
@media(max-width:760px){
    .rms-provider-block-failover{
        border-radius:17px!important;
    }
    .rms-provider-block-failover .rms-provider-block-head{
        padding:16px 14px 15px!important;
        gap:11px!important;
    }
    .rms-provider-block-failover .rms-provider-block-icon{
        width:42px!important;
        height:42px!important;
        flex-basis:42px!important;
        border-radius:12px!important;
    }
    .rms-provider-block-failover .rms-provider-block-copy strong{
        font-size:16px!important;
        line-height:1.12!important;
    }
    .rms-provider-block-failover .rms-provider-block-copy small{
        font-size:9px!important;
        line-height:1.45!important;
    }

    .rms-provider-block-failover .rms-ai-provider-section{
        margin:10px 8px 12px!important;
        border-radius:14px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-head{
        min-height:0!important;
        padding:14px!important;
        gap:10px!important;
        align-items:flex-start!important;
        flex-wrap:wrap!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-title{
        width:100%!important;
        align-items:flex-start!important;
        gap:9px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-icon{
        width:33px!important;
        height:33px!important;
        flex-basis:33px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-title strong{
        font-size:11.5px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-title small{
        max-width:none!important;
        margin-top:3px!important;
        font-size:7.8px!important;
        line-height:1.45!important;
    }
    .rms-provider-block-failover .rms-ai-provider-status{
        align-self:flex-start!important;
        height:23px!important;
        font-size:6.1px!important;
        padding:0 8px!important;
    }

    .rms-provider-block-failover .rms-ai-failover-grid{
        grid-template-columns:1fr!important;
        gap:9px!important;
        padding:11px!important;
    }
    .rms-provider-block-failover .rms-ai-config-field{
        gap:6px!important;
    }
    .rms-provider-block-failover .rms-ai-config-field>span{
        padding-left:2px!important;
        font-size:6.7px!important;
    }
    .rms-provider-block-failover .rms-custom-select-trigger{
        min-height:52px!important;
        padding:8px 9px!important;
        border-radius:11px!important;
        gap:9px!important;
    }
    .rms-provider-block-failover .rms-custom-select-leading{
        width:33px!important;
        height:33px!important;
        flex-basis:33px!important;
        border-radius:9px!important;
    }
    .rms-provider-block-failover .rms-custom-select-value strong{
        font-size:9.5px!important;
        line-height:1.2!important;
    }
    .rms-provider-block-failover .rms-custom-select-value small{
        margin-top:1px!important;
        font-size:7px!important;
    }
    .rms-provider-block-failover .rms-custom-select-arrow{
        width:30px!important;
        height:30px!important;
        flex-basis:30px!important;
    }

    .rms-provider-block-failover .rms-ai-toggle-field{
        min-height:0!important;
        padding:12px!important;
        gap:9px!important;
        border-radius:11px!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-ui{
        width:29px!important;
        height:18px!important;
        flex-basis:29px!important;
        margin-top:1px!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-field strong{
        font-size:9.5px!important;
        line-height:1.3!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-field small{
        margin-top:3px!important;
        font-size:7.5px!important;
        line-height:1.5!important;
    }

    .rms-provider-block-failover .rms-ai-provider-save-row{
        align-items:stretch!important;
        flex-direction:column!important;
        gap:10px!important;
        padding:12px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row>div{
        align-items:flex-start!important;
        gap:7px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row>div>span:last-child{
        font-size:7.5px!important;
        line-height:1.5!important;
    }
    .rms-provider-block-failover .rms-ai-save-button{
        width:100%!important;
        min-width:0!important;
        height:46px!important;
        border-radius:10px!important;
    }

    /* Dropdown stays readable and safely scrollable on small screens. */
    .rms-provider-block-failover .rms-custom-select-menu{
        max-height:min(280px,55vh)!important;
        border-radius:13px!important;
        padding:6px!important;
    }
    .rms-provider-block-failover .rms-custom-select-label{
        padding:7px 8px 6px!important;
        font-size:6.3px!important;
    }
    .rms-provider-block-failover .rms-custom-select-option{
        min-height:50px!important;
        grid-template-columns:31px minmax(0,1fr) 22px!important;
        gap:8px!important;
        padding:7px!important;
    }
    .rms-provider-block-failover .rms-custom-option-icon{
        width:30px!important;
        height:30px!important;
        flex-basis:30px!important;
    }
    .rms-provider-block-failover .rms-custom-select-option strong{
        font-size:8px!important;
    }
    .rms-provider-block-failover .rms-custom-select-option small{
        font-size:6.6px!important;
    }
}

/* Small phones */
@media(max-width:390px){
    .rms-provider-block-failover .rms-provider-block-head{
        padding:15px 12px 14px!important;
    }
    .rms-provider-block-failover .rms-provider-block-icon{
        width:40px!important;
        height:40px!important;
        flex-basis:40px!important;
    }
    .rms-provider-block-failover .rms-provider-block-copy strong{
        font-size:15px!important;
    }
    .rms-provider-block-failover .rms-provider-block-copy small{
        font-size:8.5px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-head{
        padding:13px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-title strong{
        font-size:11px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-section-title small{
        font-size:7.5px!important;
    }
    .rms-provider-block-failover .rms-ai-failover-grid{
        padding:9px!important;
        gap:8px!important;
    }
    .rms-provider-block-failover .rms-custom-select-trigger{
        min-height:50px!important;
    }
    .rms-provider-block-failover .rms-custom-select-value strong{
        font-size:9px!important;
    }
    .rms-provider-block-failover .rms-custom-select-value small{
        font-size:6.7px!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-field{
        padding:11px!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-field strong{
        font-size:9px!important;
    }
    .rms-provider-block-failover .rms-ai-toggle-field small{
        font-size:7.2px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row{
        padding:11px!important;
    }
    .rms-provider-block-failover .rms-ai-provider-save-row>div>span:last-child{
        font-size:7.2px!important;
    }
}

/* Reduced motion */
@media(prefers-reduced-motion:reduce){
    .rms-provider-block-failover .rms-ai-save-button{
        transition:none!important;
    }
}
</style>

<style>
/* =========================================================
   AGENT CREDENTIAL POOL — STAGE 5
   ========================================================= */
.rms-provider-block-agent .rms-agent-credential-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    padding:14px 18px;
    border-bottom:1px solid #f0f0f3;
}
.rms-provider-block-agent .rms-agent-credential-toolbar>div{
    min-width:0;
    display:grid;
    gap:4px;
}
.rms-provider-block-agent .rms-agent-credential-toolbar strong{
    font-size:9px;
    color:#18181b;
    font-weight:850;
}
.rms-provider-block-agent .rms-agent-credential-toolbar small{
    font-size:7px;
    line-height:1.5;
    color:#71717a;
}
.rms-agent-add-button{
    min-width:150px!important;
    min-height:42px!important;
    border:1px solid #18181b!important;
    background:linear-gradient(135deg,#18181b 0%,#27272a 100%)!important;
    color:#fff!important;
    cursor:pointer!important;
    box-shadow:0 7px 16px rgba(24,24,27,.12);
    transition:transform .22s ease,box-shadow .22s ease,background .22s ease,border-color .22s ease;
}
.rms-agent-add-button span{
    color:#fff!important;
    font-weight:850;
}
.rms-agent-add-button small{
    color:#d4d4d8!important;
    font-weight:700;
}
.rms-agent-add-button:hover{
    transform:translateY(-1px);
    border-color:#111113!important;
    background:linear-gradient(135deg,#111113 0%,#303034 100%)!important;
    box-shadow:0 10px 22px rgba(24,24,27,.16);
}
.rms-agent-add-button:active{
    transform:translateY(0);
}
.rms-agent-add-button:focus-visible{
    outline:2px solid rgba(24,24,27,.18);
    outline-offset:2px;
}
@media(max-width:760px){
    .rms-agent-add-button{
        min-height:44px!important;
        border-radius:10px!important;
    }
}
.rms-agent-add-label{
    display:inline-flex;
    align-items:center;
    min-width:0;
    transition:opacity .18s ease,transform .22s ease;
}
.rms-agent-add-meta{
    display:inline-flex!important;
    align-items:center;
    justify-content:flex-end;
    gap:8px;
    white-space:nowrap;
}
.rms-agent-add-meta>span{
    font-size:6.5px;
    color:#d4d4d8!important;
    font-weight:700;
}
.rms-agent-add-meta>i{
    position:relative;
    width:7px;
    height:7px;
    border-right:1.5px solid #d4d4d8;
    border-bottom:1.5px solid #d4d4d8;
    transform:rotate(45deg) translateY(-2px);
    transition:transform .25s cubic-bezier(.2,.8,.2,1);
}
.rms-agent-add-meta>i.is-open{
    transform:rotate(225deg) translate(-1px,-1px);
}
.rms-agent-form-collapse{
    display:grid;
    grid-template-rows:0fr;
    opacity:0;
    visibility:hidden;
    transition:grid-template-rows .42s cubic-bezier(.2,.8,.2,1),opacity .25s ease,visibility 0s linear .42s;
}
.rms-agent-form-collapse.is-open{
    grid-template-rows:1fr;
    opacity:1;
    visibility:visible;
    transition:grid-template-rows .42s cubic-bezier(.2,.8,.2,1),opacity .25s ease,visibility 0s linear 0s;
}
.rms-agent-form-collapse>.rms-agent-credential-form{
    min-height:0;
}
.rms-agent-credential-form{
    margin:12px 14px 0;
    border:1px solid #e7e7eb;
    border-radius:13px;
    background:#fafafa;
    overflow:hidden;
    transform:translateY(-5px) scale(.99);
    transform-origin:top center;
    transition:transform .42s cubic-bezier(.2,.8,.2,1),box-shadow .42s ease;
}
.rms-agent-form-collapse.is-open>.rms-agent-credential-form{
    transform:translateY(0) scale(1);
    box-shadow:0 12px 28px rgba(15,23,42,.045);
}
.rms-agent-form-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    padding:13px 14px;
    border-bottom:1px solid #ededf0;
}
.rms-agent-form-head>div{
    display:grid;
    gap:3px;
}
.rms-agent-form-head span{
    font-size:6px;
    letter-spacing:.13em;
    font-weight:900;
    color:#a1a1aa;
}
.rms-agent-form-head strong{font-size:9px;color:#27272a}
.rms-agent-form-head small{font-size:7px;color:#71717a}
.rms-agent-form-close{
    width:25px;height:25px;border:1px solid #e4e4e7;border-radius:7px;background:#fff;color:#71717a;cursor:pointer;
}
.rms-agent-form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    padding:13px 14px;
}
.rms-agent-form-grid label{
    display:grid;
    gap:6px;
    min-width:0;
}
.rms-agent-form-grid label>span{
    font-size:6px;
    color:#71717a;
    letter-spacing:.12em;
    font-weight:900;
}
.rms-agent-form-grid input{
    width:100%;
    min-height:42px;
    border:1px solid #e4e4e7;
    border-radius:9px;
    background:#fff;
    padding:0 11px;
    color:#18181b;
    outline:none;
    font-size:8px;
}
.rms-agent-form-grid input:focus{
    border-color:#c4b5fd;
    box-shadow:0 0 0 3px rgba(139,92,246,.08);
}
.rms-agent-token-input{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    border:1px solid #e4e4e7;
    border-radius:9px;
    background:#fff;
    overflow:hidden;
}
.rms-agent-token-input input{
    border:0!important;
    box-shadow:none!important;
    min-width:0;
}
.rms-agent-token-input button{
    border:0;
    border-left:1px solid #ededf0;
    background:#fafafa;
    padding:0 10px;
    color:#71717a;
    font-size:7px;
    font-weight:800;
    cursor:pointer;
}
.rms-agent-form-error{font-size:7px;color:#dc2626}
.rms-agent-form-foot{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:11px 14px;
    border-top:1px solid #ededf0;
}
.rms-agent-form-foot>span{
    font-size:6.7px;
    color:#71717a;
}
.rms-agent-form-foot>div{
    display:flex;
    align-items:center;
    gap:7px;
}
.rms-agent-cancel-button,
.rms-agent-save-button{
    min-height:34px;
    padding:0 12px;
    border-radius:8px;
    font-size:7px;
    font-weight:850;
    cursor:pointer;
}
.rms-agent-cancel-button{border:1px solid #e4e4e7;background:#fff;color:#71717a}
.rms-agent-save-button{border:0;background:#18181b;color:#fff}
.rms-agent-credential-list{
    display:grid;
    gap:8px;
    padding:12px 14px;
}
.rms-agent-credential-card{
    position:relative;
    display:grid;
    grid-template-columns:minmax(0,1.4fr) minmax(130px,.7fr) auto;
    align-items:center;
    gap:12px;
    padding:11px;
    border:1px solid #e8e8ec;
    border-radius:11px;
    background:#fff;
    min-width:0;
}
.rms-agent-credential-card.is-disabled{opacity:.72}
.rms-agent-credential-main{
    display:flex;
    align-items:center;
    gap:9px;
    min-width:0;
}
.rms-agent-credential-icon{
    width:34px;height:34px;flex:0 0 34px;display:grid;place-items:center;
    border-radius:9px;background:#18181b;color:#fff;font-size:7px;font-weight:950;
}
.rms-agent-credential-copy{display:grid;gap:4px;min-width:0}
.rms-agent-credential-title{display:flex;align-items:center;gap:7px;min-width:0}
.rms-agent-credential-title strong{font-size:8.5px;color:#27272a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rms-agent-credential-copy code{font-family:"SFMono-Regular",Consolas,monospace;font-size:7px;color:#71717a}
.rms-agent-credential-copy small{font-size:6.5px;color:#a1a1aa}
.rms-agent-status{
    display:inline-flex;align-items:center;gap:4px;height:18px;padding:0 6px;border-radius:999px;
    font-size:5.5px;font-weight:900;white-space:nowrap;
}
.rms-agent-status i{width:4px;height:4px;border-radius:50%;background:#71717a}
.rms-agent-status.is-active{background:#ecfdf3;color:#15803d}.rms-agent-status.is-active i{background:#22c55e}
.rms-agent-status.is-cooldown{background:#fffbeb;color:#a16207}.rms-agent-status.is-cooldown i{background:#f59e0b}
.rms-agent-status.is-error{background:#fef2f2;color:#b91c1c}.rms-agent-status.is-error i{background:#ef4444}
.rms-agent-status.is-disabled{background:#f4f4f5;color:#71717a}
.rms-agent-credential-meta{display:grid;grid-template-columns:1fr 1fr;gap:7px;min-width:0}
.rms-agent-credential-meta span{display:grid;gap:3px;min-width:0}
.rms-agent-credential-meta b{font-size:5.7px;letter-spacing:.08em;text-transform:uppercase;color:#a1a1aa}
.rms-agent-credential-meta em{font-size:6.5px;color:#52525b;font-style:normal;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rms-agent-cooldown{
    grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;
    margin-top:-2px;padding:7px 9px;border:1px solid #fde68a;border-radius:8px;
    background:#fffbeb;color:#a16207;font-size:7px;line-height:1.45;
}
.rms-agent-cooldown strong{font-family:"SFMono-Regular",Consolas,monospace;font-size:8px;font-weight:850;white-space:nowrap}
.rms-agent-cooldown.is-expired{border-color:#e7e5e4;background:#fafaf9;color:#78716c}
.rms-agent-cooldown.is-expired strong{font-weight:700}
.rms-agent-credential-actions{
    display:flex;
    align-items:center;
    gap:5px;
}
.rms-agent-test-button,
.rms-agent-toggle-button,
.rms-agent-delete-button{
    position:relative;
    min-height:36px;
    border-radius:9px;
    padding:0 12px;
    font-size:7.4px;
    font-weight:900;
    cursor:pointer;
    white-space:nowrap;
    transition:transform .2s cubic-bezier(.2,.8,.2,1),box-shadow .2s ease,background .2s ease,border-color .2s ease,color .2s ease;
}
.rms-agent-test-button{
    border:1px solid #ddd6fe;
    background:#faf5ff;
    color:#6d28d9;
    box-shadow:0 2px 5px rgba(109,40,217,.05);
}
.rms-agent-test-button:hover{
    transform:translateY(-1px);
    border-color:#c4b5fd;
    background:#f5f3ff;
    box-shadow:0 7px 15px rgba(109,40,217,.12);
}
.rms-agent-test-button:active{transform:translateY(0) scale(.98)}
.rms-agent-toggle-button{
    min-width:78px;
    border:1px solid #e4e4e7;
    background:#fff;
    color:#52525b;
    box-shadow:0 2px 5px rgba(24,24,27,.04);
}
.rms-agent-toggle-button.is-enabled{
    border-color:#bbf7d0;
    background:#f0fdf4;
    color:#15803d;
}
.rms-agent-toggle-button.is-disabled{
    border-color:#e4e4e7;
    background:#fafafa;
    color:#71717a;
}
.rms-agent-toggle-button:hover{
    transform:translateY(-1px);
    border-color:#a1a1aa;
    box-shadow:0 7px 15px rgba(24,24,27,.10);
}
.rms-agent-toggle-button.is-enabled:hover{
    border-color:#86efac;
    background:#dcfce7;
    box-shadow:0 7px 15px rgba(34,197,94,.13);
}
.rms-agent-toggle-button.is-disabled:hover{
    background:#f4f4f5;
}
.rms-agent-toggle-button:active{transform:translateY(0) scale(.98)}
.rms-agent-delete-button{
    width:36px;
    padding:0;
    border:1px solid #fecaca;
    background:#fff5f5;
    color:#dc2626;
    font-size:15px;
    box-shadow:0 2px 5px rgba(220,38,38,.04);
}
.rms-agent-delete-button:hover{
    transform:translateY(-1px) rotate(2deg);
    border-color:#fca5a5;
    background:#fee2e2;
    box-shadow:0 7px 15px rgba(220,38,38,.13);
}
.rms-agent-delete-button:active{transform:translateY(0) scale(.95)}
.rms-agent-test-button:disabled,.rms-agent-toggle-button:disabled,.rms-agent-delete-button:disabled{opacity:.5;cursor:wait;transform:none}
.rms-agent-empty{
    display:flex;align-items:center;gap:10px;padding:14px;border:1px dashed #d4d4d8;border-radius:11px;background:#fafafa;
}
.rms-agent-empty>span{
    width:34px;height:34px;display:grid;place-items:center;border-radius:9px;background:#f4f4f5;color:#71717a;font-size:12px;
}
.rms-agent-empty div{display:grid;gap:3px}.rms-agent-empty strong{font-size:8px;color:#52525b}.rms-agent-empty small{font-size:6.7px;line-height:1.45;color:#a1a1aa}
.rms-agent-test-note{
    display:flex;align-items:flex-start;gap:8px;margin:0 14px 14px;padding:9px;border:1px solid #fef3c7;border-radius:9px;background:#fffbeb;
}
.rms-agent-test-note>span{
    width:18px;height:18px;flex:0 0 18px;display:grid;place-items:center;border-radius:6px;background:#fef3c7;color:#a16207;font-size:8px;font-weight:900;
}
.rms-agent-test-note div{display:grid;gap:2px}.rms-agent-test-note strong{font-size:6.7px;color:#92400e}.rms-agent-test-note small{font-size:6.3px;line-height:1.45;color:#a16207}
.rms-agent-save-button,
.rms-agent-cancel-button,
.rms-agent-form-close,
.rms-agent-token-input button{
    transition:transform .2s cubic-bezier(.2,.8,.2,1),box-shadow .2s ease,background .2s ease,border-color .2s ease,color .2s ease;
}
.rms-agent-save-button:hover{
    transform:translateY(-1px);
    background:#27272a;
    box-shadow:0 7px 16px rgba(24,24,27,.16);
}
.rms-agent-save-button:active,.rms-agent-cancel-button:active,.rms-agent-form-close:active{transform:scale(.97)}
.rms-agent-cancel-button:hover{
    transform:translateY(-1px);
    background:#f4f4f5;
    border-color:#d4d4d8;
    color:#27272a;
}
.rms-agent-form-close:hover{
    transform:rotate(90deg);
    background:#f4f4f5;
    color:#27272a;
}
.rms-agent-token-input button:hover{
    background:#f4f4f5;
    color:#27272a;
}
.rms-agent-delete-overlay{
    position:fixed;
    inset:0;
    z-index:9999;
    display:grid;
    place-items:center;
    padding:24px;
}
.rms-agent-delete-backdrop{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.52);
    backdrop-filter:blur(9px);
}
.rms-agent-delete-modal{
    position:relative;
    width:min(470px,calc(100vw - 32px));
    border-radius:20px;
    border:1px solid rgba(239,68,68,.18);
    background:#fff;
    box-shadow:0 30px 80px rgba(15,23,42,.24);
    padding:25px;
    overflow:hidden;
}
.rms-agent-delete-modal::before{
    content:"";
    position:absolute;
    inset:0 0 auto;
    height:3px;
    background:#ef4444;
}
.rms-agent-delete-orb{
    width:58px;
    height:58px;
    display:grid;
    place-items:center;
    margin:0 auto 17px;
    border:1px solid #fecaca;
    border-radius:18px;
    background:#fff5f5;
    box-shadow:0 0 0 8px rgba(239,68,68,.035);
}
.rms-agent-delete-orb span{
    width:13px;
    height:13px;
    border-radius:50%;
    background:#ef4444;
    box-shadow:0 0 0 7px rgba(239,68,68,.10),0 0 0 14px rgba(239,68,68,.045);
}
.rms-agent-delete-copy{text-align:center}
.rms-agent-delete-copy>span{
    display:block;
    margin-bottom:7px;
    color:#ef4444;
    font-size:8px;
    font-weight:950;
    letter-spacing:.16em;
}
.rms-agent-delete-copy h3{
    margin:0;
    color:#18181b;
    font-size:20px;
    line-height:1.2;
    font-weight:900;
}
.rms-agent-delete-copy p{
    margin:10px auto 0;
    max-width:390px;
    color:#71717a;
    font-size:10px;
    line-height:1.55;
}
.rms-agent-delete-copy p strong{color:#27272a}
.rms-agent-delete-warning{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin:19px 0;
    padding:12px;
    border:1px solid #fecaca;
    border-radius:12px;
    background:#fff7f7;
}
.rms-agent-delete-warning>span{
    width:25px;
    height:25px;
    flex:0 0 25px;
    display:grid;
    place-items:center;
    border-radius:8px;
    background:#ef4444;
    color:#fff;
    font-size:12px;
    font-weight:950;
}
.rms-agent-delete-warning div{display:grid;gap:3px}
.rms-agent-delete-warning strong{font-size:9px;color:#b91c1c}
.rms-agent-delete-warning small{font-size:8px;line-height:1.45;color:#a16262}
.rms-agent-delete-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.rms-agent-delete-cancel,.rms-agent-delete-confirm{
    min-height:44px;
    border-radius:10px;
    font-size:9px;
    font-weight:900;
    cursor:pointer;
    transition:transform .2s cubic-bezier(.2,.8,.2,1),box-shadow .2s ease,background .2s ease,border-color .2s ease;
}
.rms-agent-delete-cancel{
    border:1px solid #e4e4e7;
    background:#fff;
    color:#52525b;
}
.rms-agent-delete-cancel:hover{
    transform:translateY(-1px);
    background:#f4f4f5;
    box-shadow:0 7px 15px rgba(24,24,27,.08);
}
.rms-agent-delete-confirm{
    border:1px solid #ef4444;
    background:#ef3333;
    color:#fff;
    box-shadow:0 7px 18px rgba(239,68,68,.17);
}
.rms-agent-delete-confirm:hover{
    transform:translateY(-1px);
    background:#dc2626;
    box-shadow:0 10px 22px rgba(239,68,68,.23);
}
.rms-agent-delete-cancel:active,.rms-agent-delete-confirm:active{transform:translateY(0) scale(.98)}
.rms-agent-delete-cancel:disabled,.rms-agent-delete-confirm:disabled{opacity:.55;cursor:wait;transform:none}
.rms-agent-delete-loading{display:inline-flex;align-items:center;justify-content:center;gap:7px}
.rms-agent-delete-loading i{
    width:10px;height:10px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:rms-agent-spin .7s linear infinite;
}
@keyframes rms-agent-spin{to{transform:rotate(360deg)}}
@media(max-width:760px){
    .rms-agent-test-button,.rms-agent-toggle-button,.rms-agent-delete-button{min-height:40px;font-size:8px}
    .rms-agent-toggle-button{min-width:86px}
    .rms-agent-delete-modal{padding:22px;border-radius:18px}
    .rms-agent-delete-copy h3{font-size:18px}
    .rms-agent-delete-copy p{font-size:9px}
    .rms-agent-delete-actions{grid-template-columns:1fr}
}

@media(max-width:900px){
    .rms-agent-credential-card{grid-template-columns:1fr auto}
    .rms-agent-credential-meta{grid-column:1/-1}
    .rms-agent-credential-actions{grid-column:1/-1;justify-content:flex-end}
}
@media(max-width:760px){
    .rms-provider-block-agent .rms-agent-credential-toolbar{align-items:stretch;flex-direction:column;padding:12px}
    .rms-agent-add-button{width:100%!important}
    .rms-agent-form-grid{grid-template-columns:1fr;padding:11px 12px}
    .rms-agent-form-foot{align-items:stretch;flex-direction:column}
    .rms-agent-form-foot>div{width:100%}.rms-agent-form-foot button{flex:1}
    .rms-agent-credential-list{padding:10px 8px}
    .rms-agent-credential-card{grid-template-columns:1fr;padding:10px}
    .rms-agent-credential-meta{grid-template-columns:1fr 1fr}
    .rms-agent-credential-actions{justify-content:stretch}
    .rms-agent-credential-actions button{flex:1}
    .rms-agent-delete-button{flex:0 0 34px!important}
    .rms-agent-test-note{margin:0 8px 10px}
}
@media(max-width:390px){
    .rms-agent-credential-meta{grid-template-columns:1fr}
    .rms-agent-credential-title{align-items:flex-start;flex-direction:column;gap:4px}
    .rms-agent-credential-actions{flex-wrap:wrap}
    .rms-agent-test-button,.rms-agent-toggle-button{min-width:0}
}
@media(prefers-reduced-motion:reduce){
    .rms-agent-credential-card,.rms-agent-test-button,.rms-agent-toggle-button{transition:none!important}
}
</style>

<style>
/* AgentKit runtime control center */
.rms-agent-runtime-panel{margin:0 0 16px;padding:17px;border:1px solid var(--rms-border,#e5e7eb);border-radius:14px;background:var(--rms-surface,#fff);display:grid;gap:14px;min-width:0}
.rms-agent-runtime-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.rms-agent-runtime-head>div{display:grid;gap:4px;min-width:0}
.rms-agent-runtime-kicker{font-size:9px;letter-spacing:.13em;font-weight:800;color:var(--rms-muted,#6b7280)}
.rms-agent-runtime-head strong{font-size:14px;font-weight:750;color:var(--rms-text,#111827)}
.rms-agent-runtime-head small,.rms-agent-runtime-message{font-size:11px;line-height:1.5;color:var(--rms-muted,#6b7280)}
.rms-agent-runtime-state{display:inline-flex;align-items:center;gap:6px;flex:0 0 auto;padding:6px 9px;border-radius:999px;font-size:10px;font-weight:750;background:#f3f4f6;color:#6b7280}
.rms-agent-runtime-state i{width:6px;height:6px;border-radius:50%;background:currentColor}
.rms-agent-runtime-state.is-running{background:#ecfdf3;color:#16834a}
.rms-agent-runtime-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.rms-agent-runtime-meta span{display:grid;gap:5px;min-width:0;padding:10px;border:1px solid var(--rms-border,#e5e7eb);border-radius:10px}
.rms-agent-runtime-meta b{font-size:9px;color:var(--rms-muted,#6b7280);font-weight:650}
.rms-agent-runtime-meta em{font-size:11px;color:var(--rms-text,#111827);font-style:normal;overflow-wrap:anywhere}
.rms-agent-runtime-message{margin:0}
.rms-agent-runtime-actions{display:flex;flex-wrap:wrap;gap:8px}
.rms-agent-runtime-actions button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;padding:0 12px;border:1px solid var(--rms-border,#e5e7eb);border-radius:9px;background:var(--rms-surface,#fff);color:var(--rms-text,#111827);font-size:11px;font-weight:700;cursor:pointer}
.rms-agent-runtime-actions button:first-child{background:#16834a;border-color:#16834a;color:#fff}
.rms-agent-runtime-actions button:disabled{opacity:.45;cursor:not-allowed}
@media(max-width:640px){.rms-agent-runtime-panel{padding:13px}.rms-agent-runtime-head{flex-direction:column}.rms-agent-runtime-meta{grid-template-columns:repeat(2,minmax(0,1fr))}.rms-agent-runtime-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.rms-agent-runtime-actions button{width:100%;padding:0 7px}}
</style>


<style>
/* Agent AI provider configuration polish */
.rms-provider-block-agent .rms-agent-login-bridge{display:grid;grid-template-columns:44px minmax(0,1fr) auto;align-items:center;gap:14px;margin:14px;padding:17px;border:1px solid #e4e4e7;border-radius:15px;background:linear-gradient(125deg,#fff,#fafafa 65%,#fff7ed);box-shadow:0 8px 24px rgba(24,24,27,.035);position:relative;overflow:hidden}
.rms-provider-block-agent .rms-agent-login-bridge:before{content:"";position:absolute;inset:0 auto 0 0;width:3px;background:linear-gradient(#f59e0b,#fb923c)}
.rms-agent-login-bridge-icon{display:grid;place-items:center;width:44px;height:44px;border:1px solid #fed7aa;border-radius:12px;background:#fff7ed;color:#c2410c;font-size:20px;font-weight:800}
.rms-agent-login-bridge-copy{display:grid;gap:7px;min-width:0}
.rms-agent-login-bridge-copy>strong{font-size:12px;line-height:1.35;font-weight:850;color:#18181b}
.rms-agent-login-bridge-copy>p{margin:0;max-width:760px;font-size:10px;line-height:1.7;color:#71717a}
.rms-agent-login-bridge-status{display:flex;flex-wrap:wrap;gap:7px}
.rms-agent-login-bridge-status>span{display:inline-flex;align-items:center;gap:6px;min-height:25px;padding:0 9px;border:1px solid #e4e4e7;border-radius:999px;background:#fff;color:#71717a;font-size:8px;font-weight:750}
.rms-agent-login-bridge-status>span i{width:6px;height:6px;border-radius:50%;background:#a1a1aa;flex:0 0 6px}
.rms-agent-login-bridge-status>span.is-detected{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}
.rms-agent-login-bridge-status>span.is-detected i{background:#22c55e}
.rms-agent-login-bridge-status>span.is-pending{border-color:#fde68a;background:#fffbeb;color:#a16207}
.rms-agent-login-bridge-status>span.is-pending i{background:#f59e0b}
.rms-agent-login-bridge>.rms-agent-save-button{min-height:40px;padding:0 15px;border-radius:10px;background:#18181b;color:#fff;font-size:9px;font-weight:850;white-space:nowrap;box-shadow:0 6px 14px rgba(24,24,27,.12)}
.rms-agent-runtime-panel{margin:0 14px 16px;padding:18px;border-color:#e4e4e7;border-radius:15px;background:linear-gradient(180deg,#fff,#fcfcfd);box-shadow:0 7px 22px rgba(24,24,27,.025)}
.rms-agent-runtime-head{align-items:center}
.rms-agent-runtime-meta span{padding:12px;border-radius:11px;background:#fff}
.rms-agent-runtime-meta b{font-size:8px;letter-spacing:.02em}
.rms-agent-runtime-meta em{font-size:10px;font-weight:650}
.rms-agent-runtime-actions button{min-height:38px;border-radius:10px;transition:transform .18s ease,box-shadow .18s ease}
.rms-agent-runtime-actions button:not(:disabled):hover{transform:translateY(-1px);box-shadow:0 5px 12px rgba(24,24,27,.06)}
.rms-agent-credential-list{gap:10px;padding:12px 14px 16px}
.rms-agent-credential-card{border-color:#e4e4e7;border-radius:13px;padding:13px;box-shadow:0 3px 12px rgba(24,24,27,.018)}
.rms-agent-credential-copy small{font-size:7px;color:#71717a}
@media(max-width:760px){.rms-provider-block-agent .rms-agent-login-bridge{grid-template-columns:38px minmax(0,1fr);gap:11px;margin:12px 9px;padding:13px}.rms-agent-login-bridge-icon{width:38px;height:38px}.rms-agent-login-bridge-copy>strong{font-size:11px}.rms-agent-login-bridge-copy>p{font-size:9px}.rms-agent-login-bridge>.rms-agent-save-button{grid-column:1/-1;width:100%;min-height:42px}.rms-agent-runtime-panel{margin:0 9px 13px;padding:13px}.rms-agent-runtime-meta{gap:7px}.rms-agent-runtime-meta span{padding:10px}.rms-agent-credential-list{padding:10px 9px 13px}}
@media(max-width:390px){.rms-agent-runtime-meta{grid-template-columns:1fr 1fr}.rms-agent-runtime-actions{grid-template-columns:1fr}}
</style>


<style>
/* AgentKit provider: credential-first layout, independent from the sidebar worker controls. */
.rms-provider-block-agent{overflow:hidden;background:#fff}
.rms-provider-block-agent .rms-provider-block-head{padding:23px 25px;gap:16px}
.rms-provider-block-agent .rms-provider-block-icon{width:46px;height:46px;border-radius:14px;box-shadow:0 5px 16px rgba(245,158,11,.12)}
.rms-provider-block-agent .rms-provider-block-copy>span{font-size:9px;letter-spacing:.17em}
.rms-provider-block-agent .rms-provider-block-copy>strong{font-size:18px;letter-spacing:-.035em}
.rms-provider-block-agent .rms-provider-block-copy>small{max-width:620px;font-size:11px;line-height:1.65}
.rms-provider-block-agent .rms-ai-provider-section{margin:0 18px 18px!important;border:1px solid #e6e7eb!important;border-radius:16px!important;background:#fff;overflow:hidden}
.rms-provider-block-agent .rms-ai-provider-section-head{padding:19px 20px!important;gap:14px}
.rms-provider-block-agent .rms-ai-provider-section-title{gap:12px}
.rms-provider-block-agent .rms-ai-provider-section-title strong{font-size:13px;letter-spacing:-.015em}
.rms-provider-block-agent .rms-ai-provider-section-title small{max-width:650px;font-size:10px;line-height:1.6}
.rms-provider-block-agent .rms-agent-credential-toolbar{padding:14px 20px;gap:14px}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child{max-width:220px}
.rms-provider-block-agent .rms-agent-credential-toolbar strong{font-size:10px}
.rms-provider-block-agent .rms-agent-credential-toolbar small{font-size:9px;line-height:1.55}
.rms-provider-block-agent .rms-agent-login-bridge{margin:15px 18px 18px;grid-template-columns:46px minmax(0,1fr) auto;gap:15px;padding:20px;border-radius:16px;background:linear-gradient(120deg,#fff 0%,#fffdf8 58%,#fff7ed 100%)}
.rms-provider-block-agent .rms-agent-login-bridge-icon{width:46px;height:46px;border-radius:14px;font-size:21px}
.rms-agent-login-bridge-copy>strong{font-size:13px;letter-spacing:-.02em}
.rms-agent-login-bridge-copy>p{font-size:11px;line-height:1.75;max-width:780px}
.rms-agent-login-bridge-status>span{min-height:28px;padding:0 10px;font-size:9px}
.rms-agent-login-bridge>.rms-agent-save-button{min-height:42px;padding:0 17px;font-size:10px}
.rms-provider-block-agent .rms-agent-credential-list{padding:0 18px 20px;gap:12px}
.rms-provider-block-agent .rms-agent-credential-card{padding:16px;border:1px solid #e7e8ec;border-radius:14px;background:linear-gradient(180deg,#fff,#fdfdfd);display:grid;grid-template-columns:minmax(0,1.25fr) minmax(180px,.8fr);align-items:center;column-gap:20px;row-gap:13px;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
.rms-provider-block-agent .rms-agent-credential-card:hover{border-color:#d4d4d8;box-shadow:0 8px 22px rgba(24,24,27,.045);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-credential-main{min-width:0}
.rms-provider-block-agent .rms-agent-credential-icon{width:38px;height:38px;border-radius:12px;flex:0 0 38px}
.rms-provider-block-agent .rms-agent-credential-copy{gap:7px;min-width:0}
.rms-provider-block-agent .rms-agent-credential-title{gap:8px;flex-wrap:wrap}
.rms-provider-block-agent .rms-agent-credential-title strong{font-size:12px;overflow-wrap:anywhere}
.rms-provider-block-agent .rms-agent-credential-copy code{font-size:10px;letter-spacing:.12em}
.rms-provider-block-agent .rms-agent-credential-copy small{font-size:9px;line-height:1.5}
.rms-provider-block-agent .rms-agent-credential-meta{grid-column:2;grid-row:1;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.rms-provider-block-agent .rms-agent-credential-meta>span{padding:10px;border:1px solid #eeeef1;border-radius:10px;background:#fff;min-width:0}
.rms-provider-block-agent .rms-agent-credential-meta b{font-size:8px;letter-spacing:.04em}
.rms-provider-block-agent .rms-agent-credential-meta em{font-size:10px;overflow-wrap:anywhere}
.rms-provider-block-agent .rms-agent-cooldown{grid-column:1/-1;margin:0;padding:9px 11px;border-radius:9px;font-size:9px}
.rms-provider-block-agent .rms-agent-credential-actions{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:8px;padding-top:12px;border-top:1px solid #f0f0f2}
.rms-provider-block-agent .rms-agent-credential-actions button{min-height:35px;padding:0 13px;border-radius:9px;font-size:10px}
.rms-provider-block-agent .rms-agent-delete-button{width:35px;min-width:35px}
.rms-provider-block-agent .rms-agent-empty{margin:0 18px 20px;padding:24px;border:1px dashed #d4d4d8;border-radius:14px;background:#fafafa}
.rms-provider-block-agent .rms-agent-empty strong{font-size:12px}
.rms-provider-block-agent .rms-agent-empty small{font-size:10px;line-height:1.65}
@media(max-width:760px){
 .rms-provider-block-agent .rms-provider-block-head{padding:18px 15px;gap:11px}
 .rms-provider-block-agent .rms-provider-block-copy>strong{font-size:16px}
 .rms-provider-block-agent .rms-ai-provider-section{margin:0 9px 12px!important}
 .rms-provider-block-agent .rms-ai-provider-section-head{padding:15px!important;align-items:flex-start}
 .rms-provider-block-agent .rms-agent-credential-toolbar{padding:12px 15px;display:grid;grid-template-columns:minmax(0,1fr);gap:10px}
 .rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child{max-width:none}
 .rms-provider-block-agent .rms-agent-login-bridge{grid-template-columns:38px minmax(0,1fr);margin:12px 9px 14px;padding:14px;gap:11px}
 .rms-provider-block-agent .rms-agent-login-bridge-icon{width:38px;height:38px}
 .rms-agent-login-bridge-copy>strong{font-size:12px}
 .rms-agent-login-bridge-copy>p{font-size:10px}
 .rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button{grid-column:1/-1;width:100%;white-space:normal}
 .rms-provider-block-agent .rms-agent-credential-list{padding:0 9px 14px;gap:10px}
 .rms-provider-block-agent .rms-agent-credential-card{grid-template-columns:minmax(0,1fr);gap:12px;padding:13px}
 .rms-provider-block-agent .rms-agent-credential-meta{grid-column:1;grid-row:auto}
 .rms-provider-block-agent .rms-agent-credential-actions{grid-column:1;gap:7px}
 .rms-provider-block-agent .rms-agent-credential-actions button{flex:1;min-width:0}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{flex:0 0 35px}
}
@media(max-width:390px){
 .rms-provider-block-agent .rms-provider-block-state{grid-column:2;justify-self:start}
 .rms-provider-block-agent .rms-agent-credential-meta{grid-template-columns:minmax(0,1fr)}
 .rms-provider-block-agent .rms-agent-credential-actions{display:grid;grid-template-columns:1fr 1fr}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{grid-column:2;justify-self:end}
}
@media(prefers-reduced-motion:reduce){
 .rms-provider-block-agent .rms-agent-credential-card{transition:none}
 .rms-provider-block-agent .rms-agent-credential-card:hover{transform:none}
}
</style>


<style>
/* Final Agent AI interaction polish: compact actions, clear hierarchy, tactile feedback. */
.rms-provider-block-agent .rms-agent-credential-toolbar{
 display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 20px;
}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child{flex:1;max-width:440px}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child strong{display:block;font-size:11px;font-weight:850;color:#27272a;margin-bottom:4px}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child small{display:block;font-size:10px;line-height:1.55;color:#71717a}
.rms-provider-block-agent .rms-agent-add-button{
 flex:0 0 auto!important;width:auto!important;min-width:148px;min-height:42px;padding:0 16px!important;
 display:inline-flex!important;align-items:center;justify-content:center;gap:9px;
 border:1px solid #27272a!important;border-radius:11px!important;background:#27272a!important;color:#fff!important;
 box-shadow:0 4px 10px rgba(24,24,27,.10);font-size:11px!important;font-weight:800!important;
 transition:background .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease;
}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled){background:#09090b!important;border-color:#09090b!important;box-shadow:0 7px 16px rgba(24,24,27,.17);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-add-button:active:not(:disabled){transform:translateY(0) scale(.98)}
.rms-provider-block-agent .rms-agent-add-button:disabled{opacity:.6;cursor:wait}
.rms-agent-add-plus{display:grid;place-items:center;width:21px;height:21px;border:1px solid rgba(255,255,255,.24);border-radius:7px;font-size:17px;line-height:1;font-weight:500}
.rms-agent-add-label{font-size:11px;white-space:nowrap}
.rms-agent-add-loading{font-size:10px;white-space:nowrap}
.rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button{
 display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:44px;padding:0 18px;
 border:1px solid #18181b;border-radius:11px;background:#18181b;color:#fff;font-size:10px;font-weight:800;
 box-shadow:0 5px 13px rgba(24,24,27,.12);transition:background .18s ease,box-shadow .18s ease,transform .18s ease;
}
.rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button:hover:not(:disabled){background:#09090b;box-shadow:0 8px 18px rgba(24,24,27,.18);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button:active:not(:disabled){transform:scale(.98)}
.rms-provider-block-agent .rms-agent-credential-actions{align-items:center;gap:9px}
.rms-provider-block-agent .rms-agent-credential-actions button{
 min-height:39px;min-width:108px;padding:0 15px;border:1px solid transparent;border-radius:10px;
 display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:10px;font-weight:800;
 letter-spacing:-.01em;cursor:pointer;transition:transform .17s ease,box-shadow .17s ease,background .17s ease,border-color .17s ease;
}
.rms-provider-block-agent .rms-agent-test-button{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9;box-shadow:0 2px 4px rgba(109,40,217,.04)}
.rms-provider-block-agent .rms-agent-test-button:hover:not(:disabled){background:#ede9fe;border-color:#c4b5fd;box-shadow:0 5px 12px rgba(109,40,217,.10);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled{background:#ecfdf3;border-color:#bbf7d0;color:#15803d}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled:hover:not(:disabled){background:#dcfce7;border-color:#86efac;box-shadow:0 5px 12px rgba(22,163,74,.09);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled{background:#16834a;border-color:#16834a;color:#fff}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled:hover:not(:disabled){background:#116b3c;border-color:#116b3c;box-shadow:0 5px 12px rgba(22,131,74,.15);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-delete-button{flex:0 0 39px!important;width:39px;min-width:39px!important;padding:0!important;background:#fff5f5;border-color:#fecaca!important;color:#dc2626;font-size:18px!important;box-shadow:none}
.rms-provider-block-agent .rms-agent-delete-button:hover:not(:disabled){background:#fee2e2;border-color:#fca5a5!important;box-shadow:0 5px 12px rgba(220,38,38,.09);transform:translateY(-1px)}
.rms-provider-block-agent .rms-agent-credential-actions button:active:not(:disabled){transform:translateY(0) scale(.98)}
.rms-provider-block-agent .rms-agent-credential-actions button:disabled{opacity:.55;cursor:wait;box-shadow:none;transform:none}
.rms-provider-block-agent .rms-agent-credential-card{box-shadow:0 2px 7px rgba(24,24,27,.025)}
.rms-provider-block-agent .rms-agent-credential-card:hover{box-shadow:0 9px 24px rgba(24,24,27,.055)}
.rms-provider-block-agent .rms-agent-status{padding:5px 8px;border-radius:999px;font-size:8px;letter-spacing:.01em}
.rms-provider-block-agent .rms-agent-cooldown{border:1px solid #fde7a8;background:#fffbeb;color:#a16207}
.rms-provider-block-agent .rms-agent-login-bridge-status>span{font-size:9px}
@media(max-width:760px){
 .rms-provider-block-agent .rms-agent-credential-toolbar{align-items:stretch;flex-direction:column;padding:14px 15px;gap:12px}
 .rms-provider-block-agent .rms-agent-add-button{width:100%!important;min-height:44px}
 .rms-provider-block-agent .rms-agent-credential-actions{display:flex;flex-wrap:wrap;justify-content:stretch;gap:8px}
 .rms-provider-block-agent .rms-agent-credential-actions button{flex:1 1 100px;min-width:0;min-height:42px;padding:0 10px}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{flex:0 0 42px!important;width:42px;min-width:42px!important}
 .rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button{min-height:44px}
}
@media(max-width:390px){
 .rms-provider-block-agent .rms-agent-credential-actions{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) 42px}
 .rms-provider-block-agent .rms-agent-credential-actions button{width:100%;min-width:0;padding:0 7px;font-size:9px}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{grid-column:auto}
 .rms-agent-add-loading{font-size:9px}
}
@media(prefers-reduced-motion:reduce){
 .rms-provider-block-agent .rms-agent-add-button,.rms-provider-block-agent .rms-agent-login-bridge>.rms-agent-save-button,.rms-provider-block-agent .rms-agent-credential-actions button{transition:none}
}
</style>


<style>
/* Agent AI: one canonical account-entry action and a cleaner credential action bar. */
.rms-provider-block-agent .rms-agent-add-button{
 position:relative;min-width:174px!important;min-height:48px!important;padding:0 18px!important;
 justify-content:flex-start!important;gap:11px!important;border-radius:13px!important;
 background:linear-gradient(135deg,#27272a,#18181b)!important;border-color:#27272a!important;
 box-shadow:0 7px 17px rgba(24,24,27,.15),inset 0 1px 0 rgba(255,255,255,.09)!important;
}
.rms-provider-block-agent .rms-agent-add-button:before{
 content:"";position:absolute;inset:0 auto 0 0;width:3px;border-radius:13px 0 0 13px;
 background:linear-gradient(180deg,#fbbf24,#f97316);
}
.rms-provider-block-agent .rms-agent-add-plus{
 width:27px;height:27px;border:1px solid rgba(255,255,255,.2);border-radius:9px;
 background:rgba(255,255,255,.08);font-size:19px;font-weight:400;
}
.rms-provider-block-agent .rms-agent-add-label{font-size:12px;font-weight:850;letter-spacing:-.015em}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled){
 background:linear-gradient(135deg,#18181b,#09090b)!important;
 box-shadow:0 10px 23px rgba(24,24,27,.2),0 0 0 3px rgba(245,158,11,.09)!important;
}
.rms-agent-login-hint{
 margin:0 18px 16px;padding:12px 14px;display:flex;align-items:center;gap:11px;
 border:1px solid #e8e8ec;border-radius:12px;background:#fafafa;min-width:0;
}
.rms-agent-login-hint-icon{
 width:27px;height:27px;flex:0 0 27px;display:grid;place-items:center;border-radius:9px;
 background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;font-size:12px;font-weight:900;
}
.rms-agent-login-hint-copy{display:grid;gap:3px;min-width:0;flex:1}
.rms-agent-login-hint-copy strong{font-size:10px;font-weight:850;color:#3f3f46}
.rms-agent-login-hint-copy span{font-size:9px;line-height:1.5;color:#71717a}
.rms-agent-login-hint-state{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-size:8px;font-weight:800;color:#71717a}
.rms-agent-login-hint-state i{width:6px;height:6px;border-radius:50%;background:#a1a1aa}
.rms-agent-login-hint-state.is-ready{color:#15803d}
.rms-agent-login-hint-state.is-ready i{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.1)}
.rms-agent-login-hint-state.is-pending{color:#a16207}
.rms-agent-login-hint-state.is-pending i{background:#f59e0b}
.rms-provider-block-agent .rms-agent-credential-actions{
 display:flex;align-items:center;gap:10px;padding-top:14px;
}
.rms-provider-block-agent .rms-agent-credential-actions button{
 min-height:43px;padding:0 16px;min-width:124px;border-radius:11px;
 display:inline-flex;align-items:center;justify-content:center;gap:8px;
 font-size:10px;font-weight:850;letter-spacing:0;box-shadow:0 2px 5px rgba(24,24,27,.035);
}
.rms-agent-action-icon{font-size:13px;line-height:1;font-weight:900}
.rms-provider-block-agent .rms-agent-test-button{
 background:#f5f3ff;border:1px solid #ddd6fe;color:#6d28d9;
}
.rms-provider-block-agent .rms-agent-test-button:hover:not(:disabled){
 background:#ede9fe;border-color:#c4b5fd;box-shadow:0 5px 13px rgba(109,40,217,.11);transform:translateY(-1px)
}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled{
 background:#fff;border:1px solid #d4d4d8;color:#52525b;
}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled:hover:not(:disabled){
 background:#f4f4f5;border-color:#a1a1aa;box-shadow:0 5px 13px rgba(24,24,27,.07);transform:translateY(-1px)
}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled{
 background:#15803d;border:1px solid #15803d;color:#fff;
}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled:hover:not(:disabled){
 background:#166534;border-color:#166534;box-shadow:0 5px 13px rgba(21,128,61,.16);transform:translateY(-1px)
}
.rms-provider-block-agent .rms-agent-delete-button{
 flex:0 0 43px!important;width:43px;min-width:43px!important;padding:0!important;
 background:#fff;border:1px solid #fecaca!important;color:#dc2626;font-size:20px!important;
}
.rms-provider-block-agent .rms-agent-delete-button:hover:not(:disabled){
 background:#fef2f2;border-color:#f87171!important;box-shadow:0 5px 13px rgba(220,38,38,.10);transform:translateY(-1px)
}
.rms-provider-block-agent .rms-agent-credential-actions button:active:not(:disabled){transform:scale(.98)}
.rms-provider-block-agent .rms-agent-credential-actions button:disabled{opacity:.5;cursor:wait;box-shadow:none;transform:none}
@media(max-width:760px){
 .rms-provider-block-agent .rms-agent-add-button{width:100%!important;min-width:0!important;justify-content:center!important}
 .rms-agent-login-hint{margin:0 9px 13px;align-items:flex-start;padding:11px}
 .rms-agent-login-hint-state{white-space:normal;align-self:center}
 .rms-provider-block-agent .rms-agent-credential-actions{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) 43px;gap:8px}
 .rms-provider-block-agent .rms-agent-credential-actions button{width:100%;min-width:0;padding:0 8px;font-size:9px}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{grid-column:auto;flex:0 0 43px!important}
}
@media(max-width:420px){
 .rms-agent-login-hint{flex-wrap:wrap}
 .rms-agent-login-hint-copy{flex-basis:calc(100% - 42px)}
 .rms-agent-login-hint-state{margin-left:38px}
 .rms-provider-block-agent .rms-agent-credential-actions{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 40px;gap:6px}
 .rms-provider-block-agent .rms-agent-credential-actions button{font-size:8px;gap:5px;padding:0 5px}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{width:40px;min-width:40px!important;flex-basis:40px!important}
}
@media(prefers-reduced-motion:reduce){
 .rms-provider-block-agent .rms-agent-add-button,.rms-provider-block-agent .rms-agent-credential-actions button{transition:none}
}
</style>


<style>
/* Agent AI premium pass — unify the entire provider surface, not just its controls. */
.rms-provider-block-agent{
 position:relative;isolation:isolate;overflow:hidden;
 border:1px solid #e7e2d8!important;border-radius:24px!important;
 background:linear-gradient(155deg,#fff 0%,#fdfcf9 62%,#fbfaf7 100%)!important;
 box-shadow:0 14px 38px rgba(28,25,23,.045),0 2px 6px rgba(28,25,23,.025)!important;
}
.rms-provider-block-agent:before{
 content:"";position:absolute;z-index:-1;inset:0 0 auto;height:3px;
 background:linear-gradient(90deg,#292524 0%,#a8a29e 22%,#fbbf24 70%,#f59e0b 100%);
}
.rms-provider-block-agent .rms-provider-block-head{
 position:relative;padding:27px 28px 24px!important;gap:17px!important;
 border-bottom:1px solid #eeeae3;background:linear-gradient(110deg,rgba(255,255,255,.94),rgba(250,248,243,.82));
}
.rms-provider-block-agent .rms-provider-block-icon{
 width:52px!important;height:52px!important;flex:0 0 52px;border-radius:17px!important;
 background:linear-gradient(145deg,#fffdf8,#fff7e8)!important;border:1px solid #f0dfbd!important;
 color:#c78308!important;box-shadow:0 7px 18px rgba(161,98,7,.07),inset 0 1px 0 #fff!important;
}
.rms-provider-block-agent .rms-provider-block-icon span{font-size:25px;filter:drop-shadow(0 2px 3px rgba(217,119,6,.15))}
.rms-provider-block-agent .rms-provider-block-copy{gap:6px!important}
.rms-provider-block-agent .rms-provider-block-copy>span{font-size:9px!important;letter-spacing:.2em!important;color:#b91c1c!important}
.rms-provider-block-agent .rms-provider-block-copy>strong{font-size:22px!important;line-height:1.12;letter-spacing:-.045em!important;color:#18181b!important}
.rms-provider-block-agent .rms-provider-block-copy>small{font-size:11px!important;line-height:1.7!important;color:#71717a!important;max-width:660px}
.rms-provider-block-agent .rms-provider-block-state{
 padding:8px 12px!important;min-height:32px;border:1px solid #e4e4e7!important;
 border-radius:999px!important;background:rgba(255,255,255,.88);box-shadow:0 2px 5px rgba(24,24,27,.025);
}
.rms-provider-block-agent .rms-provider-block-state span{font-size:8px!important;letter-spacing:.08em}
.rms-provider-block-agent .rms-provider-block-state i{width:7px;height:7px;box-shadow:0 0 0 4px rgba(34,197,94,.09)}
.rms-provider-block-agent .rms-agent-provider-card{
 margin:0 18px 18px!important;border:1px solid #e7e5e4!important;border-radius:19px!important;
 background:rgba(255,255,255,.92)!important;box-shadow:0 4px 15px rgba(28,25,23,.025)!important;
 overflow:hidden;
}
.rms-provider-block-agent .rms-ai-provider-section-head{
 padding:20px 22px!important;min-height:82px;border-bottom:1px solid #f0eeeb!important;
 background:linear-gradient(110deg,#fff,#fdfcfb);
}
.rms-provider-block-agent .rms-ai-provider-icon.rms-agent-icon{
 width:43px!important;height:43px!important;border-radius:14px!important;
 background:linear-gradient(145deg,#27272a,#09090b)!important;color:#fff!important;
 box-shadow:0 4px 9px rgba(24,24,27,.12),inset 0 1px 0 rgba(255,255,255,.1);
}
.rms-provider-block-agent .rms-ai-provider-section-title strong{font-size:14px!important;font-weight:850;letter-spacing:-.035em;color:#27272a}
.rms-provider-block-agent .rms-ai-provider-section-title small{font-size:10px!important;line-height:1.65!important;color:#85858f!important}
.rms-provider-block-agent .rms-ai-provider-status{
 display:inline-flex;align-items:center;gap:7px;padding:7px 10px!important;
 border:1px solid #d1fae5;border-radius:999px;background:#f0fdf4;color:#15803d!important;
 font-size:9px!important;font-weight:850;white-space:nowrap;
}
.rms-provider-block-agent .rms-ai-provider-status i{width:6px;height:6px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.1)}
.rms-provider-block-agent .rms-agent-credential-toolbar{
 padding:19px 22px!important;gap:20px!important;background:#fff;
}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child strong{font-size:12px!important;letter-spacing:-.02em;margin-bottom:5px}
.rms-provider-block-agent .rms-agent-credential-toolbar>div:first-child small{font-size:10px!important;line-height:1.7!important;color:#85858f!important}
.rms-provider-block-agent .rms-agent-add-button{
 min-width:174px!important;min-height:49px!important;padding:0 19px!important;border-radius:13px!important;
 background:linear-gradient(135deg,#292524,#18181b)!important;border-color:#292524!important;
 box-shadow:0 7px 17px rgba(24,24,27,.13),inset 0 1px 0 rgba(255,255,255,.1)!important;
}
.rms-provider-block-agent .rms-agent-add-button:before{width:3px;background:linear-gradient(180deg,#fbbf24,#f97316)}
.rms-provider-block-agent .rms-agent-add-plus{
 width:28px;height:28px;border-radius:9px;background:rgba(255,255,255,.09);
 border-color:rgba(255,255,255,.2);font-size:18px;
}
.rms-provider-block-agent .rms-agent-add-label{font-size:12px!important;letter-spacing:-.02em}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled){
 box-shadow:0 11px 25px rgba(24,24,27,.19),0 0 0 4px rgba(245,158,11,.075)!important;transform:translateY(-2px)
}
.rms-agent-login-hint{
 margin:0 18px 17px!important;padding:13px 15px!important;gap:12px!important;
 border:1px solid #ece9e4!important;border-radius:13px!important;
 background:linear-gradient(110deg,#fafaf9,#f8f7f4)!important;
}
.rms-agent-login-hint-icon{
 width:30px;height:30px;flex-basis:30px;border-radius:10px!important;
 background:#fff8eb!important;border-color:#f3dfbd!important;color:#b45309!important;
 box-shadow:inset 0 1px 0 #fff;
}
.rms-agent-login-hint-copy strong{font-size:10px!important;color:#44403c!important;letter-spacing:-.01em}
.rms-agent-login-hint-copy span{font-size:9px!important;line-height:1.65!important;color:#78716c!important}
.rms-agent-login-hint-state{padding:7px 9px;border:1px solid #d1fae5;border-radius:999px;background:#f0fdf4;font-size:8px!important;color:#15803d!important}
.rms-agent-login-hint-state i{width:6px;height:6px}
.rms-provider-block-agent .rms-agent-credential-list{padding:0 18px 20px!important;gap:12px!important}
.rms-provider-block-agent .rms-agent-credential-card{
 position:relative;grid-template-columns:minmax(0,1.2fr) minmax(260px,.9fr)!important;
 gap:16px 22px!important;padding:21px!important;border:1px solid #e7e5e4!important;
 border-radius:17px!important;background:linear-gradient(145deg,#fff 0%,#fff 70%,#fdfcfb 100%)!important;
 box-shadow:0 3px 10px rgba(28,25,23,.025)!important;
}
.rms-provider-block-agent .rms-agent-credential-card:before{
 content:"";position:absolute;inset:0 auto 0 0;width:3px;border-radius:17px 0 0 17px;
 background:linear-gradient(180deg,#d6d3d1,#f5f5f4);opacity:.9;
}
.rms-provider-block-agent .rms-agent-credential-card.is-enabled:before{background:linear-gradient(180deg,#86efac,#16a34a)}
.rms-provider-block-agent .rms-agent-credential-card.is-disabled:before{background:linear-gradient(180deg,#d4d4d8,#71717a)}
.rms-provider-block-agent .rms-agent-credential-card:hover{
 border-color:#d6d3d1!important;box-shadow:0 12px 28px rgba(28,25,23,.055)!important;transform:translateY(-2px)
}
.rms-provider-block-agent .rms-agent-credential-icon{
 width:44px!important;height:44px!important;flex-basis:44px!important;border-radius:14px!important;
 background:linear-gradient(145deg,#27272a,#09090b)!important;color:#fff!important;
 box-shadow:0 5px 12px rgba(24,24,27,.13),inset 0 1px 0 rgba(255,255,255,.1);
}
.rms-provider-block-agent .rms-agent-credential-copy{gap:7px!important}
.rms-provider-block-agent .rms-agent-credential-title{align-items:center;gap:9px}
.rms-provider-block-agent .rms-agent-credential-title strong{font-size:13px!important;font-weight:850;letter-spacing:-.025em;color:#27272a}
.rms-provider-block-agent .rms-agent-status{padding:5px 9px!important;font-size:8px!important;font-weight:850;letter-spacing:.015em;border:1px solid transparent}
.rms-provider-block-agent .rms-agent-status.is-active{background:#ecfdf3;border-color:#d1fae5;color:#15803d}
.rms-provider-block-agent .rms-agent-status.is-cooldown{background:#fffbeb;border-color:#fde68a;color:#a16207}
.rms-provider-block-agent .rms-agent-status.is-error{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.rms-provider-block-agent .rms-agent-status.is-disabled{background:#f4f4f5;border-color:#e4e4e7;color:#71717a}
.rms-provider-block-agent .rms-agent-status.is-pending{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.rms-provider-block-agent .rms-agent-credential-copy code{
 display:inline-flex;align-items:center;max-width:max-content;padding:5px 8px;border:1px solid #f0eeeb;
 border-radius:7px;background:#fafaf9;color:#a1a1aa;font-size:10px!important;letter-spacing:.15em!important;
}
.rms-provider-block-agent .rms-agent-credential-copy small{font-size:9px!important;color:#85858f!important;line-height:1.6}
.rms-provider-block-agent .rms-agent-credential-meta{gap:9px!important}
.rms-provider-block-agent .rms-agent-credential-meta>span{
 padding:12px 13px!important;border:1px solid #eeece8!important;border-radius:12px!important;
 background:linear-gradient(145deg,#fff,#fdfcfb)!important;gap:5px!important;
}
.rms-provider-block-agent .rms-agent-credential-meta b{font-size:8px!important;letter-spacing:.08em!important;color:#a1a1aa!important}
.rms-provider-block-agent .rms-agent-credential-meta em{font-size:10px!important;font-weight:600;color:#57534e!important}
.rms-provider-block-agent .rms-agent-cooldown{
 grid-column:1/-1;margin:0!important;padding:11px 13px!important;border:1px solid #f5dfa0!important;
 border-radius:10px!important;background:linear-gradient(90deg,#fffbeb,#fffdf6)!important;
 color:#a16207!important;font-size:9px!important;font-weight:650;
}
.rms-provider-block-agent .rms-agent-credential-actions{
 grid-column:1/-1;display:flex;align-items:center;gap:9px!important;
 padding-top:15px!important;border-top:1px solid #f0eeeb;
}
.rms-provider-block-agent .rms-agent-credential-actions button{
 min-height:42px!important;border-radius:11px!important;font-size:10px!important;font-weight:850!important;
 box-shadow:0 2px 5px rgba(28,25,23,.035);transition:transform .18s ease,box-shadow .18s ease,background .18s ease,border-color .18s ease;
}
.rms-provider-block-agent .rms-agent-test-button{min-width:132px!important;background:#f5f3ff!important;border-color:#ddd6fe!important;color:#6d28d9!important}
.rms-provider-block-agent .rms-agent-test-button:hover:not(:disabled){background:#ede9fe!important;box-shadow:0 7px 16px rgba(109,40,217,.12)!important;transform:translateY(-2px)}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled{min-width:124px!important;background:#fff!important;border-color:#e4e4e7!important;color:#52525b!important}
.rms-provider-block-agent .rms-agent-toggle-button.is-enabled:hover:not(:disabled){background:#f4f4f5!important;border-color:#d4d4d8!important;box-shadow:0 7px 16px rgba(24,24,27,.07)!important;transform:translateY(-2px)}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled{min-width:124px!important;background:linear-gradient(135deg,#16834a,#166534)!important;border-color:#16834a!important;color:#fff!important}
.rms-provider-block-agent .rms-agent-toggle-button.is-disabled:hover:not(:disabled){box-shadow:0 7px 16px rgba(22,101,52,.18)!important;transform:translateY(-2px)}
.rms-provider-block-agent .rms-agent-delete-button{
 width:42px!important;min-width:42px!important;flex:0 0 42px!important;
 border-radius:11px!important;background:#fff!important;border-color:#fee2e2!important;color:#dc2626!important;
}
.rms-provider-block-agent .rms-agent-delete-button:hover:not(:disabled){background:#fef2f2!important;border-color:#fca5a5!important;box-shadow:0 7px 16px rgba(220,38,38,.10)!important;transform:translateY(-2px)}
.rms-provider-block-agent .rms-agent-test-note{
 margin:0 18px 18px!important;padding:12px 14px!important;border:1px solid #f5e7b1!important;
 border-radius:12px!important;background:linear-gradient(100deg,#fffbeb,#fffdf6)!important;
}
.rms-provider-block-agent .rms-agent-test-note strong{font-size:9px!important;font-weight:850;color:#92400e}
.rms-provider-block-agent .rms-agent-test-note small{font-size:8px!important;line-height:1.65;color:#a16207}
.rms-provider-block-agent .rms-agent-empty{
 margin:0!important;padding:27px 20px!important;border:1px dashed #d6d3d1!important;border-radius:15px!important;background:#fafaf9!important;
}
.rms-provider-block-agent .rms-agent-empty>span{border-radius:13px;background:#fff7ed;color:#c2410c}
.rms-provider-block-agent .rms-agent-empty strong{font-size:12px!important;color:#292524}
.rms-provider-block-agent .rms-agent-empty small{font-size:10px!important;line-height:1.7!important;color:#78716c}
@media(max-width:900px){
 .rms-provider-block-agent .rms-agent-credential-card{grid-template-columns:minmax(0,1fr)!important;padding:17px!important}
 .rms-provider-block-agent .rms-agent-credential-meta{grid-column:1;grid-row:auto}
 .rms-provider-block-agent .rms-agent-credential-actions{grid-column:1}
}
@media(max-width:760px){
 .rms-provider-block-agent{border-radius:18px!important}
 .rms-provider-block-agent .rms-provider-block-head{padding:20px 16px!important;gap:12px!important}
 .rms-provider-block-agent .rms-provider-block-icon{width:44px!important;height:44px!important;flex-basis:44px!important;border-radius:14px!important}
 .rms-provider-block-agent .rms-provider-block-copy>strong{font-size:19px!important}
 .rms-provider-block-agent .rms-provider-block-copy>small{font-size:10px!important}
 .rms-provider-block-agent .rms-provider-block-state{grid-column:2;justify-self:start}
 .rms-provider-block-agent .rms-agent-provider-card{margin:0 9px 12px!important;border-radius:15px!important}
 .rms-provider-block-agent .rms-ai-provider-section-head{padding:15px!important;gap:10px}
 .rms-provider-block-agent .rms-ai-provider-section-title{align-items:flex-start;gap:10px}
 .rms-provider-block-agent .rms-ai-provider-section-title strong{font-size:12px!important}
 .rms-provider-block-agent .rms-ai-provider-status{font-size:8px!important;padding:6px 8px!important}
 .rms-provider-block-agent .rms-agent-credential-toolbar{padding:15px!important}
 .rms-provider-block-agent .rms-agent-add-button{min-height:46px!important}
 .rms-agent-login-hint{margin:0 9px 13px!important;padding:11px!important}
 .rms-agent-login-hint-state{white-space:normal;max-width:100%}
 .rms-provider-block-agent .rms-agent-credential-list{padding:0 9px 13px!important;gap:10px!important}
 .rms-provider-block-agent .rms-agent-credential-card{padding:15px!important;gap:13px!important;border-radius:14px!important}
 .rms-provider-block-agent .rms-agent-credential-meta{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important}
 .rms-provider-block-agent .rms-agent-credential-meta>span{padding:10px!important}
 .rms-provider-block-agent .rms-agent-credential-actions{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 42px;display:grid;gap:7px!important;padding-top:12px!important}
 .rms-provider-block-agent .rms-agent-credential-actions button{width:100%;min-width:0!important;min-height:42px!important;padding:0 8px!important;font-size:9px!important}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{width:42px!important;min-width:42px!important;flex-basis:42px!important}
 .rms-provider-block-agent .rms-agent-test-note{margin:0 9px 13px!important}
}
@media(max-width:420px){
 .rms-provider-block-agent .rms-ai-provider-section-head{align-items:flex-start}
 .rms-provider-block-agent .rms-agent-credential-meta{grid-template-columns:minmax(0,1fr)!important}
 .rms-agent-login-hint{align-items:flex-start;flex-wrap:wrap}
 .rms-agent-login-hint-copy{flex-basis:calc(100% - 43px)}
 .rms-agent-login-hint-state{margin-left:41px}
 .rms-provider-block-agent .rms-agent-credential-actions{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 38px}
 .rms-provider-block-agent .rms-agent-credential-actions button{font-size:8px!important;gap:5px;padding:0 5px!important}
 .rms-provider-block-agent .rms-agent-credential-actions .rms-agent-delete-button{width:38px!important;min-width:38px!important;flex-basis:38px!important}
}
@media(prefers-reduced-motion:reduce){
 .rms-provider-block-agent .rms-agent-credential-card,.rms-provider-block-agent .rms-agent-credential-actions button,.rms-provider-block-agent .rms-agent-add-button{transition:none!important}
 .rms-provider-block-agent .rms-agent-credential-card:hover,.rms-provider-block-agent .rms-agent-credential-actions button:hover{transform:none!important}
}
</style>


<style>
/* Add Account: icon-led premium action, no text symbols or numeric-looking glyphs. */
.rms-provider-block-agent .rms-agent-add-button{
 min-width:180px!important;min-height:50px!important;padding:0 15px!important;
 display:inline-flex!important;align-items:center!important;justify-content:flex-start!important;gap:11px!important;
 border:1px solid #292524!important;border-radius:14px!important;
 background:linear-gradient(135deg,#292524 0%,#18181b 72%,#111113 100%)!important;color:#fff!important;
 box-shadow:0 8px 20px rgba(24,24,27,.15),inset 0 1px 0 rgba(255,255,255,.12)!important;
}
.rms-provider-block-agent .rms-agent-add-button:before{
 width:3px!important;background:linear-gradient(180deg,#fcd34d,#f59e0b,#ea580c)!important;border-radius:14px 0 0 14px;
}
.rms-provider-block-agent .rms-agent-add-icon{
 width:30px;height:30px;flex:0 0 30px;display:grid;place-items:center;
 border:1px solid rgba(255,255,255,.17);border-radius:10px;
 background:rgba(255,255,255,.085);color:#fef3c7;
 box-shadow:inset 0 1px 0 rgba(255,255,255,.08);
}
.rms-provider-block-agent .rms-agent-add-icon svg{display:block;width:16px;height:16px}
.rms-provider-block-agent .rms-agent-add-label{
 flex:1;display:inline-flex;align-items:center;justify-content:flex-start;
 font-size:11px!important;font-weight:800!important;letter-spacing:.005em;white-space:nowrap;
}
.rms-provider-block-agent .rms-agent-add-chevron{
 width:16px;height:16px;flex:0 0 16px;color:#a8a29e;transition:transform .18s ease,color .18s ease;
}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled){
 background:linear-gradient(135deg,#1c1917,#09090b)!important;
 box-shadow:0 12px 26px rgba(24,24,27,.2),0 0 0 4px rgba(245,158,11,.08)!important;
 transform:translateY(-2px);
}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled) .rms-agent-add-icon{
 background:rgba(251,191,36,.12);border-color:rgba(251,191,36,.3);color:#fbbf24;
}
.rms-provider-block-agent .rms-agent-add-button:hover:not(:disabled) .rms-agent-add-chevron{transform:translateX(2px);color:#fef3c7}
.rms-provider-block-agent .rms-agent-add-button:active:not(:disabled){transform:translateY(0) scale(.99)}
.rms-provider-block-agent .rms-agent-add-button:disabled{opacity:.7;cursor:wait;transform:none}
@media(max-width:760px){
 .rms-provider-block-agent .rms-agent-add-button{width:100%!important;min-width:0!important;justify-content:center!important}
 .rms-provider-block-agent .rms-agent-add-label{flex:0 1 auto}
 .rms-provider-block-agent .rms-agent-add-chevron{margin-left:2px}
}
@media(prefers-reduced-motion:reduce){
 .rms-provider-block-agent .rms-agent-add-button,.rms-provider-block-agent .rms-agent-add-chevron{transition:none!important}
}
</style>


<style>
/* Final fix: use a person-plus icon and keep the label stable during Livewire requests. */
.rms-provider-block-agent .rms-agent-add-icon svg{width:19px!important;height:19px!important;overflow:visible}
.rms-provider-block-agent .rms-agent-add-icon{width:32px;height:32px;flex-basis:32px}
.rms-provider-block-agent .rms-agent-add-label{
 display:inline-flex!important;flex:1 1 auto!important;align-items:center;justify-content:flex-start;
 white-space:nowrap;font-size:11px!important;font-weight:800!important;line-height:1.2;
}
.rms-provider-block-agent .rms-agent-add-button:disabled .rms-agent-add-label{opacity:.86}
.rms-provider-block-agent .rms-agent-add-button:disabled .rms-agent-add-chevron{opacity:.35}
</style>

<style>
/* Configurable account cooldown controls */
.rms-agent-monitor-cooldown-control{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:18px;margin:16px 0 24px;padding:18px;border:1px solid #eadfce;border-radius:16px;background:linear-gradient(135deg,#fffaf2,#fff)}
.rms-agent-monitor-control-eyebrow{display:block;margin-bottom:7px;color:#a96509;font-size:9px;font-weight:800;letter-spacing:.16em}
.rms-agent-monitor-cooldown-control>div:first-child>strong{display:block;color:#292524;font-size:14px;font-weight:800}
.rms-agent-monitor-cooldown-control p{margin:6px 0 0;color:#78716c;font-size:11px;line-height:1.55}
.rms-agent-monitor-cooldown-form{display:flex;align-items:flex-end;gap:9px}
.rms-agent-monitor-cooldown-form label{display:grid;gap:6px;color:#78716c;font-size:10px;font-weight:700}
.rms-agent-monitor-cooldown-form input{width:100px;min-height:40px;padding:0 10px;border:1px solid #e7ddd0;border-radius:10px;background:#fff;color:#292524;font:inherit;font-size:13px}
.rms-agent-monitor-cooldown-form button{min-height:40px;padding:0 14px;border:0;border-radius:10px;background:#292524;color:#fff;font-size:11px;font-weight:800;white-space:nowrap}
.rms-agent-monitor-cooldown-form button:disabled{opacity:.55}
@media(max-width:760px){.rms-agent-monitor-cooldown-control{grid-template-columns:1fr;gap:14px;padding:15px;margin:14px 0 20px}.rms-agent-monitor-cooldown-form{display:grid;grid-template-columns:92px minmax(0,1fr);align-items:end}.rms-agent-monitor-cooldown-form input{width:100%}.rms-agent-monitor-cooldown-form button{white-space:normal;padding:0 10px}}

/* Responsive per-account AgentKit monitoring drawer */
.rms-agent-monitor-overlay{position:fixed;inset:0;z-index:10050;display:flex;justify-content:flex-end;background:rgba(24,24,27,.42);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);animation:rmsAgentMonitorFade .18s ease-out}
.rms-agent-monitor-backdrop{position:absolute;inset:0;width:100%;height:100%;border:0;background:transparent}
.rms-agent-monitor-drawer{position:relative;z-index:1;display:flex;flex-direction:column;width:min(680px,100%);height:100%;max-height:100dvh;background:#fbfaf8;border-left:1px solid #fff;box-shadow:-24px 0 70px rgba(24,24,27,.18);animation:rmsAgentMonitorSlide .24s cubic-bezier(.2,.8,.2,1);overflow:hidden;color:#27272a}
.rms-agent-monitor-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:29px 30px 23px;background:linear-gradient(125deg,#fff,#faf8f3);border-bottom:1px solid #eae7e1}
.rms-agent-monitor-eyebrow{display:block;margin-bottom:9px;font-size:9px;font-weight:900;letter-spacing:.19em;color:#a16207}
.rms-agent-monitor-heading h2{margin:0;font-size:24px;line-height:1.2;font-weight:850;letter-spacing:-.05em;color:#18181b;overflow-wrap:anywhere}
.rms-agent-monitor-heading p{margin:9px 0 0;max-width:480px;font-size:11px;line-height:1.7;color:#78716c}
.rms-agent-monitor-close{width:38px;height:38px;flex:0 0 38px;display:grid;place-items:center;border:1px solid #e7e5e4;border-radius:12px;background:#fff;color:#57534e;font-size:25px;cursor:pointer;transition:background .18s,border-color .18s,transform .18s}
.rms-agent-monitor-close:hover{background:#f5f5f4;border-color:#d6d3d1;transform:rotate(90deg)}
.rms-agent-monitor-scroll{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:22px 30px 30px;scrollbar-width:thin;scrollbar-color:#d6d3d1 transparent}
.rms-agent-monitor-status-row{display:flex;align-items:center;gap:11px;padding:14px 15px;border:1px solid #e7e5e4;border-radius:15px;background:#fff;box-shadow:0 3px 10px rgba(28,25,23,.025)}
.rms-agent-monitor-status-dot{width:9px;height:9px;flex:0 0 9px;border-radius:50%;background:#a1a1aa;box-shadow:0 0 0 4px #f4f4f5}
.rms-agent-monitor-status-dot.is-healthy{background:#16a34a;box-shadow:0 0 0 4px #dcfce7}.rms-agent-monitor-status-dot.is-warning{background:#d97706;box-shadow:0 0 0 4px #fef3c7}.rms-agent-monitor-status-dot.is-problem{background:#dc2626;box-shadow:0 0 0 4px #fee2e2}
.rms-agent-monitor-status-row>div{display:grid;gap:4px;min-width:0;flex:1}.rms-agent-monitor-status-row>div strong{font-size:12px;font-weight:850;color:#292524}.rms-agent-monitor-status-row>div small{font-size:9px;color:#a1a1aa}
.rms-agent-monitor-refresh{flex:0 0 auto;padding:8px 10px;border:1px solid #e7e5e4;border-radius:9px;background:#fafaf9;color:#57534e;font-size:10px;font-weight:800;cursor:pointer}.rms-agent-monitor-refresh:disabled{opacity:.6;cursor:wait}
.rms-agent-monitor-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px;margin-top:13px}
.rms-agent-monitor-metrics article{display:grid;gap:7px;padding:17px 18px;border:1px solid #eae7e1;border-radius:15px;background:linear-gradient(145deg,#fff,#fdfcfb);box-shadow:0 3px 10px rgba(28,25,23,.02);min-width:0}
.rms-agent-monitor-metrics article>span,.rms-agent-monitor-details>div span{font-size:9px;font-weight:800;letter-spacing:.04em;color:#a1a1aa}
.rms-agent-monitor-metrics article strong{font-size:25px;line-height:1;font-weight:850;letter-spacing:-.055em;color:#292524;overflow-wrap:anywhere}.rms-agent-monitor-metrics article small{font-size:9px;color:#78716c}
.rms-agent-monitor-section{margin-top:24px}.rms-agent-monitor-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:12px}
.rms-agent-monitor-section-head>div>span{font-size:8px;font-weight:900;letter-spacing:.16em;color:#a16207}.rms-agent-monitor-section-head h3{margin:5px 0 0;font-size:15px;font-weight:850;letter-spacing:-.035em;color:#292524}.rms-agent-monitor-section-head>small{font-size:9px;color:#a1a1aa}
.rms-agent-monitor-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1px;overflow:hidden;border:1px solid #eae7e1;border-radius:14px;background:#eae7e1}
.rms-agent-monitor-details>div{display:grid;gap:7px;min-width:0;padding:14px;background:#fff}.rms-agent-monitor-details>div strong{font-size:10px;font-weight:750;line-height:1.5;color:#57534e;overflow-wrap:anywhere}
.rms-agent-monitor-error{margin-top:10px;padding:13px 14px;border:1px solid #fecaca;border-radius:12px;background:#fff7f7}.rms-agent-monitor-error strong{font-size:10px;color:#b91c1c}.rms-agent-monitor-error p{margin:7px 0 0;font-size:10px;line-height:1.7;color:#7f1d1d;overflow-wrap:anywhere;white-space:pre-wrap}
.rms-agent-monitor-footnote{margin:10px 1px 0;font-size:9px;line-height:1.7;color:#a1a1aa}
.rms-agent-monitor-log{display:flex;gap:11px;padding:14px 0;border-bottom:1px solid #eeece8}.rms-agent-monitor-log:last-child{border-bottom:0}
.rms-agent-monitor-log-mark{width:27px;height:27px;flex:0 0 27px;display:grid;place-items:center;border:1px solid #e7e5e4;border-radius:9px;background:#fafaf9}.rms-agent-monitor-log-mark i{width:6px;height:6px;border-radius:50%;background:#a1a1aa}
.rms-agent-monitor-log-mark.is-success{background:#f0fdf4;border-color:#bbf7d0}.rms-agent-monitor-log-mark.is-success i{background:#16a34a}.rms-agent-monitor-log-mark.is-error{background:#fef2f2;border-color:#fecaca}.rms-agent-monitor-log-mark.is-error i{background:#dc2626}.rms-agent-monitor-log-mark.is-info{background:#eff6ff;border-color:#bfdbfe}.rms-agent-monitor-log-mark.is-info i{background:#2563eb}
.rms-agent-monitor-log-copy{flex:1;min-width:0}.rms-agent-monitor-log-copy strong{font-size:10px;font-weight:850;color:#292524;overflow-wrap:anywhere}.rms-agent-monitor-log-copy p{margin:4px 0 6px;font-size:10px;line-height:1.6;color:#78716c;overflow-wrap:anywhere}.rms-agent-monitor-log-copy small{font-size:8px;line-height:1.6;color:#a1a1aa}
.rms-agent-monitor-empty{display:grid;justify-items:center;gap:7px;padding:28px 15px;border:1px dashed #d6d3d1;border-radius:13px;background:#fff;text-align:center}.rms-agent-monitor-empty>span{font-size:21px;color:#a8a29e}.rms-agent-monitor-empty strong{font-size:11px;color:#57534e}.rms-agent-monitor-empty p{max-width:300px;margin:0;font-size:9px;line-height:1.7;color:#a1a1aa}
.rms-agent-monitor-footer{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 30px;border-top:1px solid #eae7e1;background:#fff}.rms-agent-monitor-footer>span{display:flex;align-items:center;gap:7px;font-size:9px;font-weight:750;color:#78716c}.rms-agent-monitor-footer>span i{width:6px;height:6px;border-radius:50%;background:#16a34a}.rms-agent-monitor-footer>button{min-width:92px;padding:10px 15px;border:1px solid #292524;border-radius:10px;background:#292524;color:#fff;font-size:10px;font-weight:850;cursor:pointer}
.rms-agent-monitor-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:118px;padding:0 13px!important;border:1px solid #d6d3d1!important;border-radius:11px!important;background:#fff!important;color:#44403c!important;font-size:10px!important;font-weight:850!important;cursor:pointer}.rms-agent-monitor-button svg{width:15px;height:15px;flex:0 0 15px}.rms-agent-monitor-button:hover{background:#fafaf9!important;border-color:#a8a29e!important;transform:translateY(-1px)}
@keyframes rmsAgentMonitorFade{from{opacity:0}to{opacity:1}}@keyframes rmsAgentMonitorSlide{from{transform:translateX(28px);opacity:.7}to{transform:translateX(0);opacity:1}}
@media(max-width:760px){.rms-agent-monitor-overlay{background:rgba(24,24,27,.45)}.rms-agent-monitor-drawer{width:100%;border-left:0;animation:rmsAgentMonitorMobile .22s ease-out}.rms-agent-monitor-header{padding:22px 18px 18px;gap:12px}.rms-agent-monitor-heading h2{font-size:21px}.rms-agent-monitor-heading p{font-size:10px}.rms-agent-monitor-scroll{padding:16px 16px 24px}.rms-agent-monitor-metrics{gap:8px}.rms-agent-monitor-metrics article{padding:14px}.rms-agent-monitor-metrics article strong{font-size:22px}.rms-agent-monitor-details{grid-template-columns:minmax(0,1fr)}.rms-agent-monitor-footer{padding:12px 16px}.rms-agent-monitor-button{grid-column:1/-1;width:100%;min-height:40px}}
@keyframes rmsAgentMonitorMobile{from{transform:translateY(18px);opacity:.7}to{transform:translateY(0);opacity:1}}
@media(prefers-reduced-motion:reduce){.rms-agent-monitor-overlay,.rms-agent-monitor-drawer{animation:none!important}.rms-agent-monitor-close,.rms-agent-monitor-button{transition:none!important}}
</style>


<style>
/* Real, cache-backed Codex login progress: the state follows PowerShell/import, not the Livewire request. */
.rms-agent-login-spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.32);border-top-color:#fbbf24;border-radius:50%;animation:rmsCodexSpin .7s linear infinite}
.rms-agent-login-progress{display:flex;align-items:flex-start;gap:10px;margin:0 18px 14px;padding:12px 14px;border:1px solid #e7e5e4;border-radius:12px;background:#fff}
.rms-agent-login-progress:empty{display:none}
.rms-agent-login-progress>div{display:grid;gap:4px;min-width:0}
.rms-agent-login-progress strong{font-size:10px;font-weight:850;color:#44403c}
.rms-agent-login-progress small{font-size:9px;line-height:1.6;color:#78716c;overflow-wrap:anywhere}
.rms-agent-login-progress-dot{width:8px;height:8px;flex:0 0 8px;margin-top:3px;border-radius:50%;background:#d97706;box-shadow:0 0 0 4px #fef3c7}
.rms-agent-login-progress-dot.is-running{animation:rmsCodexPulse 1.5s ease-in-out infinite}
.rms-agent-login-progress-dot.is-success{background:#16a34a;box-shadow:0 0 0 4px #dcfce7}
.rms-agent-login-progress-dot.is-failed{background:#dc2626;box-shadow:0 0 0 4px #fee2e2}
@keyframes rmsCodexSpin{to{transform:rotate(360deg)}}@keyframes rmsCodexPulse{50%{box-shadow:0 0 0 7px rgba(217,119,6,.08)}}
@media(max-width:760px){.rms-agent-login-progress{margin:0 9px 13px;padding:11px}}
@media(prefers-reduced-motion:reduce){.rms-agent-login-spinner,.rms-agent-login-progress-dot.is-running{animation:none!important}}
</style>
