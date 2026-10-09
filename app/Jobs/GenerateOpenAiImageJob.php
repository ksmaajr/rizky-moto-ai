<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\ActivityLogService;
use App\Services\OpenAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class GenerateOpenAiImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;
    public bool $failOnTimeout = true;

    public function __construct(public int $generationId)
    {
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('generation:' . $this->generationId))
                ->dontRelease()
                ->expireAfter(330),
        ];
    }

    public function handle(OpenAiImageService $service): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation || in_array($generation->status, ['completed', 'cancelled'], true)) {
            return;
        }

        $metadata = array_merge($generation->metadata ?? [], [
            'worker_pid' => getmypid(),
            'worker_label' => $this->workerLabel(),
            'queue_attempt' => $this->attempts(),
            'queue_job_id' => $this->job?->getJobId(),
            'queue' => (string) ($this->job?->getQueue() ?: config('queue.connections.' . config('queue.default', 'database') . '.queue', 'default')),
            'worker_started_at' => now()->toIso8601String(),
        ]);

        $generation->update(['metadata' => $metadata]);

        app(ActivityLogService::class)->processing(
            action: 'generation_worker_started',
            category: 'worker',
            title: 'Generation diambil oleh queue worker.',
            description: 'Worker mulai memproses Generation #' . $generation->id . '.',
            metadata: [
                'generation_id' => $generation->id,
                'worker_pid' => getmypid(),
                'worker_label' => $this->workerLabel(),
                'queue_attempt' => $this->attempts(),
                'queue_job_id' => $this->job?->getJobId(),
                'queue' => (string) ($this->job?->getQueue() ?: config('queue.connections.' . config('queue.default', 'database') . '.queue', 'default')),
            ],
        );

        $service->processQueuedGeneration($generation);
    }

    private function workerLabel(): string
    {
        $process = trim((string) env('RIZKY_QUEUE_WORKER_PROCESS', ''));
        $queue = (string) ($this->job?->getQueue() ?: config('queue.default', 'database'));

        if ($queue === (string) config('services.agent_ai.queue', 'agentkit')) {
            return $process !== ''
                ? 'agentkit-worker-' . $process
                : 'agentkit-worker-pid-' . getmypid();
        }

        return $process !== ''
            ? 'worker-' . $process
            : 'worker-pid-' . getmypid();
    }

    public function failed(Throwable $exception): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation) {
            return;
        }

        if ($generation->status === 'failed') {
            return;
        }

        $generation->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
            'metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 100,
                'progress_stage' => 'Gagal',
                'failed_at' => now()->toIso8601String(),
                'worker_pid' => getmypid(),
                'worker_label' => $this->workerLabel(),
            ]),
        ]);

        app(ActivityLogService::class)->error(
            action: 'generate_openai_image',
            category: 'generator',
            title: 'Background generation gagal.',
            description: $exception->getMessage(),
            metadata: [
                'source' => 'queue_failed',
                'generation_id' => $this->generationId,
                'exception' => get_class($exception),
                'worker_pid' => getmypid(),
                'worker_label' => $this->workerLabel(),
                'queue_job_id' => $this->job?->getJobId(),
            ],
        );
    }
}
