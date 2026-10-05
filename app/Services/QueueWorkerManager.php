<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class QueueWorkerManager
{
    private const PID_FILE = 'framework/queue-worker.pid';
    private const STARTED_FILE = 'framework/queue-worker.started';
    private const STDOUT_LOG = 'logs/queue-worker.log';
    private const STDERR_LOG = 'logs/queue-worker-error.log';

    private const SLEEP = 2;
    private const TRIES = 3;
    private const TIMEOUT = 300;
    private const MAX_JOBS = 100;
    private const MAX_TIME = 3600;

    private function usesSupervisor(): bool
    {
        return PHP_OS_FAMILY !== 'Windows'
            && strtolower((string) env('QUEUE_WORKER_DRIVER', 'supervisor')) === 'supervisor';
    }

    private function supervisorProgram(): string
    {
        return (string) env('QUEUE_WORKER_SUPERVISOR_PROGRAM', 'rizky-moto-ai-worker');
    }

    private function supervisorCommand(string $action): array
    {
        $program = $this->supervisorProgram();
        $binary = (string) env('QUEUE_WORKER_SUPERVISOR_BIN', '/usr/bin/supervisorctl');

        return [
            'sudo', '-n', $binary, $action, $program,
        ];
    }

    private function supervisorStatus(): array
    {
        $result = Process::run($this->supervisorCommand('status'));
        $output = trim($result->output() . PHP_EOL . $result->errorOutput());
        $program = preg_quote($this->supervisorProgram(), '/');

        if ($result->failed()) {
            return [
                'running' => false,
                'pid' => null,
                'command' => null,
                'supervisor_error' => $output,
            ];
        }

        if (preg_match('/^' . $program . '\s+RUNNING\s+pid\s+(\d+)/mi', $result->output(), $m) === 1) {
            return [
                'running' => true,
                'pid' => (int) $m[1],
                'command' => 'supervisor:' . $this->supervisorProgram(),
                'supervisor_error' => null,
            ];
        }

        return [
            'running' => false,
            'pid' => null,
            'command' => 'supervisor:' . $this->supervisorProgram(),
            'supervisor_error' => $output !== '' ? $output : null,
        ];
    }

    public function start(): array
    {
        $current = $this->status();

        if ($current['running']) {
            return [
                'success' => false,
                'message' => 'Queue worker sudah berjalan.',
                ...$current,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('start'));
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

            usleep(1000000);
            $status = $this->status();

            if (! $status['running']) {
                throw new RuntimeException(
                    'Supervisor start berhasil dipanggil, tetapi worker belum RUNNING.'
                );
            }

            return [
                'success' => true,
                'message' => 'Queue worker berhasil dijalankan melalui Supervisor.',
                ...$status,
            ];
        }

        $this->ensureRuntimeDirectories();
        $this->clearStaleState();

        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $queue = (string) config('queue.default', 'database');
        $stdout = storage_path(self::STDOUT_LOG);
        $stderr = storage_path(self::STDERR_LOG);

        if (! is_file($artisan)) {
            throw new RuntimeException("File artisan tidak ditemukan: {$artisan}");
        }

        if (! is_file($php) && PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException("PHP CLI tidak ditemukan: {$php}");
        }

        // Windows Start-Process receives ArgumentList as one command line.
        // Use a relative `artisan` path there so project paths containing spaces
        // (for example `D:\Website\Tools Generating Image ...`) are not split.
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

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                /*
                 * On Windows, use Start-Process so queue:work is detached from
                 * the Livewire/HTTP request. Laravel Process::start() can keep
                 * the child tied to the request and the PHP process may exit.
                 */
                $script = storage_path('framework/start-queue-worker.ps1');

                $psArguments = [];
                foreach ($arguments as $argument) {
                    $escaped = str_replace("'", "''", $argument);
                    $psArguments[] = "'{$escaped}'";
                }

                $phpEscaped = str_replace("'", "''", $php);
                $stdoutEscaped = str_replace("'", "''", $stdout);
                $stderrEscaped = str_replace("'", "''", $stderr);
                $workingDirectory = str_replace("'", "''", base_path());

                $scriptContents = <<<PS1
\$ErrorActionPreference = 'Stop'
\$proc = Start-Process -FilePath '{$phpEscaped}' -ArgumentList @(
    {$this->joinPowerShellArguments($psArguments)}
) -WorkingDirectory '{$workingDirectory}' -WindowStyle Hidden -RedirectStandardOutput '{$stdoutEscaped}' -RedirectStandardError '{$stderrEscaped}' -PassThru
Set-Content -Path '{$this->escapePowerShellSingleQuote(storage_path(self::PID_FILE))}' -Value \$proc.Id -Encoding ascii
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
                    $error = trim($result->errorOutput());
                    throw new RuntimeException($error !== '' ? $error : 'PowerShell gagal membuat background worker.');
                }
            } else {
                $process = Process::path(base_path())
                    ->timeout(0)
                    ->start(array_merge([$php], $arguments));

                $pid = $process->id();

                if (! $pid) {
                    throw new RuntimeException('OS tidak mengembalikan PID worker.');
                }

                file_put_contents(
                    storage_path(self::PID_FILE),
                    (string) $pid,
                    LOCK_EX
                );
            }

            file_put_contents(
                storage_path(self::STARTED_FILE),
                now()->toIso8601String(),
                LOCK_EX
            );

            // Give the detached worker enough time to initialize Laravel.
            usleep(1200000);

            $status = $this->status();

            if (! $status['running']) {
                $error = $this->tail(self::STDERR_LOG, 30);

                $message = 'Worker process gagal tetap berjalan.';
                if ($error !== '') {
                    $message .= ' ' . trim($error);
                }

                $this->clearState();

                return [
                    'success' => false,
                    'message' => $message,
                    ...$status,
                ];
            }

            return [
                'success' => true,
                'message' => 'Queue worker berhasil dijalankan.',
                ...$status,
            ];
        } catch (Throwable $e) {
            $this->clearState();

            throw new RuntimeException(
                'Gagal menjalankan queue worker: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function stop(): array
    {
        $status = $this->status();

        if (! $status['running'] || ! $status['pid']) {
            $this->clearState();

            return [
                'success' => false,
                'message' => 'Queue worker tidak sedang berjalan.',
                'running' => false,
                'pid' => null,
            ];
        }

        if ($this->usesSupervisor()) {
            $result = Process::run($this->supervisorCommand('stop'));
            if ($result->failed()) {
                $message = trim($result->errorOutput() ?: $result->output());
                throw new RuntimeException(
                    'Supervisor gagal menghentikan worker' . ($message !== '' ? ': ' . $message : '.')
                );
            }

            $this->clearState();

            return [
                'success' => true,
                'message' => 'Queue worker berhasil dihentikan melalui Supervisor.',
                'running' => false,
                'pid' => null,
            ];
        }

        $pid = (int) $status['pid'];

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                Process::run([
                    'taskkill',
                    '/PID',
                    (string) $pid,
                    '/T',
                    '/F',
                ]);
            } else {
                /*
                 * SIGTERM gives Laravel Queue Worker a chance to shut down
                 * cleanly after its current job.
                 */
                if (function_exists('posix_kill')) {
                    @posix_kill($pid, SIGTERM);
                } else {
                    Process::run(['kill', '-TERM', (string) $pid]);
                }
            }

            $this->waitUntilStopped($pid);

            $this->clearState();

            return [
                'success' => true,
                'message' => 'Queue worker berhasil dihentikan.',
                'running' => false,
                'pid' => null,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Gagal menghentikan queue worker: ' . $e->getMessage(),
                previous: $e
            );
        }
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

            return [
                'running' => $supervisor['running'],
                'pid' => $supervisor['pid'],
                'queue' => (string) config('queue.default', 'database'),
                'started_at' => $supervisor['running'] ? $this->readStartedAt() : null,
                'command' => $supervisor['command'],
                'discovered' => false,
                'supervisor_error' => $supervisor['supervisor_error'],
            ];
        }

        $pid = $this->readPid();

        if ($pid) {
            $processInfo = $this->inspectProcess($pid);

            if ($processInfo['running']) {
                return [
                    'running' => true,
                    'pid' => $pid,
                    'queue' => (string) config('queue.default', 'database'),
                    'started_at' => $this->readStartedAt(),
                    'command' => $processInfo['command'],
                ];
            }

            $this->clearState();
        }

        /*
         * Discover a worker that was started manually outside this manager.
         * This is deliberately an explicit status action, never mount-time
         * dashboard logic.
         */
        $discovered = $this->discoverWorker();

        if ($discovered) {
            file_put_contents(
                storage_path(self::PID_FILE),
                (string) $discovered['pid'],
                LOCK_EX
            );

            return [
                'running' => true,
                'pid' => $discovered['pid'],
                'queue' => (string) config('queue.default', 'database'),
                'started_at' => $this->readStartedAt(),
                'command' => $discovered['command'],
                'discovered' => true,
            ];
        }

        return [
            'running' => false,
            'pid' => null,
            'queue' => (string) config('queue.default', 'database'),
            'started_at' => null,
            'command' => null,
            'discovered' => false,
        ];
    }

    public function isRunning(): bool
    {
        return $this->status()['running'];
    }

    public function logs(int $lines = 50): array
    {
        return [
            'stdout' => $this->tail(self::STDOUT_LOG, $lines),
            'stderr' => $this->tail(self::STDERR_LOG, $lines),
        ];
    }

    public function paths(): array
    {
        return [
            'pid' => storage_path(self::PID_FILE),
            'started' => storage_path(self::STARTED_FILE),
            'stdout' => storage_path(self::STDOUT_LOG),
            'stderr' => storage_path(self::STDERR_LOG),
        ];
    }

    private function inspectProcess(int $pid): array
    {
        if ($pid <= 0) {
            return ['running' => false, 'command' => null];
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // tasklist only tells us that *some* process owns the PID.
            // We must verify that the PID is really our Laravel queue:work process.
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

            $isPhp = $name === 'php.exe' || str_ends_with($name, '\php.exe');
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
            'running' => $command !== '',
            'command' => $command,
        ];
    }

    private function discoverWorker(): ?array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // IMPORTANT: the old query matched its own PowerShell command line
            // because the query itself contained the text "artisan queue:work".
            // Restrict discovery to actual php.exe processes and validate the command.
            $result = Process::run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                "Get-CimInstance Win32_Process | Where-Object { \$_.Name -ieq 'php.exe' -and \$_.CommandLine -match '(?i)artisan(?:\\.php)?\\s+queue:work(?:\\s|$)' } | Select-Object ProcessId,CommandLine | ConvertTo-Json -Compress",
            ]);

            if ($result->failed() || trim($result->output()) === '') {
                return null;
            }

            $decoded = json_decode(trim($result->output()), true);

            if (! is_array($decoded)) {
                return null;
            }

            if (isset($decoded['ProcessId'])) {
                $decoded = [$decoded];
            }

            foreach ($decoded as $item) {
                $candidate = (int) ($item['ProcessId'] ?? 0);
                $command = (string) ($item['CommandLine'] ?? '');

                if (
                    $candidate > 0 &&
                    $candidate !== getmypid() &&
                    preg_match('~(?:^|[\s"\\\\])artisan(?:\.php)?\s+queue:work(?:\s|$)~i', $command) === 1
                ) {
                    return [
                        'pid' => $candidate,
                        'command' => $command,
                    ];
                }
            }

            return null;
        }

        $result = Process::run([
            'pgrep',
            '-af',
            base_path('artisan') . ' queue:work',
        ]);

        if ($result->failed()) {
            return null;
        }

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

            if ($candidate > 0 && $candidate !== getmypid()) {
                return [
                    'pid' => $candidate,
                    'command' => $command,
                ];
            }
        }

        return null;
    }

    private function joinPowerShellArguments(array $arguments): string
    {
        return implode(",\n    ", $arguments);
    }

    private function escapePowerShellSingleQuote(string $value): string
    {
        return str_replace("'", "''", $value);
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

        /*
         * Windows taskkill /F should normally terminate immediately.
         * On Unix, if SIGTERM did not stop the process within 5 seconds,
         * escalate to SIGKILL so the dashboard never reports a false stop.
         */
        if (PHP_OS_FAMILY !== 'Windows' && $this->inspectProcess($pid)['running']) {
            if (function_exists('posix_kill')) {
                @posix_kill($pid, SIGKILL);
            } else {
                Process::run(['kill', '-KILL', (string) $pid]);
            }
        }
    }

    private function ensureRuntimeDirectories(): void
    {
        foreach ([
            storage_path('framework'),
            storage_path('logs'),
        ] as $directory) {
            if (! is_dir($directory)) {
                if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
                    throw new RuntimeException("Tidak dapat membuat directory: {$directory}");
                }
            }
        }
    }

    private function readPid(): ?int
    {
        $path = storage_path(self::PID_FILE);

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

    private function clearStaleState(): void
    {
        foreach ([self::PID_FILE, self::STARTED_FILE] as $relative) {
            $path = storage_path($relative);

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function clearState(): void
    {
        $this->clearStaleState();
    }

    private function tail(string $relativePath, int $lines): string
    {
        $path = storage_path($relativePath);

        if (! is_file($path)) {
            return '';
        }

        $content = file($path, FILE_IGNORE_NEW_LINES);

        if (! $content) {
            return '';
        }

        return implode(PHP_EOL, array_slice($content, -max(1, $lines)));
    }
}
