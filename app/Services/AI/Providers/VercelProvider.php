<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ImageProviderInterface;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ImageGenerationResult;
use App\Services\ActivityLogService;
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
        private readonly ActivityLogService $activity,
    ) {
    }

    public function name(): string
    {
        return 'vercel';
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with(strtolower(trim($model)), 'openai/');
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

        $promptLength = mb_strlen((string) $payload['prompt']);
        if ($promptLength > 32000) {
            throw new RuntimeException(
                'Prompt terlalu panjang untuk Vercel AI Gateway: '
                . number_format($promptLength, 0, ',', '.')
                . ' karakter. Maksimum 32.000 karakter. '
                . 'Periksa prompt Template atau input generation sebelum mencoba lagi.'
            );
        }

        $lastError = null;
        $attemptedIds = [];
        $maxAttempts = max(1, $this->apiPool->totalCount($userId) + 1);

        try {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $credential = $this->apiPool->acquire($userId, null, $attemptedIds);

                if (! $credential) {
                    $this->activity->error(
                        action: 'ai_provider_credential_missing',
                        category: 'api',
                        title: 'Vercel AI Gateway API Key tidak tersedia.',
                        description: 'Tidak ada credential aktif yang dapat digunakan untuk generation.',
                        metadata: [
                            'provider' => $this->name(),
                            'model' => $request->model,
                            'generation_id' => $generationId,
                            'user_id' => $userId,
                        ],
                    );

                    break;
                }

                if ($credential['id'] !== null) {
                    $attemptedIds[] = $credential['id'];
                }

                $this->activity->processing(
                    action: 'ai_provider_request',
                    category: 'api',
                    title: 'Request Vercel AI Gateway dimulai.',
                    description: sprintf(
                        'Attempt %d menggunakan credential %s untuk model %s.',
                        $attempt,
                        $credential['name'] ?? 'unknown',
                        $request->model
                    ),
                    metadata: [
                        'provider' => $this->name(),
                        'model' => $request->model,
                        'generation_id' => $generationId,
                        'gateway_key_id' => $credential['id'] ?? null,
                        'gateway_key_name' => $credential['name'] ?? null,
                        'gateway_key_source' => $credential['source'] ?? null,
                        'attempt' => $attempt,
                        'attachment_count' => count($attachments),
                        'image_count' => $request->normalizedImageCount(),
                    ],
                );

                $requestBuilder = Http::acceptJson()
                    ->withToken($credential['key'])
                    ->connectTimeout(15)
                    ->timeout(240);

                $handles = [];
                $response = null;

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

                    $response = $requestBuilder->post(
                        self::BASE_URL . '/images/edits',
                        $payload
                    );
                } catch (Throwable $e) {
                    $this->activity->error(
                        action: 'ai_provider_request',
                        category: 'api',
                        title: 'Request Vercel AI Gateway gagal sebelum response.',
                        description: $e->getMessage(),
                        metadata: [
                            'provider' => $this->name(),
                            'model' => $request->model,
                            'generation_id' => $generationId,
                            'gateway_key_id' => $credential['id'] ?? null,
                            'gateway_key_name' => $credential['name'] ?? null,
                            'attempt' => $attempt,
                            'exception' => get_class($e),
                        ],
                    );

                    throw $e;
                } finally {
                    foreach ($handles as $handle) {
                        if (is_resource($handle)) {
                            fclose($handle);
                        }
                    }
                }

                if ($response->successful()) {
                    $images = $this->normalizeImages($response);

                    $this->apiPool->reportSuccess($credential['id'], $userId);
                    $this->activity->success(
                        action: 'ai_provider_request',
                        category: 'api',
                        title: 'Vercel AI Gateway request berhasil.',
                        description: sprintf(
                            'Provider mengembalikan %d image item dengan HTTP %d.',
                            count($images),
                            $response->status()
                        ),
                        metadata: [
                            'provider' => $this->name(),
                            'model' => $request->model,
                            'generation_id' => $generationId,
                            'gateway_key_id' => $credential['id'] ?? null,
                            'gateway_key_name' => $credential['name'] ?? null,
                            'gateway_key_source' => $credential['source'] ?? null,
                            'attempt' => $attempt,
                            'image_count' => count($images),
                        ],
                        httpStatus: $response->status(),
                    );

                    return new ImageGenerationResult(
                        images: $images,
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

                $this->activity->error(
                    action: 'ai_provider_request',
                    category: 'api',
                    title: 'Vercel AI Gateway request gagal.',
                    description: $detail,
                    metadata: [
                        'provider' => $this->name(),
                        'model' => $request->model,
                        'generation_id' => $generationId,
                        'gateway_key_id' => $credential['id'] ?? null,
                        'gateway_key_name' => $credential['name'] ?? null,
                        'attempt' => $attempt,
                        'classification' => $classification['reason'],
                        'retry' => $classification['retry'],
                    ],
                    httpStatus: $response->status(),
                );

                if (! $classification['retry']) {
                    throw new RuntimeException($detail);
                }
            }
        } finally {
            // Temporary reference boards/files must be removed even when an
            // attachment read, HTTP request, or response handler throws.
            $this->cleanupTemporaryAttachments($attachments);
        }

        $message = $lastError
            ?: 'Semua Vercel AI Gateway API key yang tersedia gagal digunakan.';

        $this->activity->error(
            action: 'ai_provider_exhausted',
            category: 'api',
            title: 'Semua credential Vercel gagal.',
            description: $message,
            metadata: [
                'provider' => $this->name(),
                'model' => $request->model,
                'generation_id' => $generationId,
                'attempts' => count($attemptedIds),
            ],
        );

        throw new RuntimeException($message);
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
            fn ($item): array => [
                'b64_json' => is_array($item) ? ($item['b64_json'] ?? null) : null,
                'url' => is_array($item) ? ($item['url'] ?? null) : null,
                'provider' => $this->name(),
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
