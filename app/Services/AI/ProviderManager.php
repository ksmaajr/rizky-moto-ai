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
     * Vercel keeps its live Gateway catalog. AgentKit intentionally exposes
     * only the two models supported by the upstream toolkit.
     *
     * @return array<int,array{id:string,value:string,label:string,description:string,owned_by:string}>
     */
    public function availableModels(?int $userId = null): array
    {
        $provider = $this->activeProviderName($userId);

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

        return app(AppServicesOpenAiImageService::class)->availableImageModels();
    }

    /**
     * Resolve the queue that should execute a generation. AgentKit gets its
     * dedicated queue so the AgentKit worker pool can be stopped independently
     * from the normal application queue workers.
     */
    public function queueForGeneration(string $model, ?int $userId = null): string
    {
        $provider = $this->providerFor($model, $userId);

        return $provider->name() === 'agentkit'
            ? (string) config('services.agent_ai.queue', 'agentkit')
            : (string) config('queue.default', 'database');
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        $setting = $this->settingFor($request->userId);
        $primary = $this->providerFor($request->model, $request->userId);

        try {
            return $primary->generate($request);
        } catch (Throwable $primaryError) {
            $fallbackName = $setting?->fallback_provider;

            if (! $setting?->allow_provider_fallback || ! $fallbackName || $fallbackName === $primary->name()) {
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
