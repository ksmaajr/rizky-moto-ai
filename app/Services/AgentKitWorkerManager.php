<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

final class AgentKitWorkerManager
{
    private const PID_PREFIX = 'framework/agentkit-worker-';
    private const STARTED_FILE = 'framework/agentkit-worker.started';
    private const STDOUT_PREFIX = 'logs/agentkit-worker-';
    private const STDERR_PREFIX = 'logs/agentkit-worker-error-';
    private const MAX_WORKERS = 3;

    public function workerCount(): int
    {
        return max(1, min(
            self::MAX_WORKERS,
            (int) env('AGENT_AI_WORKER_COUNT', 3)
        ));
    }

    private function queue(): string
    {
        return (string) config('services.agent_ai.queue', 'agentkit');
    }

    private function usesSupervisor(): bool
    {
        return PHP_OS_FAMILY !== 'Windows'
            && strtolower((string) env('AGENT_AI_WORKER_DRIVER', 'supervisor')) === 'supervisor';
    }

    private function supervisorProgram(): string
    {
        return (string) env('AGENT_AI_WORKER_SUPERVISOR_PROGRAM', 'rizky-moto-ai-agent');
    }

    private function supervisorBinary(): string
    {
        return (string) env('AGENT_AI_WORKER_SUPERVISOR_BIN', '/usr/bin/supervisorctl');
    }

    private function supervisorCommand(string $action): array
    {
        return [
            'sudo',
            '-n',
            $this->supervisorBinary(),
            $action,
            $this->supervisorProgram() . ':*',
        ];
    }

    public function start(): array
    {
        $current = $this->status();

        if (($current['running_count'] ?? 0) >= $this->workerCount()) {
            return [
                'success' => false,
                'message' => sprintf('Semua %d AgentKit worker sudah berjalan.', $this->workerCount()),
                ...$current,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('start'));

            if ($result->failed()) {
                $message = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    'Supervisor gagal menjalankan AgentKit worker' . ($message !== '' ? ': ' . $message : '.')
                );
            }

            $this->ensureRuntimeDirectories();
            file_put_contents(storage_path(self::STARTED_FILE), now()->toIso8601String(), LOCK_EX);
            usleep(1200000);
            $status = $this->status();

            if (($status['running_count'] ?? 0) === 0) {
                throw new RuntimeException('Supervisor start berhasil dipanggil, tetapi tidak ada AgentKit worker yang RUNNING.');
            }

            $this->logActivity(
                'success',
                'AgentKit worker aktif.',
                sprintf('%d/%d AgentKit worker aktif melalui Supervisor.', $status['running_count'], $this->workerCount()),
                $status,
            );

            return [
                'success' => true,
                'message' => sprintf('%d/%d AgentKit worker aktif melalui Supervisor.', $status['running_count'], $this->workerCount()),
                ...$status,
            ];
        }

        $this->ensureRuntimeDirectories();
        $this->cleanupStalePidFiles();

        $started = 0;
        $current = $this->status();

        for ($worker = 1; $worker <= $this->workerCount(); $worker++) {
            $existing = $current['workers'][$worker - 1] ?? null;

            if ($existing && $existing['running']) {
                continue;
            }

            $this->startLocalWorker($worker);
            $started++;
        }

        file_put_contents(storage_path(self::STARTED_FILE), now()->toIso8601String(), LOCK_EX);
        usleep(1200000);

        $status = $this->status();

        if (($status['running_count'] ?? 0) === 0) {
            $error = $this->tail($this->stderrFile(1), 30);
            throw new RuntimeException(
                'Tidak ada AgentKit worker yang berhasil berjalan.'
                . ($error !== '' ? ' ' . trim($error) : '')
            );
        }

        $this->logActivity(
            'success',
            'AgentKit worker lokal aktif.',
            sprintf('%d/%d AgentKit worker aktif (%d process baru dibuat).', $status['running_count'], $this->workerCount(), $started),
            $status,
        );

        return [
            'success' => true,
            'message' => sprintf('%d/%d AgentKit worker aktif (%d process baru dibuat).', $status['running_count'], $this->workerCount(), $started),
            ...$status,
        ];
    }

    private function startLocalWorker(int $workerNumber): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $stdout = storage_path($this->stdoutFile($workerNumber));
        $stderr = storage_path($this->stderrFile($workerNumber));
        $pidFile = storage_path($this->pidFile($workerNumber));

        $arguments = [
            PHP_OS_FAMILY === 'Windows' ? 'artisan' : $artisan,
            'queue:work',
            (string) config('queue.default', 'database'),
            '--queue=' . $this->queue(),
            '--sleep=2',
            '--tries=1',
            '--timeout=' . (int) config('services.agent_ai.timeout', 300),
            '--max-jobs=100',
            '--max-time=3600',
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $script = storage_path('framework/start-agentkit-worker-' . $workerNumber . '.ps1');
            $psArguments = array_map(
                static fn (string $argument): string => "'" . str_replace("'", "''", $argument) . "'",
                $arguments
            );

            $phpEscaped = str_replace("'", "''", $php);
            $stdoutEscaped = str_replace("'", "''", $stdout);
            $stderrEscaped = str_replace("'", "''", $stderr);
            $pidEscaped = str_replace("'", "''", $pidFile);
            $workingDirectory = str_replace("'", "''", base_path());

            $scriptContents = <<<PS1
\$ErrorActionPreference = 'Stop'
\$proc = Start-Process -FilePath '{$phpEscaped}' -ArgumentList @(
    {$this->joinPowerShellArguments($psArguments)}
) -WorkingDirectory '{$workingDirectory}' -WindowStyle Hidden -RedirectStandardOutput '{$stdoutEscaped}' -RedirectStandardError '{$stderrEscaped}' -PassThru
Set-Content -Path '{$pidEscaped}' -Value \$proc.Id -Encoding ascii
PS1;

            file_put_contents($script, $scriptContents, LOCK_EX);

            $result = Process::run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                $script,
            ]);

            @unlink($script);

            if ($result->failed()) {
                $error = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException($error !== '' ? $error : "AgentKit worker #{$workerNumber} gagal dibuat.");
            }

            return;
        }

        $process = Process::path(base_path())
            ->timeout(0)
            ->start(array_merge([$php], $arguments));

        $pid = $process->id();

        if (! $pid) {
            throw new RuntimeException("OS tidak mengembalikan PID AgentKit worker #{$workerNumber}.");
        }

        file_put_contents($pidFile, (string) $pid, LOCK_EX);
    }

    public function stop(): array
    {
        $status = $this->status();

        if (($status['running_count'] ?? 0) === 0) {
            $this->clearState();

            return [
                'success' => false,
                'message' => 'Tidak ada AgentKit worker yang sedang berjalan.',
                ...$status,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('stop'));

            if ($result->failed()) {
                $message = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    'Supervisor gagal menghentikan AgentKit worker' . ($message !== '' ? ': ' . $message : '.')
                );
            }

            $this->clearState();

            return [
                'success' => true,
                'message' => 'Semua AgentKit worker berhasil dihentikan melalui Supervisor.',
                ...$this->status(),
            ];
        }

        $stopped = 0;

        foreach ($status['workers'] as $worker) {
            if (! $worker['running'] || ! $worker['pid']) {
                continue;
            }

            $pid = (int) $worker['pid'];

            if (PHP_OS_FAMILY === 'Windows') {
                Process::run(['taskkill', '/PID', (string) $pid, '/T', '/F']);
            } elseif (function_exists('posix_kill')) {
                @posix_kill($pid, SIGTERM);
            } else {
                Process::run(['kill', '-TERM', (string) $pid]);
            }

            $stopped++;
        }

        foreach ($status['workers'] as $worker) {
            if ($worker['pid']) {
                $this->waitUntilStopped((int) $worker['pid']);
            }
        }

        $this->clearState();
        $finalStatus = $this->status();

        $this->logActivity(
            'success',
            'AgentKit worker dihentikan.',
            sprintf('%d AgentKit worker dihentikan.', $stopped),
            $finalStatus,
        );

        return [
            'success' => $stopped > 0,
            'message' => sprintf('%d AgentKit worker berhasil dihentikan.', $stopped),
            ...$finalStatus,
        ];
    }

    public function restart(): array
    {
        $this->stop();
        usleep(300000);

        return $this->start();
    }

    public function status(): array
    {
        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('status'));
            $output = trim($result->output() . PHP_EOL . $result->errorOutput());

            if ($result->failed()) {
                return [
                    'running' => false,
                    'healthy' => false,
                    'running_count' => 0,
                    'worker_count' => 0,
                    'target_workers' => $this->workerCount(),
                    'workers' => [],
                    'pid' => null,
                    'queue' => $this->queue(),
                    'started_at' => null,
                    'command' => 'supervisor:' . $this->supervisorProgram(),
                    'supervisor_error' => $output !== '' ? $output : 'Supervisor status gagal.',
                ];
            }

            $workers = [];
            $prefix = preg_quote($this->supervisorProgram(), '/');

            foreach (preg_split('/\R+/', trim($result->output())) as $line) {
                if ($line === '') {
                    continue;
                }

                if (preg_match('/^(?:' . $prefix . ':)?(' . $prefix . '(?:_[0-9]+|:[0-9]+)?)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF)\s*(?:pid\s+(\d+))?/i', trim($line), $m) !== 1) {
                    continue;
                }

                $workers[] = [
                    'id' => count($workers) + 1,
                    'name' => $m[1],
                    'status' => strtoupper($m[2]),
                    'running' => strtoupper($m[2]) === 'RUNNING',
                    'pid' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null,
                ];
            }

            usort($workers, static fn (array $a, array $b) => strcmp($a['name'], $b['name']));
            $runningWorkers = array_values(array_filter($workers, static fn (array $worker) => $worker['running']));

            return [
                'running' => $runningWorkers !== [],
                'healthy' => count($runningWorkers) >= $this->workerCount(),
                'running_count' => count($runningWorkers),
                'worker_count' => count($workers),
                'target_workers' => $this->workerCount(),
                'workers' => $workers,
                'pid' => $runningWorkers[0]['pid'] ?? null,
                'queue' => $this->queue(),
                'started_at' => $runningWorkers !== [] ? $this->readStartedAt() : null,
                'command' => 'supervisor:' . $this->supervisorProgram(),
                'supervisor_error' => null,
            ];
        }

        $workers = [];

        for ($worker = 1; $worker <= $this->workerCount(); $worker++) {
            $pid = $this->readPid($worker);

            if ($pid) {
                $info = $this->inspectProcess($pid);

                if ($info['running']) {
                    $workers[] = [
                        'id' => $worker,
                        'pid' => $pid,
                        'running' => true,
                        'command' => $info['command'],
                    ];
                    continue;
                }

                @unlink($this->pidFile($worker));
            }

            $workers[] = [
                'id' => $worker,
                'pid' => null,
                'running' => false,
                'command' => null,
            ];
        }

        $runningWorkers = array_values(array_filter($workers, static fn (array $worker) => $worker['running']));

        return [
            'running' => $runningWorkers !== [],
            'healthy' => count($runningWorkers) >= $this->workerCount(),
            'running_count' => count($runningWorkers),
            'worker_count' => count($workers),
            'target_workers' => $this->workerCount(),
            'workers' => array_values($workers),
            'pid' => $runningWorkers[0]['pid'] ?? null,
            'queue' => $this->queue(),
            'started_at' => $runningWorkers !== [] ? $this->readStartedAt() : null,
            'command' => $runningWorkers[0]['command'] ?? null,
            'discovered' => false,
            'supervisor_error' => null,
        ];
    }

    public function logs(int $lines = 50): array
    {
        $stdout = '';
        $stderr = '';

        for ($worker = 1; $worker <= $this->workerCount(); $worker++) {
            $out = $this->tail($this->stdoutFile($worker), $lines);
            $err = $this->tail($this->stderrFile($worker), $lines);

            if ($out !== '') {
                $stdout .= "===== AGENT {$worker} =====\n{$out}\n";
            }

            if ($err !== '') {
                $stderr .= "===== AGENT {$worker} =====\n{$err}\n";
            }
        }

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }

    private function inspectProcess(int $pid): array
    {
        if ($pid <= 0) {
            return ['running' => false, 'command' => null];
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                "(Get-CimInstance Win32_Process -Filter \"ProcessId = {$pid}\") | Select-Object Name,ProcessId,CommandLine | ConvertTo-Json -Compress",
            ]);

            if ($result->failed() || trim($result->output()) === '') {
                return ['running' => false, 'command' => null];
            }

            $decoded = json_decode(trim($result->output()), true);

            if (! is_array($decoded)) {
                return ['running' => false, 'command' => null];
            }

            $name = strtolower((string) ($decoded['Name'] ?? ''));
            $command = trim((string) ($decoded['CommandLine'] ?? ''));
            $isPhp = $name === 'php.exe' || str_ends_with($name, '\\php.exe');
            $isAgentQueue = preg_match('~(?:^|[\s"\\])artisan(?:\.php)?\s+queue:work(?:\s|$)~i', $command) === 1
                && str_contains(strtolower($command), '--queue=' . strtolower($this->queue()));

            return ['running' => $isPhp && $isAgentQueue, 'command' => $command !== '' ? $command : null];
        }

        $result = Process::run(['ps', '-p', (string) $pid, '-o', 'args=']);

        if ($result->failed()) {
            return ['running' => false, 'command' => null];
        }

        $command = trim($result->output());

        return [
            'running' => $command !== ''
                && preg_match('~(?:^|[\s/])artisan(?:\.php)?\s+queue:work(?:\s|$)~', $command) === 1
                && str_contains($command, '--queue=' . $this->queue()),
            'command' => $command !== '' ? $command : null,
        ];
    }

    private function waitUntilStopped(int $pid): void
    {
        $deadline = microtime(true) + 5;

        while (microtime(true) < $deadline) {
            if (! $this->inspectProcess($pid)['running']) {
                return;
            }

            usleep(150000);
        }

        if (PHP_OS_FAMILY !== 'Windows' && $this->inspectProcess($pid)['running') {
            if (function_exists('posix_kill')) {
                @posix_kill($pid, SIGKILL);
            } else {
                Process::run(['kill', '-KILL', (string) $pid]);
            }
        }
    }

    private function clearState(): void
    {
        for ($worker = 1; $worker <= self::MAX_WORKERS; $worker++) {
            @unlink(storage_path($this->pidFile($worker)));
        }

        @unlink(storage_path(self::STARTED_FILE));
    }

    private function cleanupStalePidFiles(): void
    {
        for ($worker = 1; $worker <= self::MAX_WORKERS; $worker++) {
            $pid = $this->readPid($worker);

            if ($pid && ! $this->inspectProcess($pid)['running']) {
                @unlink(storage_path($this->pidFile($worker)));
            }
        }
    }

    private function readPid(int $worker): ?int
    {
        $path = storage_path($this->pidFile($worker));

        if (! is_file($path)) {
            return null;
        }

        $pid = (int) trim((string) @file_get_contents($path));

        return $pid > 0 ? $pid : null;
    }

    private function readStartedAt(): ?string
    {
        $path = storage_path(self::STARTED_FILE);

        if (! is_file($path)) {
            return null;
        }

        $value = trim((string) @file_get_contents($path));

        return $value !== '' ? $value : null;
    }

    private function ensureRuntimeDirectories(): void
    {
        foreach ([storage_path('framework'), storage_path('logs')] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }
    }

    private function tail(string $relativePath, int $lines): string
    {
        $path = storage_path($relativePath);

        if (! is_file($path)) {
            return '';
        }

        $content = (string) @file_get_contents($path);

        if ($content === '') {
            return '';
        }

        return implode(PHP_EOL, array_slice(preg_split('/\R/', $content) ?: [], -max(1, $lines)));
    }

    private function pidFile(int $worker): string
    {
        return self::PID_PREFIX . $worker . '.pid';
    }

    private function stdoutFile(int $worker): string
    {
        return self::STDOUT_PREFIX . $worker . '.log';
    }

    private function stderrFile(int $worker): string
    {
        return self::STDERR_PREFIX . $worker . '.log';
    }

    private function joinPowerShellArguments(array $arguments): string
    {
        return implode(",\n    ", $arguments);
    }

    private function logActivity(string $status, string $title, string $description, array $metadata = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                action: 'agentkit_worker',
                category: 'worker',
                status: $status,
                title: $title,
                description: $description,
                metadata: [
                    'queue' => $this->queue(),
                    'target_workers' => $this->workerCount(),
                    'platform' => PHP_OS_FAMILY,
                    ...$metadata,
                ],
            );
        } catch (Throwable) {
        }
    }
}
