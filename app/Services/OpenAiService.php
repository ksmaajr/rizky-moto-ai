<?php

namespace App\Services;

use App\Models\OpenAiConnectionLog;
use App\Models\OpenAiSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiService
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function saveConfiguration(
        ?string $apiKey,
        string $model,
        string $aspectRatio,
        string $quality
    ): OpenAiSetting {
        $settings = OpenAiSetting::query()->first();

        if (! $settings) {
            $settings = new OpenAiSetting();
        }

        if (filled($apiKey)) {
            $settings->api_key = trim($apiKey);
        }

        $settings->model = $model;
        $settings->default_aspect_ratio = $aspectRatio;
        $settings->default_quality = $quality;
        $settings->is_active = true;
        $settings->save();

        Cache::forget('openai.image-models');

        return $settings->fresh();
    }

    /**
     * Save only the API key while preserving the current OpenAI configuration.
     * The dashboard settings screen uses this convenience method.
     */
    public function saveApiKey(?string $apiKey): OpenAiSetting
    {
        $settings = OpenAiSetting::query()->first();

        return $this->saveConfiguration(
            apiKey: $apiKey,
            model: $settings?->model ?: 'OpenAI Image Generation',
            aspectRatio: $settings?->default_aspect_ratio ?: '1:1',
            quality: $settings?->default_quality ?: 'standard',
        );
    }

    /**
     * Return image-capable model IDs directly from the authenticated OpenAI account.
     * No manual model list is maintained in the application.
     */
    public function availableImageModels(?string $apiKey = null): array
    {
        $settings = OpenAiSetting::query()->first();

        $key = filled($apiKey)
            ? trim($apiKey)
            : ($settings?->api_key ?? null);

        if (! filled($key)) {
            return [];
        }

        return Cache::remember('openai.image-models', now()->addMinutes(5), function () use ($key) {
            $response = Http::acceptJson()
                ->withToken($key)
                ->connectTimeout(8)
                ->timeout(20)
                ->get(self::BASE_URL . '/models');

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('data', []))
                ->filter(function (array $model): bool {
                    $id = strtolower((string) ($model['id'] ?? ''));

                    return str_contains($id, 'gpt-image')
                        || str_contains($id, 'chatgpt-image');
                })
                ->map(fn (array $model) => [
                    'id' => (string) $model['id'],
                    'value' => (string) $model['id'],
                    'label' => (string) $model['id'],
                    'description' => (string) ($model['owned_by'] ?? 'OpenAI'),
                    'icon' => 'AI',
                    'owned_by' => (string) ($model['owned_by'] ?? 'OpenAI'),
                ])
                ->sortBy('id')
                ->values()
                ->all();
        });
    }

    public function testConnection(
        ?string $apiKey = null,
        ?string $model = null
    ): array {
        $settings = OpenAiSetting::query()->first();

        $key = filled($apiKey)
            ? trim($apiKey)
            : ($settings?->api_key ?? null);

        $selectedModel = $model
            ?: ($settings?->model ?? 'OpenAI Image Generation');

        if (! filled($key)) {
            $message = 'API key belum tersedia.';
            $detail = 'Simpan API key OpenAI terlebih dahulu sebelum melakukan test connection.';

            $this->activityLog()->error(
                action: 'test_openai_connection',
                category: 'api',
                title: 'Test koneksi OpenAI gagal.',
                description: $detail,
                metadata: ['source' => 'openai_service', 'reason' => 'missing_api_key'],
                durationMs: 0,
            );

            $this->saveConnectionLog(
                status: 'error',
                message: $message,
                detail: $detail,
                httpStatus: null,
                durationMs: 0,
                model: $this->normalizeModel($selectedModel),
            );

            $this->updateTestStatus('error', $message);

            return [
                'status' => 'error',
                'title' => 'Test koneksi gagal',
                'message' => $message,
                'detail' => $detail,
                'has_key' => false,
            ];
        }

        $startedAt = microtime(true);

        $this->activityLog()->processing(
            action: 'test_openai_connection',
            category: 'api',
            title: 'Test koneksi OpenAI dimulai.',
            description: 'Mengirim authenticated request ke OpenAI /models.',
            metadata: [
                'source' => 'openai_service',
                'model' => $this->normalizeModel($selectedModel),
            ],
        );

        try {
            $response = Http::acceptJson()
                ->withToken($key)
                ->connectTimeout(8)
                ->timeout(20)
                ->get(self::BASE_URL . '/models');

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($response->successful()) {
                $message = 'OpenAI API berhasil terhubung.';
                $detail = sprintf(
                    'Authenticated request berhasil. HTTP %s • %d ms',
                    $response->status(),
                    $durationMs
                );

                $this->saveConnectionLog(
                    status: 'success',
                    message: $message,
                    detail: $detail,
                    httpStatus: $response->status(),
                    durationMs: $durationMs,
                    model: $this->normalizeModel($selectedModel),
                );

                $this->activityLog()->success(
                    action: 'test_openai_connection',
                    category: 'api',
                    title: 'OpenAI API berhasil terhubung.',
                    description: $detail,
                    metadata: [
                        'source' => 'openai_service',
                        'model' => $this->normalizeModel($selectedModel),
                    ],
                    durationMs: $durationMs,
                    httpStatus: $response->status(),
                );

                $this->updateTestStatus('success', $message);

                return [
                    'status' => 'success',
                    'title' => 'Koneksi berhasil',
                    'message' => $message,
                    'detail' => $detail,
                    'has_key' => true,
                ];
            }

            $status = $response->status();

            $message = match (true) {
                $status === 401 => 'API key tidak valid atau sudah tidak aktif.',
                $status === 403 => 'API key tidak memiliki izin yang diperlukan.',
                $status === 429 => 'Request OpenAI terkena rate limit atau quota.',
                $status >= 500 => 'OpenAI sedang mengalami gangguan server.',
                default => 'OpenAI mengembalikan response error.',
            };

            $detail = $this->extractErrorDetail($response);

            $this->saveConnectionLog(
                status: 'error',
                message: $message,
                detail: $detail,
                httpStatus: $status,
                durationMs: $durationMs,
                model: $this->normalizeModel($selectedModel),
            );

            $this->activityLog()->error(
                action: 'test_openai_connection',
                category: 'api',
                title: 'Test koneksi OpenAI gagal.',
                description: $detail,
                metadata: [
                    'source' => 'openai_service',
                    'model' => $this->normalizeModel($selectedModel),
                ],
                durationMs: $durationMs,
                httpStatus: $status,
            );

            $this->updateTestStatus('error', $message);

            return [
                'status' => 'error',
                'title' => 'Test koneksi gagal',
                'message' => $message,
                'detail' => $detail,
                'has_key' => true,
            ];
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $detail = $e->getMessage();

            $this->activityLog()->error(
                action: 'test_openai_connection',
                category: 'api',
                title: 'Test koneksi OpenAI mengalami error.',
                description: $detail,
                metadata: [
                    'source' => 'openai_service',
                    'model' => $this->normalizeModel($selectedModel),
                    'exception' => get_class($e),
                ],
                durationMs: $durationMs,
            );

            $this->saveConnectionLog(
                status: 'error',
                message: 'Terjadi error internal saat menghubungi OpenAI.',
                detail: $detail,
                httpStatus: null,
                durationMs: $durationMs,
                model: $this->normalizeModel($selectedModel),
            );

            $this->updateTestStatus(
                'error',
                'Terjadi error internal saat menghubungi OpenAI.'
            );

            return [
                'status' => 'error',
                'title' => 'Test koneksi gagal',
                'message' => 'Terjadi error internal saat menghubungi OpenAI.',
                'detail' => $detail,
                'has_key' => true,
            ];
        }
    }

    private function saveConnectionLog(
        string $status,
        string $message,
        ?string $detail = null,
        ?int $httpStatus = null,
        ?int $durationMs = null,
        ?string $model = null,
    ): OpenAiConnectionLog {
        return OpenAiConnectionLog::query()->create([
            'user_id' => Auth::id(),
            'status' => $status,
            'message' => $message,
            'detail' => $detail,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'model' => $model,
            'tested_at' => now(),
        ]);
    }

    private function updateTestStatus(string $status, string $message): void
    {
        $settings = OpenAiSetting::query()->first();

        if (! $settings) {
            return;
        }

        $settings->last_tested_at = now();
        $settings->last_test_status = $status;
        $settings->last_test_message = $message;
        $settings->save();
    }

    private function normalizeModel(string $model): string
    {
        return match ($model) {
            'GPT Image',
            'OpenAI Image Generation',
            '' => 'gpt-image-2',
            default => $model,
        };
    }

    private function extractErrorDetail(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            $error = $json['error'] ?? null;

            if (is_array($error)) {
                $message = $error['message'] ?? null;
                $type = $error['type'] ?? null;
                $code = $error['code'] ?? null;

                $parts = array_filter([
                    $message,
                    $type ? "type: {$type}" : null,
                    $code ? "code: {$code}" : null,
                ]);

                if ($parts !== []) {
                    return implode(' • ', $parts);
                }
            }
        }

        $body = trim($response->body());

        return $body !== ''
            ? $body
            : 'OpenAI tidak mengembalikan detail error.';
    }

    private function activityLog(): ActivityLogService
    {
        return app(ActivityLogService::class);
    }

    public function getApiKey(): ?string
    {
        return OpenAiSetting::query()->first()?->api_key;
    }

    public function getConfiguration(): ?OpenAiSetting
    {
        return OpenAiSetting::query()->first();
    }
}
