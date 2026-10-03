<div
    class="rms-settings-page"
    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'settings-openai-root'; ?>wire:key="settings-openai-root"
    x-data="{
        logs: [],

        addLog(type, message) {
            const now = new Date();

            this.logs.unshift({
                type: type,
                message: message,
                time: now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                })
            });
        },

        testConnection() {
            this.addLog('INFO', 'Memulai test koneksi OpenAI...');

            setTimeout(() => {
                this.addLog(
                    'INFO',
                    'Request koneksi sedang menunggu backend OpenAI.'
                );
            }, 500);

            setTimeout(() => {
                this.addLog(
                    'WARNING',
                    'Backend OpenAI belum dikonfigurasi.'
                );
            }, 1100);
        },

        clearLogs() {
            this.logs = [];
        }
    }"
>

    
    <section class="rms-settings-hero rms-settings-reveal">

        <div class="rms-settings-hero-grid"></div>
        <div class="rms-settings-hero-glow"></div>

        <span class="rms-settings-eyebrow">
            <i></i>
            WORKSPACE / SETTINGS
        </span>

        <div class="rms-settings-hero-row">

            <div>
                <h1>
                    OpenAI API
                    <span>Configuration.</span>
                </h1>

                <p>
                    Kelola koneksi creative engine yang digunakan oleh
                    Product Generator.
                </p>
            </div>

            <span class="rms-settings-status-pill">
                <i></i>
                INTERNAL CONFIGURATION
            </span>

        </div>

        <div class="rms-settings-meta">

            <span>
                <i></i>
                Creative engine workspace active
            </span>

            <b></b>

            <span>
                OpenAI API
            </span>

            <span>
                Ready to configure
            </span>

        </div>

    </section>


    
    <section
        class="rms-settings-openai-card rms-settings-reveal"
        style="--reveal-delay:.06s"
    >

        
        <div class="rms-settings-openai-head">

            <div class="rms-settings-engine-icon">
                <span>AI</span>
            </div>

            <div class="rms-settings-engine-copy">
                <span>CREATIVE ENGINE</span>

                <h2>
                    OpenAI API
                </h2>

                <p>
                    Connection &amp; generation configuration
                </p>
            </div>

            <span class="rms-settings-ready">
                <i></i>
                Ready to configure
            </span>

        </div>


        
        <div class="rms-settings-openai-form">

            
            <label class="rms-settings-field">

                <span>
                    API Key
                </span>

                <div
                    class="rms-settings-input-wrap"
                    x-data="{ visible: false }"
                >

                    <input
                        type="password"
                        :type="visible ? 'text' : 'password'"
                        value=""
                        placeholder="Masukkan OpenAI API key"
                        autocomplete="off"
                    >

                    <button
                        type="button"
                        @click="visible = !visible"
                        x-text="visible ? 'Hide' : 'Show'"
                    ></button>

                </div>

                <small>
                    API key hanya digunakan oleh server untuk proses
                    generate gambar.
                </small>

            </label>


            
            <label class="rms-settings-field">

                <span>
                    Image Model
                </span>

                <select>
                    <option>
                        OpenAI Image Generation
                    </option>

                    <option>
                        gpt-image-1
                    </option>
                </select>

                <small>
                    Model yang digunakan Product Generator.
                </small>

            </label>

        </div>


        
        <div class="rms-settings-connection">

            <div class="rms-settings-connection-copy">

                <div class="rms-settings-check">
                    ✓
                </div>

                <div>

                    <strong>
                        API configuration
                    </strong>

                    <p>
                        UI sudah disiapkan untuk koneksi OpenAI.
                        Penyimpanan credential dapat disambungkan
                        pada tahap backend.
                    </p>

                </div>

            </div>

            <button
                type="button"
                class="rms-settings-test-button"
                @click="testConnection()"
            >
                <span>
                    Test Connection
                </span>

                <b>
                    →
                </b>
            </button>

        </div>

    </section>


    
    <section
        class="rms-settings-logs rms-settings-reveal"
        style="--reveal-delay:.1s"
    >

        
        <div class="rms-settings-logs-head">

            <div class="rms-settings-logs-title">

                <span class="rms-settings-terminal-icon">
                    &gt;_
                </span>

                <div>
                    <strong>
                        Connection Logs
                    </strong>

                    <small>
                        Riwayat aktivitas test koneksi OpenAI.
                    </small>
                </div>

            </div>


            <div class="rms-settings-log-actions">

                
                <span
                    class="rms-settings-log-status"
                    :class="logs.length ? 'has-log' : ''"
                >
                    <i></i>

                    <span
                        x-text="logs.length
                            ? logs.length + ' EVENT' + (logs.length > 1 ? 'S' : '')
                            : 'WAITING FOR TEST'"
                    ></span>
                </span>


                
                <button
                    type="button"
                    @click="clearLogs()"
                    :disabled="logs.length === 0"
                    :class="{ 'is-disabled': logs.length === 0 }"
                >
                    Clear
                </button>

            </div>

        </div>


        
        <div class="rms-settings-log-body">

            
            <template x-if="logs.length === 0">

                <div class="rms-settings-log-empty">

                    <div class="rms-settings-log-icon">
                        &gt;_
                    </div>

                    <strong>
                        No connection activity yet.
                    </strong>

                    <p>
                        Jalankan
                        <b>Test Connection</b>
                        untuk melihat aktivitas koneksi
                        OpenAI di panel ini.
                    </p>

                </div>

            </template>


            
            <template x-if="logs.length > 0">

                <div class="rms-settings-log-list">

                    <template
                        x-for="(log, index) in logs"
                        :key="index"
                    >

                        <div
                            class="rms-settings-log-row"
                            :class="'log-' + log.type.toLowerCase()"
                        >

                            <div class="rms-settings-log-marker">
                                <i></i>
                            </div>


                            <div class="rms-settings-log-content">

                                <div class="rms-settings-log-main">

                                    <span
                                        class="rms-settings-log-type"
                                        x-text="log.type"
                                    ></span>

                                    <span
                                        class="rms-settings-log-message"
                                        x-text="log.message"
                                    ></span>

                                </div>

                            </div>


                            <time
                                x-text="log.time"
                            ></time>

                        </div>

                    </template>

                </div>

            </template>

        </div>


        
        <div class="rms-settings-logs-footer">

            <span>
                <i></i>
                Connection monitor
            </span>

            <span>
                OpenAI API
            </span>

        </div>

    </section>


    
    <section
        class="rms-settings-openai-footer rms-settings-reveal"
        style="--reveal-delay:.14s"
    >

        <div>
            <span>
                OpenAI connection
            </span>

            <small>
                Configuration will be used by Product Generator.
            </small>
        </div>

        <button
            type="button"
            class="rms-settings-save-button"
        >
            <span>
                Save Configuration
            </span>

            <b>
                ✓
            </b>
        </button>

    </section>

</div>



<style>
    /* ---------------------------------------------------------
       BASE TYPOGRAPHY
       --------------------------------------------------------- */

    .rms-settings-page {
        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;
    }


    /* ---------------------------------------------------------
       HERO
       --------------------------------------------------------- */

    .rms-settings-hero h1 {
        font-size: clamp(26px, 2.1vw, 34px);
        line-height: 1.08;
        letter-spacing: -0.045em;
        font-weight: 750;
    }

    .rms-settings-hero h1 span {
        font-weight: 450;
    }

    .rms-settings-hero p {
        margin-top: 9px;
        font-size: 13px;
        line-height: 1.55;
        color: #71717a;
    }

    .rms-settings-eyebrow {
        font-size: 10px;
        line-height: 1;
        letter-spacing: .15em;
        font-weight: 750;
    }

    .rms-settings-status-pill {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
    }

    .rms-settings-meta {
        font-size: 10px;
        font-weight: 550;
    }


    /* ---------------------------------------------------------
       OPENAI CARD HEADER
       --------------------------------------------------------- */

    .rms-settings-openai-head {
        min-height: 104px;
    }

    .rms-settings-engine-icon {
        width: 50px;
        height: 50px;
        flex: 0 0 50px;
    }

    .rms-settings-engine-icon span {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
    }

    .rms-settings-engine-copy > span {
        display: block;
        margin-bottom: 5px;
        font-size: 9px;
        line-height: 1;
        letter-spacing: .15em;
        font-weight: 800;
        color: #ef3030;
    }

    .rms-settings-engine-copy h2 {
        font-size: 21px;
        line-height: 1.15;
        letter-spacing: -0.025em;
        font-weight: 750;
    }

    .rms-settings-engine-copy p {
        margin-top: 4px;
        font-size: 11px;
        line-height: 1.4;
        color: #8b8b93;
    }

    .rms-settings-ready {
        font-size: 10px;
        font-weight: 650;
    }


    /* ---------------------------------------------------------
       FORM
       --------------------------------------------------------- */

    .rms-settings-field > span {
        display: block;
        margin-bottom: 8px;
        font-size: 11px;
        line-height: 1.2;
        font-weight: 700;
        color: #27272a;
    }

    .rms-settings-field small {
        display: block;
        margin-top: 7px;
        font-size: 9px;
        line-height: 1.45;
        color: #a1a1aa;
    }

    .rms-settings-input-wrap input,
    .rms-settings-field select {
        font-size: 11px;
        line-height: 1;
    }

    .rms-settings-input-wrap input::placeholder {
        font-size: 11px;
        color: #a1a1aa;
    }

    .rms-settings-input-wrap button {
        font-size: 10px;
        font-weight: 700;
    }


    /* ---------------------------------------------------------
       CONNECTION BOX
       --------------------------------------------------------- */

    .rms-settings-connection-copy strong {
        font-size: 11px;
        line-height: 1.2;
        font-weight: 750;
    }

    .rms-settings-connection-copy p {
        margin-top: 4px;
        font-size: 9px;
        line-height: 1.45;
        color: #9999a1;
    }

    .rms-settings-check {
        font-size: 13px;
        font-weight: 800;
    }

    .rms-settings-test-button {
        font-size: 10px;
        font-weight: 750;
        letter-spacing: .01em;
    }


    /* ---------------------------------------------------------
       LOG HEADER
       --------------------------------------------------------- */

    .rms-settings-logs {
        overflow: hidden;
    }

    .rms-settings-logs-head {
        min-height: 72px;
    }

    .rms-settings-logs-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rms-settings-terminal-icon {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border: 1px solid #e4e4e7;
        border-radius: 10px;
        background: #fafafa;
        color: #18181b;
        font-family: "SFMono-Regular", Consolas, monospace;
        font-size: 12px;
        font-weight: 700;
    }

    .rms-settings-logs-title strong {
        display: block;
        font-size: 13px;
        line-height: 1.2;
        font-weight: 750;
        color: #18181b;
    }

    .rms-settings-logs-title small {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        line-height: 1.4;
        color: #a1a1aa;
    }


    /* ---------------------------------------------------------
       LOG STATUS
       --------------------------------------------------------- */

    .rms-settings-log-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rms-settings-log-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 9px;
        line-height: 1;
        letter-spacing: .08em;
        font-weight: 750;
        color: #a1a1aa;
    }

    .rms-settings-log-status i {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #d4d4d8;
    }

    .rms-settings-log-status.has-log {
        color: #52525b;
    }

    .rms-settings-log-status.has-log i {
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, .10);
    }

    .rms-settings-log-actions > button {
        height: 30px;
        padding: 0 12px;
        border: 1px solid #e4e4e7;
        border-radius: 8px;
        background: #fff;
        color: #52525b;
        font-size: 10px;
        font-weight: 650;
        transition:
            opacity .18s ease,
            background .18s ease,
            color .18s ease;
    }

    .rms-settings-log-actions > button:hover:not(.is-disabled) {
        background: #18181b;
        border-color: #18181b;
        color: #fff;
    }

    .rms-settings-log-actions > button.is-disabled {
        cursor: not-allowed;
        opacity: .4;
    }


    /* ---------------------------------------------------------
       EMPTY LOG
       --------------------------------------------------------- */

    .rms-settings-log-body {
        min-height: 190px;
    }

    .rms-settings-log-empty {
        min-height: 190px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 34px 20px;
        text-align: center;
    }

    .rms-settings-log-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        margin-bottom: 12px;
        border-radius: 12px;
        background: #18181b;
        color: #fff;
        font-family: "SFMono-Regular", Consolas, monospace;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 10px 24px rgba(0, 0, 0, .12);
    }

    .rms-settings-log-empty strong {
        font-size: 12px;
        line-height: 1.3;
        font-weight: 700;
        color: #3f3f46;
    }

    .rms-settings-log-empty p {
        max-width: 450px;
        margin-top: 6px;
        font-size: 10px;
        line-height: 1.55;
        color: #a1a1aa;
    }

    .rms-settings-log-empty p b {
        color: #71717a;
        font-weight: 700;
    }


    /* ---------------------------------------------------------
       LOG LIST
       --------------------------------------------------------- */

    .rms-settings-log-list {
        padding: 6px 0;
    }

    .rms-settings-log-row {
        display: grid;
        grid-template-columns: 18px minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        min-height: 52px;
        padding: 9px 18px;
        border-bottom: 1px solid #f4f4f5;
        animation: rmsLogIn .22s ease both;
    }

    .rms-settings-log-row:last-child {
        border-bottom: 0;
    }

    .rms-settings-log-marker {
        display: flex;
        justify-content: center;
    }

    .rms-settings-log-marker i {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #a1a1aa;
    }

    .rms-settings-log-row.log-info .rms-settings-log-marker i {
        background: #3b82f6;
    }

    .rms-settings-log-row.log-success .rms-settings-log-marker i {
        background: #22c55e;
    }

    .rms-settings-log-row.log-warning .rms-settings-log-marker i {
        background: #f59e0b;
    }

    .rms-settings-log-row.log-error .rms-settings-log-marker i {
        background: #ef4444;
    }

    .rms-settings-log-main {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .rms-settings-log-type {
        flex: 0 0 auto;
        min-width: 48px;
        font-size: 8px;
        line-height: 18px;
        text-align: center;
        border-radius: 5px;
        background: #f4f4f5;
        color: #71717a;
        font-weight: 800;
        letter-spacing: .07em;
    }

    .rms-settings-log-row.log-info .rms-settings-log-type {
        background: #eff6ff;
        color: #2563eb;
    }

    .rms-settings-log-row.log-success .rms-settings-log-type {
        background: #f0fdf4;
        color: #16a34a;
    }

    .rms-settings-log-row.log-warning .rms-settings-log-type {
        background: #fffbeb;
        color: #d97706;
    }

    .rms-settings-log-row.log-error .rms-settings-log-type {
        background: #fef2f2;
        color: #dc2626;
    }

    .rms-settings-log-message {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 10px;
        line-height: 1.4;
        color: #52525b;
    }

    .rms-settings-log-row time {
        font-family: "SFMono-Regular", Consolas, monospace;
        font-size: 9px;
        color: #a1a1aa;
        white-space: nowrap;
    }

    @keyframes rmsLogIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }


    /* ---------------------------------------------------------
       LOG FOOTER
       --------------------------------------------------------- */

    .rms-settings-logs-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 42px;
        padding: 0 18px;
        border-top: 1px solid #f4f4f5;
        background: #fafafa;
        font-size: 9px;
        color: #a1a1aa;
    }

    .rms-settings-logs-footer span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .rms-settings-logs-footer span:first-child i {
        width: 5px;
        height: 5px;
        border-radius: 999px;
        background: #22c55e;
    }


    /* ---------------------------------------------------------
       SAVE FOOTER
       --------------------------------------------------------- */

    .rms-settings-openai-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 2px 4px;
    }

    .rms-settings-openai-footer > div > span {
        display: block;
        font-size: 10px;
        font-weight: 650;
        color: #71717a;
    }

    .rms-settings-openai-footer > div > small {
        display: block;
        margin-top: 3px;
        font-size: 9px;
        color: #a1a1aa;
    }

    .rms-settings-save-button {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 38px;
        padding: 0 15px;
        border-radius: 10px;
        background: #ef3030;
        color: #fff;
        font-size: 10px;
        font-weight: 750;
        box-shadow: 0 8px 20px rgba(239, 48, 48, .18);
        transition:
            transform .18s ease,
            box-shadow .18s ease;
    }

    .rms-settings-save-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 11px 24px rgba(239, 48, 48, .23);
    }

    .rms-settings-save-button b {
        font-size: 12px;
        font-weight: 800;
    }


    /* ---------------------------------------------------------
       RESPONSIVE
       --------------------------------------------------------- */

    @media (max-width: 760px) {

        .rms-settings-hero-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .rms-settings-openai-head {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .rms-settings-ready {
            margin-left: 62px;
        }

        .rms-settings-connection {
            flex-direction: column;
            align-items: stretch;
        }

        .rms-settings-test-button {
            width: 100%;
            justify-content: center;
        }

        .rms-settings-logs-head {
            align-items: flex-start;
            gap: 14px;
        }

        .rms-settings-log-main {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .rms-settings-log-row {
            grid-template-columns: 18px minmax(0, 1fr);
        }

        .rms-settings-log-row time {
            grid-column: 2;
        }

        .rms-settings-openai-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .rms-settings-save-button {
            width: 100%;
            justify-content: center;
        }
    }
</style><?php /**PATH F:\Website\rizky-tools-ai\resources\views\livewire\dashboard\settings\openai.blade.php ENDPATH**/ ?>