window.rmsFilterDropdown = (config = {}) => ({
    open: false,

    value: config.value ?? 'all',

    options: config.options ?? [],

    get selected() {
        return this.options.find(
            option => option.value === this.value
        ) || this.options[0];
    },

    select(option) {
        this.value = option.value;
        this.open = false;

        if (typeof config.onSelect === 'function') {
            config.onSelect(option.value);
        }
    },

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },

    handleKeydown(event) {

        if (event.key === 'Escape') {
            this.close();
            return;
        }

        if (
            event.key === 'Enter' ||
            event.key === ' '
        ) {
            event.preventDefault();
            this.toggle();
        }
    },
});

window.activityLogStreamV44 = () => ({
    feedUrl: '',
    clearUrl: '',

    logs: [],

    search: '',
    category: 'all',
    status: 'all',
    timeframe: 'all',

    loading: false,
    clearing: false,

    syncError: false,
    errorMessage: '',

    timer: null,

    newIds: [],
    newCount: 0,

    atTop: true,
    initialized: false,

    init() {
        if (this.initialized) return;

        this.initialized = true;

        this.feedUrl = this.$root.dataset.feedUrl;
        this.clearUrl = this.$root.dataset.clearUrl;

        this.refresh();

        this.timer = window.setInterval(() => {
            this.refresh();
        }, 2500);

        window.addEventListener('activity-log-refresh', () => {
            this.refresh();
        });

        window.addEventListener('beforeunload', () => {
            if (this.timer) {
                window.clearInterval(this.timer);
            }
        }, { once: true });
    },

    async refresh() {
        if (this.loading) return;

        this.loading = true;

        try {
            const url = new URL(
                this.feedUrl,
                window.location.origin
            );

            url.searchParams.set('limit', '100');

            const response = await fetch(url.toString(), {
                method: 'GET',

                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },

                credentials: 'same-origin',

                cache: 'no-store',
            });

            const text = await response.text();

            let payload;

            try {
                payload = JSON.parse(text);
            } catch {
                throw new Error(
                    `Response feed bukan JSON. HTTP ${response.status}`
                );
            }

            if (!response.ok || payload.ok !== true) {
                throw new Error(
                    payload.message ||
                    `Activity feed gagal. HTTP ${response.status}`
                );
            }

            const incoming = Array.isArray(payload.logs)
                ? payload.logs.map(log => this.normalize(log))
                : [];

            const oldIds = new Set(
                this.logs.map(log => Number(log.id))
            );

            const newLogs = incoming.filter(
                log => !oldIds.has(Number(log.id))
            );

            this.logs = incoming;

            this.syncError = false;
            this.errorMessage = '';

            if (newLogs.length > 0) {
                this.newIds = newLogs.map(
                    log => Number(log.id)
                );

                if (!this.atTop) {
                    this.newCount += newLogs.length;
                } else {
                    this.newCount = 0;
                }

                window.setTimeout(() => {
                    this.newIds = [];
                }, 800);
            }

        } catch (error) {

            console.error(
                '[Activity Logs]',
                error
            );

            this.syncError = true;

            this.errorMessage =
                error?.message ||
                'Activity feed gagal dimuat.';

        } finally {
            this.loading = false;
        }
    },

    normalize(log) {
        return {
            id: Number(log?.id || 0),

            action: String(
                log?.action || ''
            ),

            category: String(
                log?.category || 'system'
            ).toLowerCase(),

            status: String(
                log?.status || 'info'
            ).toLowerCase(),

            title: String(
                log?.title ||
                log?.action ||
                'Activity'
            ),

            description: String(
                log?.description || ''
            ),

            metadata:
                log?.metadata &&
                typeof log.metadata === 'object'
                    ? log.metadata
                    : {},

            created_at:
                log?.created_at || null,
        };
    },

    get filteredLogs() {

        const query =
            this.search
                .trim()
                .toLowerCase();

        const now = Date.now();

        return this.logs.filter(log => {

            if (
                this.category !== 'all' &&
                log.category !== this.category
            ) {
                return false;
            }

            if (
                this.status !== 'all' &&
                log.status !== this.status
            ) {
                return false;
            }

            if (
                this.timeframe !== 'all' &&
                log.created_at
            ) {

                const timestamp =
                    new Date(
                        log.created_at
                    ).getTime();

                if (
                    this.timeframe === 'today' &&
                    !this.sameDay(timestamp, now)
                ) {
                    return false;
                }

                if (
                    this.timeframe === 'yesterday' &&
                    !this.sameDay(
                        timestamp,
                        now - 86400000
                    )
                ) {
                    return false;
                }

                if (
                    this.timeframe === '7days' &&
                    timestamp <
                        now - 7 * 86400000
                ) {
                    return false;
                }

                if (
                    this.timeframe === '30days' &&
                    timestamp <
                        now - 30 * 86400000
                ) {
                    return false;
                }
            }

            if (!query) {
                return true;
            }

            const haystack = [
                log.title,
                log.description,
                log.action,
                log.category,
                JSON.stringify(
                    log.metadata || {}
                ),
            ]
                .join(' ')
                .toLowerCase();

            return haystack.includes(query);
        });
    },

    sameDay(a, b) {

        const first = new Date(a);
        const second = new Date(b);

        return (
            first.getFullYear() ===
                second.getFullYear() &&

            first.getMonth() ===
                second.getMonth() &&

            first.getDate() ===
                second.getDate()
        );
    },

    statusClass(status) {

        return [
            'success',
            'error',
            'warning',
            'info',
            'processing',
        ].includes(status)
            ? status
            : 'info';
    },

    statusBadgeClass(status) {

        return (
            'rms-status-' +
            this.statusClass(status)
        );
    },

    metaItems(metadata) {

        if (
            !metadata ||
            typeof metadata !== 'object'
        ) {
            return [];
        }

        const keys = [
            'model',
            'http_status',
            'duration_ms',
            'endpoint',
        ];

        const labels = {
            model: 'MODEL',
            http_status: 'HTTP',
            duration_ms: '',
            endpoint: '',
        };

        return keys
            .filter(key =>
                metadata[key] !== undefined &&
                metadata[key] !== null &&
                metadata[key] !== ''
            )
            .map(key => {

                let value = metadata[key];

                if (key === 'duration_ms') {
                    value += ' ms';
                }

                return {
                    key,
                    label: labels[key],
                    value,
                };
            });
    },

    formatTime(value) {

        if (!value) {
            return '--:--:--';
        }

        return new Intl.DateTimeFormat(
            'id-ID',
            {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            }
        ).format(new Date(value));
    },

    formatDate(value) {

        if (!value) {
            return '';
        }

        return new Intl.DateTimeFormat(
            'id-ID',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }
        ).format(new Date(value));
    },

    onScroll() {

        const viewport =
            this.$refs.viewport;

        if (!viewport) {
            return;
        }

        this.atTop =
            viewport.scrollTop < 28;

        if (this.atTop) {
            this.newCount = 0;
        }
    },

    jumpToLatest() {

        const viewport =
            this.$refs.viewport;

        if (!viewport) {
            return;
        }

        viewport.scrollTo({
            top: 0,
            behavior: 'smooth',
        });

        this.newCount = 0;
        this.atTop = true;
    },

    async clearLogs() {

        if (
            this.clearing ||
            this.logs.length === 0
        ) {
            return;
        }

        if (
            !window.confirm(
                'Hapus seluruh Activity Logs?'
            )
        ) {
            return;
        }

        this.clearing = true;

        try {

            const response = await fetch(
                this.clearUrl,
                {
                    method: 'DELETE',

                    headers: {
                        Accept:
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest',

                        'X-CSRF-TOKEN':
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.content || '',
                    },

                    credentials:
                        'same-origin',
                }
            );

            const payload =
                await response.json();

            if (
                !response.ok ||
                payload.ok !== true
            ) {
                throw new Error(
                    payload.message ||
                    'Gagal membersihkan Activity Logs.'
                );
            }

            this.logs = [];
            this.newIds = [];
            this.newCount = 0;

            this.syncError = false;
            this.errorMessage = '';

        } catch (error) {

            console.error(
                '[Activity Logs Clear]',
                error
            );

            this.syncError = true;

            this.errorMessage =
                error?.message ||
                'Gagal membersihkan Activity Logs.';

        } finally {

            this.clearing = false;
        }
    },
});