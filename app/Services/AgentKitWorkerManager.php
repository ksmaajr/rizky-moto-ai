<?php

namespace App\Services;

use App\Contracts\AgentKitWorkerDriver;
use App\Services\AgentKit\LocalAgentKitWorkerDriver;
use App\Services\AgentKit\SupervisorAgentKitWorkerDriver;
use RuntimeException;
use Throwable;

final class AgentKitWorkerManager
{
    private const MAX_WORKERS = 3;

    public function workerCount(): int
    {
        return max(1, min(
            self::MAX_WORKERS,
            (int) config('services.agent_ai.worker_count', 3)
        ));
    }

    public function driverName(): string
    {
        return strtolower((string) config(
            'services.agent_ai.worker_driver',
            PHP_OS_FAMILY === 'Windows' ? 'local' : 'supervisor'
        ));
    }

    public function start(): array
    {
        $result = $this->driver()->start();
        $this->logActivity('success', 'AgentKit worker aktif.', $result['message'] ?? '', $result);
        return $result;
    }

    public function stop(): array
    {
        $result = $this->driver()->stop();

        if (($result['success'] ?? false) === true) {
            $this->logActivity('success', 'AgentKit worker dihentikan.', $result['message'] ?? '', $result);
        }

        return $result;
    }

    public function restart(): array
    {
        $result = $this->driver()->restart();

        if (($result['success'] ?? false) === true) {
            $this->logActivity('success', 'AgentKit worker direstart.', $result['message'] ?? '', $result);
        }

        return $result;
    }

    public function status(): array
    {
        return [
            ...$this->driver()->status(),
            'driver' => $this->driverName(),
            'platform' => PHP_OS_FAMILY,
        ];
    }

    public function logs(int $lines = 50): array
    {
        return $this->driver()->logs($lines);
    }

    private function driver(): AgentKitWorkerDriver
    {
        return match ($this->driverName()) {
            'local' => new LocalAgentKitWorkerDriver(
                workerCount: $this->workerCount(),
                queue: $this->queue(),
                timeout: (int) config('services.agent_ai.timeout', 300),
            ),
            'supervisor' => new SupervisorAgentKitWorkerDriver(
                workerCount: $this->workerCount(),
                queue: $this->queue(),
                program: (string) config('services.agent_ai.worker_supervisor_program', 'rizky-moto-ai-agent'),
                binary: (string) env('AGENT_AI_WORKER_SUPERVISOR_BIN', '/usr/bin/supervisorctl'),
            ),
            default => throw new RuntimeException(
                "AgentKit worker driver '{$this->driverName()}' tidak didukung. Gunakan 'local' atau 'supervisor'."
            ),
        };
    }

    private function queue(): string
    {
        return (string) config('services.agent_ai.queue', 'agentkit');
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
                    'driver' => $this->driverName(),
                    ...$metadata,
                ],
            );
        } catch (Throwable) {
        }
    }
}
