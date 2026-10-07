<?php

namespace App\Jobs;

use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TestQueueWorkerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(
        public string $testId,
        public int $sequence,
        public int $sleepSeconds = 5,
    ) {
    }

    public function handle(ActivityLogService $activityLog): void
    {
        $startedAt = microtime(true);
        $pid = getmypid();

        $activityLog->info(
            action: 'queue_worker_test_started',
            category: 'worker',
            title: "Queue worker test #{$this->sequence} dimulai.",
            description: "Test {$this->testId} sedang diproses oleh worker PID {$pid}.",
            metadata: [
                'source' => 'queue_worker_test',
                'test_id' => $this->testId,
                'sequence' => $this->sequence,
                'worker_pid' => $pid,
                'sleep_seconds' => $this->sleepSeconds,
            ],
        );

        sleep(max(1, min(30, $this->sleepSeconds)));

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $activityLog->success(
            action: 'queue_worker_test_completed',
            category: 'worker',
            title: "Queue worker test #{$this->sequence} selesai.",
            description: "Test {$this->testId} selesai oleh worker PID {$pid}.",
            metadata: [
                'source' => 'queue_worker_test',
                'test_id' => $this->testId,
                'sequence' => $this->sequence,
                'worker_pid' => $pid,
                'sleep_seconds' => $this->sleepSeconds,
            ],
            durationMs: $durationMs,
        );
    }

    public function failed(Throwable $exception): void
    {
        app(ActivityLogService::class)->error(
            action: 'queue_worker_test_failed',
            category: 'worker',
            title: "Queue worker test #{$this->sequence} gagal.",
            description: $exception->getMessage(),
            metadata: [
                'source' => 'queue_worker_test',
                'test_id' => $this->testId,
                'sequence' => $this->sequence,
                'worker_pid' => getmypid(),
                'exception' => get_class($exception),
            ],
        );
    }
}
