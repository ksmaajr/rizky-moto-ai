<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use RuntimeException;

final class ProviderManager
{
    /**
     * @param iterable<ImageProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function providerFor(string $model): ImageProviderInterface
    {
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
        return $this->providerFor($request->model)->generate($request);
    }
}
