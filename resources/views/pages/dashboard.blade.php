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
    public string $pendingStoreSearch = '';
    public string $pendingTemplateSearch = '';
    public ?int $pendingGenerationId = null;
    public array $dashboardData = [];
    public ?string $notificationReadAt = null;
    public array $generationHistory = [];
    public bool $generationHistoryLoaded = false;
    public string $historyStatus = 'all';
    public string $historyStore = 'all';
    public string $historySort = 'newest';
    public string $historySearch = '';
    public int $historyLimit = 40;
    public array $generationHistoryStats = [];






    public function mount(): void

    {

        $settings = \App\Models\OpenAiSetting::query()->first();



        if ($settings) {

            $this->imageModel = $settings->model ?: 'OpenAI Image Generation';

            $this->defaultAspectRatio = $settings->default_aspect_ratio ?: '1:1';

            $this->defaultQuality = $settings->default_quality ?: 'standard';

            $this->hasOpenAiKey = filled($settings->api_key);

        }



        $this->notificationReadAt = session('dashboard_notifications_read_at');
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
        if ($this->generationHistoryLoaded) {
            $latestHistoryId = (int) collect($this->generationHistory)->max('id');
            $latestGenerationId = (int) \App\Models\Generation::query()->max('id');
            if ($latestGenerationId > $latestHistoryId) {
                $this->refreshGenerationHistory();
            }
        }
        $this->refreshWorkerStatus();
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

    public function getGenerationHistorySummaryProperty(): array
    {
        $query = \App\Models\Generation::query()
            ->when($this->historyStatus !== 'all', fn ($q) => $q->where('status', $this->historyStatus))
            ->when($this->historyStore !== 'all', fn ($q) => $q->where('store_id', (int) $this->historyStore));

        $counts = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($value) => (int) $value);

        return [
            'total' => (int) $counts->sum(),
            'completed' => (int) $counts->only(['completed', 'success', 'succeeded'])->sum(),
            'active' => (int) $counts->only(['processing', 'queued'])->sum(),
            'failed' => (int) $counts->only(['failed', 'cancelled', 'canceled'])->sum(),
        ];
    }

    public function loadGenerationHistory(): void
    {
        $this->generationHistoryLoaded = true;
        $this->historyLimit = 40;
        $this->refreshGenerationHistory();
    }

    public function loadMoreGenerationHistory(): void
    {
        if (! $this->generationHistoryLoaded) {
            $this->loadGenerationHistory();
            return;
        }

        $this->historyLimit += 40;
        $this->refreshGenerationHistory();
    }

    public function unloadGenerationHistory(): void
    {
        $this->generationHistory = [];
        $this->generationHistoryStats = [];
        $this->generationHistoryLoaded = false;
        $this->historyLimit = 40;
    }

    public function updatedHistoryStatus(): void
    {
        if ($this->generationHistoryLoaded) $this->refreshGenerationHistory();
    }

    public function updatedHistoryStore(): void
    {
        if ($this->generationHistoryLoaded) $this->refreshGenerationHistory();
    }

    public function updatedHistorySort(): void
    {
        if ($this->generationHistoryLoaded) $this->refreshGenerationHistory();
    }

    public function updatedHistorySearch(): void
    {
        if ($this->generationHistoryLoaded) $this->refreshGenerationHistory();
    }

    private function refreshGenerationHistory(): void
    {
        $search = trim($this->historySearch);
        $query = \App\Models\Generation::query()
            ->with([
                'store:id,name,logo_path',
                'template:id,name',
                'generatedImages:id,generation_id,image_path,image_url,is_primary,is_favorite',
            ])
            ->when($this->historyStatus !== 'all', fn ($q) => $q->where('status', $this->historyStatus))
            ->when($this->historyStore !== 'all', fn ($q) => $q->where('store_id', (int) $this->historyStore))
            ->when($search !== '', function ($q) use ($search) {
                $keyword = '%' . $search . '%';
                $q->where(function ($nested) use ($keyword) {
                    $nested->where('prompt', 'like', $keyword)
                        ->orWhere('model', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhere('metadata', 'like', $keyword)
                        ->orWhereHas('store', fn ($store) => $store->where('name', 'like', $keyword))
                        ->orWhereHas('template', fn ($template) => $template->where('name', 'like', $keyword));
                });
            });

        $query->orderBy(match ($this->historySort) {
            'oldest' => 'created_at',
            default => 'created_at',
        }, $this->historySort === 'oldest' ? 'asc' : 'desc');

        if ($this->historySort === 'failed') {
            $query->orderByRaw("CASE WHEN status = 'failed' THEN 0 ELSE 1 END")->orderByDesc('created_at');
        } elseif ($this->historySort === 'completed') {
            $query->orderByRaw("CASE WHEN status IN ('completed','success','succeeded') THEN 0 ELSE 1 END")->orderByDesc('created_at');
        }

        $this->generationHistoryStats = [
            'total' => (clone $query)->reorder()->count(),
            'completed' => (clone $query)->reorder()->whereIn('status', ['completed', 'success', 'succeeded'])->count(),
            'active' => (clone $query)->reorder()->whereIn('status', ['processing', 'queued'])->count(),
            'failed' => (clone $query)->reorder()->whereIn('status', ['failed', 'cancelled', 'canceled'])->count(),
        ];

        $this->generationHistory = $query->limit($this->historyLimit)->get()->map(fn ($generation) => [
            'id' => $generation->id,
            'title' => data_get($generation->metadata, 'title') ?: ('Generation #' . $generation->id),
            'status' => $generation->status,
            'model' => \Illuminate\Support\Str::afterLast((string) $generation->model, '/'),
            'store' => $generation->store?->name ?? 'Store',
            'template' => $generation->template?->name ?? 'Template',
            'created_at' => $generation->created_at?->diffForHumans(),
            'created_at_raw' => $generation->created_at?->format('d M Y H:i'),
            'error' => data_get($generation->metadata, 'error')
                ?: data_get($generation->metadata, 'error_message')
                ?: data_get($generation->metadata, 'message')
                ?: null,
            'images' => $generation->generatedImages->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->image_url ?: ($image->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) : null),
                'favorite' => (bool) $image->is_favorite,
            ])->filter(fn ($image) => filled($image['url']))->values()->all(),
        ])->all();
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

        $workspaceStats = Store::query()
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->first();

        $workspaceTemplates = (int) \App\Models\Template::query()->count();

        $base = \App\Models\Generation::query()
            ->where('created_at', '>=', $from)
            ->when($this->dashboardStatus !== 'all', fn ($q) => $q->where('status', $this->dashboardStatus))
            ->when($this->dashboardStore !== 'all', fn ($q) => $q->where('store_id', (int) $this->dashboardStore));

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($value) => (int) $value);

        $total = (int) $statusCounts->sum();
        $completed = (int) $statusCounts->only(['completed', 'success', 'succeeded'])->sum();
        $processing = (int) ($statusCounts['processing'] ?? 0);
        $queued = (int) ($statusCounts['queued'] ?? 0);
        $failed = (int) ($statusCounts['failed'] ?? 0);

        $imageCount = (int) \App\Models\GeneratedImage::query()
            ->whereHas('generation', function ($q) use ($from) {
                $q->where('created_at', '>=', $from)
                    ->when($this->dashboardStatus !== 'all', fn ($nested) => $nested->where('status', $this->dashboardStatus))
                    ->when($this->dashboardStore !== 'all', fn ($nested) => $nested->where('store_id', (int) $this->dashboardStore));
            })
            ->count();

        $avgDuration = (clone $base)
            ->whereIn('status', ['completed', 'success', 'succeeded'])
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as average_seconds')
            ->value('average_seconds');

        $avgDuration = $avgDuration !== null ? (int) round((float) $avgDuration) : 0;

        $dailyRows = (clone $base)
            ->selectRaw('DATE(created_at) as bucket, COUNT(*) as aggregate')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('bucket')
            ->pluck('aggregate', 'bucket')
            ->map(fn ($value) => (int) $value)
            ->all();

        $trendDays = match ($this->dashboardTimeframe) {
            'today' => 12,
            '30d' => 10,
            '90d' => 12,
            'year' => 12,
            default => 7,
        };

        $trend = [];

        if ($this->dashboardTimeframe === 'today') {
            $hourly = (clone $base)
                ->selectRaw('FLOOR(HOUR(created_at) / 2) as bucket, COUNT(*) as aggregate')
                ->groupByRaw('FLOOR(HOUR(created_at) / 2)')
                ->pluck('aggregate', 'bucket')
                ->map(fn ($value) => (int) $value);

            for ($i = 0; $i < 12; $i++) {
                $startHour = $i * 2;
                $trend[] = [
                    'label' => str_pad((string) $startHour, 2, '0', STR_PAD_LEFT) . ':00',
                    'value' => (int) ($hourly[$i] ?? 0),
                ];
            }
        } else {
            $rangeDays = max(1, $from->diffInDays($now) + 1);
            $bucketSize = $this->dashboardTimeframe === 'year'
                ? 1
                : max(1, (int) ceil($rangeDays / $trendDays));

            for ($i = 0; $i < $trendDays; $i++) {
                $start = $this->dashboardTimeframe === 'year'
                    ? $now->copy()->startOfYear()->addMonths($i)
                    : $from->copy()->addDays($i * $bucketSize);

                if ($start->greaterThan($now)) {
                    break;
                }

                $end = $this->dashboardTimeframe === 'year'
                    ? $start->copy()->addMonth()
                    : $start->copy()->addDays($bucketSize);

                $value = 0;
                foreach ($dailyRows as $date => $count) {
                    $dateValue = \Carbon\Carbon::parse($date);
                    if ($dateValue->greaterThanOrEqualTo($start->copy()->startOfDay()) && $dateValue->lessThan($end->copy()->startOfDay())) {
                        $value += $count;
                    }
                }

                $trend[] = [
                    'label' => $this->dashboardTimeframe === 'year' ? $start->format('M') : ($bucketSize === 1 ? $start->format('d M') : $start->format('d M')),
                    'value' => $value,
                ];
            }
        }

        $recent = \App\Models\Generation::query()
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
            'stores_total' => (int) ($workspaceStats?->total ?? 0),
            'stores_active' => (int) ($workspaceStats?->active ?? 0),
            'templates_total' => $workspaceTemplates,
            'trend' => $trend,
            'max_trend' => max(1, max(array_column($trend, 'value') ?: [1])),
            'recent' => $recent->map(fn ($generation) => [
                'id' => $generation->id,
                'title' => data_get($generation->metadata, 'title') ?: ('Generation #' . $generation->id),
                'status' => $generation->status,
                'model' => \Illuminate\Support\Str::afterLast((string) $generation->model, '/'),
                'store' => $generation->store?->name ?? 'Store',
                'template' => $generation->template?->name ?? 'Template',
                'created_at' => $generation->created_at?->diffForHumans(),
                'created_at_raw' => $generation->created_at?->format('d M Y H:i'),
                'images' => $generation->generatedImages->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->image_url ?: ($image->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) : null),
                    'favorite' => (bool) $image->is_favorite,
                ])->filter(fn ($image) => filled($image['url']))->values()->all(),
            ])->all(),
        ];
    }

    public function getDashboardEngineProperty(): array
    {
        $cacheKey = 'dashboard-engine-health:' . (int) auth()->id();

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 8, function (): array {
            $keys = \App\Models\VercelGatewayApiKey::query();
            $totalKeys = (int) $keys->count();
            $activeKeys = (int) (clone $keys)
                ->where('is_active', true)
                ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
                ->where(function ($q) {
                    $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now());
                })
                ->count();
            $cooldownKeys = (int) (clone $keys)->where('status', 'cooldown')->count();
            $unavailableKeys = (int) (clone $keys)->whereIn('status', ['exhausted', 'invalid', 'disabled'])->count();
            $lastKeyUse = (clone $keys)
                ->whereNotNull('last_used_at')
                ->latest('last_used_at')
                ->first(['name', 'last_used_at', 'status']);

            $legacyConfigured = filled(\App\Models\OpenAiSetting::query()->value('api_key'));
            $envConfigured = trim((string) env('AI_GATEWAY_API_KEY', '')) !== '';
            $gatewayReady = $activeKeys > 0 || $legacyConfigured || $envConfigured;

            $agentConnected = class_exists(\App\Services\AI\Providers\AgentKitProvider::class);

            $apiMetrics = \App\Models\ActivityLog::query()
                ->where('category', 'api')
                ->where('created_at', '>=', now()->subHours(24))
                ->selectRaw('
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_requests,
                    SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as failed_requests,
                    AVG(CASE WHEN duration_ms IS NOT NULL THEN duration_ms END) as avg_duration_ms
                ')
                ->first();

            $latestApi = \App\Models\ActivityLog::query()
                ->where('category', 'api')
                ->latest('created_at')
                ->first(['status', 'title', 'created_at', 'http_status', 'duration_ms']);

            $latestGeneration = \App\Models\Generation::query()
                ->whereNotNull('model')
                ->latest('created_at')
                ->first(['model', 'created_at']);
            
            $recentModels = \App\Models\Generation::query()
                ->whereNotNull('model')
                ->where('model', '<>', '')
                ->latest('created_at')
                ->pluck('model')
                ->map(fn ($model) => \Illuminate\Support\Str::afterLast((string) $model, '/'))
                ->filter()
                ->unique()
                ->take(6)
                ->values()
                ->all();

            $recentProviders = \App\Models\Generation::query()
                ->whereNotNull('model')
                ->where('model', '<>', '')
                ->latest('created_at')
                ->pluck('model')
                ->map(function ($model) {
                    $model = (string) $model;

                    return str_contains($model, '/')
                        ? \Illuminate\Support\Str::before($model, '/')
                        : null;
                })
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all();

            $totalRequests = (int) ($apiMetrics?->total_requests ?? 0);
            $successfulRequests = (int) ($apiMetrics?->successful_requests ?? 0);
            $failedRequests = (int) ($apiMetrics?->failed_requests ?? 0);
            $successRate = $totalRequests > 0
                ? round(($successfulRequests / $totalRequests) * 100, 1)
                : null;

            return [
                'gateway' => [
                    'state' => $gatewayReady ? 'online' : 'offline',
                    'label' => $gatewayReady ? 'Gateway ready' : 'Gateway offline',
                    'keys_total' => $totalKeys,
                    'keys_active' => $activeKeys,
                    'keys_cooldown' => $cooldownKeys,
                    'keys_unavailable' => $unavailableKeys,
                    'legacy' => $legacyConfigured,
                    'env' => $envConfigured,                    'last_key' => $lastKeyUse?->name,
                    'last_used_at' => $lastKeyUse?->last_used_at?->diffForHumans(),
                    'last_status' => $lastKeyUse?->status,
                ],
                'provider' => [
                    'name' => 'Vercel AI Gateway',
                    'model' => $latestGeneration?->model
                        ? \Illuminate\Support\Str::afterLast($latestGeneration->model, '/')
                        : 'Belum ada generation',
                    'models' => $recentModels,
                    'providers' => $recentProviders,
                    'last_status' => $latestApi?->status,
                    'last_http' => $latestApi?->http_status,
                    'last_at' => $latestApi?->created_at?->diffForHumans(),
                    'last_duration_ms' => $latestApi?->duration_ms,
                    'requests_24h' => $totalRequests,
                    'success_24h' => $successfulRequests,
                    'failed_24h' => $failedRequests,
                    'success_rate_24h' => $successRate,
                    'avg_duration_ms_24h' => $apiMetrics?->avg_duration_ms !== null
                        ? (int) round((float) $apiMetrics->avg_duration_ms)
                        : null,
                ],
                'openai' => [
                    'state' => $legacyConfigured ? 'configured' : 'standby',
                    'label' => $legacyConfigured ? 'OpenAI configured' : 'OpenAI standby',
                    'detail' => $legacyConfigured ? 'Legacy OpenAI credential tersedia.' : 'Belum ada credential OpenAI langsung.',
                ],
                'agent' => [
                    'state' => $agentConnected ? 'connected' : 'standby',
                    'label' => $agentConnected ? 'AgentKit connected' : 'Agent layer standby',
                    'detail' => $agentConnected
                        ? 'Provider adapter AgentKit tersedia.'
                        : 'Siap ditambahkan tanpa mengubah generation flow saat ini.',
                ],
                'workers' => [
                    'state' => $this->workerStatus['healthy'] ? 'online' : 'offline',
                    'label' => $this->workerStatus['healthy'] ? 'Workers healthy' : 'Workers offline',
                    'running' => (int) ($this->workerStatus['running_count'] ?? 0),
                    'target' => (int) ($this->workerStatus['target_workers'] ?? 0),
                ],
                'checked_at' => now()->format('H:i:s'),
            ];
        });
    }

    public function getDashboardNotificationUnreadCountProperty(): int
    {
        $readAt = session('dashboard_notifications_read_at');
        return (int) \App\Models\ActivityLog::query()
            ->when($readAt, fn ($q) => $q->where('created_at', '>', $readAt))
            ->count();
    }

    public function markNotificationsRead(): void
    {
        $this->notificationReadAt = now()->format('Y-m-d H:i:s');
        session(['dashboard_notifications_read_at' => $this->notificationReadAt]);
        $this->dispatch('toast', type: 'success', title: 'Notifikasi dibaca', message: 'Semua notifikasi terbaru sudah ditandai dibaca.');
    }

    public function getDashboardNotificationsProperty(): array
    {
        return \App\Models\ActivityLog::query()
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'category', 'status', 'title', 'description', 'created_at'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'category' => $item->category,
                'status' => $item->status,
                'title' => $item->title,
                'description' => $item->description,
                'created_at' => $item->created_at?->diffForHumans(),
                'tone' => $this->notificationTone((string) $item->category, (string) $item->status, (string) $item->title),
                'icon' => $this->notificationIcon((string) $item->category, (string) $item->status, (string) $item->title),
            ])->all();
    }

    private function notificationTone(string $category, string $status, string $title): string
    {
        $haystack = strtolower($category . ' ' . $title);
        if ($status === 'error' || str_contains($haystack, 'fail') || str_contains($haystack, 'error')) return 'red';
        if (str_contains($haystack, 'worker') || str_contains($haystack, 'queue')) return 'amber';
        if (str_contains($haystack, 'api') || str_contains($haystack, 'gateway') || str_contains($haystack, 'key')) return 'violet';
        if ($status === 'success') return 'green';
        return 'blue';
    }

    private function notificationIcon(string $category, string $status, string $title): string
    {
        $haystack = strtolower($category . ' ' . $title);
        if ($status === 'error' || str_contains($haystack, 'fail') || str_contains($haystack, 'error')) return 'alert';
        if (str_contains($haystack, 'worker') || str_contains($haystack, 'queue')) return 'worker';
        if (str_contains($haystack, 'api') || str_contains($haystack, 'gateway') || str_contains($haystack, 'key')) return 'key';
        if ($status === 'success') return 'check';
        return 'info';
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
        $results = [];

        $quickNavigation = match (strtolower($term)) {
            'store', 'stores' => ['type' => 'Navigate', 'label' => 'Stores', 'meta' => 'Buka Store Management', 'action' => 'stores', 'id' => 0],
            'template', 'templates' => ['type' => 'Navigate', 'label' => 'Templates', 'meta' => 'Buka Template Library', 'action' => 'templates', 'id' => 0],
            'generation', 'generations', 'generator' => ['type' => 'Navigate', 'label' => 'Generations', 'meta' => 'Buka Generator & history', 'action' => 'generator', 'id' => 0],
            'setting', 'settings' => ['type' => 'Navigate', 'label' => 'Settings', 'meta' => 'Buka System Settings', 'action' => 'settings', 'id' => 0],
            'activity', 'activities', 'log', 'logs' => ['type' => 'Navigate', 'label' => 'Activity Logs', 'meta' => 'Buka System Activity', 'action' => 'activity', 'id' => 0],
            default => null,
        };

        if ($quickNavigation) {
            $results[] = $quickNavigation;
        }

        $stores = Store::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('brand_name', 'like', $keyword)
                    ->orWhere('marketplace', 'like', $keyword);
            })
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'name', 'marketplace', 'brand_name']);

        foreach ($stores as $item) {
            $results[] = [
                'type' => 'Store', 'label' => $item->name,
                'meta' => trim(($item->brand_name ?: $item->marketplace ?: 'Marketplace store')),
                'action' => 'stores', 'id' => $item->id,
            ];
        }

        $templates = \App\Models\Template::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('slug', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword);
            })
            ->with('store:id,name')
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'store_id', 'name', 'slug']);

        foreach ($templates as $item) {
            $results[] = [
                'type' => 'Template', 'label' => $item->name,
                'meta' => 'Template · ' . ($item->store?->name ?? 'Store'),
                'action' => 'templates', 'id' => $item->id,
            ];
        }

        $apiKeys = \App\Models\VercelGatewayApiKey::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('status', 'like', $keyword);
            })
            ->orderBy('priority')
            ->orderBy('name')
            ->limit(3)
            ->get(['id', 'name', 'status', 'is_active', 'last_used_at']);

        foreach ($apiKeys as $item) {
            $state = $item->is_active ? ucfirst((string) $item->status) : 'Disabled';
            $results[] = [
                'type' => 'API Key',
                'label' => $item->name,
                'meta' => 'Vercel Gateway · ' . $state . ' · ' . ($item->last_used_at?->diffForHumans() ?? 'belum digunakan'),
                'action' => 'settings',
                'id' => $item->id,
            ];
        }

        $generations = \App\Models\Generation::query()
            ->where(function ($q) use ($keyword) {
                $q->where('id', 'like', $keyword)
                    ->orWhere('prompt', 'like', $keyword)
                    ->orWhere('model', 'like', $keyword)
                    ->orWhere('status', 'like', $keyword)
                    ->orWhere('metadata', 'like', $keyword);
            })
            ->with(['store:id,name', 'template:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'store_id', 'template_id', 'model', 'status', 'created_at']);

        foreach ($generations as $item) {
            $results[] = [
                'type' => 'Generation', 'label' => 'Generation #' . $item->id,
                'meta' => ucfirst((string) $item->status) . ' · ' . \Illuminate\Support\Str::afterLast((string) $item->model, '/') . ' · ' . ($item->store?->name ?? 'Store'),
                'action' => 'generator', 'id' => $item->id,
            ];
        }

        $activities = \App\Models\ActivityLog::query()
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword)
                    ->orWhere('action', 'like', $keyword)
                    ->orWhere('category', 'like', $keyword);
            })
            ->latest('created_at')
            ->limit(4)
            ->get(['id', 'title', 'status', 'category', 'created_at']);

        foreach ($activities as $item) {
            $results[] = [
                'type' => 'Activity', 'label' => $item->title ?: 'System activity',
                'meta' => strtoupper((string) $item->category) . ' · ' . ucfirst((string) $item->status) . ' · ' . $item->created_at?->diffForHumans(),
                'action' => 'activity', 'id' => $item->id,
            ];
        }

        return array_slice($results, 0, 12);
    }

    public function navigateGlobalSearch(string $action, ?int $id = null): void
    {
        $term = trim($this->globalSearch);
        $this->globalSearch = '';

        $this->pendingStoreSearch = '';
        $this->pendingTemplateSearch = '';
        $this->pendingGenerationId = null;

        if ($action === 'stores') {
            // Pure navigation ("Stores") must open the full library.
            // Only an actual Store result should carry a search term.
            $this->pendingStoreSearch = $id
                ? (string) (Store::query()->whereKey($id)->value('name') ?: '')
                : '';
            $this->openStore(true);
            return;
        }

        if ($action === 'templates') {
            // Pure navigation ("Templates") must open the full library.
            // Only an actual Template result should carry a search term.
            $this->pendingTemplateSearch = $id
                ? (string) (\App\Models\Template::query()->whereKey($id)->value('name') ?: '')
                : '';
            $this->openTemplates(true);
            return;
        }

        if ($action === 'generator') {
            $this->pendingGenerationId = $id ?: null;
            $this->openGenerator(true);
            return;
        }

        if ($action === 'activity') {
            $this->activitySearch = $term;
            $this->openSettings();
            return;
        }

        if ($action === 'settings') {
            $this->openSettings();
            return;
        }

        $this->openDashboard();
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



    public function openStore(bool $preserveSearch = false): void
    {
        if (! $preserveSearch) {
            $this->pendingStoreSearch = '';
        }

        $this->activeSection = 'stores';



        $this->dispatch('workspace-section-changed', section: 'stores');

    }



    public function openDashboard(): void
    {
        $this->pendingStoreSearch = '';
        $this->pendingTemplateSearch = '';
        $this->pendingGenerationId = null;
        $this->activeSection = 'dashboard';



        $this->dispatch('workspace-section-changed', section: 'dashboard');

    }



    public function openTemplates(bool $preserveSearch = false): void
    {
        if (! $preserveSearch) {
            $this->pendingTemplateSearch = '';
        }

        $this->activeSection = 'templates';

    }



    public function openGenerator(bool $preserveFocus = false): void
    {
        if (! $preserveFocus) {
            $this->pendingGenerationId = null;
        }

        $this->activeSection = 'generator';

    }



    public function openSettings(): void

    {

        $this->activeSection = 'settings-general';

        $this->activeTab = 'general';

        $this->loadActivityLogs();
        $this->loadOpenAiLogs();
        $this->loadVercelGatewayLogs();

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
            :class="workerOpen ? 'is-open' : 'is-collapsed'"            x-data="{
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
                    <svg class="top-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input type="text" wire:model.live.debounce.450ms="globalSearch" placeholder="Search workspace..." aria-label="Search workspace...">
                    <span class="search-shortcut">Ctrl K</span>

                    @if(mb_strlen(trim($globalSearch)) >= 2)
                        <div class="rms-global-search-results">
                            <div class="rms-global-search-head">
                                <div>
                                    <span class="rms-global-search-eyebrow">WORKSPACE SEARCH</span>
                                    <strong>Search results</strong>
                                </div>
                                <span class="rms-global-search-count">{{ count($this->globalSearchResults) }} results</span>
                            </div>

                            @if(count($this->globalSearchResults))
                                <div class="rms-global-search-list">
                                    @foreach($this->globalSearchResults as $result)
                                        @php
                                            $searchType = strtolower((string) ($result['type'] ?? 'result'));
                                            $searchIcon = match ($searchType) {
                                                'navigate' => '↗',
                                                'store' => '◆',
                                                'template' => '◇',
                                                'generation' => '▣',
                                                'activity' => '◌',
                                                'api key' => '⌁',
                                                default => '•',
                                            };
                                        @endphp
                                        <button type="button" wire:click="navigateGlobalSearch('{{ $result['action'] }}', {{ (int) ($result['id'] ?? 0) }})" class="rms-global-search-item">
                                            <span class="rms-search-result-icon rms-search-type-{{ str_replace(' ', '-', $searchType) }}">{{ $searchIcon }}</span>
                                            <span class="rms-search-result-copy">
                                                <span class="rms-search-result-topline">
                                                    <strong>{{ $result['label'] }}</strong>
                                                    <small>{{ strtoupper($result['type']) }}</small>
                                                </span>
                                                <span class="rms-search-result-meta">{{ $result['meta'] }}</span>
                                            </span>
                                            <span class="rms-search-result-arrow">→</span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="rms-global-search-empty">
                                    <span class="rms-search-empty-icon">⌕</span>
                                    <strong>No results found</strong>
                                    <span>Try a store, template, generation, activity, or API key.</span>
                                </div>
                            @endif

                            <div class="rms-global-search-foot">
                                <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                                <span><kbd>Enter</kbd> Open</span>
                                <span><kbd>Esc</kbd> Close</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- GLOBAL SEARCH KEYBOARD SHORTCUTS --}}
                <script>
                    if (!window.__rmsGlobalSearchShortcutBound) {
                        window.__rmsGlobalSearchShortcutBound = true;
                        document.addEventListener('keydown', (event) => {
                            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                                event.preventDefault();
                                document.querySelector('.top-search input')?.focus();
                            }

                            if (event.key === 'Escape') {
                                const input = document.querySelector('.top-search input');
                                if (document.activeElement === input) input.blur();
                            }
                        });
                    }
                </script>

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

                        <span class="notification-dot {{ $this->dashboardNotificationUnreadCount === 0 ? 'is-hidden' : '' }}"></span>

                        <span class="notification-count {{ $this->dashboardNotificationUnreadCount === 0 ? 'is-hidden' : '' }}">{{ min(99, $this->dashboardNotificationUnreadCount) }}</span>

                    </button>



                    <div class="notification-dropdown" id="notificationDropdown" aria-hidden="true">
                        <div class="notification-dropdown-head">
                            <div><span class="notification-eyebrow">SYSTEM CENTER</span><h3>Notifikasi</h3><p>Update terbaru dari workspace kamu.</p></div>
                            <button type="button" class="notification-mark-read" id="notificationMarkRead" wire:click="markNotificationsRead">Tandai dibaca</button>
                        </div>
                        <div class="notification-list">
                            @forelse($this->dashboardNotifications as $notification)
                                <button type="button" class="notification-item">
                                    <span class="notification-item-icon notification-tone-{{ $notification['tone'] }}">
                                        @if($notification['icon'] === 'check')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/></svg>
                                        @elseif($notification['icon'] === 'alert')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4 3.5 19h17L12 4Z"/><path d="M12 9v4m0 3h.01"/></svg>
                                        @elseif($notification['icon'] === 'key')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="15" r="3"/><path d="m10.2 12.8 8.3-8.3 2 2-2 2-1.7-1.7-2.2 2.2 1.6 1.6-2 2"/></svg>
                                        @elseif($notification['icon'] === 'worker')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6m-6 3h6m-6 3h3"/></svg>
                                        @else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>@endif
                                    </span>
                                    <span class="notification-item-content">
                                        <strong>{{ $notification['title'] }}</strong>
                                        <span>{{ Str::limit($notification['description'], 110) }}</span>
                                        <small>{{ $notification['created_at'] }}</small>
                                    </span>
                                </button>
                            @empty
                                <div class="notification-empty">Belum ada notifikasi.</div>
                            @endforelse
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





            @php
    $dashboardPeriodLabels = ['today' => 'Hari ini', '7d' => '7 hari', '30d' => '30 hari', '90d' => '90 hari', 'year' => 'Tahun ini'];
    $dashboardStatusLabels = ['all' => 'Semua status', 'completed' => 'Completed', 'processing' => 'Processing', 'queued' => 'Queued', 'failed' => 'Failed'];
    $historyStatusLabels = $dashboardStatusLabels;
    $historySortLabels = ['newest' => 'Terbaru', 'oldest' => 'Terlama', 'completed' => 'Completed dulu', 'failed' => 'Failed dulu'];
@endphp
<div wire:poll.visible.10s="refreshDashboard" class="rms-dashboard-live">
                <section class="rms-dashboard-toolbar reveal reveal-3">
                    <div><span class="section-kicker">REALTIME OPERATIONS</span><h2>Creative activity</h2><p>Monitoring generation, queue, output, dan performa creative engine secara realtime.</p></div>
                    <div class="rms-dashboard-filters">
                        <div class="rms-filter-dropdown" x-data="{ open:false }" :class="{ 'is-open': open }" @click.outside="open=false">
                            <button type="button" class="rms-filter-trigger" @click="open=!open" :aria-expanded="open.toString()">
                                <span class="rms-filter-copy"><small>PERIOD</small><strong>{{ $dashboardPeriodLabels[$dashboardTimeframe] ?? '7 hari' }}</strong></span><span class="rms-filter-chevron">⌄</span>
                            </button>
                            <div class="rms-filter-menu" x-show="open" x-transition.opacity x-cloak>
                                @foreach(['today'=>'Hari ini','7d'=>'7 hari','30d'=>'30 hari','90d'=>'90 hari','year'=>'Tahun ini'] as $value => $label)
                                    <button type="button" class="rms-filter-option {{ $dashboardTimeframe === $value ? 'is-selected' : '' }}" wire:click="$set('dashboardTimeframe','{{ $value }}')" @click="open=false"><span>{{ $label }}</span><b>✓</b></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="rms-filter-dropdown" x-data="{ open:false }" :class="{ 'is-open': open }" @click.outside="open=false">
                            <button type="button" class="rms-filter-trigger" @click="open=!open" :aria-expanded="open.toString()">
                                <span class="rms-filter-copy"><small>STATUS</small><strong>{{ $dashboardStatusLabels[$dashboardStatus] ?? 'Semua status' }}</strong></span><span class="rms-filter-chevron">⌄</span>
                            </button>
                            <div class="rms-filter-menu" x-show="open" x-transition.opacity x-cloak>
                                @foreach(['all'=>'Semua status','completed'=>'Completed','processing'=>'Processing','queued'=>'Queued','failed'=>'Failed'] as $value => $label)
                                    <button type="button" class="rms-filter-option {{ $dashboardStatus === $value ? 'is-selected' : '' }}" wire:click="$set('dashboardStatus','{{ $value }}')" @click="open=false"><span>{{ $label }}</span><b>✓</b></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="rms-filter-dropdown" x-data="{ open:false }" :class="{ 'is-open': open }" @click.outside="open=false">
                            <button type="button" class="rms-filter-trigger" @click="open=!open" :aria-expanded="open.toString()">
                                <span class="rms-filter-copy"><small>STORE</small><strong>{{ $dashboardStore === 'all' ? 'Semua store' : ($this->dashboardStores->firstWhere('id', (int) $dashboardStore)?->name ?? 'Store') }}</strong></span><span class="rms-filter-chevron">⌄</span>
                            </button>
                            <div class="rms-filter-menu" x-show="open" x-transition.opacity x-cloak>
                                <button type="button" class="rms-filter-option {{ $dashboardStore === 'all' ? 'is-selected' : '' }}" wire:click="$set('dashboardStore','all')" @click="open=false"><span>Semua store</span><b>✓</b></button>
                                @foreach($this->dashboardStores as $store)
                                    <button type="button" class="rms-filter-option {{ (string) $dashboardStore === (string) $store->id ? 'is-selected' : '' }}" wire:click="$set('dashboardStore','{{ $store->id }}')" @click="open=false"><span>{{ $store->name }}</span><b>✓</b></button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
                @php $d=$this->dashboardData; $trend=$d['trend']??[]; $maxTrend=max(1,(int)($d['max_trend']??1)); @endphp
                <section class="rms-dashboard-kpis reveal reveal-3">
                    <article class="rms-kpi rms-kpi-primary"><div class="rms-kpi-top"><span class="rms-kpi-icon">✦</span><span class="rms-kpi-live"><i></i> LIVE</span></div><span class="rms-kpi-label">Generations</span><strong>{{ number_format($d['total']??0) }}</strong><small>{{ number_format($d['images']??0) }} visual tersimpan</small></article>
                    <article class="rms-kpi"><div class="rms-kpi-top"><span class="rms-kpi-icon success">✓</span><span class="rms-kpi-tag">SUCCESS</span></div><span class="rms-kpi-label">Completed</span><strong>{{ number_format($d['completed']??0) }}</strong><small>{{ $d['success_rate']??0 }}% success rate</small></article>
                    <article class="rms-kpi"><div class="rms-kpi-top"><span class="rms-kpi-icon amber">◷</span><span class="rms-kpi-tag">QUEUE</span></div><span class="rms-kpi-label">Processing / Queued</span><strong>{{ ($d['processing']??0)+($d['queued']??0) }}</strong><small>{{ $d['processing']??0 }} processing · {{ $d['queued']??0 }} queued</small></article>
                    <article class="rms-kpi"><div class="rms-kpi-top"><span class="rms-kpi-icon danger">!</span><span class="rms-kpi-tag">ERROR</span></div><span class="rms-kpi-label">Failed</span><strong>{{ number_format($d['failed']??0) }}</strong><small>Avg. {{ $d['avg_duration'] ? $d['avg_duration'].' detik' : '—' }} / generation</small></article>
                </section>
                <section class="rms-workspace-health-strip reveal reveal-3">
                    <div><span class="rms-health-mini-icon">▣</span><div><small>ACTIVE STORES</small><strong>{{ $d['stores_active'] ?? 0 }} / {{ $d['stores_total'] ?? 0 }}</strong></div></div>
                    <div><span class="rms-health-mini-icon violet">▦</span><div><small>VISUAL TEMPLATES</small><strong>{{ $d['templates_total'] ?? 0 }}</strong></div></div>
                    <div><span class="rms-health-mini-icon green">◉</span><div><small>OUTPUTS</small><strong>{{ number_format($d['images'] ?? 0) }}</strong></div></div>
                    <div><span class="rms-health-mini-icon amber">◷</span><div><small>AVG. GENERATION</small><strong>{{ $d['avg_duration'] ? $d['avg_duration'].'s' : '—' }}</strong></div></div>
                </section>

                @php $engine = $this->dashboardEngine; @endphp
                <section class="rms-engine-health-panel reveal reveal-3" x-data="{ engineOpen: window.innerWidth > 760 }" :class="{ 'is-collapsed': !engineOpen }">
                    <div class="rms-engine-health-head">
                        <div>
                            <span class="section-kicker">AI ENGINE HEALTH</span>
                            <h3>Generation infrastructure</h3>
                            <p>Gateway, model routing, provider, dan agent layer dipantau dari satu control center.</p>
                        </div>
                        <div class="rms-engine-health-meta">
                            <span class="rms-health-pill {{ $engine['gateway']['state'] === 'online' ? 'is-ok' : 'is-off' }}"><i></i>{{ $engine['gateway']['state'] === 'online' ? 'SYSTEM READY' : 'ATTENTION' }}</span>
                            <small>Checked {{ $engine['checked_at'] }}</small>
                        </div>
                        <button type="button" class="rms-engine-mobile-toggle" @click="engineOpen = !engineOpen" :aria-expanded="engineOpen.toString()">
                            <span><i></i><strong x-text="engineOpen ? 'Hide engine details' : 'Show engine details'"></strong></span>
                            <b :class="{ 'is-open': engineOpen }">⌄</b>
                        </button>
                    </div>

                    <div class="rms-engine-health-grid">