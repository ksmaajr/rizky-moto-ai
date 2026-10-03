<div class="rms-settings-page" x-data="{ showApiKey: <?php if ((object) ('showApiKey') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showApiKey'->value()); ?>')<?php echo e('showApiKey'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showApiKey'); ?>')<?php endif; ?> }">
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'openai'): ?>
                <div
                    class="rms-settings-panel rms-settings-panel-enter rms-openai-api-panel"
                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'settings-openai-panel'; ?>wire:key="settings-openai-panel"
                >
                    <div class="rms-settings-panel-head rms-openai-head-upgraded">
                        <div class="rms-settings-panel-brand">
                            <span class="rms-settings-panel-mark rms-openai-mark">AI</span>
                            <div>
                                <span>CREATIVE ENGINE / CONNECTION</span>
                                <strong>OpenAI API</strong>
                                <small>API credential, connection test, and live system activity.</small>
                            </div>
                        </div>
                        <div class="rms-api-connection-badge rms-api-connection-badge-dynamic">
                            <i class="<?php echo e($this->openAiStatus['state'] === 'connected' ? 'is-connected' : ($this->openAiStatus['state'] === 'error' ? 'is-error' : '')); ?>"></i>
                            <span><?php echo e($this->openAiStatus['label']); ?></span>
                            <b><?php echo e($this->openAiStatus['badge']); ?></b>
                        </div>
                    </div>

                    <div class="rms-settings-divider"></div>

                    <div class="rms-settings-form rms-openai-form-upgraded rms-openai-api-only-form">
                        <label class="rms-settings-field rms-settings-field-wide">
                            <span>API Key</span>
                            <div class="rms-api-input-wrap rms-api-input-upgraded">
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
                            <small>
                                API key hanya digunakan oleh server. Credential disimpan terenkripsi melalui backend dan tidak pernah ditampilkan di activity log.
                            </small>
                        </label>
                    </div>

                    <div class="rms-settings-notice rms-openai-notice-upgraded rms-openai-secure-card">
                        <div class="rms-openai-notice-copy">
                            <span class="rms-settings-notice-icon">✓</span>
                            <div>
                                <strong>Secure API configuration</strong>
                                <small>
                                    Settings ini hanya mengatur koneksi OpenAI. Model, aspect ratio, quality, prompt, dan parameter generation akan dikonfigurasi di Product Generator.
                                </small>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rms-settings-dark-button rms-test-connection-upgraded"
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            wire:target="testConnection"
                        >
                            <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                            <span wire:loading wire:target="testConnection">Testing...</span>
                            <b wire:loading.remove wire:target="testConnection">→</b>
                            <b wire:loading wire:target="testConnection" class="rms-spinner"></b>
                        </button>
                    </div>

                    <?php echo $__env->make('livewire.dashboard.activity-log-stream', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <div class="rms-settings-panel-footer rms-openai-footer-upgraded">
                        <span>OpenAI API connection</span>
                        <button
                            type="button"
                            class="rms-settings-save"
                            wire:click="saveOpenAi"
                            wire:loading.attr="disabled"
                            wire:target="saveOpenAi"
                        >
                            <span wire:loading.remove wire:target="saveOpenAi">Save Configuration</span>
                            <span wire:loading wire:target="saveOpenAi">Saving...</span>
                            <span wire:loading.remove wire:target="saveOpenAi">✓</span>
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <div class="rms-settings-panel rms-settings-panel-enter" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'settings-general-panel'; ?>wire:key="settings-general-panel">
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
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

</style>

</div>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views/livewire/dashboard/settings/index.blade.php ENDPATH**/ ?>