<?php

namespace App\Livewire\Dashboard\Settings;

use App\Livewire\Concerns\ManagesAgentAiCredentials;
use App\Models\AiProviderSetting;
use App\Models\ActivityLog;
use App\Models\VercelGatewayApiKey;
use App\Models\Generation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use ManagesAgentAiCredentials;

    public string $activeTab = 'general';

    public string $activeProvider = 'vercel';
    public string $fallbackProvider = '';
    public bool $allowProviderFallback = false;
    public bool $emergencyFallback = false;

    public string $apiKey = '';

    public string $imageModel = 'OpenAI Image Generation';

    public string $defaultAspectRatio = '1:1';

    public string $defaultQuality = 'high';

    public bool $showApiKey = false;

    public bool $testingConnection = false;

    public bool $saved = false;
    public string $newVercelKeyName = '';
    public string $newVercelApiKey = '';
    public array $vercelGatewayKeys = [];
    public array $vercelGatewayLogs = [];
    public array $openAiLogs = [];
    public array $activityLogs = [];
    public string $activitySearch = '';
    public string $activityCategory = 'all';
    public string $activityStatus = 'all';
    public string $activityTimeframe = 'all';
    public int $activityLogCount = 0;
    public int $filteredActivityLogCount = 0;

    public function mount(string $initialTab = 'general'): void
    {
        $this->activeTab = in_array($initialTab, ['provider', 'openai', 'activity', 'server'], true)
            ? ($initialTab === 'openai' ? 'provider' : $initialTab)
            : 'general';

        $this->loadAiProviderSettings();
        $this->loadAgentAiCredentials();
        $this->refreshVercelGatewayKeys();
        $this->refreshActivityLogs();
        if ($this->activeTab === 'server') {
            $this->refreshServerMetrics();
        }
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['general', 'provider', 'openai', 'activity', 'server'], true)
            ? ($tab === 'openai' ? 'provider' : $tab)
            : 'general';

    }

    #[On('settings-tab-changed')]
    public function syncTab(string $tab): void
    {
        $this->selectTab($tab);
    }


    public function loadAiProviderSettings(): void
    {
        $setting = AiProviderSetting::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [Auth::id()])
            ->first();

        if ($setting) {
            $this->activeProvider = $setting->active_provider ?: 'vercel';
            $this->fallbackProvider = (string) ($setting->fallback_provider ?? '');
            $this->allowProviderFallback = (bool) $setting->allow_provider_fallback;
            $this->emergencyFallback = (bool) $setting->emergency_fallback;
        }
    }

    public function saveAiProviderConfiguration(): void
    {
        $this->validate([
            'activeProvider' => ['required', 'in:vercel,agentkit'],
            'fallbackProvider' => ['nullable', 'in:vercel,agentkit'],
        ]);

        if ($this->fallbackProvider === $this->activeProvider) {
            $this->fallbackProvider = '';
        }

        AiProviderSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'active_provider' => $this->activeProvider,
                'fallback_provider' => $this->fallbackProvider ?: null,
                'allow_provider_fallback' => $this->allowProviderFallback,
                'emergency_fallback' => $this->emergencyFallback,
            ]
        );

        $this->saved = true;
        $this->dispatch(
            'toast',
            type: 'success',
            title: 'AI provider updated',
            message: 'Provider aktif berhasil diperbarui.'
        );
    }


    public function refreshVercelGatewayKeys(): void
    {
        $this->vercelGatewayKeys = VercelGatewayApiKey::query()
            ->where(fn ($q) => $q->where('user_id', Auth::id())->orWhereNull('user_id'))
            ->orderBy('priority')->orderBy('id')->get()
            ->map(fn (VercelGatewayApiKey $key) => [
                'id' => $key->id, 'name' => $key->name, 'status' => $key->status,
                'masked_key' => $key->masked_key, 'request_count' => $key->request_count,
                'success_count' => $key->success_count, 'failure_count' => $key->failure_count,
                'last_used_at' => optional($key->last_used_at)->diffForHumans(),
                'last_error_type' => $key->last_error_type, 'last_error' => $key->last_error,
            ])->values()->all();
    }

    public function addVercelGatewayKey(): void
    {
        $this->validate(['newVercelKeyName' => ['required','string','max:120'], 'newVercelApiKey' => ['required','string','min:10','max:500']]);
        $key = VercelGatewayApiKey::create([
            'user_id' => Auth::id(), 'name' => trim($this->newVercelKeyName),
            'api_key' => trim($this->newVercelApiKey), 'status' => 'active', 'is_active' => true, 'priority' => 100,
        ]);
        $this->writeSettingsActivity('provider_credential_added','Vercel API key added','Vercel credential pool menerima credential baru.','success',['provider'=>'vercel','credential_id'=>$key->id,'credential_name'=>$key->name]);
        $this->newVercelKeyName = ''; $this->newVercelApiKey = ''; $this->refreshVercelGatewayKeys();
        $this->toast('success','Vercel key added','Credential terenkripsi dan masuk ke pool.');
    }

    public function removeVercelGatewayKey(int $id): void
    {
        $key = VercelGatewayApiKey::query()->whereKey($id)->where('user_id',Auth::id())->firstOrFail();
        $name=$key->name; $key->delete();
        $this->writeSettingsActivity('provider_credential_removed','Vercel API key removed','Credential dihapus dari pool Vercel.','warning',['provider'=>'vercel','credential_name'=>$name]);
        $this->refreshVercelGatewayKeys(); $this->toast('success','Vercel key removed','Credential berhasil dihapus dari pool.');
    }

    public function toggleVercelGatewayKey(int $id): void
    {
        $key=VercelGatewayApiKey::query()->whereKey($id)->where('user_id',Auth::id())->firstOrFail();
        $active=!$key->is_active; $key->update(['is_active'=>$active,'status'=>$active?'active':'disabled','cooldown_until'=>null]);
        $this->refreshVercelGatewayKeys();
    }

    public function resetVercelGatewayKey(int $id): void
    {
        $key=VercelGatewayApiKey::query()->whereKey($id)->where('user_id',Auth::id())->firstOrFail();
        $key->update(['status'=>'active','is_active'=>true,'last_error_type'=>null,'last_error'=>null,'last_http_status'=>null,'cooldown_until'=>null]);
        $this->refreshVercelGatewayKeys(); $this->toast('success','Key reset','Status credential dikembalikan menjadi active.');
    }

    public function testVercelGatewayKey(int $id): void
    {
        $key=VercelGatewayApiKey::query()->whereKey($id)->where(fn($q)=>$q->where('user_id',Auth::id())->orWhereNull('user_id'))->firstOrFail();
        $started=microtime(true);
        try {
            $response=Http::withToken($key->api_key)->acceptJson()->connectTimeout(8)->timeout(15)->get('https://ai-gateway.vercel.sh/v1/models');
            $ok=$response->successful(); $status=$ok?'active':($response->status()===429?'cooldown':($response->status()>=500?'error':'invalid'));
            $key->update(['status'=>$status,'is_active'=>$status!=='invalid','last_http_status'=>$response->status(),'last_used_at'=>now(),'last_success_at'=>$ok?now():$key->last_success_at,'last_failure_at'=>$ok?$key->last_failure_at:now(),'last_error_type'=>$ok?null:'connection_test_failed','last_error'=>$ok?null:mb_substr($response->body(),0,1000),'cooldown_until'=>$status==='cooldown'?now()->addMinute():null]);
            $this->writeSettingsActivity('provider_connection_test','Vercel connection test '.($ok?'passed':'failed'),'Connection test untuk '.$key->name.'.',$ok?'success':'error',['provider'=>'vercel','credential_id'=>$key->id,'credential_name'=>$key->name,'http_status'=>$response->status(),'duration_ms'=>(int)round((microtime(true)-$started)*1000)]);
            $this->toast($ok?'success':'error',$ok?'Connection healthy':'Connection failed',$ok?'Vercel Gateway merespons dengan baik.':'Credential tidak lolos connection test.');
        } catch (\Throwable $e) {
            $key->update(['status'=>'error','last_error_type'=>'connection_exception','last_error'=>mb_substr($e->getMessage(),0,1000),'last_failure_at'=>now()]);
            $this->toast('error','Connection failed','Gateway tidak dapat dihubungi saat ini.');
        }
        $this->refreshVercelGatewayKeys();
    }

    public function testAllVercelGatewayKeys(): void
    {
        foreach(VercelGatewayApiKey::query()->where('is_active',true)->where(fn($q)=>$q->where('user_id',Auth::id())->orWhereNull('user_id'))->pluck('id') as $id) $this->testVercelGatewayKey((int)$id);
        $this->refreshVercelGatewayKeys();
    }

    public function refreshActivityLogs(): void
    {
        $query=ActivityLog::query()->with('user')->latest('id')->limit(150);
        if($this->activitySearch!==''){ $term='%'.trim($this->activitySearch).'%'; $query->where(fn($q)=>$q->where('title','like',$term)->orWhere('description','like',$term)->orWhere('action','like',$term)); }
        if($this->activityCategory!=='all') $query->where('category',$this->activityCategory);
        if($this->activityStatus!=='all') $query->where('status',$this->activityStatus);
        if($this->activityTimeframe!=='all'){ $from=match($this->activityTimeframe){ 'today'=>now()->startOfDay(),'7d'=>now()->subDays(7),'30d'=>now()->subDays(30),default=>null }; if($from) $query->where('created_at','>=',$from); }
        $rows=$query->get(); $this->activityLogCount=ActivityLog::count(); $this->filteredActivityLogCount=$rows->count();
        $this->activityLogs=$rows->map(function(ActivityLog $log){ $m=is_array($log->metadata)?$log->metadata:[]; return ['id'=>$log->id,'category'=>$log->category?:'system','action'=>$log->action,'status'=>$log->status?:'info','title'=>$log->title?:($log->action?:'Activity'),'description'=>$log->description?:'','user_name'=>optional($log->user)->name?:'System','created_at'=>optional($log->created_at)->diffForHumans(),'http_status'=>$m['http_status']??null,'duration_ms'=>$m['duration_ms']??null,'entity_type'=>$m['entity_type']??null,'entity_id'=>$m['entity_id']??null,'copy_text'=>sprintf('[%s] %s — %s',optional($log->created_at)->toDateTimeString(),$log->title?:$log->action,$log->description?:'')]; })->values()->all();
    }

    public function clearActivityLogs(): void
    {
        ActivityLog::query()->delete(); $this->refreshActivityLogs(); $this->toast('success','Activity cleared','Global activity log berhasil dibersihkan.');
    }

    private function writeSettingsActivity(string $action,string $title,string $description,string $status='info',array $metadata=[]): void
    {
        ActivityLog::create(['user_id'=>Auth::id(),'action'=>$action,'category'=>'system','status'=>$status,'title'=>$title,'description'=>$description,'metadata'=>$metadata]);
    }

    private function toast(string $type,string $title,string $message): void
    {
        $this->dispatch('toast',type:$type,title:$title,message:$message);
    }

    public function saveGeneral(): void
    {
        $this->saved = true;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Pengaturan tersimpan',
            message: 'Preferensi workspace berhasil diperbarui.'
        );
    }

    public function saveOpenAi(): void
    {
        $this->saved = true;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'OpenAI API disimpan',
            message: 'Konfigurasi API berhasil disimpan.'
        );
    }

    public function testConnection(): void
    {
        $this->testingConnection = true;

        // Backend OpenAI connection will be wired in the next step.
        $this->dispatch(
            'toast',
            type: 'info',
            title: 'Connection test',
            message: 'UI test connection siap dihubungkan ke OpenAI API.'
        );

        $this->testingConnection = false;
    }

    public array $serverMetrics = [];

    public function refreshServerMetrics(): void
    {
        $read = static function (string $path): string {
            return is_readable($path) ? (string) file_get_contents($path) : '';
        };
        $mem = [];
        foreach (preg_split('/\n/', $read('/proc/meminfo')) as $line) {
            if (preg_match('/^(MemTotal|MemAvailable|MemFree|Buffers|Cached|SwapTotal|SwapFree):\s+(\d+)/', $line, $m)) {
                $mem[$m[1]] = (int) $m[2] * 1024;
            }
        }
        $total = $mem['MemTotal'] ?? 0;
        $available = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 0);
        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $uptimeText = $read('/proc/uptime');
        $uptimeSeconds = (int) (explode(' ', trim($uptimeText))[0] ?? 0);
        $services = [];
        foreach (['nginx', 'php8.5-fpm', 'mysql', 'redis-server', 'supervisor'] as $service) {
            $services[$service] = null;
            if (function_exists('shell_exec')) {
                $output = @shell_exec('systemctl is-active ' . escapeshellarg($service) . ' 2>/dev/null');
                if (is_string($output) && trim($output) !== '') {
                    $services[$service] = trim($output) === 'active';
                }
            }
        }
        $this->serverMetrics = [
            'hostname' => gethostname() ?: 'Server',
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
            'time' => now()->toIso8601String(),
            'uptime_seconds' => $uptimeSeconds,
            'load' => array_map(static fn ($v) => round((float) $v, 2), $load),
            'cpu_cores' => function_exists('shell_exec') ? (int) trim((string) @shell_exec('nproc 2>/dev/null')) ?: 1 : 1,
            'memory_total' => $total,
            'memory_available' => $available,
            'memory_used' => max(0, $total - $available),
            'swap_total' => $mem['SwapTotal'] ?? 0,
            'swap_used' => max(0, ($mem['SwapTotal'] ?? 0) - ($mem['SwapFree'] ?? 0)),
            'disk_total' => $diskTotal ?: 0,
            'disk_free' => $diskFree ?: 0,
            'disk_used' => max(0, ($diskTotal ?: 0) - ($diskFree ?: 0)),
            'services' => $services,
            'queue_workers' => function_exists('shell_exec') ? count(preg_split('/\n/', trim((string) @shell_exec("pgrep -af 'artisan queue:work' 2>/dev/null")) , -1, PREG_SPLIT_NO_EMPTY)) : null,
        ];

        // Generation reliability snapshot: read-only diagnostics; never re-dispatch jobs here.
        $since = now()->subDay();
        $recent = Generation::query()->where('created_at', '>=', $since);
        $this->serverMetrics['generation_reliability'] = [
            'window_hours' => 24,
            'total' => (clone $recent)->count(),
            'completed' => (clone $recent)->where('status', 'completed')->count(),
            'failed' => (clone $recent)->where('status', 'failed')->count(),
            'processing' => (clone $recent)->whereIn('status', ['processing', 'pending', 'queued'])->count(),
            'latest_failures' => Generation::query()
                ->where('status', 'failed')
                ->orderByDesc('completed_at')
                ->limit(5)
                ->get(['id', 'model', 'error_message', 'completed_at', 'metadata'])
                ->map(static fn (Generation $generation) => [
                    'id' => $generation->id,
                    'model' => $generation->model ?: 'Unknown provider/model',
                    'error' => \Illuminate\Support\Str::limit((string) ($generation->error_message ?: 'No error detail recorded'), 180),
                    'time' => optional($generation->completed_at)->diffForHumans(),
                    'provider' => data_get($generation->metadata, 'provider', data_get($generation->metadata, 'ai_provider', 'unknown')),
                ])->all(),
            'queue_pending' => Schema::hasTable('jobs')
                ? DB::table('jobs')->count()
                : null,
            'failed_jobs' => Schema::hasTable('failed_jobs')
                ? DB::table('failed_jobs')->count()
                : null,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.settings.index', [
            'serverMetrics' => $this->serverMetrics,
        ]);
    }
}
