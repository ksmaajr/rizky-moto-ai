<?php

namespace App\Services\AI;

use App\Models\AiProviderSetting;
use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\ActivityLogService;
use RuntimeException;
use Throwable;

final class ProviderManager
{
    /**
     * @param iterable<ImageProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ActivityLogService $activity,
    ) {
    }

    public function providerFor(string $model, ?int $userId = null): ImageProviderInterface
    {
        $setting = $this->settingFor($userId);
        $activeName = $setting?->active_provider ?: 'vercel';

        foreach ($this->providers as $provider) {
            if ($provider->name() !== $activeName) {
                continue;
            }

            if ($provider->supportsModel($model)) {
                return $provider;
            }

            break;
        }

        if ($setting?->allow_provider_fallback && $setting?->fallback_provider) {
            foreach ($this->providers as $fallback) {
                if (
                    $fallback->name() === $setting->fallback_provider
                    && $fallback->supportsModel($model)
                ) {
                    return $fallback;
                }
            }
        }

        throw new RuntimeException(sprintf(
            'Model "%s" tidak tersedia untuk provider aktif "%s". Pilih model yang tersedia untuk provider tersebut.',
            $model,
            $activeName
        ));
    }

    /**
     * Return the provider currently selected in Settings.
     */
    public function activeProviderName(?int $userId = null): string
    {
        return $this->settingFor($userId)?->active_provider ?: 'vercel';
    }

    /**
     * Return the image model catalog for the currently selected provider.
     *
     * Vercel keeps its live Gateway catalog. AgentKit and NVIDIA expose only the
     * models explicitly supported by their respective adapters.
     *
     * @return array<int,array{id:string,value:string,label:string,description:string,owned_by:string}>
     */
    public function availableModels(?int $userId = null): array
    {
        $provider = $this->activeProviderName($userId);

        if ($provider === 'nvidia') {
            return [
                [
                    'id' => 'black-forest-labs/flux.2-klein-4b',
                    'value' => 'black-forest-labs/flux.2-klein-4b',
                    'label' => 'FLUX.2 Klein 4B',
                    'description' => 'NVIDIA NIM · fast image generation and editing',
                    'owned_by' => 'NVIDIA NIM',
                ],
                [
                    'id' => 'black-forest-labs/flux.3-image-klein',
                    'value' => 'black-forest-labs/flux.3-image-klein',
                    'label' => 'FLUX.3 Image Klein',
                    'description' => 'NVIDIA NIM · high-quality image generation and editing',
                    'owned_by' => 'NVIDIA NIM',
                ],
            ];
        }

        if ($provider === 'agentkit') {
            return [
                [
                    'id' => 'openai/gpt-image-2.5-sunburst',
                    'value' => 'openai/gpt-image-2.5-sunburst',
                    'label' => 'GPT Image 2.5 Sunburst',
                    'description' => 'Agent AI · precision generation & editing',
                    'owned_by' => 'Agent AI',
                ],
                [
                    'id' => 'openai/gpt-image-2.5-flare',
                    'value' => 'openai/gpt-image-2.5-flare',
                    'label' => 'GPT Image 2.5 Flare',
                    'description' => 'Agent AI · fast everyday generation',
                    'owned_by' => 'Agent AI',
                ],
            ];
        }

        return app(\App\Services\OpenAiImageService::class)->availableImageModels();
    }

    /**
     * Resolve the queue that should execute a generation. AgentKit gets its
     * dedicated queue so the AgentKit worker pool can be stopped independently
     * from the normal application queue workers.
     */
    public function queueForGeneration(string $model, ?int $userId = null): string
    {
        $provider = $this->providerFor($model, $userId);

        if ($provider->name() === 'agentkit') {
            return (string) config('services.agent_ai.queue', 'agentkit');
        }

        // queue:work's first argument is the connection name, not the queue name.
        // Route normal providers to the connection's configured default queue
        // (usually "default") instead of incorrectly enqueueing onto "database".
        $connection = (string) config('queue.default', 'database');

        return (string) config("queue.connections.{$connection}.queue", 'default');
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        $setting = $this->settingFor($request->userId);
        $primary = $this->providerFor($request->model, $request->userId);

        try {
            return $primary->generate($request);
        } catch (Throwable $primaryError) {
            // NVIDIA's public Trial endpoint cannot accept the generator's custom
            // product/installed/store-logo references. For that specific known
            // limitation, automatically route the same generation through Vercel
            // using its default image model and the original individual references.
            // This is a compatibility fallback, not a text-only NVIDIA retry.
            if (
                $primary->name() === 'nvidia'
                && str_contains($primaryError->getMessage(), 'NVIDIA API Trial')
                && str_contains($primaryError->getMessage(), 'NVIDIA_FLUX')
            ) {
                $vercel = null;
                foreach ($this->providers as $candidate) {
                    if ($candidate->name() === 'vercel') {
                        $vercel = $candidate;
                        break;
                    }
                }

                if ($vercel && $vercel->supportsModel('openai/gpt-image-2.5-sunburst')) {
                    $fallbackModel = app(\\App\\Services\\OpenAiImageService::class)->defaultImageModel();
                    $fallbackRequest = new ImageGenerationRequest(
                        model: $fallbackModel,
                        prompt: $request->prompt,
                        references: $request->references,
                        imageCount: $request->imageCount,
                        size: $request->size,
                        quality: $request->quality,
                        outputFormat: $request->outputFormat,
                        userId: $request->userId,
                        generationId: $request->generationId,
                        metadata: array_diff_key($request->metadata, [
                            'attachments' => true,
                            'reference_transport' => true,
                        ]),
                    );

                    $this->activity->warning(
                        action: 'ai_provider_failover',
                        category: 'api',
                        title: 'NVIDIA Trial tidak mendukung referensi kustom; beralih ke Vercel.',
                        description: 'Generation dialihkan ke Vercel AI Gateway agar foto produk, foto terpasang (jika ada), dan logo Store tetap dikirim sebagai referensi asli.',
                        metadata: [
                            'generation_id' => $request->generationId,
                            'user_id' => $request->userId,
                            'primary_provider' => 'nvidia',
                            'fallback_provider' => 'vercel',
                            'fallback_model' => $fallbackModel,
                            'reason' => 'nvidia_trial_custom_image_input_unsupported',
                            'reference_count' => count($request->references),
                            'reference_roles' => array_map(
                                static fn ($reference): string => strtolower(trim((string) $reference->role)),
                                $request->references,
                            ),
                        ],
                    );

                    return $vercel->generate($fallbackRequest);
                }
            }

            $fallbackName = $setting?->fallback_provider;

            if (! ($setting?->allow_provider_fallback || $setting?->emergency_fallback) || ! $fallbackName || $fallbackName === $primary->name()) {
                throw $primaryError;
            }

            foreach ($this->providers as $fallback) {
                if ($fallback->name() !== $fallbackName || ! $fallback->supportsModel($request->model)) {
                    continue;
                }

                $this->activity->warning(
                    action: 'ai_provider_failover',
                    category: 'api',
                    title: 'AI provider berpindah ke fallback.',
                    description: sprintf('Provider %s gagal, mencoba fallback %s.', $primary->name(), $fallback->name()),
                    metadata: [
                        'generation_id' => $request->generationId,
                        'user_id' => $request->userId,
                        'primary_provider' => $primary->name(),
                        'fallback_provider' => $fallback->name(),
                        'emergency_fallback' => (bool) ($setting->emergency_fallback),
                        'exception' => get_class($primaryError),
                    ],
                );

                return $fallback->generate($request);
            }

            throw $primaryError;
        }
    }

    private function supportsProvider(string $name, string $model): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->name() === $name) {
                return $provider->supportsModel($model);
            }
        }

        return false;
    }

    private function settingFor(?int $userId): ?AiProviderSetting
    {
        if ($userId === null) {
            return AiProviderSetting::query()->whereNull('user_id')->first();
        }

        return AiProviderSetting::query()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId])
            ->first();
    }
}
