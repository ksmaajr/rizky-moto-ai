<?php

use App\Http\Controllers\ActivityLogFeedController;
use App\Http\Controllers\GeneratedImageDownloadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', 'pages::auth.login')->name('login');

// Use the domain root as the dashboard URL after authentication.
Route::livewire('/', 'pages::dashboard')
    ->middleware('auth')
    ->name('dashboard');

// Keep old dashboard links working, but normalize the visible URL to "/".
Route::redirect('/dashboard', '/')->name('dashboard.legacy');

Route::middleware('auth')->group(function () {

    // Account settings pages.
    Route::middleware('verified')->group(function () {
        Route::livewire('/settings/profile', 'pages::settings.profile')->name('profile.edit');
        Route::livewire('/settings/security', 'pages::settings.security')
            ->middleware('password.confirm')
            ->name('security.edit');
        Route::livewire('/settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    });

    Route::post('/dashboard/queue-workers/{action}', \App\Http\Controllers\QueueWorkerActionController::class)
        ->whereIn('action', ['start', 'stop', 'restart'])
        ->name('dashboard.queue-workers.action');

    Route::post('/dashboard/agentkit-workers/{action}', \App\Http\Controllers\AgentKitWorkerActionController::class)
        ->whereIn('action', ['start', 'stop', 'restart'])
        ->name('dashboard.agentkit-workers.action');

    Route::get('/dashboard/server-monitoring/metrics', function () {
        $read = static fn (string $path): string => is_readable($path) ? (string) file_get_contents($path) : '';
        $mem = [];
        foreach (preg_split('/\\n/', $read('/proc/meminfo')) as $line) {
            if (preg_match('/^(MemTotal|MemAvailable|MemFree|Buffers|Cached|SwapTotal|SwapFree):\\s+(\\d+)/', $line, $m)) $mem[$m[1]] = (int) $m[2] * 1024;
        }
        $total = $mem['MemTotal'] ?? 0; $available = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 0);
        $diskTotal = @disk_total_space(base_path()) ?: 0; $diskFree = @disk_free_space(base_path()) ?: 0;
        $up = preg_split('/\\s+/', trim($read('/proc/uptime')));
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0,0,0];
        $shellAvailable = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true);
        $cmd = static fn (string $c) => $shellAvailable ? @shell_exec($c) : null;
        $cores = max(1, (int) (trim((string) $cmd('nproc 2>/dev/null')) ?: 1));
        $services = [];
        foreach (['nginx','php8.5-fpm','mysql','redis-server','supervisor'] as $service) {
            $status = $cmd('systemctl is-active '.escapeshellarg($service).' 2>/dev/null');
            $services[$service] = is_string($status) && trim($status) !== '' ? trim($status) : 'unknown';
        }
        $workersText = $cmd("pgrep -af '[a]rtisan queue:work' 2>/dev/null");
        $processesText = $cmd('ps -e --no-headers 2>/dev/null');
        $cpuLine = preg_match('/^cpu\\s+(.+)$/m', $read('/proc/stat'), $cpuMatch) ? preg_split('/\\s+/', trim($cpuMatch[1])) : [];
        $network = ['rx_bytes'=>0,'tx_bytes'=>0];
        foreach (array_slice(preg_split('/\\n/', $read('/proc/net/dev')), 2) as $line) {
            if (str_contains($line, ':')) { [$iface,$values] = explode(':', $line, 2); if (trim($iface) !== 'lo') { $v=preg_split('/\\s+/', trim($values)); $network['rx_bytes']+=(int)($v[0]??0); $network['tx_bytes']+=(int)($v[8]??0); } }
        }
        $diskIo = ['read_sectors'=>0,'write_sectors'=>0];
        foreach (preg_split('/\\n/', $read('/proc/diskstats')) as $line) {
            $v=preg_split('/\\s+/', trim($line)); if (count($v)>13 && preg_match('/^(nvme|xvd|vd|sd)/', $v[2])) { $diskIo['read_sectors']+=(int)$v[5]; $diskIo['write_sectors']+=(int)$v[9]; }
        }
        $historyRange = request()->query('range', '24h');
        if (!in_array($historyRange, ['1h', '24h', '7d'], true)) $historyRange = '24h';
        $historySince = now()->subSeconds(match ($historyRange) { '1h' => 3600, '7d' => 604800, default => 86400 });
        $historyRows = \Illuminate\Support\Facades\DB::table('server_metric_snapshots')
            ->where('collected_at', '>=', $historySince)
            ->orderBy('collected_at')
            ->limit(match ($historyRange) { '1h' => 120, '7d' => 2000, default => 1000 })
            ->get(['collected_at', 'metrics']);
        $history = $historyRows->map(static function ($row) {
            $m = json_decode($row->metrics, true) ?: [];
            return ['time' => $row->collected_at, 'cpu' => (float) ($m['cpu_percent'] ?? 0), 'ram' => ($m['memory_total'] ?? 0) > 0 ? (($m['memory_used'] ?? 0) / $m['memory_total'] * 100) : 0, 'disk' => ($m['disk_total'] ?? 0) > 0 ? (($m['disk_used'] ?? 0) / $m['disk_total'] * 100) : 0, 'load' => (float) ($m['load'][0] ?? 0)];
        })->values();
        $analytics = [];
        foreach (['cpu', 'ram', 'disk', 'load'] as $metric) {
            $values = $history->pluck($metric)->map(static fn ($v) => (float) $v);
            $analytics[$metric] = ['avg' => round((float) $values->avg(), 2), 'max' => round((float) $values->max(), 2), 'min' => round((float) $values->min(), 2)];
        }
        $alerts = \Illuminate\Support\Facades\DB::table('server_monitor_alerts')
            ->orderByRaw('resolved_at IS NULL DESC')->orderByDesc('started_at')->limit(12)
            ->get(['id', 'metric_key', 'severity', 'title', 'message', 'observed_value', 'threshold_value', 'started_at', 'resolved_at'])
            ->map(static fn ($a) => (array) $a)->values();
        return response()->json([
            'ok'=>true,'time'=>now()->toIso8601String(),'history_range'=>$historyRange,'history'=>$history,'analytics'=>$analytics,'alerts'=>$alerts,'hostname'=>gethostname()?:'Server','os'=>php_uname('s').' '.php_uname('r'),
            'php'=>PHP_VERSION,'laravel'=>app()->version(),'environment'=>app()->environment(),'uptime_seconds'=>(int)($up[0]??0),
            'load'=>array_map(static fn($v)=>round((float)$v,2),$load),'cpu_cores'=>$cores,'cpu_ticks'=>array_map('intval',$cpuLine),
            'memory_total'=>$total,'memory_available'=>$available,'memory_used'=>max(0,$total-$available),
            'buffers_bytes'=>$mem['Buffers']??0,'cached_bytes'=>$mem['Cached']??0,
            'swap_total'=>$mem['SwapTotal']??0,'swap_used'=>max(0,($mem['SwapTotal']??0)-($mem['SwapFree']??0)),
            'disk_total'=>$diskTotal,'disk_free'=>$diskFree,'disk_used'=>max(0,$diskTotal-$diskFree),
            'network'=>$network,'disk_io'=>$diskIo,
            'services'=>$services,'queue_workers'=>is_string($workersText)&&trim($workersText)!==''?count(preg_split('/\\n/',trim($workersText),-1,PREG_SPLIT_NO_EMPTY)):0,
            'process_count'=>is_string($processesText)&&trim($processesText)!==''?count(preg_split('/\\n/',trim($processesText),-1,PREG_SPLIT_NO_EMPTY)):null,
            'process_access'=>$shellAvailable,
        ]);
    })->name('dashboard.server-monitoring.metrics');

    Route::get('/dashboard/activity-logs/feed', [
        ActivityLogFeedController::class,
        'index',
    ])->name('dashboard.activity-logs.feed');

    Route::delete('/dashboard/activity-logs', [
        ActivityLogFeedController::class,
        'destroy',
    ])->name('dashboard.activity-logs.destroy');

    // Generated image download / compression endpoint.
    Route::get('/generated-images/{generatedImage}/download', GeneratedImageDownloadController::class)
        ->name('generated-images.download');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
