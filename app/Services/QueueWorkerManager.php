<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use App\Services\ActivityLogService;
use RuntimeException;
use Throwable;

class QueueWorkerManager
{
    private const PID_PREFIX = 'framework/queue-worker-';
    private const STARTED_FILE = 'framework/queue-worker.started';
    private const STDOUT_PREFIX = 'logs/queue-worker-';
    private const STDERR_PREFIX = 'logs/queue-worker-error-';

    private const SLEEP = 2;
    private const TRIES = 3;
    private const TIMEOUT = 300;
    private const MAX_JOBS = 100;
    private const MAX_TIME = 3600;
    private const MAX_WORKERS = 3;

    public function workerCount(): int
    {
        return max(1, min(
            self::MAX_WORKERS,
            (int) env('QUEUE_WORKER_COUNT', 3)
        ));
    }

    private function usesSupervisor(): bool
    {
        return PHP_OS_FAMILY !== 'Windows'
            && strtolower((string) env('QUEUE_WORKER_DRIVER', 'supervisor')) === 'supervisor';
    }

    private function supervisorProgram(): string
    {
        return (string) env('QUEUE_WORKER_SUPERVISOR_PROGRAM', 'rizky-moto-ai-worker');
    }

    private function supervisorBinary(): string
    {
        return (string) env('QUEUE_WORKER_SUPERVISOR_BIN', '/usr/bin/supervisorctl');
    }

    private function supervisorCommand(string $action, bool $wildcard = false): array
    {
        $program = $this->supervisorProgram() . ($wildcard ? ':*' : '');

        return [
            'sudo',
            '-n',
            $this->supervisorBinary(),
            $action,
            $program,
        ];
    }

    private function supervisorStatus(): array
    {
        $result = Process::run($this->supervisorCommand('status', true));
        $output = trim($result->output() . PHP_EOL . $result->errorOutput());

        if ($result->failed()) {
            return [
                'running' => false,
                'workers' => [],
                'supervisor_error' => $output !== '' ? $output : 'Supervisor status gagal.',
            ];
        }

        $workers = [];
        $prefix = preg_quote($this->supervisorProgram(), '/');

        foreach (preg_split('/\R+/', trim($result->output())) as $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(' . $prefix . '(?:_[0-9]+|:[0-9]+)?)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF)\s*(?:pid\s+(\d+))?/i', trim($line), $m) !== 1) {
                continue;
            }

            $workers[] = [
                'name' => $m[1],
                'status' => strtoupper($m[2]),
                'running' => strtoupper($m[2]) === 'RUNNING',
                'pid' => isset($m[3]) && $m[3] !== '' ? (int) $m[3] : null,
            ];
        }

        usort($workers, static fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return [
            'running' => collect($workers)->contains(fn (array $worker) => $worker['running']),
            'workers' => $workers,
            'supervisor_error' => null,
        ];
    }

    public function start(): array
    {
        $current = $this->status();

        if (($current['running_count'] ?? 0) >= $this->workerCount()) {
            return [
                'success' => false,
                'message' => sprintf('Semua %d queue worker sudah berjalan.', $this->workerCount()),
                ...$current,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('start', true));

            if ($result->failed()) {
                $message = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    'Supervisor gagal menjalankan worker' . ($message !== '' ? ': ' . $message : '.')
                );
            }

            file_put_contents(
                storage_path(self::STARTED_FILE),
                now()->toIso8601String(),
                LOCK_EX
            );

            usleep(1200000);
            $status = $this->status();

            if (($status['running_count'] ?? 0) === 0) {
                throw new RuntimeException(
                    'Supervisor start berhasil dipanggil, tetapi tidak ada worker yang RUNNING.'
                );
            }

            $this->logActivity(
                'success',
                'Queue worker Supervisor aktif.',
                sprintf('%d/%d worker aktif.', $status['running_count'], $this->workerCount()),
                $status,
            );

            return [
                'success' => true,
                'message' => sprintf(
                    '%d/%d queue worker berhasil dijalankan melalui Supervisor.',
                    $status['running_count'],
                    $this->workerCount()
                ),
                ...$status,
            ];
        }

        $this->ensureRuntimeDirectories();
        $this->cleanupStalePidFiles();

        $target = $this->workerCount();
        $current = $this->status();
        $started = 0;

        for ($worker = 1; $worker <= $target; $worker++) {
            $existing = $current['workers'][$worker - 1] ?? null;

            if ($existing && $existing['running']) {
                continue;
            }

            $this->startLocalWorker($worker);
            $started++;
        }

        file_put_contents(
            storage_path(self::STARTED_FILE),
            now()->toIso8601String(),
            LOCK_EX
        );

        usleep(1200000);
        $status = $this->status();

        if (($status['running_count'] ?? 0) === 0) {
            $error = $this->tail($this->stderrFile(1), 30);
            throw new RuntimeException(
                'Tidak ada queue worker yang berhasil berjalan.'
                . ($error !== '' ? ' ' . trim($error) : '')
            );
        }

        $this->logActivity(
            'success',
            'Queue worker lokal aktif.',
            sprintf('%d/%d worker aktif (%d process baru dibuat).', $status['running_count'], $target, $started),
            $status,
        );

        return [
            'success' => true,
            'message' => sprintf(
                '%d/%d queue worker aktif (%d process baru dibuat).',
                $status['running_count'],
                $target,
                $started
            ),
            ...$status,
        ];
    }

    private function startLocalWorker(int $workerNumber): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $queue = (string) config('queue.default', 'database');
        $stdout = storage_path($this->stdoutFile($workerNumber));
        $stderr = storage_path($this->stderrFile($workerNumber));
        $pidFile = storage_path($this->pidFile($workerNumber));

        if (! is_file($artisan)) {
            throw new RuntimeException("File artisan tidak ditemukan: {$artisan}");
        }

        $arguments = [
            PHP_OS_FAMILY === 'Windows' ? 'artisan' : $artisan,
            'queue:work',
            $queue,
            '--sleep=' . self::SLEEP,
            '--tries=' . self::TRIES,
            '--timeout=' . self::TIMEOUT,
            '--max-jobs=' . self::MAX_JOBS,
            '--max-time=' . self::MAX_TIME,
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $script = storage_path('framework/start-queue-worker-' . $workerNumber . '.ps1');
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
                throw new RuntimeException(
                    $error !== '' ? $error : "Worker #{$workerNumber} gagal dibuat."
                );
            }

            return;
        }

        $process = Process::path(base_path())
            ->timeout(0)
            ->start(array_merge([$php], $arguments));

        $pid = $process->id();

        if (! $pid) {
            throw new RuntimeException("OS tidak mengembalikan PID worker #{$workerNumber}.");
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
                'message' => 'Tidak ada queue worker yang sedang berjalan.',
                ...$status,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('stop', true));

            if ($result->failed()) {
                $message = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    'Supervisor gagal menghentikan worker' . ($message !== '' ? ': ' . $message : '.')
                );
            }

            $this->clearState();

            return [
                'success' => true,
                'message' => 'Semua queue worker berhasil dihentikan melalui Supervisor.',
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

        if ($stopped > 0) {
            $this->logActivity(
                'success',
                'Queue worker dihentikan.',
                sprintf('%d worker dihentikan.', $stopped),
                $finalStatus,
            );
        }

        return [
            'success' => $stopped > 0,
            'message' => sprintf('%d queue worker berhasil dihentikan.', $stopped),
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
            $supervisor = $this->supervisorStatus();
            // supervisorctl status program:* already scopes the result to our
            // worker pool, so every parsed entry belongs to this application.

            $runningWorkers = array_values(array_filter(
                $workers,
                static fn (array $worker) => $worker['running']
            ));

            return [
                'running' => $runningWorkers !== [],
                'healthy' => count($runningWorkers) >= $this->workerCount(),
                'running_count' => count($runningWorkers),
                'worker_count' => count($workers),
                'target_workers' => $this->workerCount(),
                'workers' => $workers,
                'pid' => $runningWorkers[0]['pid'] ?? null,
                'queue' => (string) config('queue.default', 'database'),
                'started_at' => $runningWorkers !== [] ? $this->readStartedAt() : null,
                'command' => 'supervisor:' . $this->supervisorProgram(),
                'discovered' => false,
                'supervisor_error' => $supervisor['supervisor_error'],
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

        // Recover processes started before this request/tab existed.
        $discovered = $this->discoverWorkers();

        foreach ($discovered as $found) {
            $alreadyTracked = collect($workers)->contains(
                fn (array $worker) => (int) ($worker['pid'] ?? 0) === (int) $found['pid']
            );

            if ($alreadyTracked) {
                continue;
            }

            $emptyIndex = collect($workers)->search(
                fn (array $worker) => ! $worker['running']
            );

            if ($emptyIndex !== false) {
                $workers[$emptyIndex] = [
                    'id' => $workers[$emptyIndex]['id'],
                    'pid' => $found['pid'],
                    'running' => true,
                    'command' => $found['command'],
                ];
                file_put_contents(
                    $this->pidFile($workers[$emptyIndex]['id']),
                    (string) $found['pid'],
                    LOCK_EX
                );
            }
        }

        $runningWorkers = array_values(array_filter(
            $workers,
            static fn (array $worker) => $worker['running']
        ));

        return [
            'running' => $runningWorkers !== [],
            'healthy' => count($runningWorkers) >= $this->workerCount(),
            'running_count' => count($runningWorkers),
            'worker_count' => count($workers),
            'target_workers' => $this->workerCount(),
            'workers' => array_values($workers),
            'pid' => $runningWorkers[0]['pid'] ?? null,
            'queue' => (string) config('queue.default', 'database'),
            'started_at' => $runningWorkers !== [] ? $this->readStartedAt() : null,
            'command' => $runningWorkers[0]['command'] ?? null,
            'discovered' => $discovered !== [],
            'supervisor_error' => null,
        ];
    }

    public function isRunning(): bool
    {
        return $this->status()['running'];
    }

    public function logs(int $lines = 50): array
    {
        $stdout = '';
        $stderr = '';

        for ($worker = 1; $worker <= $this->workerCount(); $worker++) {
            $out = $this->tail($this->stdoutFile($worker), $lines);
            $err = $this->tail($this->stderrFile($worker), $lines);

            if ($out !== '') {
                $stdout .= "===== WORKER {$worker} =====\n{$out}\n";
            }

            if ($err !== '') {
                $stderr .= "===== WORKER {$worker} =====\n{$err}\n";
            }
        }

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    public function paths(): array
    {
        $paths = [];

        for ($worker = 1; $worker <= $this->workerCount(); $worker++) {
            $paths["worker_{$worker}"] = [
                'pid' => storage_path($this->pidFile($worker)),
                'stdout' => storage_path($this->stdoutFile($worker)),
                'stderr' => storage_path($this->stderrFile($worker)),
            ];
        }

        $paths['started'] = storage_path(self::STARTED_FILE);

        return $paths;
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
            $isQueueWorker = preg_match('~(?:^|[\s"\\\\])artisan(?:\.php)?\s+queue:work(?:\s|$)~i', $command) === 1;

            return [
                'running' => $isPhp && $isQueueWorker,
                'command' => $command !== '' ? $command : null,
            ];
        }

        $result = Process::run([
            'ps',
            '-p',
            (string) $pid,
            '-o',
            'args=',
        ]);

        if ($result->failed()) {
            return ['running' => false, 'command' => null];
        }

        $command = trim($result->output());

        return [
            'running' => $command !== '' && preg_match('~(?:^|[\s/])artisan(?:\.php)?\s+queue:work(?:\s|$)~', $command) === 1,
            'command' => $command !== '' ? $command : null,
        ];
    }

    private function discoverWorkers(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $result = Process::run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                "Get-CimInstance Win32_Process | Where-Object { \$_.Name -ieq 'php.exe' -and \$_.CommandLine -match '(?i)artisan(?:\\.php)?\\s+queue:work(?:\\s|$)' } | Select-Object ProcessId,CommandLine | ConvertTo-Json -Compress",
            ]);

            if ($result->failed() || trim($result->output()) === '') {
                return [];
            }

            $decoded = json_decode(trim($result->output()), true);

            if (! is_array($decoded)) {
                return [];
            }

            if (isset($decoded['ProcessId'])) {
                $decoded = [$decoded];
            }

            $found = [];

            foreach ($decoded as $item) {
                $candidate = (int) ($item['ProcessId'] ?? 0);
                $command = trim((string) ($item['CommandLine'] ?? ''));

                if (
                    $candidate > 0 &&
                    $candidate !== getmypid() &&
                    preg_match('~(?:^|[\s"\\\\])artisan(?:\.php)?\s+queue:work(?:\s|$)~i', $command) === 1
                ) {
                    $found[] = [
                        'pid' => $candidate,
                        'command' => $command,
                    ];
                }
            }

            return $found;
        }

        $result = Process::run([
            'pgrep',
            '-af',
            base_path('artisan') . ' queue:work',
        ]);

        if ($result->failed()) {
            return [];
        }

        $found = [];

        foreach (preg_split('/\R+/', trim($result->output())) as $line) {
            if ($line === '') {
                continue;
            }

            [$pid, $command] = array_pad(
                preg_split('/\s+/', $line, 2),
                2,
                ''
            );

            $candidate = (int) $pid;

            if (
                $candidate > 0 &&
                $candidate !== getmypid() &&
                preg_match('~(?:^|[\s/])artisan(?:\.php)?\s+queue:work(?:\s|$)~', $command) === 1
            ) {
                $found[] = [
                    'pid' => $candidate,
                    'command' => $command,
                ];
            }
        }

        return $found;
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

        if (PHP_OS_FAMILY !== 'Windows' && $this->inspectProcess($pid)['running']) {
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
        foreach ([
            storage_path('framework'),
            storage_path('logs'),
        ] as $directory) {
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

        return implode(
            PHP_EOL,
            array_slice(preg_split('/\R/', $content) ?: [], -max(1, $lines))
        );
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

    private function logActivity(string $status, string $title, string $description, array $metadata = []): void
    {
        try {
            app(ActivityLogService::class)->log(
                action: 'queue_worker',
                category: 'worker',
                status: $status,
                title: $title,
                description: $description,
                metadata: [
                    'target_workers' => $this->workerCount(),
                    'platform' => PHP_OS_FAMILY,
                    ...$metadata,
                ],
            );
        } catch (Throwable) {
            // Activity logging must never prevent worker control.
        }
    }

    private function joinPowerShellArguments(array $arguments): string
    {
        return implode(",\n    ", $arguments);
    }
}
