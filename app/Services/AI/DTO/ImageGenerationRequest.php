<?php

namespace App\Services\AI\DTO;

final readonly class ImageGenerationRequest
{
    /**
     * @param array<int, ReferenceImage> $references
     */
    public function __construct(
        public string $model,
        public string $prompt,
        public array $references = [],
        public int $imageCount = 1,
        public string $size = '1024x1024',
        public string $quality = 'medium',
        public string $outputFormat = 'png',
        public ?int $userId = null,
        public ?int $generationId = null,
        public array $metadata = [],
    ) {
    }

    public function normalizedImageCount(): int
    {
        return max(1, min(4, $this->imageCount));
    }
}
