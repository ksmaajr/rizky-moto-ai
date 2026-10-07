<?php

namespace App\Services\AI\DTO;

final readonly class ImageGenerationResult
{
    /**
     * @param array<int, array{b64_json?:string|null,url?:string|null,provider?:string|null,model?:string|null}> $images
     */
    public function __construct(
        public array $images,
        public string $provider,
        public string $model,
        public array $metadata = [],
    ) {
    }

    public function count(): int
    {
        return count($this->images);
    }

    public function isEmpty(): bool
    {
        return $this->images === [];
    }
}
