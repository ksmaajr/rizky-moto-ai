<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Throwable;

class CollectServerTelemetry extends Command
{
    protected $signature = 'server:telemetry:collect';
    protected $description = 'Collect a persistent server telemetry sample in the background';

    public function handle(): int
    {
        try {
            $read = static fn (string $path): string => is_readable($path) ? (string) file_get_contents($path) : '';
            $mem = [];
            foreach (preg_split('/\n/', $read('/proc/meminfo')) as $line) {
                if (preg_match('/^(MemTotal|MemAvailable|MemFree|Buffers|Cached|SwapTotal|SwapFree):\s+(\d+)/', $line, $m)) $mem[$m[1]] = (int) $m[2] * 1024;
            }
            $total = $mem['MemTotal'] ?? 0;
            $available = $mem['MemAvailable'] ?? ($mem['MemFree'] ?? 0);
            $diskTotal = @disk_total_space(base_path()) ?: 0;
            $diskFree = @disk_free_space(base_path()) ?: 0;
            $uptime = preg_split('/\s+/', trim($read('/proc/uptime')));
            $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
            $cpuLine = preg_match('/^cpu\s+(.+)$/m', $read('/proc/stat'), $cpuMatch) ? preg_split('/\s+/', trim($cpuMatch[1])) : [];
            $cpuTicks = array_map('intval', $cpuLine);
            $network = ['rx_bytes' => 0, 'tx_bytes' => 0];
            foreach (array_slice(preg_split('/\n/', $read('/proc/net/dev')), 2) as $line) {
                if (!str_contains($line, ':')) continue;
                [$iface, $values] = explode(':', $line, 2);
                if (trim($iface) === 'lo') continue;
                $v = preg_split('/\s+/', trim($values));
                $network['rx_bytes'] += (int) ($v[0] ?? 0);
                $network['tx_bytes'] += (int) ($v[8] ?? 0);
            }
            $diskIo = ['read_sectors' => 0, 'write_sectors' => 0];
            foreach (preg_split('/\n/', $read('/proc/diskstats')) as $line) {
                $v = preg_split('/\s+/', trim($line));
                if (count($v) > 13 && preg_match('/^(nvme|xvd|vd|sd)/', $v[2])) {
                    $diskIo['read_sectors'] += (int) $v[5];
                    $diskIo['write_sectors'] += (int) $v[9];
                }
            }
            $shellAvailable = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true);
            $cmd = static fn (string $c) => $shellAvailable ? @shell_exec($c) : null;
            $processes = $cmd('ps -e --no-headers 2>/dev/null');
            $services = [];
            foreach (['nginx', 'php8.5-fpm', 'mysql', 'redis-server', 'supervisor'] as $service) {
                $status = $cmd('systemctl is-active '.escapeshellarg($service).' 2>/dev/null');
                $services[$service] = is_string($status) && trim($status) !== '' ? trim($status) : 'unknown';
            }
            $workers = $cmd("pgrep -af '[a]rtisan queue:work' 2>/dev/null");
            $now = now();
            $previous = DB::table('server_metric_snapshots')->orderByDesc('collected_at')->first();
            $elapsed = $previous ? max(1, $now->timestamp - Carbon::parse($previous->collected_at)->timestamp) : 60;
            $old = $previous ? json_decode($previous->metrics, true) : [];
            $oldCpu = $old['cpu_ticks'] ?? [];
            $deltaTotal = array_sum($cpuTicks) - array_sum($oldCpu);
            $deltaIdle = (($cpuTicks[3] ?? 0) + ($cpuTicks[4] ?? 0)) - (($oldCpu[3] ?? 0) + ($oldCpu[4] ?? 0));
            $cpuPercent = $deltaTotal > 0 ? round(max(0, min(100, ($deltaTotal - $deltaIdle) / $deltaTotal * 100)), 2) : 0;
            $metrics = [
                'hostname' => gethostname() ?: 'Server',
                'os' => php_uname('s').' '.php_uname('r'),
                'php' => PHP_VERSION, 'laravel' => app()->version(), 'environment' => app()->environment(),
                'uptime_seconds' => (int) ($uptime[0] ?? 0),
                'load' => array_map(static fn ($v) => round((float) $v, 2), $load),
                'cpu_cores' => max(1, (int) (trim((string) $cmd('nproc 2>/dev/null')) ?: 1)),
                'cpu_ticks' => $cpuTicks, 'cpu_percent' => $cpuPercent,
                'memory_total' => $total, 'memory_available' => $available, 'memory_used' => max(0, $total - $available),
                'buffers_bytes' => $mem['Buffers'] ?? 0, 'cached_bytes' => $mem['Cached'] ?? 0,
                'swap_total' => $mem['SwapTotal'] ?? 0, 'swap_used' => max(0, ($mem['SwapTotal'] ?? 0) - ($mem['SwapFree'] ?? 0)),
                'disk_total' => $diskTotal, 'disk_free' => $diskFree, 'disk_used' => max(0, $diskTotal - $diskFree),
                'network' => $network, 'disk_io' => $diskIo,
                'rx_bytes_sec' => $previous ? max(0, $network['rx_bytes'] - (int) ($old['network']['rx_bytes'] ?? 0)) / $elapsed : 0,
                'tx_bytes_sec' => $previous ? max(0, $network['tx_bytes'] - (int) ($old['network']['tx_bytes'] ?? 0)) / $elapsed : 0,
                'read_bytes_sec' => $previous ? max(0, ($diskIo['read_sectors'] - (int) ($old['disk_io']['read_sectors'] ?? 0)) * 512) / $elapsed : 0,
                'write_bytes_sec' => $previous ? max(0, ($diskIo['write_sectors'] - (int) ($old['disk_io']['write_sectors'] ?? 0)) * 512) / $elapsed : 0,
                'services' => $services,
                'queue_workers' => is_string($workers) && trim($workers) !== '' ? count(preg_split('/\n/', trim($workers), -1, PREG_SPLIT_NO_EMPTY)) : 0,
                'process_count' => is_string($processes) && trim($processes) !== '' ? count(preg_split('/\n/', trim($processes), -1, PREG_SPLIT_NO_EMPTY)) : null,
                'process_access' => $shellAvailable,
            ];
            // Persist alert state transitions in the background collector; no browser session is required.
            $ramPct = $total > 0 ? (($metrics['memory_used'] / $total) * 100) : 0;
            $diskPct = $diskTotal > 0 ? (($metrics['disk_used'] / $diskTotal) * 100) : 0;
            $swapPct = ($metrics['swap_total'] ?? 0) > 0 ? (($metrics['swap_used'] / $metrics['swap_total']) * 100) : 0;
            $checks = [
                ['key' => 'cpu', 'value' => $cpuPercent, 'warning' => 80, 'critical' => 95, 'title' => 'CPU utilization', 'unit' => '%'],
                ['key' => 'memory', 'value' => $ramPct, 'warning' => 80, 'critical' => 95, 'title' => 'Memory pressure', 'unit' => '%'],
                ['key' => 'disk', 'value' => $diskPct, 'warning' => 85, 'critical' => 95, 'title' => 'Root filesystem usage', 'unit' => '%'],
                ['key' => 'swap', 'value' => $swapPct, 'warning' => 50, 'critical' => 80, 'title' => 'Swap consumption', 'unit' => '%'],
            ];
            foreach ($checks as $check) {
                $severity = $check['value'] >= $check['critical'] ? 'critical' : ($check['value'] >= $check['warning'] ? 'warning' : null);
                $active = DB::table('server_monitor_alerts')->where('metric_key', $check['key'])->whereNull('resolved_at')->orderByDesc('id')->first();
                if ($severity !== null) {
                    $message = $check['title'].' reached '.round($check['value'], 1).$check['unit'].' (threshold '.$check[$severity].$check['unit'].').';
                    if ($active) {
                        DB::table('server_monitor_alerts')->where('id', $active->id)->update([
                            'severity' => $severity, 'message' => $message, 'observed_value' => round($check['value'], 2),
                            'threshold_value' => $check[$severity], 'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('server_monitor_alerts')->insert([
                            'metric_key' => $check['key'], 'severity' => $severity, 'title' => $check['title'],
                            'message' => $message, 'observed_value' => round($check['value'], 2),
                            'threshold_value' => $check[$severity], 'started_at' => $now,
                            'resolved_at' => null, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                } elseif ($active) {
                    DB::table('server_monitor_alerts')->where('id', $active->id)->update(['resolved_at' => $now, 'updated_at' => $now]);
                }
            }

            // Detect core service failures and a completely stopped Laravel queue pool.
            $operationalChecks = [];
            if ($shellAvailable) {
                foreach ($services as $service => $status) {
                    if ($status !== 'unknown') {
                        $operationalChecks[] = [
                            'key' => 'service_'.$service,
                            'failed' => $status !== 'active',
                            'title' => $service.' service',
                            'message' => $service.' service status is '.strtoupper($status).'.',
                        ];
                    }
                }
                $operationalChecks[] = [
                    'key' => 'queue_workers',
                    'failed' => $metrics['queue_workers'] < 1,
                    'title' => 'Laravel queue workers',
                    'message' => $metrics['queue_workers'] < 1
                        ? 'No Laravel queue:work process was detected; image generation jobs may remain queued.'
                        : $metrics['queue_workers'].' Laravel queue worker process(es) detected.',
                ];
            }
            foreach ($operationalChecks as $check) {
                $active = DB::table('server_monitor_alerts')->where('metric_key', $check['key'])->whereNull('resolved_at')->orderByDesc('id')->first();
                if ($check['failed']) {
                    if ($active) {
                        DB::table('server_monitor_alerts')->where('id', $active->id)->update([
                            'severity' => 'critical', 'message' => $check['message'],
                            'observed_value' => 0, 'threshold_value' => 1, 'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('server_monitor_alerts')->insert([
                            'metric_key' => $check['key'], 'severity' => 'critical', 'title' => $check['title'],
                            'message' => $check['message'], 'observed_value' => 0, 'threshold_value' => 1,
                            'started_at' => $now, 'resolved_at' => null, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                } elseif ($active) {
                    DB::table('server_monitor_alerts')->where('id', $active->id)->update(['resolved_at' => $now, 'updated_at' => $now]);
                }
            }

            DB::table('server_metric_snapshots')->insert([
                'collected_at' => $now,
                'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('server_metric_snapshots')->where('collected_at', '<', $now->copy()->subDays(7))->delete();
            $this->info('Server telemetry snapshot saved at '.$now->toDateTimeString());
            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error('Telemetry collection failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
