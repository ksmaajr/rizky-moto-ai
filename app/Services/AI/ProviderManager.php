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

        if ($setting?->active_provider) {
            foreach ($this->providers as $provider) {
                if ($provider->name() === $setting->active_provider && $provider->supportsModel($model)) {
                    return $provider;
                }
            }
        }

        foreach ($this->providers as $provider) {
            if ($provider->supportsModel($model)) {
                return $provider;
            }
        }

        throw new RuntimeException(
            'Tidak ada AI image provider yang mendukung model: ' . $model
        );
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
