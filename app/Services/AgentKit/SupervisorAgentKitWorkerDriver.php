<?php

namespace App\Services\AgentKit;

use App\Contracts\AgentKitWorkerDriver;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class SupervisorAgentKitWorkerDriver implements AgentKitWorkerDriver
{
    public function __construct(
        private readonly int $workerCount,
        private readonly string $queue,
        private readonly string $program,
        private readonly string $binary,
    ) {}

    public function start(): array
    {
        $current = $this->status();

        if (($current['running_count'] ?? 0) >= $this->workerCount) {
            return ['success' => false, 'message' => sprintf('Semua %d AgentKit worker sudah berjalan.', $this->workerCount), ...$current];
        }

        $this->runSupervisor('start');
        sleep(1);
        $status = $this->status();

        if (($status['running_count'] ?? 0) === 0) {
            throw new RuntimeException('Supervisor start berhasil dipanggil, tetapi tidak ada AgentKit worker yang RUNNING.');
        }

        return [
            'success' => true,
            'message' => sprintf('%d/%d AgentKit worker aktif melalui Supervisor.', $status['running_count'], $this->workerCount),
            ...$status,
        ];
    }

    public function stop(): array
    {
        $status = $this->status();

        if (($status['running_count'] ?? 0) === 0) {
            return ['success' => false, 'message' => 'Tidak ada AgentKit worker yang sedang berjalan.', ...$status];
        }

        $this->runSupervisor('stop');
        $finalStatus = $this->status();

        return [
            'success' => true,
            'message' => 'Semua AgentKit worker berhasil dihentikan melalui Supervisor.',
            ...$finalStatus,
        ];
    }

    public function restart(): array
    {
        $this->runSupervisor('restart');
        sleep(1);
        $status = $this->status();

        return [
            'success' => ($status['running_count'] ?? 0) > 0,
            'message' => sprintf('%d/%d AgentKit worker aktif setelah restart.', $status['running_count'], $this->workerCount),
            ...$status,
        ];
    }

    public function status(): array
    {
        $result = Process::run($this->supervisorCommand('status'));
        $output = trim($result->output() . PHP_EOL . $result->errorOutput());

        if ($result->failed()) {
            return [
                'running' => false,
                'healthy' => false,
                'running_count' => 0,
                'worker_count' => 0,
                'target_workers' => $this->workerCount,
                'workers' => [],
                'pid' => null,
                'queue' => $this->queue,
                'started_at' => null,
                'command' => 'supervisor:' . $this->program,
                'supervisor_error' => $output !== '' ? $output : 'Supervisor status gagal.',
            ];
        }

        $workers = [];

        foreach (preg_split('/\R+/', trim($result->output())) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|FATAL|EXITED|BACKOFF)(?:\s+pid\s+(\d+))?/i', $line, $m) !== 1) continue;
            if (! str_starts_with($m[1], $this->program . ':')) continue;

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
            'healthy' => count($runningWorkers) >= $this->workerCount,
            'running_count' => count($runningWorkers),
            'worker_count' => count($workers),
            'target_workers' => $this->workerCount,
            'workers' => $workers,
            'pid' => $runningWorkers[0]['pid'] ?? null,
            'queue' => $this->queue,
            'started_at' => $runningWorkers !== [] ? now()->toIso8601String() : null,
            'command' => 'supervisor:' . $this->program,
            'supervisor_error' => null,
        ];
    }

    public function logs(int $lines = 50): array
    {
        $result = Process::run($this->supervisorCommand('status'));

        return [
            'stdout' => trim($result->output()),
            'stderr' => trim($result->errorOutput()),
        ];
    }

    private function runSupervisor(string $action): void
    {
        $result = Process::run($this->supervisorCommand($action));

        if ($result->failed()) {
            $message = trim($result->errorOutput() ?: $result->output());
            throw new RuntimeException('Supervisor gagal menjalankan aksi AgentKit worker' . ($message !== '' ? ': ' . $message : '.'));
        }
    }

    private function supervisorCommand(string $action): array
    {
        return ['sudo', '-n', $this->binary, $action, $this->program . ':*'];
    }
}
