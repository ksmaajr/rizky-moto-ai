<?php

namespace App\Console\Commands;

use App\Services\AgentKitWorkerManager;
use Illuminate\Console\Command;
use Throwable;

class AgentKitWorkerCommand extends Command
{
    protected $signature = 'agent:worker
                            {action : start, stop, restart, status, logs}
                            {--lines=30 : Number of log lines for the logs action}';

    protected $description = 'Manage the AgentKit queue worker pool.';

    public function handle(AgentKitWorkerManager $manager): int
    {
        $action = strtolower((string) $this->argument('action'));

        try {
            $result = match ($action) {
                'start' => $manager->start(),
                'stop' => $manager->stop(),
                'restart' => $manager->restart(),
                'status' => $manager->status(),
                'logs' => null,
                default => throw new \InvalidArgumentException(
                    'Action harus: start, stop, restart, status, atau logs.'
                ),
            };

            if ($action === 'logs') {
                $logs = $manager->logs((int) $this->option('lines'));

                $this->line('<fg=cyan>STDOUT</>');
                $this->line($logs['stdout'] !== '' ? $logs['stdout'] : '(kosong)');
                $this->newLine();
                $this->line('<fg=yellow>STDERR</>');
                $this->line($logs['stderr'] !== '' ? $logs['stderr'] : '(kosong)');

                return self::SUCCESS;
            }

            $this->table(
                ['Key', 'Value'],
                collect($result)
                    ->map(fn ($value, $key) => [
                        $key,
                        is_scalar($value) || $value === null
                            ? (string) ($value ?? 'null')
                            : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ])
                    ->values()
                    ->all()
            );

            return ($result['success'] ?? true) ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
