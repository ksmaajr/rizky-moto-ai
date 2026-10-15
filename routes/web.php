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
        $memory = [];
        foreach (preg_split('/\\n/', $read('/proc/meminfo')) as $line) {
            if (preg_match('/^(MemTotal|MemAvailable|MemFree|SwapTotal|SwapFree):\\s+(\\d+)/', $line, $matches)) {
                $memory[$matches[1]] = (int) $matches[2] * 1024;
            }
        }
        $memTotal = $memory['MemTotal'] ?? 0;
        $memAvailable = $memory['MemAvailable'] ?? ($memory['MemFree'] ?? 0);
        $diskTotal = @disk_total_space(base_path()) ?: 0;
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $uptimeParts = preg_split('/\\s+/', trim($read('/proc/uptime')));
        $uptime = (int) ($uptimeParts[0] ?? 0);
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cores = max(1, (int) (trim((string) @shell_exec('nproc 2>/dev/null')) ?: 1));
        $services = [];
        foreach (['nginx', 'php8.5-fpm', 'mysql', 'redis-server', 'supervisor'] as $service) {
            $status = @shell_exec('systemctl is-active ' . escapeshellarg($service) . ' 2>/dev/null');
            $services[$service] = is_string($status) && trim($status) !== '' ? trim($status) : 'unknown';
        }
        $workersOutput = @shell_exec("pgrep -af '[a]rtisan queue:work' 2>/dev/null");
        $workers = is_string($workersOutput) && trim($workersOutput) !== ''
            ? count(preg_split('/\\n/', trim($workersOutput), -1, PREG_SPLIT_NO_EMPTY))
            : 0;
        return response()->json([
            'ok' => true,
            'time' => now()->toIso8601String(),
            'hostname' => gethostname() ?: 'Server',
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
            'uptime_seconds' => $uptime,
            'load' => array_map(static fn ($value) => round((float) $value, 2), $load),
            'cpu_cores' => $cores,
            'memory_total' => $memTotal,
            'memory_available' => $memAvailable,
            'memory_used' => max(0, $memTotal - $memAvailable),
            'swap_total' => $memory['SwapTotal'] ?? 0,
            'swap_used' => max(0, ($memory['SwapTotal'] ?? 0) - ($memory['SwapFree'] ?? 0)),
            'disk_total' => $diskTotal,
            'disk_free' => $diskFree,
            'disk_used' => max(0, $diskTotal - $diskFree),
            'services' => $services,
            'queue_workers' => $workers,
            'process_access' => function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true),
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
