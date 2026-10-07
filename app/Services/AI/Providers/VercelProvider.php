<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use RuntimeException;

final class VercelProvider implements ImageProviderInterface
{
    private const BASE_URL = 'https://ai-gateway.vercel.sh/v1';

    public function name(): string
    {
        return 'vercel';
    }

    public function supportsModel(string $model): bool
    {
        $model = strtolower(trim($model));

        return $model !== ''
            && (
                str_contains($model, '/')
                || str_starts_with($model, 'gpt-image-')
            );
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        throw new RuntimeException(
            'VercelProvider belum diaktifkan. Migrasi transport Vercel dari OpenAiImageService dilakukan pada tahap berikutnya.'
        );
    }
}
