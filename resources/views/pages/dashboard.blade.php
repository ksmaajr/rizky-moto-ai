<?php



use Livewire\Attributes\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

use App\Models\Store;
use App\Services\QueueWorkerManager;



new

#[Layout('layouts::app')]

class extends Component

{

    use \App\Livewire\Concerns\ManagesVercelGatewayKeys;



    public function getUserProperty(): ?object

    {

        return auth()->user();

    }



    public string $activeSection = 'dashboard';



    // Settings workspace state

    public string $activeTab = 'general';

    public bool $showApiKey = false;

    public string $apiKey = '';

    public string $imageModel = 'OpenAI Image Generation';

    public string $defaultAspectRatio = '1:1';

    public string $defaultQuality = 'standard';

    public bool $hasOpenAiKey = false;



    
    public array $workerStatus = [
        'running' => false,
        'healthy' => false,
        'running_count' => 0,
        'worker_count' => 0,
        'target_workers' => 3,
        'workers' => [],
        'pid' => null,
        'queue' => 'database',
        'started_at' => null,
    ];

// Legacy OpenAI connection logs kept for the existing settings activity UI.

    public array $openAiLogs = [];



    public string $storeSearch = '';

    public string $storeStatus = 'all';



    // Global activity log workspace.

    public array $activityLogs = [];

    public string $activitySearch = '';

    public string $activityCategory = 'all';

    public string $activityStatus = 'all';

    public string $activityTimeframe = 'all';

    // Dashboard realtime workspace state.
    public string $dashboardTimeframe = '7d';
    public string $dashboardStatus = 'all';
    public string $dashboardStore = 'all';
    public string $globalSearch = '';
    public array $dashboardData = [];






    public function mount(): void

    {

        $settings = \App\Models\OpenAiSetting::query()->first();



        if ($settings) {

            $this->imageModel = $settings->model ?: 'OpenAI Image Generation';

            $this->defaultAspectRatio = $settings->default_aspect_ratio ?: '1:1';

            $this->defaultQuality = $settings->default_quality ?: 'standard';

            $this->hasOpenAiKey = filled($settings->api_key);

        }



        $this->loadDashboardData();

        // Worker state is server-side. Resolve it on every dashboard mount so a
        // new tab/device reflects the real process state instead of Livewire's
        // initial default values.
        $this->refreshWorkerStatus();

    }



    private function loadOpenAiLogs(): void

    {

        $this->openAiLogs = \App\Models\OpenAiConnectionLog::query()

            ->latest('tested_at')

            ->limit(50)

            ->get()

            ->map(fn ($log) => [

                'id' => $log->id,

                'status' => $log->status,

                'message' => $log->message,

                'detail' => $log->detail,

                'http_status' => $log->http_status,

                'duration_ms' => $log->duration_ms,

                'model' => $log->model,

                'tested_at' => $log->tested_at?->format('d M Y, H:i:s'),

            ])

            ->toArray();

    }



    private function loadActivityLogs(): void

    {

        $query = \App\Models\ActivityLog::query()
            ->with('user:id,name')
            ->latest('created_at');



        $search = trim($this->activitySearch);



        if ($search !== '') {

            $keyword = '%' . $search . '%';



            $query->where(function ($q) use ($keyword) {

                $q->where('title', 'like', $keyword)

                    ->orWhere('description', 'like', $keyword)

                    ->orWhere('action', 'like', $keyword)

                    ->orWhere('category', 'like', $keyword)

                    ->orWhere('metadata', 'like', $keyword);

            });

        }



        if ($this->activityCategory !== 'all') {

            $query->where('category', $this->activityCategory);

        }



        if ($this->activityStatus !== 'all') {

            $query->where('status', $this->activityStatus);

        }



        if ($this->activityTimeframe !== 'all') {

            $from = match ($this->activityTimeframe) {

                'today' => now()->startOfDay(),

                '7d' => now()->subDays(7)->startOfDay(),

                '30d' => now()->subDays(30)->startOfDay(),

                default => null,

            };



            if ($from) {

                $query->where('created_at', '>=', $from);

            }

        }



        $this->activityLogs = $query

            ->limit(100)

            ->get()

            ->map(fn ($log) => [

                'id' => $log->id,

                'category' => $log->category,

                'action' => $log->action,

                'status' => $log->status,

                'title' => $log->title,

                'description' => $log->description,

                'metadata' => $log->metadata ?? [],

                'duration_ms' => $log->duration_ms,

                'http_status' => $log->http_status,

                'entity_type' => $log->entity_type,

                'entity_id' => $log->entity_id,
                'user_id' => $log->user_id,
                'user_name' => $log->user?->name ?? 'System',

                'created_at' => $log->created_at?->format('d M Y, H:i:s'),

                'created_at_human' => $log->created_at?->diffForHumans(),
                'copy_text' => $this->formatActivityLogForCopy($log),

            ])

            ->toArray();

    }



    private function formatActivityLogForCopy(\App\Models\ActivityLog $log): string
    {
        $metadata = is_array($log->metadata) ? $log->metadata : [];

        $redact = function ($value) use (&$redact) {
            if (is_array($value)) {
                $clean = [];

                foreach ($value as $key => $item) {
                    $keyString = strtolower((string) $key);

                    if (preg_match('/api[_-]?key|access[_-]?token|refresh[_-]?token|authorization|password|secret|credential/i', $keyString)) {
                        $clean[$key] = '[REDACTED]';
                        continue;
                    }

                    $clean[$key] = $redact($item);
                }

                return $clean;
            }

            return is_scalar($value) || $value === null ? $value : (string) $value;
        };

        $safeMetadata = $redact($metadata);

        $lines = [
            '=== GLOBAL ACTIVITY LOG ===',
            'ID: ' . $log->id,
            'Time: ' . ($log->created_at?->format('Y-m-d H:i:s') ?? '-'),
            'Category: ' . ($log->category ?: '-'),
            'Status: ' . ($log->status ?: '-'),
            'Actor: ' . ($log->user?->name ?? 'System'),
            'Title: ' . ($log->title ?: '-'),
            'Description: ' . ($log->description ?: '-'),
            'Action: ' . ($log->action ?: '-'),
        ];

        if ($log->http_status !== null) {
            $lines[] = 'HTTP: ' . $log->http_status;
        }

        if ($log->duration_ms !== null) {
            $lines[] = 'Duration: ' . $log->duration_ms . ' ms';
        }

        if ($log->entity_type !== null || $log->entity_id !== null) {
            $lines[] = 'Entity: ' . ($log->entity_type ?: '-') . ' #' . ($log->entity_id ?: '-');
        }

        if ($safeMetadata !== []) {
            $lines[] = 'Metadata: ' . json_encode($safeMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return implode(PHP_EOL, $lines);
    }



    public function refreshActivityLogs(): void

    {

        $this->loadActivityLogs();

    }



    public function getActivityLogCountProperty(): int

    {

        return \App\Models\ActivityLog::query()->count();

    }



    public function getFilteredActivityLogCountProperty(): int

    {

        return count($this->activityLogs);

    }



    public function getOpenAiStatusProperty(): array

    {

        $settings = \App\Models\OpenAiSetting::query()->first();



        $latest = \App\Models\ActivityLog::query()

            ->where('category', 'api')

            ->where('action', 'test_openai_connection')

            ->latest('created_at')

            ->first();



        if (! $settings || ! filled($settings->api_key)) {

            return [

                'state' => 'not_configured',

                'label' => 'Not configured',

                'subtitle' => 'API key belum disimpan',

                'badge' => 'STEP 0',

            ];

        }



        if ($latest?->status === 'success') {

            return [

                'state' => 'connected',

                'label' => 'Connected',

                'subtitle' => 'OpenAI API authenticated',

                'badge' => 'LIVE',

            ];

        }



        if ($latest?->status === 'error') {

            return [

                'state' => 'error',

                'label' => 'Connection error',

                'subtitle' => 'Periksa Connection Logs',

                'badge' => 'CHECK',

            ];

        }



        return [

            'state' => 'configured',

            'label' => 'Configured',

            'subtitle' => 'Run connection test',

            'badge' => 'STEP 1',

        ];

    }



    public function getFavoriteCountProperty(): int

    {

        return \App\Models\GeneratedImage::query()

            ->where('is_favorite', true)

            ->count();

    }



    public function getStoresProperty()

    {

        return Store::query()

            ->withCount(['templates', 'generations'])

            ->when($this->storeSearch !== '', function ($query) {

                $keyword = '%' . trim($this->storeSearch) . '%';

                $query->where(function ($q) use ($keyword) {

                    $q->where('name', 'like', $keyword)

                        ->orWhere('brand_name', 'like', $keyword)

                        ->orWhere('marketplace', 'like', $keyword);

                });

            })

            ->when($this->storeStatus === 'active', fn ($query) => $query->where('is_active', true))

            ->when($this->storeStatus === 'inactive', fn ($query) => $query->where('is_active', false))

            ->orderBy('sort_order')

            ->orderBy('name')

            ->get();

    }



    public function getTotalStoresProperty(): int

    {

        return Store::count();

    }



    public function getActiveStoresProperty(): int

    {

        return Store::where('is_active', true)->count();

    }



    public function getTotalTemplatesProperty(): int

    {

        return (int) Store::withCount('templates')->get()->sum('templates_count');

    }



    public function getTotalGenerationsProperty(): int

    {

        return (int) Store::withCount('generations')->get()->sum('generations_count');

    }



    

    public function refreshDashboard(): void
    {
        if ($this->activeSection !== 'dashboard') {
            return;
        }

        $this->loadDashboardData();
    }

    public function updatedDashboardTimeframe(): void
    {
        $this->loadDashboardData();
    }

    public function updatedDashboardStatus(): void
    {
        $this->loadDashboardData();
    }

    public function updatedDashboardStore(): void
    {
        $this->loadDashboardData();
    }

    private function loadDashboardData(): void
    {
        $now = now();
        $from = match ($this->dashboardTimeframe) {
            'today' => $now->copy()->startOfDay(),
            '30d' => $now->copy()->subDays(29)->startOfDay(),
            '90d' => $now->copy()->subDays(89)->startOfDay(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->subDays(6)->startOfDay(),
        };

        $generationBase = AppModelsGeneration::query()
            ->where('created_at', '>=', $from)
            ->when($this->dashboardStatus !== 'all', fn ($q) => $q->where('status', $this->dashboardStatus))
            ->when($this->dashboardStore !== 'all', fn ($q) => $q->where('store_id', (int) $this->dashboardStore));

        $total = (clone $generationBase)->count();
        $completed = (clone $generationBase)->whereIn('status', ['completed', 'success', 'succeeded'])->count();
        $processing = (clone $generationBase)->where('status', 'processing')->count();
        $queued = (clone $generationBase)->where('status', 'queued')->count();
        $failed = (clone $generationBase)->where('status', 'failed')->count();

        $imageCount = (int) AppModelsGeneratedImage::query()
            ->whereHas('generation', function ($q) use ($from) {
                $q->where('created_at', '>=', $from)
                    ->when($this->dashboardStatus !== 'all', fn ($nested) => $nested->where('status', $this->dashboardStatus))
                    ->when($this->dashboardStore !== 'all', fn ($nested) => $nested->where('store_id', (int) $this->dashboardStore));
            })
            ->count();

        $durations = (clone $generationBase)
            ->whereIn('status', ['completed', 'success', 'succeeded'])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get(['started_at', 'completed_at']);

        $avgDuration = $durations->isEmpty()
            ? 0
            : (int) round($durations->avg(fn ($item) => $item->started_at->diffInSeconds($item->completed_at)));

        $trendStart = $this->dashboardTimeframe === 'today'
            ? $from
            : ($this->dashboardTimeframe === 'year' ? $now->copy()->startOfMonth() : $from);

        $trendDays = $this->dashboardTimeframe === 'year' ? 12 : ($this->dashboardTimeframe === '90d' ? 12 : ($this->dashboardTimeframe === '30d' ? 10 : ($this->dashboardTimeframe === 'today' ? 12 : 7)));
        $bucketSeconds = $this->dashboardTimeframe === 'today' ? 2 * 3600 : ($this->dashboardTimeframe === 'year' ? 30 * 86400 : max(86400, (int) ceil($now->diffInSeconds($from) / max(1, $trendDays - 1))));
        $trend = [];

        for ($i = 0; $i < $trendDays; $i++) {
            if ($this->dashboardTimeframe === 'today') {
                $start = $from->copy()->addHours($i * 2);
                $end = $start->copy()->addHours(2);
                $label = $start->format('H:i');
            } elseif ($this->dashboardTimeframe === 'year') {
                $start = $now->copy()->startOfYear()->addMonths($i);
                $end = $start->copy()->addMonth();
                $label = $start->format('M');
            } else {
                $start = $from->copy()->addDays($i * max(1, (int) floor($now->diffInDays($from) / max(1, $trendDays - 1))));
                $end = $start->copy()->addDays(max(1, (int) ceil($now->diffInDays($from) / max(1, $trendDays - 1))));
                $label = $start->format('d M');
            }

            $count = (clone $generationBase)->where('created_at', '>=', $start)->where('created_at', '<', $end)->count();
            $trend[] = ['label' => $label, 'value' => $count];
        }

        $recent = AppModelsGeneration::query()
            ->with([
                'store:id,name,logo_path',
                'template:id,name',
                'generatedImages:id,generation_id,image_path,image_url,is_primary,is_favorite',
            ])
            ->when($this->dashboardStatus !== 'all', fn ($q) => $q->where('status', $this->dashboardStatus))
            ->when($this->dashboardStore !== 'all', fn ($q) => $q->where('store_id', (int) $this->dashboardStore))
            ->latest('created_at')
            ->limit(8)
            ->get();

        $this->dashboardData = [
            'total' => $total,
            'completed' => $completed,
            'processing' => $processing,
            'queued' => $queued,
            'failed' => $failed,
            'images' => $imageCount,
            'avg_duration' => $avgDuration,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'trend' => $trend,
            'max_trend' => max(1, max(array_column($trend, 'value'))),
            'recent' => $recent->map(fn ($generation) => [
                'id' => $generation->id,
                'title' => data_get($generation->metadata, 'title') ?: ('Generation #' . $generation->id),
                'status' => $generation->status,
                'model' => IlluminateSupportStr::afterLast((string) $generation->model, '/'),
                'store' => $generation->store?->name ?? 'Store',
                'template' => $generation->template?->name ?? 'Template',
                'created_at' => $generation->created_at?->diffForHumans(),
                'created_at_raw' => $generation->created_at?->format('d M Y H:i'),
                'images' => $generation->generatedImages->map(fn ($image) => [
                    'url' => $image->image_url ?: ($image->image_path ? IlluminateSupportFacadesStorage::disk('public')->url($image->image_path) : null),
                    'favorite' => (bool) $image->is_favorite,
                ])->filter(fn ($image) => filled($image['url']))->values()->all(),
            ])->all(),
        ];
    }

    public function getDashboardStoresProperty()
    {
        return Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function getGlobalSearchResultsProperty(): array
    {
        $term = trim($this->globalSearch);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $keyword = '%' . $term . '%';

        $stores = Store::query()
            ->where('name', 'like', $keyword)
            ->limit(4)
            ->get(['id', 'name'])
            ->map(fn ($item) => ['type' => 'Store', 'label' => $item->name, 'meta' => 'Marketplace store', 'action' => 'stores'])
            ->all();

        $templates = AppModelsTemplate::query()
            ->where('name', 'like', $keyword)
            ->limit(4)
            ->get(['id', 'name'])
            ->map(fn ($item) => ['type' => 'Template', 'label' => $item->name, 'meta' => 'Visual template', 'action' => 'templates'])
            ->all();

        $generations = AppModelsGeneration::query()
            ->where(function ($q) use ($keyword) {
                $q->where('prompt', 'like', $keyword)
                    ->orWhere('model', 'like', $keyword)
                    ->orWhere('status', 'like', $keyword);
            })
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'model', 'status'])
            ->map(fn ($item) => ['type' => 'Generation', 'label' => 'Generation #' . $item->id, 'meta' => $item->status . ' · ' . IlluminateSupportStr::afterLast($item->model, '/'), 'action' => 'generator'])
            ->all();

        return array_slice(array_merge($stores, $templates, $generations), 0, 8);
    }

    /**
     * Queue worker controls used directly from the dashboard sidebar.
     */
    // Worker status is refreshed on mount and after every worker action.
    // Intentionally no wire:poll here: this dashboard has global Livewire loading
    // indicators, so continuous polling makes the whole page appear to load forever.
    #[On('worker-status-refresh')]
    public function refreshWorkerStatus(): void
    {
        try {
            $this->assertWorkerControlAccess();

            $status = app(QueueWorkerManager::class)->status();

            $this->workerStatus = [
                'running' => (bool) ($status['running'] ?? false),
                'healthy' => (bool) ($status['healthy'] ?? false),
                'running_count' => (int) ($status['running_count'] ?? 0),
                'worker_count' => (int) ($status['worker_count'] ?? 0),
                'target_workers' => (int) ($status['target_workers'] ?? 3),
                'workers' => $status['workers'] ?? [],
                'pid' => $status['pid'] ?? null,
                'queue' => (string) ($status['queue'] ?? config('queue.default', 'database')),
                'started_at' => $status['started_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            report($e);

            $this->workerStatus = [
                'running' => false,
                'healthy' => false,
                'running_count' => 0,
                'worker_count' => 0,
                'target_workers' => 3,
                'workers' => [],
                'pid' => null,
                'queue' => (string) config('queue.default', 'database'),
                'started_at' => null,
            ];
        }
    }

    public function startQueueWorker(): void
    {
        try {
            $this->assertWorkerControlAccess();

            $result = app(QueueWorkerManager::class)->start();
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: ($result['success'] ?? false) ? 'success' : 'info',
                title: ($result['success'] ?? false) ? 'Worker berhasil dijalankan' : 'Worker sudah berjalan',
                message: (string) ($result['message'] ?? 'Queue worker siap digunakan.'),
            );
        } catch (\Throwable $e) {
            report($e);
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Worker gagal dijalankan',
                message: $e->getMessage(),
            );
        }
    }

    public function stopQueueWorker(): void
    {
        try {
            $this->assertWorkerControlAccess();

            $result = app(QueueWorkerManager::class)->stop();
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: ($result['success'] ?? false) ? 'success' : 'info',
                title: ($result['success'] ?? false) ? 'Worker stopped' : 'Worker sudah berhenti',
                message: (string) ($result['message'] ?? 'Queue worker tidak aktif.'),
            );
        } catch (\Throwable $e) {
            report($e);
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Worker gagal dihentikan',
                message: $e->getMessage(),
            );
        }
    }

    public function restartQueueWorker(): void
    {
        try {
            $this->assertWorkerControlAccess();

            $result = app(QueueWorkerManager::class)->restart();
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: ($result['success'] ?? false) ? 'success' : 'error',
                title: ($result['success'] ?? false) ? 'Worker restarted' : 'Restart worker gagal',
                message: (string) ($result['message'] ?? 'Queue worker sudah direstart.'),
            );
        } catch (\Throwable $e) {
            report($e);
            $this->refreshWorkerStatus();

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Worker gagal direstart',
                message: $e->getMessage(),
            );
        }
    }

    private function assertWorkerControlAccess(): void
    {
        abort_unless(auth()->check(), 403);
    }


public function getUserInitialsProperty(): string

    {

        $name = trim((string) ($this->user?->name ?? 'User'));

        $parts = preg_split('/\s+/', $name) ?: [];



        return collect($parts)

            ->filter()

            ->take(2)

            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))

            ->implode('') ?: 'U';

    }



    public function openStore(): void

    {

        $this->activeSection = 'stores';



        $this->dispatch('workspace-section-changed', section: 'stores');

    }



    public function openDashboard(): void

    {

        $this->activeSection = 'dashboard';



        $this->dispatch('workspace-section-changed', section: 'dashboard');

    }



    public function openTemplates(): void

    {

        $this->activeSection = 'templates';

    }



    public function openGenerator(): void

    {

        $this->activeSection = 'generator';

    }



    public function openSettings(): void

    {

        $this->activeSection = 'settings-general';

        $this->activeTab = 'general';

    }



    public function openGeneralSettings(): void

    {

        $this->activeSection = 'settings-general';

        $this->activeTab = 'general';

    }



    public function openOpenAiSettings(): void

    {

        $this->activeSection = 'settings-openai';

        $this->activeTab = 'openai';

    }



    public function selectTab(string $tab): void

    {

        if (! in_array($tab, ['general', 'openai'], true)) {

            return;

        }



        $this->activeTab = $tab;

        $this->activeSection = $tab === 'openai'

            ? 'settings-openai'

            : 'settings-general';

    }



    public function testConnection(): void

    {

        try {

            $result = app(\App\Services\OpenAiService::class)->testConnection(

                apiKey: trim($this->apiKey) !== '' ? trim($this->apiKey) : null,

                model: $this->imageModel,

            );



            $this->hasOpenAiKey = $result['has_key'];



            $this->dispatch(

                'openai-test-result',

                type: $result['status'],

                message: $result['message'],

                detail: $result['detail'],

            );



            $this->dispatch(

                'toast',

                type: $result['status'] === 'success' ? 'success' : ($result['status'] === 'warning' ? 'warning' : 'error'),

                title: $result['title'],

                message: $result['message'],

            );

        } catch (\Throwable $e) {

            report($e);



            $this->dispatch(

                'openai-test-result',

                type: 'error',

                message: 'Test koneksi gagal.',

                detail: 'Terjadi error internal saat menghubungi OpenAI.',

            );



            $this->dispatch(

                'toast',

                type: 'error',

                title: 'Test koneksi gagal',

                message: 'Terjadi error internal. Periksa log Laravel.',

            );

        }

        $this->loadActivityLogs();

        $this->loadOpenAiLogs();

        $this->loadVercelGatewayLogs();

    }



    public function clearActivityLogs(): void

    {

        try {

            \App\Models\ActivityLog::query()->delete();

            $this->loadActivityLogs();



            $this->dispatch(

                'toast',

                type: 'success',

                title: 'Activity logs dibersihkan',

                message: 'Seluruh riwayat aktivitas sudah dihapus.'

            );

        } catch (\Throwable $e) {

            report($e);



            $this->dispatch(

                'toast',

                type: 'error',

                title: 'Gagal membersihkan logs',

                message: 'Activity logs tidak dapat dihapus.'

            );

        }

    }



    public function saveOpenAi(): void

    {

        try {

            $this->validate([

                'apiKey' => ['nullable', 'string', 'max:500'],

            ]);



            $existing = \App\Models\OpenAiSetting::query()->first();

            $newApiKey = trim((string) $this->apiKey);



            if ($newApiKey === '' && ! filled($existing?->api_key)) {

                $this->dispatch(

                    'toast',

                    type: 'warning',

                    title: 'API Key belum diisi',

                    message: 'Masukkan API Key OpenAI terlebih dahulu.'

                );



                return;

            }



            $savedSettings = app(\App\Services\OpenAiService::class)->saveApiKey(

                $newApiKey !== '' ? $newApiKey : null,

            );



            $this->hasOpenAiKey = filled($savedSettings->api_key);



            if (! $this->hasOpenAiKey) {

                $this->dispatch(

                    'toast',

                    type: 'error',

                    title: 'API Key gagal disimpan',

                    message: 'Credential tidak berhasil tersimpan di database.'

                );



                return;

            }



            app(\App\Services\ActivityLogService::class)->success(

                category: 'api',

                action: 'update_openai_configuration',

                title: 'OpenAI API configuration diperbarui.',

                description: 'Credential OpenAI berhasil disimpan melalui backend.',

            );



            $this->apiKey = '';

            $this->showApiKey = false;

            $this->loadActivityLogs();



            $this->dispatch(

                'toast',

                type: 'success',

                title: 'Konfigurasi tersimpan',

                message: 'OpenAI API siap digunakan oleh Product Generator.'

            );

        } catch (\Illuminate\Validation\ValidationException $e) {

            $this->dispatch(

                'toast',

                type: 'warning',

                title: 'Periksa konfigurasi',

                message: $e->validator->errors()->first()

            );



            throw $e;

        } catch (\Throwable $e) {

            report($e);



            $this->dispatch(

                'toast',

                type: 'error',

                title: 'Gagal menyimpan',

                message: 'Konfigurasi OpenAI tidak dapat disimpan.'

            );

        }

    }



    public function saveGeneral(): void

    {

        $this->dispatch(

            'toast',

            type: 'success',

            title: 'Preferences siap',

            message: 'Pengaturan workspace sudah siap disimpan.'

        );

    }

};



?>



<div class="min-h-screen bg-[#f4f4f5] text-zinc-950" x-data="{ sidebarCollapsed:false, sidebarGroups:{workspace:true,management:true,activity:true,system:true}, init(){ try{this.sidebarCollapsed=localStorage.getItem('rms-sidebar-collapsed')==='1'; Object.assign(this.sidebarGroups,JSON.parse(localStorage.getItem('rms-sidebar-groups')||'{}'));}catch(e){} this.$watch('sidebarCollapsed',v=>{try{localStorage.setItem('rms-sidebar-collapsed',v?'1':'0')}catch(e){}}); this.$watch('sidebarGroups',v=>{try{localStorage.setItem('rms-sidebar-groups',JSON.stringify(v))}catch(e){}})}, toggleGroup(g){this.sidebarGroups[g]=!this.sidebarGroups[g]} }" :class="{ 'sidebar-is-collapsed': sidebarCollapsed }">



    {{-- Ambient page background --}}

    <div class="pointer-events-none fixed inset-0 overflow-hidden">

        <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-red-500/[0.045] blur-3xl"></div>

        <div class="absolute -bottom-48 right-0 h-[32rem] w-[32rem] rounded-full bg-zinc-900/[0.035] blur-3xl"></div>

    </div>



    {{-- MOBILE OVERLAY --}}

    <div id="mobileOverlay" class="dashboard-overlay"></div>

{{-- SIDEBAR --}}

    <aside id="dashboardSidebar" class="dashboard-sidebar">



        {{-- Sidebar ambient glow --}}

        <div class="sidebar-glow sidebar-glow-red"></div>

        <div class="sidebar-glow sidebar-glow-white"></div>



        {{-- BRAND / LOGO ONLY --}}

        <div class="sidebar-brand">

            <div class="brand-logo-wrap">

                <img

                    src="{{ asset('images/logo-rizky-moto-shop.png') }}"

                    alt="Rizky Moto Shop"

                    class="brand-logo"

                >

            </div>



                        <button type="button" class="sidebar-collapse-toggle" @click="sidebarCollapsed = !sidebarCollapsed" :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5l7 7-7 7"/></svg>
            </button>

<button

                type="button"

                class="mobile-close"

                onclick="closeMobileSidebar()"

                aria-label="Close sidebar"

            >

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                    <path d="M6 6l12 12M18 6 6 18"/>

                </svg>

            </button>

        </div>



        {{-- NAVIGATION --}}
        <div class="sidebar-scroll">
            <div class="sidebar-nav-section" :class="{ 'is-collapsed': !sidebarGroups.workspace }">
                <button type="button" class="nav-heading-toggle" @click="toggleGroup('workspace')"><span>Workspace</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 9 5 5 5-5"/></svg></button>
                <div class="nav-section-body"><div class="nav-list">
                    <button type="button" class="nav-item {{ $activeSection === 'dashboard' ? 'active' : '' }}" wire:click="openDashboard">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></span>
                        <span class="nav-label">Dashboard</span><span class="nav-item-arrow">›</span>
                    </button>
                    <button type="button" class="nav-item nav-item-generator {{ $activeSection === 'generator' ? 'active' : '' }}" wire:click="openGenerator">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/></svg></span>
                        <span class="nav-label">Product Generator</span><span class="nav-pill">AI</span>
                    </button>
                </div></div>
            </div>
            <div class="sidebar-nav-section" :class="{ 'is-collapsed': !sidebarGroups.management }">
                <button type="button" class="nav-heading-toggle" @click="toggleGroup('management')"><span>Management</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 9 5 5 5-5"/></svg></button>
                <div class="nav-section-body"><div class="nav-list">
                    <button type="button" class="nav-item {{ $activeSection === 'stores' ? 'active' : '' }}" wire:click="openStore">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 10h16"/><path d="M5 10v9h14v-9"/><path d="M3 10 5 4h14l2 6"/><path d="M8 14h8"/></svg></span>
                        <span class="nav-label">Stores</span><span class="nav-item-arrow">›</span>
                    </button>
                    <button type="button" class="nav-item {{ $activeSection === 'templates' ? 'active' : '' }}" wire:click="openTemplates">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="18" height="7" rx="1.5"/></svg></span>
                        <span class="nav-label">Templates</span><span class="nav-item-arrow">›</span>
                    </button>
                </div></div>
            </div>
            <div class="sidebar-nav-section" :class="{ 'is-collapsed': !sidebarGroups.activity }">
                <button type="button" class="nav-heading-toggle" @click="toggleGroup('activity')"><span>Generation</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 9 5 5 5-5"/></svg></button>
                <div class="nav-section-body"><div class="nav-list">
                    <button type="button" class="nav-item {{ $activeSection === 'generator' ? 'active' : '' }}" wire:click="openGenerator">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="m7 16 4-4 3 3 3-5"/></svg></span>
                        <span class="nav-label">Generations</span><span class="nav-item-caption">Generator & history</span>
                    </button>
                </div></div>
            </div>
            <div class="sidebar-nav-section rms-settings-nav" :class="{ 'is-collapsed': !sidebarGroups.system }">
                <button type="button" class="nav-heading-toggle" @click="toggleGroup('system')"><span>System</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m7 9 5 5 5-5"/></svg></button>
                <div class="nav-section-body"><div class="nav-list">
                    <button type="button" class="nav-item {{ str_starts_with($activeSection, 'settings-') ? 'active' : '' }}" wire:click="openSettings">
                        <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3v2"/><path d="M12 19v2"/><path d="m4.2 4.2 1.4 1.4"/><path d="m18.4 18.4 1.4 1.4"/><path d="M3 12h2"/><path d="M19 12h2"/><path d="m4.2 19.8 1.4-1.4"/><path d="m18.4 5.6 1.4-1.4"/><circle cx="12" cy="12" r="3.5"/></svg></span>
                        <span class="nav-label">Settings</span><span class="nav-item-arrow">›</span>
                    </button>
                </div></div>
            </div>
        </div>

        {{-- AI ENGINE / QUEUE WORKER STATUS --}}
        <div
            class="sidebar-api worker-widget-shell"
            :class="workerOpen ? 'is-open' : 'is-collapsed'"
            x-data="{
                workerOpen: true,
                workerAction: '',
                init() {
                    try {
                        const saved = window.localStorage.getItem('rms-worker-widget-open');
                        if (saved !== null) this.workerOpen = saved === '1';
                    } catch (_) {}
                    this.$watch('workerOpen', value => {
                        try {
                            window.localStorage.setItem('rms-worker-widget-open', value ? '1' : '0');
                        } catch (_) {}
                    });
                }
            }"
        >
            <div class="worker-widget-collapsed">
                <button type="button" class="worker-widget-mini" @click="workerOpen = true" aria-label="Show queue worker status">
                    <span class="worker-widget-mini-icon">AI</span>
                    <span class="worker-widget-mini-copy">
                        <strong>Workers</strong>
                        <small>{{ $this->workerStatus['running_count'] }}/{{ $this->workerStatus['target_workers'] }} active</small>
                    </span>
                    <span class="worker-widget-mini-state {{ $this->workerStatus['running'] ? 'is-running' : 'is-stopped' }}"></span>
                    <span class="worker-widget-chevron">⌃</span>
                </button>
            </div>

            <div class="api-status-card worker-status-card worker-widget-expanded">
                <div class="api-status-top">
                    <div class="api-brand">
                        <span class="api-mark">AI</span>
                        <div>
                            <div class="api-title">AI Engine</div>
                            <div class="api-subtitle">Creative generation</div>
                        </div>
                    </div>

                    <div class="worker-widget-top-actions">
                        <span class="api-live-dot {{ $this->workerStatus['running'] ? 'worker-live' : 'worker-offline' }}" aria-hidden="true"></span>
                        <button
                            type="button"
                            class="worker-collapse"
                            @click="workerOpen = false"
                            aria-label="Hide queue worker status"
                            title="Hide worker status"
                        >⌄</button>
                    </div>
                </div>

                <div class="worker-mini-divider"></div>

                <div class="worker-fleet-head">
                    <div class="worker-status-line">
                        <span class="api-status-dot {{ $this->workerStatus['healthy'] ? 'is-connected' : 'is-error' }}"></span>
                        <div class="worker-status-copy">
                            <strong>Queue Workers</strong>
                            <span>{{ $this->workerStatus['running_count'] }}/{{ $this->workerStatus['target_workers'] }} process aktif</span>
                        </div>
                    </div>
                    <span class="api-status-badge">{{ $this->workerStatus['healthy'] ? 'LIVE' : 'OFF' }}</span>
                </div>

                <div class="worker-fleet-summary">
                    <span><small>QUEUE</small><strong>{{ $this->workerStatus['queue'] }}</strong></span>
                    <span><small>ACTIVE</small><strong>{{ $this->workerStatus['running_count'] }}/{{ $this->workerStatus['target_workers'] }}</strong></span>
                </div>

                <div class="worker-fleet-list">
                    @php
                        $workerRows = $this->workerStatus['workers'] ?? [];
                        $targetWorkers = max(1, (int) ($this->workerStatus['target_workers'] ?? 3));
                    @endphp

                    @for ($workerIndex = 0; $workerIndex < $targetWorkers; $workerIndex++)
                        @php
                            $worker = $workerRows[$workerIndex] ?? [];
                            $workerId = $worker['id'] ?? ($workerIndex + 1);
                            $workerRunning = (bool) ($worker['running'] ?? false);
                            $workerPid = $worker['pid'] ?? null;
                        @endphp

                        <div class="worker-fleet-row {{ $workerRunning ? 'is-running' : 'is-stopped' }}">
                            <div class="worker-fleet-indicator"></div>
                            <div class="worker-fleet-copy">
                                <strong>W{{ $workerId }}</strong>
                                <span>{{ $workerRunning ? 'Running' : 'Stopped' }}</span>
                            </div>
                            <code>{{ $workerPid ?: '—' }}</code>
                        </div>
                    @endfor
                </div>

                <div class="worker-actions">
                    @if ($this->workerStatus['running'])
                        <button type="button" class="worker-action worker-action-restart"
                            @click="workerAction='restart'; fetch('/dashboard/queue-workers/restart',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},credentials:'same-origin'}).then(() => Livewire.dispatch('worker-status-refresh')).catch(console.error).finally(() => workerAction='')" :disabled="!!workerAction">
                            <span x-show="workerAction !== 'restart'">↻</span>
                            <span x-show="workerAction === 'restart'" class="worker-spinner" x-cloak>◌</span>
                            Restart
                        </button>
                        <button type="button" class="worker-action worker-action-stop"
                            @click="workerAction='stop'; fetch('/dashboard/queue-workers/stop',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},credentials:'same-origin'}).then(() => Livewire.dispatch('worker-status-refresh')).catch(console.error).finally(() => workerAction='')" :disabled="!!workerAction">
                            <span x-show="workerAction !== 'stop'">■</span>
                            <span x-show="workerAction === 'stop'" class="worker-spinner" x-cloak>◌</span>
                            Stop
                        </button>
                    @else
                        <button type="button" class="worker-action worker-action-start"
                            @click="workerAction='start'; fetch('/dashboard/queue-workers/start',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},credentials:'same-origin'}).then(() => Livewire.dispatch('worker-status-refresh')).catch(console.error).finally(() => workerAction='')" :disabled="!!workerAction">
                            <span x-show="workerAction !== 'start'">▶</span>
                            <span x-show="workerAction === 'start'" class="worker-spinner" x-cloak>◌</span>
                            Start Worker
                        </button>
                    @endif
                </div>

                @if ($this->workerStatus['running'] && $this->workerStatus['started_at'])
                    <div class="worker-started-at">Started {{ $this->workerStatus['started_at'] }}</div>
                @endif

                <div class="worker-api-footer">
                    <button type="button" class="worker-refresh" wire:click="refreshWorkerStatus" wire:loading.attr="disabled" wire:target="refreshWorkerStatus" title="Refresh worker status" aria-label="Refresh worker status">
                        <span wire:loading.remove wire:target="refreshWorkerStatus">↻</span>
                        <span wire:loading wire:target="refreshWorkerStatus" class="worker-spinner">◌</span>
                    </button>

                    <span>
                        <i class="worker-footer-dot {{ $this->openAiStatus['state'] === 'connected' ? 'is-online' : '' }}"></i>
                        API {{ $this->openAiStatus['label'] }}
                    </span>

                    <span class="worker-step">{{ $this->workerStatus['running'] ? 'ENGINE READY' : 'ENGINE PAUSED' }}</span>
                </div>
            </div>
        </div>

        </aside>





    {{-- MAIN AREA --}}

    <div class="dashboard-main">



        {{-- TOPBAR --}}

        <header class="topbar">

            <div class="topbar-left">



                <button

                    type="button"

                    class="mobile-menu"

                    onclick="openMobileSidebar()"

                    aria-label="Open sidebar"

                >

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">

                        <path d="M4 6h16M4 12h16M4 18h16"/>

                    </svg>

                </button>



                <div class="topbar-title">

                    <div class="topbar-eyebrow">

                        <span></span>

                        Workspace

                    </div>

                    <div class="topbar-heading">AI Creative Dashboard</div>

                </div>

            </div>



            <div class="topbar-right">



                <div class="top-search">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                        <circle cx="11" cy="11" r="7"/>

                        <path d="m20 20-4-4"/>

                    </svg>

                    <input type="text" placeholder="Search workspace...">

                    <span class="search-shortcut">⌘ K</span>

                </div>



                {{-- NOTIFICATION MENU --}}

                <div class="notification-menu" id="notificationMenu">

                    <button

                        type="button"

                        class="topbar-button notification-button"

                        id="notificationButton"

                        aria-label="Notifications"

                        aria-expanded="false"

                        aria-haspopup="true"

                    >

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>

                            <path d="M10 21h4"/>

                        </svg>

                        <span class="notification-dot"></span>

                        <span class="notification-count">4</span>

                    </button>



                    <div

                        class="notification-dropdown"

                        id="notificationDropdown"

                        aria-hidden="true"

                    >

                        <div class="notification-dropdown-head">

                            <div>

                                <span class="notification-eyebrow">SYSTEM CENTER</span>

                                <h3>Notifikasi</h3>

                                <p>Update terbaru dari workspace kamu.</p>

                            </div>



                            <button type="button" class="notification-mark-read" id="notificationMarkRead">

                                Tandai dibaca

                            </button>

                        </div>



                        <div class="notification-list">

                            <button type="button" class="notification-item is-unread">

                                <span class="notification-item-icon notification-icon-red">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/>

                                        <path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>AI Creative Engine siap digunakan</strong>

                                    <span>Workspace visual berhasil dimuat dan siap untuk proses berikutnya.</span>

                                    <small>Baru saja</small>

                                </span>

                                <span class="notification-unread-dot"></span>

                            </button>



                            <button type="button" class="notification-item is-unread">

                                <span class="notification-item-icon notification-icon-green">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="M20 6 9 17l-5-5"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>OpenAI API status: Ready</strong>

                                    <span>Koneksi engine kreatif tersedia untuk tahap konfigurasi.</span>

                                    <small>2 menit lalu</small>

                                </span>

                                <span class="notification-unread-dot"></span>

                            </button>



                            <button type="button" class="notification-item">

                                <span class="notification-item-icon notification-icon-dark">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <rect x="3" y="3" width="18" height="18" rx="3"/>

                                        <path d="M7 8h10M7 12h7M7 16h5"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>Template workspace diperbarui</strong>

                                    <span>Template visual baru akan muncul di Template Library.</span>

                                    <small>18 menit lalu</small>

                                </span>

                            </button>



                            <button type="button" class="notification-item">

                                <span class="notification-item-icon notification-icon-violet">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                        <path d="M12 3v18M3 12h18"/>

                                        <circle cx="12" cy="12" r="8"/>

                                    </svg>

                                </span>

                                <span class="notification-item-content">

                                    <strong>Dashboard berhasil diperbarui</strong>

                                    <span>UI workspace Rizky Moto Shop menggunakan tampilan terbaru.</span>

                                    <small>1 jam lalu</small>

                                </span>

                            </button>

                        </div>



                        <div class="notification-dropdown-footer">

                            <span><i></i> Internal workspace</span>

                            <button type="button" id="notificationClose">Tutup</button>

                        </div>

                    </div>

                </div>



                <div class="topbar-divider"></div>



                {{-- PROFILE MENU --}}

                <div class="profile-menu" id="profileMenu">



                    <button

                        type="button"

                        class="profile-button"

                        id="profileButton"

                        aria-expanded="false"

                        aria-haspopup="true"

                    >

                        <span class="profile-avatar">

                            {{ $this->userInitials }}

                        </span>



                        <span class="profile-info">

                            <span class="profile-name">

                                {{ $this->user?->name ?? 'User' }}

                            </span>



                            <span class="profile-role">

                                Administrator

                            </span>

                        </span>



                        <svg

                            class="profile-chevron"

                            id="profileChevron"

                            viewBox="0 0 24 24"

                            fill="none"

                            stroke="currentColor"

                            stroke-width="1.8"

                        >

                            <path d="m6 9 6 6 6-6"/>

                        </svg>

                    </button>



                    <div

                        class="profile-dropdown"

                        id="profileDropdown"

                        aria-hidden="true"

                    >

                        <div class="profile-dropdown-header">

                            <div class="profile-dropdown-avatar">

                                {{ $this->userInitials }}

                            </div>



                            <div class="profile-dropdown-identity">

                                <strong>

                                    {{ $this->user?->name ?? 'User' }}

                                </strong>



                                <span>

                                    {{ $this->user?->email ?? '-' }}

                                </span>

                            </div>

                        </div>



                        <div class="profile-dropdown-divider"></div>



                        <button

                            type="button"

                            class="profile-dropdown-item"

                            data-profile-settings

                        >

                            <span class="profile-dropdown-icon">

                                <svg

                                    viewBox="0 0 24 24"

                                    fill="none"

                                    stroke="currentColor"

                                    stroke-width="1.7"

                                >

                                    <circle cx="12" cy="12" r="3"/>

                                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2h-2.5v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.8-1.8.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6.5v-2.5h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.8-1.8.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V4h2.5v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0-1.5 1h.2v2.5h-.2a1.7 1.7 0 0 0-1.5 1.4Z"/>

                                </svg>

                            </span>



                            <span class="profile-dropdown-text">

                                <strong>Pengaturan Akun</strong>

                                <small>Kelola profil dan akun</small>

                            </span>

                        </button>



                        <div class="profile-dropdown-divider"></div>



                        <form

                            method="POST"

                            action="{{ route('logout') }}"

                            class="profile-logout-form"

                        >

                            @csrf



                            <button

                                type="submit"

                                class="profile-dropdown-item profile-logout"

                            >

                                <span class="profile-dropdown-icon">

                                    <svg

                                        viewBox="0 0 24 24"

                                        fill="none"

                                        stroke="currentColor"

                                        stroke-width="1.7"

                                    >

                                        <path d="M10 17l5-5-5-5"/>

                                        <path d="M15 12H3"/>

                                        <path d="M21 4v16"/>

                                    </svg>

                                </span>



                                <span class="profile-dropdown-text">

                                    <strong>Keluar</strong>

                                    <small>Keluar dari akun internal</small>

                                </span>

                            </button>

                        </form>

                    </div>

                </div>



            </div>

        </header>





        <main class="dashboard-content">



            @if ($activeSection === 'dashboard')

            <div class="rms-content-enter" wire:key="workspace-dashboard">







            {{-- =========================================================

                 PREMIUM PAGE INTRO

                 ======================================================== --}}

            <section class="workspace-intro reveal reveal-1">



                <div class="workspace-intro-copy">

                    <div class="eyebrow-label">

                        <span class="eyebrow-dot"></span>

                        INTERNAL CREATIVE WORKSPACE

                    </div>



                    <h1 class="page-title">

                        Good afternoon,

                        <span>{{ $this->user?->name ?? 'User' }}.</span>

                    </h1>



                    <p class="page-description">

                        Satu workspace terpusat untuk mengelola visual produk,

                        Store, Template, dan aktivitas AI Rizky Moto Shop.

                    </p>



                    <div class="workspace-meta">

                        <span class="workspace-meta-item">

                            <i></i>

                            Workspace active

                        </span>



                        <span class="workspace-meta-divider"></span>



                        <span class="workspace-meta-item muted">

                            Last sync just now

                        </span>

                    </div>

                </div>



                <div class="workspace-intro-status">

                    <span class="system-status-dot"></span>



                    <div>

                        <span class="system-status-title">All Systems</span>

                        <span class="system-status-subtitle">Operational</span>

                    </div>



                    <span class="system-status-arrow">↗</span>

                </div>



            </section>





            {{-- =========================================================

                 HERO — AI CREATIVE ENGINE

                 ========================================================= --}}

            <section class="premium-hero reveal reveal-2">



                <div class="premium-hero-grid"></div>

                <div class="premium-hero-noise"></div>



                <div class="premium-hero-glow premium-hero-glow-red"></div>

                <div class="premium-hero-glow premium-hero-glow-white"></div>



                <div class="premium-hero-content">



                    <div class="hero-kicker">

                        <span class="hero-kicker-dot"></span>

                        AI CREATIVE ENGINE

                    </div>



                    <h2 class="premium-hero-title">

                        From product photo

                        <span>to marketplace-ready.</span>

                    </h2>



                    <p class="premium-hero-description">

                        Siapkan bahan visual, gunakan preset Template,

                        lalu biarkan creative engine membantu menghasilkan

                        visual produk yang konsisten.

                    </p>



                    <div class="premium-hero-actions">

                        <button type="button" class="hero-primary">

                            <span class="sparkle-icon">✦</span>

                            Product Generator

                            <span class="button-arrow">→</span>

                        </button>



                        <button type="button" class="hero-secondary">

                            Explore Workspace

                            <span>↗</span>

                        </button>

                    </div>



                    <div class="hero-trust-row">

                        <span class="hero-trust-item">

                            <i></i>

                            Internal workspace

                        </span>



                        <span class="hero-trust-item">

                            <i></i>

                            Template based

                        </span>



                        <span class="hero-trust-item">

                            <i></i>

                            AI ready

                        </span>

                    </div>



                </div>





                {{-- AI PIPELINE VISUAL --}}

                <div class="ai-engine-visual">



                    <div class="engine-orbit engine-orbit-a"></div>

                    <div class="engine-orbit engine-orbit-b"></div>

                    <div class="engine-orbit engine-orbit-c"></div>



                    <div class="engine-scan-line"></div>



                    <div class="engine-core">

                        <div class="engine-core-ring"></div>

                        <div class="engine-core-inner">

                            <span class="engine-core-label">AI</span>

                            <small>ENGINE</small>

                        </div>

                    </div>



                    <div class="engine-node engine-node-img">

                        <span class="engine-node-icon">IMG</span>

                        <small>Product</small>

                    </div>



                    <div class="engine-node engine-node-style">

                        <span class="engine-node-icon">✦</span>

                        <small>Style</small>

                    </div>



                    <div class="engine-node engine-node-shop">

                        <span class="engine-node-icon">▣</span>

                        <small>Store</small>

                    </div>



                    <div class="engine-connection engine-connection-1"></div>

                    <div class="engine-connection engine-connection-2"></div>

                    <div class="engine-connection engine-connection-3"></div>



                </div>



                <div class="premium-hero-footer">

                    <span>RIZKY MOTO SHOP</span>

                    <span class="premium-hero-footer-line"></span>

                    <span>CREATIVE SYSTEM / 01</span>

                </div>



            </section>





            {{-- =========================================================

                 KPI / WORKSPACE OVERVIEW

                 ========================================================= --}}

            <section class="dashboard-section-heading reveal reveal-3">

                <div>

                    <span class="section-kicker">WORKSPACE OVERVIEW</span>

                    <h2>Creative activity</h2>

                </div>



                <span class="section-live">

                    <i></i>

                    LIVE DATA

                </span>

            </section>





            <section class="premium-stats-grid reveal reveal-3">



                {{-- STORES --}}

                <article class="premium-stat-card premium-stat-red">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="M4 10h16"/>

                                <path d="M5 10v9h14v-9"/>

                                <path d="M3 10 5 4h14l2 6"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status live">

                            <i></i>

                            LIVE

                        </span>

                    </div>



                    <div class="premium-stat-label">Active Stores</div>

                    <div class="premium-stat-value">{{ str_pad((string) $this->activeStores, 2, '0', STR_PAD_LEFT) }}</div>



                    <div class="premium-stat-bottom">

                        <span>Connected marketplace stores</span>

                        <strong>{{ $this->totalStores > 0 ? round(($this->activeStores / $this->totalStores) * 100) : 0 }}%</strong>

                    </div>



                    <div class="premium-stat-progress">

                        <span style="width: {{ $this->totalStores > 0 ? round(($this->activeStores / $this->totalStores) * 100) : 0 }}%"></span>

                    </div>



                </article>





                {{-- TEMPLATES --}}

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon violet">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>

                                <rect x="3" y="14" width="18" height="7" rx="1.5"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            READY

                        </span>

                    </div>



                    <div class="premium-stat-label">Templates</div>

                    <div class="premium-stat-value">{{ $this->totalTemplates }}</div>



                    <div class="premium-stat-bottom">

                        <span>Configured visual templates</span>

                        <strong>{{ $this->totalTemplates }} total</strong>

                    </div>



                    <div class="premium-stat-progress violet">

                        <span style="width: {{ $this->totalTemplates > 0 ? 100 : 0 }}%"></span>

                    </div>



                </article>





                {{-- GENERATIONS --}}

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon orange">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <rect x="3" y="3" width="18" height="18" rx="3"/>

                                <path d="m7 16 4-4 3 3 3-5"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            TOTAL

                        </span>

                    </div>



                    <div class="premium-stat-label">Generations</div>

                    <div class="premium-stat-value">{{ $this->totalGenerations }}</div>



                    <div class="premium-stat-bottom">

                        <span>Visuals generated</span>

                        <strong>LIVE</strong>

                    </div>



                    <div class="premium-stat-progress orange">

                        <span style="width: {{ $this->totalGenerations > 0 ? 100 : 0 }}%"></span>

                    </div>



                </article>





                {{-- FAVORITES --}}

                <article class="premium-stat-card">



                    <div class="premium-stat-top">

                        <div class="premium-stat-icon green">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">

                                <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>

                            </svg>

                        </div>



                        <span class="premium-stat-status">

                            SAVED

                        </span>

                    </div>



                    <div class="premium-stat-label">Favorites</div>

                    <div class="premium-stat-value">{{ $this->favoriteCount }}</div>



                    <div class="premium-stat-bottom">

                        <span>Saved visual results</span>

                        <strong>{{ $this->favoriteCount }} saved</strong>

                    </div>



                    <div class="premium-stat-progress green">

                        <span style="width: {{ $this->favoriteCount > 0 ? 100 : 0 }}%"></span>

                    </div>



                </article>



            </section>





            {{-- =========================================================

                 LOWER WORKSPACE

                 ========================================================= --}}

            <section class="premium-lower-grid reveal reveal-4">



                {{-- QUICK ACCESS --}}

                <div class="premium-content-card quick-card">



                    <div class="premium-card-heading">

                        <div>

                            <span class="section-kicker">WORKSPACE</span>

                            <h3>Quick access</h3>

                            <p>Shortcut untuk aktivitas yang paling sering digunakan.</p>

                        </div>



                        <span class="card-index">01</span>

                    </div>





                    <div class="premium-quick-list">



                        <div class="premium-quick-item">

                            <div class="premium-quick-icon red">

                                <span>✦</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Product Generator</strong>

                                <span>Create a new product visual</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>





                        <div class="premium-quick-item">

                            <div class="premium-quick-icon dark">

                                <span>▣</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Store Management</strong>

                                <span>Manage marketplace identity</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>





                        <div class="premium-quick-item">

                            <div class="premium-quick-icon violet">

                                <span>▦</span>

                            </div>



                            <div class="premium-quick-copy">

                                <strong>Template Library</strong>

                                <span>Manage visual presets</span>

                            </div>



                            <span class="premium-quick-arrow">↗</span>

                        </div>



                    </div>



                </div>





                {{-- RECENT ACTIVITY --}}

                <div class="premium-content-card activity-card">



                    <div class="premium-card-heading">

                        <div>

                            <span class="section-kicker">ACTIVITY</span>

                            <h3>Recent generations</h3>

                            <p>Visual terbaru yang dibuat dari creative engine.</p>

                        </div>



                        <button type="button" class="premium-view-all">

                            View all

                            <span>→</span>

                        </button>

                    </div>





                    <div class="premium-activity-empty">



                        <div class="activity-visual">



                            <div class="activity-visual-ring ring-one"></div>

                            <div class="activity-visual-ring ring-two"></div>



                            <div class="activity-visual-core">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">

                                    <rect x="3" y="3" width="18" height="18" rx="3"/>

                                    <path d="m7 16 4-4 3 3 3-5"/>

                                </svg>

                            </div>



                            <span class="activity-node activity-node-one">IMG</span>

                            <span class="activity-node activity-node-two">AI</span>

                            <span class="activity-node activity-node-three">SHOP</span>



                        </div>



                        <div class="premium-empty-copy">

                            <strong>Your creative space is ready.</strong>

                            <span>

                                Generated visuals will appear here once the

                                AI generator starts creating results.

                            </span>

                        </div>



                        <div class="premium-empty-status">

                            <i></i>

                            Waiting for first generation

                        </div>



                    </div>



                </div>



            </section>





            {{-- =========================================================

                 SYSTEM / BRAND FOOTER

                 ========================================================= --}}

            <section class="premium-system-strip reveal reveal-5">



                <div class="premium-system-brand">



                    <div class="mini-logo">

                        <img

                            src="{{ asset('images/logo-rizky-moto-shop.png') }}"

                            alt="Rizky Moto Shop"

                        >

                    </div>



                    <div>

                        <strong>Rizky Moto Shop AI Tools</strong>

                        <span>Internal creative workspace</span>

                    </div>



                </div>





                <div class="premium-system-info">



                    <span class="premium-system-online">

                        <i></i>

                        System online

                    </span>



                    <span class="premium-system-divider"></span>



                    <span class="premium-system-version">

                        OPENAI: {{ $this->openAiStatus['label'] }}

                    </span>



                </div>





            </section>

            </div>



            @elseif ($activeSection === 'stores')

                <div

                    class="workspace-section-shell rms-content-enter"

                    wire:key="workspace-stores"

                >

                    <livewire:stores />

                </div>



            @elseif ($activeSection === 'templates')

                <div

                    class="workspace-section-shell rms-content-enter"

                    wire:key="workspace-templates"

                >

                    <livewire:dashboard.templates.index />

                </div>



            @elseif ($activeSection === 'generator')

                <div

                    class="workspace-section-shell rms-content-enter"

                    wire:key="workspace-generator"

                >

                    <livewire:dashboard.generator.index />

                </div>



            @elseif (in_array($activeSection, ['settings-general', 'settings-openai'], true))

                <div

                    class="workspace-section-shell rms-content-enter rms-settings-section-shell"

                    wire:key="workspace-settings"

                >

                    @include('livewire.dashboard.settings.index')

                </div>

            @endif



        </main>

    </div>



    {{-- UNIVERSAL TOAST HOST --}}

    @teleport('body')

        <div

            x-data="{

                visible: false,

                type: 'success',

                title: '',

                message: '',

                duration: 4500,

                progress: 100,

                timer: null,

                progressTimer: null,

                showToast(detail = {}) {

                    clearTimeout(this.timer);

                    clearInterval(this.progressTimer);

                    this.type = detail.type || 'success';

                    this.title = detail.title || 'Berhasil';

                    this.message = detail.message || '';

                    this.duration = Number(detail.duration ?? 4500);

                    this.progress = 100;

                    this.visible = true;

                    if (this.duration <= 0) return;

                    const started = Date.now();

                    this.progressTimer = setInterval(() => {

                        this.progress = Math.max(0, 100 - ((Date.now() - started) / this.duration) * 100);

                    }, 40);

                    this.timer = setTimeout(() => this.hideToast(), this.duration);

                },

                hideToast() {

                    clearTimeout(this.timer);

                    clearInterval(this.progressTimer);

                    this.visible = false;

                    this.progress = 100;

                },

                icon() {

                    return { success: '✓', error: '!', warning: '!', info: 'i', loading: '↻' }[this.type] || '✓';

                },

                tone() {

                    return {

                        success: 'rms-toast-success', error: 'rms-toast-error', warning: 'rms-toast-warning',

                        info: 'rms-toast-info', loading: 'rms-toast-loading'

                    }[this.type] || 'rms-toast-success';

                }

            }"

            x-on:toast.window="showToast($event.detail || {})"

            x-on:toast-close.window="hideToast()"

            x-show="visible"

            x-cloak

            class="rms-universal-toast"

            :class="tone()"

            role="status"

            aria-live="polite"

        >

            <div class="rms-toast-icon" x-text="icon()"></div>

            <div class="rms-toast-copy">

                <strong x-text="title"></strong>

                <span x-text="message"></span>

            </div>

            <button type="button" class="rms-toast-close" @click="hideToast()" aria-label="Close notification">×</button>

            <div class="rms-toast-progress"><i :style="`width:${progress}%`"></i></div>

        </div>

    @endteleport



    <style>

        .rms-universal-toast{position:fixed;right:22px;top:22px;z-index:100000;width:min(390px,calc(100vw - 28px));display:flex;align-items:center;gap:12px;padding:13px 14px 15px;border:1px solid rgba(228,228,231,.95);border-radius:17px;background:rgba(255,255,255,.97);box-shadow:0 22px 70px rgba(24,24,27,.18);backdrop-filter:blur(16px);font-family:Inter,system-ui,sans-serif;animation:rmsToastIn .28s cubic-bezier(.22,.8,.2,1)}

        .rms-toast-icon{display:grid;width:38px;height:38px;flex:0 0 38px;place-items:center;border-radius:12px;color:#fff;font-size:15px;font-weight:950;box-shadow:0 8px 20px rgba(0,0,0,.12)}

        .rms-toast-copy{min-width:0;flex:1;display:grid;gap:3px}.rms-toast-copy strong{font-size:11px;color:#18181b}.rms-toast-copy span{font-size:9px;line-height:1.45;color:#71717a}.rms-toast-close{width:28px;height:28px;flex:0 0 28px;border:0;border-radius:8px;background:#f4f4f5;color:#71717a;font-size:17px;cursor:pointer}.rms-toast-close:hover{background:#e4e4e7;color:#18181b}.rms-toast-progress{position:absolute;left:12px;right:12px;bottom:6px;height:2px;overflow:hidden;border-radius:999px;background:#f4f4f5}.rms-toast-progress i{display:block;height:100%;border-radius:999px;background:#18181b;transition:width .04s linear}.rms-toast-success .rms-toast-icon{background:#16a34a}.rms-toast-success .rms-toast-progress i{background:#16a34a}.rms-toast-error .rms-toast-icon{background:#dc2626}.rms-toast-error .rms-toast-progress i{background:#dc2626}.rms-toast-warning .rms-toast-icon{background:#d97706}.rms-toast-warning .rms-toast-progress i{background:#d97706}.rms-toast-info .rms-toast-icon{background:#2563eb}.rms-toast-info .rms-toast-progress i{background:#2563eb}.rms-toast-loading .rms-toast-icon{background:#18181b;animation:rmsToastSpin 1s linear infinite}.rms-toast-loading .rms-toast-progress i{background:#18181b}

        @keyframes rmsToastIn{from{opacity:0;transform:translate3d(18px,-8px,0) scale(.97)}to{opacity:1;transform:none}}@keyframes rmsToastSpin{to{transform:rotate(360deg)}}

        @media(max-width:640px){.rms-universal-toast{left:12px;right:12px;top:12px;width:auto;padding:12px}.rms-toast-icon{width:35px;height:35px;flex-basis:35px}.rms-toast-copy strong{font-size:10px}.rms-toast-copy span{font-size:9px}}

        @media(max-width:1100px){
    .worker-widget-expanded .worker-fleet-summary{gap:5px}
    .worker-widget-expanded .worker-fleet-row{padding-left:6px;padding-right:6px}
    .worker-widget-expanded .worker-fleet-copy{grid-template-columns:26px 1fr}
}
@media(max-width:900px){
    .worker-widget-shell{margin-top:4px}
    .worker-widget-expanded .worker-fleet-list{gap:4px}
    .worker-widget-expanded .worker-fleet-row{min-height:25px}
    .worker-widget-expanded .worker-actions{margin-top:8px}
    .worker-widget-expanded .worker-started-at{font-size:6.5px}
}
@media(max-width:640px){
    .worker-widget-mini{min-height:46px;padding:6px 8px}
    .worker-widget-mini-icon{width:29px;height:29px;flex-basis:29px}
    .worker-widget-expanded .worker-meta-grid{grid-template-columns:1fr 1fr}
    .worker-widget-expanded .worker-fleet-summary{grid-template-columns:1fr 1fr}
    .worker-widget-expanded .worker-fleet-copy{grid-template-columns:28px 1fr}
    .worker-widget-expanded .worker-action{height:30px}
}
@media(max-width:380px){
    .worker-widget-expanded .worker-fleet-copy span{font-size:6.5px}
    .worker-widget-expanded .worker-fleet-row code{max-width:54px;font-size:6.3px}
    .worker-widget-expanded .worker-action{padding:0 7px;font-size:7px}
    .worker-widget-expanded .worker-api-footer{gap:5px}
    .worker-widget-expanded .worker-step{font-size:5.5px}
}
@media(prefers-reduced-motion:reduce){.rms-universal-toast{animation:none}.rms-toast-icon{animation:none}}

        .api-status-dot.is-connected{background:#16a34a;box-shadow:0 0 0 4px rgba(22,163,74,.10)}

        .api-status-dot.is-error{background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.10)}
        /* Sidebar AI Engine / Queue Worker */
.worker-refresh{margin-left:auto;width:24px;height:24px;border:0;border-radius:7px;background:rgba(255,255,255,.06);color:#a1a1aa;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}.worker-refresh:hover{background:rgba(255,255,255,.1);color:#fff}.worker-refresh:disabled{opacity:.55;cursor:wait}
        .worker-fleet-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .worker-fleet-summary{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:9px}
        .worker-fleet-summary>span{display:grid;gap:3px;min-width:0;padding:6px 8px;border:1px solid rgba(255,255,255,.06);border-radius:8px;background:rgba(255,255,255,.025)}
        .worker-fleet-summary small{font-size:6px;line-height:1;color:#52525b;letter-spacing:.13em;font-weight:900}
        .worker-fleet-summary strong{font-size:8px;line-height:1.15;color:#a1a1aa;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-fleet-list{display:grid;gap:5px;margin-top:8px}
        .worker-fleet-row{display:flex;align-items:center;gap:7px;min-height:27px;padding:5px 7px;border:1px solid rgba(255,255,255,.055);border-radius:8px;background:rgba(255,255,255,.022)}
        .worker-fleet-row.is-running{border-color:rgba(34,197,94,.13);background:rgba(34,197,94,.025)}
        .worker-fleet-indicator{width:6px;height:6px;flex:0 0 6px;border-radius:50%;background:#52525b}
        .worker-fleet-row.is-running .worker-fleet-indicator{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08),0 0 9px rgba(34,197,94,.28)}
        .worker-fleet-row.is-stopped .worker-fleet-indicator{background:#71717a}
        .worker-fleet-copy{display:grid;grid-template-columns:30px 1fr;align-items:center;gap:5px;min-width:0;flex:1}
        .worker-fleet-copy strong{font-size:7.5px;color:#e4e4e7;font-weight:850}
        .worker-fleet-copy span{font-size:7px;color:#71717a;white-space:nowrap}
        .worker-fleet-row.is-running .worker-fleet-copy span{color:#86efac}
        .worker-fleet-row code{max-width:62px;color:#71717a;font-family:"SFMono-Regular",Consolas,monospace;font-size:6.8px;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-widget-shell{position:relative;width:100%;min-width:0;overflow:visible}
        .worker-widget-expanded,.worker-widget-collapsed{overflow:hidden;transition:max-height .46s cubic-bezier(.22,1,.36,1),opacity .32s ease,transform .46s cubic-bezier(.22,1,.36,1),filter .32s ease}
        .worker-widget-expanded{max-height:560px;opacity:1;transform:translate3d(0,0,0) scale(1);filter:blur(0);transform-origin:bottom center;will-change:max-height,opacity,transform}
        .worker-widget-collapsed{max-height:0;opacity:0;transform:translate3d(0,8px,0) scale(.985);filter:blur(1px);pointer-events:none;will-change:max-height,opacity,transform}
        .worker-widget-shell.is-collapsed .worker-widget-expanded{max-height:0;opacity:0;transform:translate3d(0,8px,0) scale(.985);filter:blur(1px);pointer-events:none}
        .worker-widget-shell.is-collapsed .worker-widget-collapsed{max-height:64px;opacity:1;transform:translate3d(0,0,0) scale(1);filter:blur(0);pointer-events:auto}
        .worker-widget-shell.is-open .worker-widget-expanded{max-height:560px;opacity:1;transform:translate3d(0,0,0) scale(1);filter:blur(0);pointer-events:auto}
        .worker-widget-shell.is-open .worker-widget-collapsed{max-height:0;opacity:0;transform:translate3d(0,8px,0) scale(.985);filter:blur(1px);pointer-events:none}
        .worker-widget-top-actions{display:flex;align-items:center;gap:7px}
        .worker-collapse{width:23px;height:23px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.07);border-radius:7px;background:rgba(255,255,255,.035);color:#71717a;font:inherit;font-size:12px;line-height:1;cursor:pointer;transition:background .2s ease,color .24s cubic-bezier(.22,1,.36,1),transform .28s cubic-bezier(.22,1,.36,1),border-color .2s ease}
        .worker-collapse:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);color:#f4f4f5;transform:translateY(-1px)}
        .worker-collapse:active{transform:translateY(0) scale(.94)}
        .worker-widget-mini{position:relative;width:100%;min-height:48px;display:flex;align-items:center;gap:9px;padding:7px 9px;border:1px solid rgba(255,255,255,.09);border-radius:13px;background:linear-gradient(145deg,rgba(255,255,255,.055),rgba(255,255,255,.025));color:#fff;font-family:inherit;text-align:left;cursor:pointer;box-shadow:0 10px 28px rgba(0,0,0,.12);transition:transform .28s cubic-bezier(.22,1,.36,1),border-color .2s ease,background .2s ease,box-shadow .28s ease}
        .worker-widget-mini:hover{transform:translateY(-1px);border-color:rgba(255,255,255,.15);background:rgba(255,255,255,.065);box-shadow:0 13px 32px rgba(0,0,0,.16)}
        .worker-widget-mini:active{transform:translateY(0) scale(.99)}
        .worker-widget-mini-icon{display:grid;place-items:center;width:30px;height:30px;flex:0 0 30px;border-radius:9px;background:#fff;color:#18181b;font-size:8px;font-weight:950;letter-spacing:.02em}
        .worker-widget-mini-copy{display:grid;gap:2px;min-width:0;flex:1}
        .worker-widget-mini-copy strong{font-size:8.5px;line-height:1.1;color:#f4f4f5;font-weight:850}
        .worker-widget-mini-copy small{font-size:7px;line-height:1.2;color:#71717a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-widget-mini-state{width:6px;height:6px;flex:0 0 6px;border-radius:50%;background:#71717a;transition:transform .3s cubic-bezier(.22,1,.36,1),box-shadow .3s ease}
        .worker-widget-mini-state.is-running{background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.09),0 0 10px rgba(34,197,94,.35)}
        .worker-widget-mini-state.is-stopped{background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.07)}
        .worker-widget-chevron{width:20px;height:20px;display:grid;place-items:center;border-radius:6px;background:rgba(255,255,255,.045);color:#a1a1aa;font-size:11px;line-height:1;transition:transform .42s cubic-bezier(.22,1,.36,1),background .2s ease}
        .worker-widget-shell.is-open .worker-widget-chevron{transform:rotate(180deg)}
        .worker-widget-shell.is-collapsed .worker-widget-chevron{transform:rotate(0)}
        .worker-widget-shell.is-open .worker-widget-expanded .worker-fleet-row,.worker-widget-shell.is-open .worker-widget-expanded .worker-fleet-summary,.worker-widget-shell.is-open .worker-widget-expanded .worker-actions,.worker-widget-shell.is-open .worker-widget-expanded .worker-api-footer{animation:workerContentIn .44s cubic-bezier(.22,1,.36,1) both}
        .worker-widget-shell.is-open .worker-widget-expanded .worker-fleet-row:nth-child(2){animation-delay:.035s}
        .worker-widget-shell.is-open .worker-widget-expanded .worker-fleet-row:nth-child(3){animation-delay:.07s}
        @keyframes workerContentIn{from{opacity:.25;transform:translate3d(0,5px,0)}to{opacity:1;transform:translate3d(0,0,0)}}
        @media(prefers-reduced-motion:reduce){
            .worker-widget-expanded,.worker-widget-collapsed,.worker-collapse,.worker-widget-mini,.worker-widget-mini-state,.worker-widget-chevron{transition:none!important;animation:none!important}
        }

        .worker-status-card{position:relative;overflow:hidden}
        .worker-status-card:before{content:"";position:absolute;inset:auto -35px -55px auto;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(239,68,68,.12),transparent 68%);pointer-events:none}
        .api-live-dot.worker-live{background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.10),0 0 14px rgba(34,197,94,.38)}
        .api-live-dot.worker-offline{background:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.08)}
        .worker-mini-divider{height:1px;background:rgba(255,255,255,.08);margin:12px 0 10px}
        .worker-status-line{display:flex;align-items:center;gap:9px;min-width:0}
        .worker-status-copy{display:grid;gap:2px;min-width:0;flex:1}
        .worker-status-copy strong{font-size:10px;line-height:1.2;color:#f4f4f5;font-weight:850}
        .worker-status-copy span{font-size:8px;line-height:1.35;color:#71717a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:10px}
        .worker-meta-grid>div{min-width:0;padding:7px 8px;border:1px solid rgba(255,255,255,.06);border-radius:9px;background:rgba(255,255,255,.025)}
        .worker-meta-grid small{display:block;font-size:6.5px;line-height:1;color:#52525b;letter-spacing:.14em;font-weight:900;margin-bottom:4px}
        .worker-meta-grid strong{display:block;font-size:8px;line-height:1.15;color:#a1a1aa;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-actions{display:flex;gap:6px;margin-top:9px}
        .worker-action{height:28px;display:inline-flex;align-items:center;justify-content:center;gap:5px;border:1px solid rgba(255,255,255,.08);border-radius:8px;padding:0 9px;background:rgba(255,255,255,.035);color:#a1a1aa;font-family:inherit;font-size:7.5px;font-weight:850;cursor:pointer;transition:transform .18s ease,border-color .18s ease,background .18s ease,color .18s ease}
        .worker-action:hover{transform:translateY(-1px);background:rgba(255,255,255,.065);color:#f4f4f5;border-color:rgba(255,255,255,.14)}
        .worker-action:disabled{opacity:.55;cursor:wait;transform:none}
        .worker-action-start{flex:1;background:linear-gradient(135deg,#dc2626,#b91c1c);border-color:rgba(248,113,113,.25);color:#fff;box-shadow:0 8px 18px rgba(220,38,38,.18)}
        .worker-action-start:hover{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
        .worker-action-restart{flex:1}
        .worker-action-stop{color:#fca5a5;border-color:rgba(239,68,68,.16)}
        .worker-action-stop:hover{color:#fecaca;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.07)}
        .worker-spinner{display:inline-block;animation:workerSpin .8s linear infinite}
        .worker-started-at{margin-top:7px;font-size:6.8px;color:#52525b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-api-footer{display:flex;align-items:center;justify-content:space-between;gap:7px;margin-top:10px;padding-top:9px;border-top:1px solid rgba(255,255,255,.06);font-size:7px;color:#71717a}
        .worker-api-footer>span:first-child{display:inline-flex;align-items:center;gap:5px;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .worker-footer-dot{width:5px;height:5px;border-radius:50%;display:inline-block;background:#d97706;box-shadow:0 0 0 3px rgba(217,119,6,.08)}
        .worker-footer-dot.is-online{background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08)}
        .worker-step{font-size:6px;letter-spacing:.08em;color:#52525b;font-weight:900;white-space:nowrap}
        @keyframes workerSpin{to{transform:rotate(360deg)}}


    </style>

</div>




