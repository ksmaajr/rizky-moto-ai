<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\VercelGatewayKeyPool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class VercelProvider implements ImageProviderInterface
{
    private const BASE_URL = 'https://ai-gateway.vercel.sh/v1';

    public function __construct(
        private readonly VercelGatewayKeyPool $apiPool,
    ) {
    }

    public function name(): string
    {
        return 'vercel';
    }

    public function supportsModel(string $model): bool
    {
        $model = strtolower(trim($model));

        return str_starts_with($model, 'openai/');
    }

    public function generate(ImageGenerationRequest $request): ImageGenerationResult
    {
        $userId = $request->userId;
        $generationId = $request->generationId;

        $attachments = $this->attachments($request);
        $payload = [
            'model' => $request->model,
            'prompt' => $request->prompt,
            'n' => $request->normalizedImageCount(),
            'size' => $request->size,
            'quality' => $request->quality,
            'output_format' => $request->outputFormat,
        ];

        $negativePrompt = trim((string) ($request->metadata['negative_prompt'] ?? ''));
        if ($negativePrompt !== '') {
            $payload['prompt'] .= "\n\nAvoid: " . $negativePrompt;
        }

        $lastError = null;
        $attemptedIds = [];
        $maxAttempts = max(1, $this->apiPool->totalCount($userId) + 1);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $credential = $this->apiPool->acquire($userId, null, $attemptedIds);

            if (! $credential) {
                break;
            }

            if ($credential['id'] !== null) {
                $attemptedIds[] = $credential['id'];
            }

            $requestBuilder = Http::acceptJson()
                ->withToken($credential['key'])
                ->connectTimeout(15)
                ->timeout(240);

            $handles = [];

            try {
                foreach ($attachments as $attachment) {
                    $path = $attachment[0] ?? null;
                    $filename = $attachment[1] ?? basename((string) $path);

                    if (! is_string($path) || ! is_readable($path)) {
                        throw new RuntimeException('File input tidak dapat dibaca: ' . $filename);
                    }

                    $handle = fopen($path, 'rb');
                    if ($handle === false) {
                        throw new RuntimeException('File input tidak dapat dibuka: ' . $filename);
                    }

                    $handles[] = $handle;
                    $requestBuilder = $requestBuilder->attach('image[]', $handle, $filename);
                }

                $response = $requestBuilder->post(self::BASE_URL . '/images/edits', $payload);
            } finally {
                foreach ($handles as $handle) {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                }
            }

            if ($response->successful()) {
                $this->apiPool->reportSuccess($credential['id'], $userId);
                $this->cleanupTemporaryAttachments($attachments);

                return new ImageGenerationResult(
                    images: $this->normalizeImages($response),
                    provider: $this->name(),
                    model: $request->model,
                    metadata: [
                        'gateway_key_id' => $credential['id'] ?? null,
                        'gateway_key_name' => $credential['name'] ?? null,
                        'gateway_key_source' => $credential['source'] ?? null,
                        'http_status' => $response->status(),
                    ],
                );
            }

            $detail = $this->extractError($response);
            $classification = $this->apiPool->reportFailure(
                $credential['id'],
                $userId,
                $response,
                $detail,
            );

            $lastError = $detail;

            if (! $classification['retry']) {
                throw new RuntimeException($detail);
            }
        }

        $this->cleanupTemporaryAttachments($attachments);

        throw new RuntimeException(
            $lastError ?: 'Semua Vercel AI Gateway API key yang tersedia gagal digunakan.'
        );
    }

    /**
     * @return array<int,array{0:string,1:string,2:string,3:bool}>
     */
    private function attachments(ImageGenerationRequest $request): array
    {
        $configured = $request->metadata['attachments'] ?? null;

        if (is_array($configured) && $configured !== []) {
            return $configured;
        }

        return array_map(
            static fn ($reference): array => [
                $reference->path,
                $reference->filename,
                $reference->role,
                false,
            ],
            $request->references,
        );
    }

    /**
     * @return array<int,array{b64_json?:string|null,url?:string|null,provider?:string|null,model?:string|null}>
     */
    private function normalizeImages(Response $response): array
    {
        $data = $response->json('data', []);

        if (! is_array($data)) {
            return [];
        }

        return array_values(array_map(
            static fn ($item): array => [
                'b64_json' => is_array($item) ? ($item['b64_json'] ?? null) : null,
                'url' => is_array($item) ? ($item['url'] ?? null) : null,
                'provider' => 'vercel',
            ],
            $data,
        ));
    }

    private function cleanupTemporaryAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment[0] ?? null;
            $temporary = (bool) ($attachment[3] ?? false);

            if ($temporary && is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function extractError(Response $response): string
    {
        $message = $response->json('error.message');

        if (is_string($message) && trim($message) !== '') {
            return trim($message) . ' (HTTP ' . $response->status() . ')';
        }

        return 'Vercel AI Gateway image generation gagal. HTTP ' . $response->status() . '.';
    }
}
