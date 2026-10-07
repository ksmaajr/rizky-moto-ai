<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;

interface ImageProviderInterface
{
    /**
     * Generate one or more images using the provider.
     *
     * Providers own authentication, HTTP transport, provider-specific
     * request formatting, retries/failover, and response normalization.
     * The generator/orchestrator must not depend on those details.
     */
    public function generate(ImageGenerationRequest $request): ImageGenerationResult;

    /**
     * Determine whether this provider can handle the requested model.
     */
    public function supportsModel(string $model): bool;

    /**
     * Stable provider identifier used by ProviderManager and activity logs.
     */
    public function name(): string;
}
