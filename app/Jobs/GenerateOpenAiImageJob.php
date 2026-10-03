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
use Throwable;

class GenerateOpenAiImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $generationId)
    {
    }

    public function handle(OpenAiImageService $service): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->status === 'completed') {
            return;
        }

        $service->processQueuedGeneration($generation);
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
            ],
        );
    }
}
