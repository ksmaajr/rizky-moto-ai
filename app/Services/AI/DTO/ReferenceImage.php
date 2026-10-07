<?php

namespace App\Services\AI\DTO;

final readonly class ReferenceImage
{
    public function __construct(
        public string $role,
        public string $path,
        public string $filename,
    ) {
    }

    /**
     * Convert the semantic reference into a safe public manifest entry.
     *
     * The filesystem path is intentionally excluded so metadata/logs cannot
     * accidentally expose server-local paths.
     */
    public function toManifest(): array
    {
        return [
            'role' => $this->role,
            'filename' => $this->filename,
        ];
    }
}
