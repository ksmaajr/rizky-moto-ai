<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NvidiaImageService
{
    private const MODELS = [
        'black-forest-labs/flux.3-image-klein' => [
            'endpoint' => 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.3-image-klein',
            'label' => 'FLUX.3 Image Klein',
            'supports_editing' => true,
        ],
        'black-forest-labs/flux.2-klein-4b' => [
            'endpoint' => 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.2-klein-4b',
            'label' => 'FLUX.2 Klein 4B',
            'supports_editing' => true,
        ],
        'black-forest-labs/flux.1-kontext-dev' => [
            'endpoint' => 'https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.1-kontext-dev',
            'label' => 'FLUX.1 Kontext-dev',
            'supports_editing' => true,
        ],
    ];

    public function models(): array
    {
        return collect(self::MODELS)->map(fn (array $model, string $id) => [
            'id' => $id,
            'label' => $model['label'],
            'supports_editing' => $model['supports_editing'],
        ])->values()->all();
    }

    public function generate(
        string $apiKey,
        string $model,
        string $prompt,
        ?string $imageDataUri = null,
    ): array {
        $definition = self::MODELS[$model] ?? null;

        if (! $definition) {
            throw new RuntimeException('Model NVIDIA yang dipilih tidak didukung oleh konfigurasi ini.');
        }

        $payload = [
            'prompt' => mb_substr(trim($prompt), 0, $model === 'black-forest-labs/flux.1-kontext-dev' ? 10000 : 2000),
            'height' => 1024,
            'width' => 1024,
            'seed' => 0,
        ];

        if ($model === 'black-forest-labs/flux.3-image-klein') {
            $payload += [
                'mode' => $imageDataUri ? 'Image Editing' : 'Image Generation',
                'cfg_scale' => 3.5,
                'image' => $imageDataUri,
                'samples' => 1,
                'steps' => 30,
            ];
        } elseif ($model === 'black-forest-labs/flux.2-klein-4b') {
            $payload += [
                'mode' => $imageDataUri ? 'Image Editing' : 'Image Generation',
                'image' => $imageDataUri,
                'steps' => 30,
            ];
        } else {
            $payload += [
                'image' => $imageDataUri,
                'cfg_scale' => 3.5,
                'aspect_ratio' => 'match_input_image',
                'steps' => 30,
            ];
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(12)
            ->timeout(180)
            ->post($definition['endpoint'], $payload);

        if (! $response->successful()) {
            throw new NvidiaImageRequestException(
                $this->safeErrorMessage($response),
                $response->status(),
                $model,
            );
        }

        $image = $this->extractImage($response);

        if (! $image) {
            throw new NvidiaImageRequestException(
                'NVIDIA merespons tanpa data gambar yang dikenali. Periksa format respons model dan endpoint yang aktif.',
                $response->status(),
                $model,
            );
        }

        return [
            'image' => $image,
            'http_status' => $response->status(),
            'model' => $model,
            'endpoint' => $definition['endpoint'],
            'response_keys' => array_keys($response->json() ?? []),
        ];
    }

    private function extractImage(Response $response): ?string
    {
        $body = $response->json() ?? [];

        $candidates = [
            data_get($body, 'artifacts.0.base64'),
            data_get($body, 'artifacts.0.b64_json'),
            data_get($body, 'data.0.b64_json'),
            data_get($body, 'images.0.b64_json'),
            data_get($body, 'image'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            if (str_starts_with($candidate, 'data:image/')) {
                $candidate = substr($candidate, strpos($candidate, ',') + 1);
            }

            if (base64_decode($candidate, true) !== false) {
                return $candidate;
            }
        }

        $url = data_get($body, 'data.0.url') ?? data_get($body, 'images.0.url') ?? data_get($body, 'artifacts.0.url');
        if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $download = Http::timeout(45)->get($url);
            if ($download->successful() && str_starts_with((string) $download->header('Content-Type'), 'image/')) {
                return base64_encode($download->body());
            }
        }

        return null;
    }

    private function safeErrorMessage(Response $response): string
    {
        $message = data_get($response->json(), 'detail')
            ?? data_get($response->json(), 'error.message')
            ?? data_get($response->json(), 'message')
            ?? $response->body();

        $message = trim(strip_tags((string) $message));
        if ($response->status() === 401 || $response->status() === 403) {
            return 'Autentikasi ditolak NVIDIA. Periksa API key dan akses model pada akunmu.';
        }
        if ($response->status() === 429) {
            return 'NVIDIA membatasi request (HTTP 429). Periksa quota, rate limit, atau status trial endpoint.';
        }

        return mb_substr($message !== '' ? $message : 'Request NVIDIA gagal.', 0, 1200);
    }
}

class NvidiaImageRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $model = null,
    ) {
        parent::__construct($message);
    }
}
