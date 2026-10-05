<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\Generation;
use App\Models\Store;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OpenAiImageService
{
    private const BASE_URL = 'https://ai-gateway.vercel.sh/v1';

    private const DEFAULT_IMAGE_MODEL = 'bytedance/seedream-5.0-pro';

    private const DEFAULT_TITLE_MODEL = 'openai/gpt-5.6-luna';

    /**
     * Single source of truth for the Generator's default image model.
     */
    public function defaultImageModel(): string
    {
        return self::DEFAULT_IMAGE_MODEL;
    }

    /**
     * Return image-capable models exposed by Vercel AI Gateway.
     * The default model is always promoted to the top when present.
     */
    public function availableImageModels(): array
    {
        return cache()->remember('vercel.gateway.image-models', now()->addMinutes(30), function (): array {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(8)
                    ->timeout(20)
                    ->get(self::BASE_URL . '/models');

                if (! $response->successful()) {
                    return $this->defaultImageModelFallback();
                }

                $models = collect($response->json('data', []))
                    ->filter(function (array $model): bool {
                        $type = strtolower((string) ($model['type'] ?? ''));
                        $tags = collect($model['tags'] ?? [])
                            ->map(fn ($tag) => strtolower((string) $tag));

                        $outputModalities = collect(data_get($model, 'modalities.output', []))
                            ->map(fn ($modality) => strtolower((string) $modality));

                        return $type === 'image'
                            || $tags->contains('image-generation')
                            || $outputModalities->contains('image');
                    })
                    ->map(function (array $model): array {
                        $id = trim((string) ($model['id'] ?? ''));
                        $name = trim((string) ($model['name'] ?? $id));
                        $provider = str_contains($id, '/')
                            ? (string) str($id)->before('/')
                            : ((string) ($model['owned_by'] ?? 'Vercel AI Gateway'));

                        return [
                            'id' => $id,
                            'value' => $id,
                            'label' => $name !== '' ? $name : $id,
                            'description' => 'Vercel AI Gateway · ' . $provider,
                            'icon' => 'AI',
                            'owned_by' => $provider,
                            'type' => 'image',
                        ];
                    })
                    ->filter(fn (array $model): bool => $model['id'] !== '')
                    ->unique('id')
                    ->values();

                if ($models->isEmpty()) {
                    return $this->defaultImageModelFallback();
                }

                $default = self::DEFAULT_IMAGE_MODEL;

                return $models
                    ->sortBy(function (array $model) use ($default): array {
                        return [
                            $model['id'] === $default ? 0 : 1,
                            strtolower((string) $model['label']),
                            strtolower((string) $model['id']),
                        ];
                    })
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                report($e);

                return $this->defaultImageModelFallback();
            }
        });
    }

    private function defaultImageModelFallback(): array
    {
        return [[
            'id' => self::DEFAULT_IMAGE_MODEL,
            'value' => self::DEFAULT_IMAGE_MODEL,
            'label' => self::DEFAULT_IMAGE_MODEL,
            'description' => 'Vercel AI Gateway · Default image model',
            'icon' => 'AI',
            'owned_by' => 'Vercel AI Gateway',
            'type' => 'image',
        ]];
    }

    public function generate(
        User $user,
        Store $store,
        Template $template,
        $imageOne,
        $imageTwo,
        string $model,
        string $aspectRatio,
        string $quality,
        int $imageCount = 1,
        ?string $customTitle = null,
    ): Generation {
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        $startedAt = microtime(true);
        $httpErrorLogged = false;
        $hasInstalledReference = (bool) $imageTwo;
        $hasTemplateReference = $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);
        $generationMode = $this->generationMode($hasInstalledReference);
        $customTitle = $this->normalizeCustomTitle($customTitle);
        $titleSource = $customTitle !== null ? 'custom' : 'ai';
        $referenceCount = ($attachTemplateReference ? 1 : 0)
            + 1
            + ($hasInstalledReference ? 1 : 0)
            + ($hasStoreLogo ? 1 : 0);

        $this->activity()->processing(
            action: 'generate_openai_image',
            category: 'generator',
            title: 'Generate AI image via Vercel dimulai.',
            description: 'Generator menerima request dan mulai menyiapkan input untuk Vercel AI Gateway.',
            metadata: [
                'source' => 'dashboard_generator',
                'store_id' => $store->id,
                'template_id' => $template->id,
                'model' => $model ?: null,
                'aspect_ratio' => $aspectRatio,
                'quality' => $quality,
                'requested_image_count' => $imageCount,
                'generation_mode' => $generationMode,
                'title_source' => $titleSource,
                'custom_title' => $customTitle,
                'final_title' => $customTitle,
                'has_template_reference' => $hasTemplateReference,
                'template_reference_attached' => $attachTemplateReference,
                'has_product_reference' => true,
                'has_installed_reference' => $hasInstalledReference,
                'has_store_logo' => $hasStoreLogo,
                'use_installed_reference' => $hasInstalledReference,
                'reference_count' => $referenceCount,
            ],
        );

        $credential = $this->apiPool()->acquire($user->id);
        $apiKey = $credential['key'] ?? null;

        if (! filled($apiKey)) {
            $message = 'Belum ada Vercel AI Gateway API Key yang tersedia. Buka Settings → AI Gateway.';

            $this->activity()->error(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generate AI image gagal.',
                description: $message,
                metadata: [
                    'source' => 'openai_image_service',
                    'reason' => 'missing_api_key',
                ],
                durationMs: $this->durationMs($startedAt),
            );

            throw new RuntimeException($message);
        }

        $model = $this->normalizeGatewayImageModel($model);

        if ($model === '') {
            $message = 'Model OpenAI belum dipilih.';

            $this->activity()->error(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generate AI image gagal.',
                description: $message,
                metadata: [
                    'source' => 'openai_image_service',
                    'reason' => 'missing_model',
                ],
                durationMs: $this->durationMs($startedAt),
            );

            throw new RuntimeException($message);
        }

        $prompt = $this->buildPrompt($store, $template, $hasInstalledReference, $customTitle, $attachTemplateReference);
        $negativePrompt = trim((string) ($template->negative_prompt ?? ''));

        $generation = Generation::query()->create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'template_id' => $template->id,
            'product_image_1_path' => null,
            'product_image_2_path' => null,
            'reference_image_path' => $template->example_image_path,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt ?: null,
            'aspect_ratio' => $aspectRatio,
            'output_quality' => $quality,
            'model' => $model,
            'status' => 'processing',
            'metadata' => [
                'source' => 'dashboard_generator',
                'template_id' => $template->id,
                'store_id' => $store->id,
                'requested_image_count' => $imageCount,
                'generation_mode' => $generationMode,
                'title_source' => $titleSource,
                'custom_title' => $customTitle,
                'final_title' => $customTitle,
                'has_template_reference' => $hasTemplateReference,
                'template_reference_attached' => $attachTemplateReference,
                'has_product_reference' => true,
                'has_installed_reference' => $hasInstalledReference,
                'has_store_logo' => $hasStoreLogo,
                'use_installed_reference' => $hasInstalledReference,
                'reference_count' => $referenceCount,
            ],
            'started_at' => now(),
        ]);

        $this->activity()->processing(
            action: 'generate_openai_image',
            category: 'generator',
            title: 'Input generate sedang diproses.',
            description: 'Generation record #' . $generation->id . ' dibuat. File produk sedang disiapkan.',
            metadata: [
                'source' => 'openai_image_service',
                'generation_id' => $generation->id,
                'store_id' => $store->id,
                'template_id' => $template->id,
                'model' => $model,
            ],
        );

        try {
            $productOnePath = $imageOne->getRealPath();
            $productTwoPath = $imageTwo?->getRealPath();

            if (! $productOnePath || ! is_readable($productOnePath)) {
                throw new RuntimeException('Gambar utama tidak dapat dibaca oleh server.');
            }

            if ($imageTwo && (! $productTwoPath || ! is_readable($productTwoPath))) {
                throw new RuntimeException('Foto referensi pemasangan tidak dapat dibaca oleh server.');
            }

            $sourceOne = $imageOne->store('generations/source', 'public');
            $sourceTwo = $imageTwo ? $imageTwo->store('generations/source', 'public') : null;

            if (! $sourceOne || ($imageTwo && ! $sourceTwo)) {
                throw new RuntimeException('File gambar input gagal disimpan ke storage server.');
            }

            $generation->update([
                'product_image_1_path' => $sourceOne,
                'product_image_2_path' => $sourceTwo,
            ]);

            if ($customTitle === null) {
                $autoTitle = $this->resolveAutomaticProductTitle(
                    apiKey: $apiKey,
                    productPath: $productOnePath,
                );

                if ($autoTitle !== null) {
                    $customTitle = $autoTitle;
                    $titleSource = 'ai';
                    $prompt = $this->buildPrompt($store, $template, $hasInstalledReference, $customTitle, $attachTemplateReference);
                    $generation->update([
                        'prompt' => $prompt,
                        'metadata' => array_merge($generation->metadata ?? [], [
                            'final_title' => $customTitle,
                            'title_source' => 'ai',
                            'custom_title' => null,
                            'title_engine' => 'motorcycle_part_vision_v2',
                        ]),
                    ]);
                }
            } else {
                $generation->update([
                    'metadata' => array_merge($generation->metadata ?? [], [
                        'final_title' => $customTitle,
                        'title_source' => 'custom',
                        'custom_title' => $customTitle,
                    ]),
                ]);
            }

            // Attachment order is intentional:
            // 1. Template master reference (layout/style/composition)
            // 2. Product reference (product identity)
            // 3. Installed/in-use reference (optional factual usage)
            // 4. Store logo (official branding)
            $attachments = [];

            if ($attachTemplateReference) {
                $templateReferencePath = $this->templateReferencePath($template);
                if ($templateReferencePath) {
                    $attachments[] = [$templateReferencePath, basename($templateReferencePath)];
                }
            }

            $attachments[] = [$productOnePath, basename($productOnePath)];

            if ($imageTwo && $productTwoPath) {
                $attachments[] = [$productTwoPath, basename($productTwoPath)];
            }

            if ($hasStoreLogo) {
                $logoPath = Storage::disk('public')->path($store->logo_path);
                if (is_readable($logoPath)) {
                    $attachments[] = [$logoPath, basename($logoPath)];
                }
            }

            $this->activity()->processing(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Mengirim request ke Vercel AI Gateway.',
                description: sprintf(
                    'Vercel AI Gateway POST /v1/images/edits dengan %d attachment, model %s.',
                    count($attachments),
                    $model
                ),
                metadata: [
                    'source' => 'openai_image_service',
                    'generation_id' => $generation->id,
                    'endpoint' => '/v1/images/edits',
                    'attachment_count' => count($attachments),
                    'model' => $model,
                    'size' => $this->mapSize($aspectRatio),
                    'quality' => $this->mapQuality($quality),
                    'image_count' => $imageCount,
                ],
            );

            $payload = [
                'model' => $model,
                'prompt' => $prompt,
                'n' => max(1, min(4, $imageCount)),
                'size' => $this->mapSize($aspectRatio),
                'quality' => $this->mapQuality($quality),
                'output_format' => 'png',
            ];

            if ($negativePrompt !== '') {
                $payload['prompt'] .= "\n\nAvoid: " . $negativePrompt;
            }

            [$response, $usedCredential] = $this->postImageEditsWithFailover(
                userId: $user->id,
                preferredCredential: $credential,
                attachments: $attachments,
                payload: $payload,
                generationId: $generation->id,
                source: 'openai_image_service',
            );
            $apiKey = $usedCredential['key'];

            $generation->update([
                'metadata' => array_merge($generation->metadata ?? [], [
                    'gateway_key_id' => $usedCredential['id'] ?? null,
                    'gateway_key_name' => $usedCredential['name'] ?? null,
                    'gateway_key_source' => $usedCredential['source'] ?? null,
                ]),
            ]);

            $durationMs = $this->durationMs($startedAt);

            if (! $response->successful()) {
                $error = $this->extractError($response);

                $this->activity()->error(
                    action: 'generate_openai_image',
                    category: 'generator',
                    title: 'Vercel AI Gateway image generation gagal.',
                    description: $error,
                    metadata: [
                        'source' => 'openai_image_service',
                        'generation_id' => $generation->id,
                        'endpoint' => '/v1/images/edits',
                        'model' => $model,
                        'response_body' => $this->safeResponseBody($response),
                    ],
                    durationMs: $durationMs,
                    httpStatus: $response->status(),
                );

                $httpErrorLogged = true;
                throw new RuntimeException($error);
            }

            $data = $response->json('data', []);

            if (! is_array($data) || $data === []) {
                throw new RuntimeException('Vercel AI Gateway tidak mengembalikan gambar.');
            }

            $this->activity()->processing(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Response Vercel AI Gateway diterima.',
                description: sprintf(
                    'Vercel AI Gateway mengembalikan %d item image. Server mulai menyimpan hasil.',
                    count($data)
                ),
                metadata: [
                    'source' => 'openai_image_service',
                    'generation_id' => $generation->id,
                    'model' => $model,
                    'response_items' => count($data),
                ],
                durationMs: $durationMs,
                httpStatus: $response->status(),
            );

            $saved = 0;

            foreach ($data as $index => $item) {
                try {
                    $binary = $this->resolveImageBinary($item);
                } catch (Throwable $e) {
                    $this->activity()->error(
                        action: 'generate_openai_image',
                        category: 'generator',
                        title: 'Hasil image dari Vercel gagal diambil.',
                        description: $e->getMessage(),
                        metadata: [
                            'source' => 'openai_image_service',
                            'generation_id' => $generation->id,
                            'openai_index' => $index,
                        ],
                        durationMs: $this->durationMs($startedAt),
                    );

                    throw $e;
                }

                if ($binary === null || $binary === '') {
                    throw new RuntimeException(
                        'Vercel AI Gateway mengembalikan item image #' . ($index + 1) . ' tanpa binary image yang dapat dibaca.'
                    );
                }

                $filename = 'generation-' . $generation->id . '-' . ($index + 1) . '-' . uniqid() . '.png';
                $path = 'generations/output/' . now()->format('Y/m') . '/' . $filename;

                Storage::disk('public')->put($path, $binary);
                [$width, $height] = $this->imageDimensions(Storage::disk('public')->path($path));

                GeneratedImage::query()->create([
                    'generation_id' => $generation->id,
                    'image_path' => $path,
                    'image_url' => Storage::disk('public')->url($path),
                    'model' => $model,
                    'format' => 'png',
                    'width' => $width,
                    'height' => $height,
                    'metadata' => [
                        'openai_index' => $index,
                        'title_source' => $titleSource,
                        'final_title' => $customTitle,
                        'custom_title' => $titleSource === 'custom' ? $customTitle : null,
                        'source' => 'images/edits',
                        'size' => $this->mapSize($aspectRatio),
                        'quality' => $this->mapQuality($quality),
                        'stored_at' => now()->toIso8601String(),
                    ],
                    'is_primary' => $saved === 0,
                    'is_favorite' => false,
                ]);

                $saved++;

                $this->activity()->processing(
                    action: 'generate_openai_image',
                    category: 'generator',
                    title: 'Hasil image tersimpan.',
                    description: sprintf(
                        'Image %d/%d berhasil disimpan ke storage.',
                        $saved,
                        count($data)
                    ),
                    metadata: [
                        'source' => 'openai_image_service',
                        'generation_id' => $generation->id,
                        'image_index' => $index,
                        'format' => 'png',
                        'width' => $width,
                        'height' => $height,
                        'path' => $path,
                    ],
                );
            }

            if ($saved === 0) {
                throw new RuntimeException(
                    'Vercel AI Gateway mengembalikan response tanpa binary image yang dapat disimpan.'
                );
            }

            $generation->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'saved_images' => $saved,
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->activity()->success(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generate AI image via Vercel berhasil.',
                description: sprintf(
                    '%d gambar berhasil dibuat dan disimpan. Generation #%d.',
                    $saved,
                    $generation->id
                ),
                metadata: [
                    'source' => 'openai_image_service',
                    'generation_id' => $generation->id,
                    'store_id' => $store->id,
                    'template_id' => $template->id,
                    'store_logo_path' => $store->logo_path,
                    'model' => $model,
                    'generation_mode' => $generationMode,
                    'saved_images' => $saved,
                    'aspect_ratio' => $aspectRatio,
                    'quality' => $quality,
                ],
                durationMs: $this->durationMs($startedAt),
                httpStatus: $response->status(),
            );

            return $generation->fresh(['generatedImages', 'store', 'template']);
        } catch (Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            /*
             * Some errors (for example API errors above) have already been
             * logged with richer HTTP metadata. We still emit one generic
             * failure event only when the exception did not come from the
             * explicit HTTP error branch.
             */
            if (! $httpErrorLogged) {
                $this->activity()->error(
                    action: 'generate_openai_image',
                    category: 'generator',
                    title: 'Generate AI image gagal.',
                    description: $e->getMessage(),
                    metadata: [
                        'source' => 'openai_image_service',
                        'generation_id' => $generation->id,
                        'model' => $model,
                        'exception' => get_class($e),
                    ],
                    durationMs: $this->durationMs($startedAt),
                );
            }

            throw $e;
        }
    }

    /**
     * Store the uploaded assets and create a queued generation record.
     * The expensive OpenAI call is deliberately not performed in the Livewire request.
     */
    public function queueGeneration(
        User $user,
        Store $store,
        Template $template,
        $imageOne,
        $imageTwo,
        string $model,
        string $aspectRatio,
        string $quality,
        int $imageCount = 1,
        ?string $customTitle = null,
    ): Generation {
        $startedAt = microtime(true);

        if (! $this->apiPool()->hasAvailableKey($user->id)) {
            throw new RuntimeException('Belum ada Vercel AI Gateway API Key yang tersedia. Buka Settings → AI Gateway.');
        }

        $model = trim($model);
        if ($model === '') {
            throw new RuntimeException('Model image Vercel AI Gateway belum dipilih.');
        }

        $sourceOne = $imageOne->store('generations/source', 'public');
        $sourceTwo = $imageTwo ? $imageTwo->store('generations/source', 'public') : null;

        if (! $sourceOne || ($imageTwo && ! $sourceTwo)) {
            throw new RuntimeException('File gambar input gagal disimpan ke storage server.');
        }

        $hasInstalledReference = (bool) $imageTwo;
        $hasTemplateReference = $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);
        $generationMode = $this->generationMode($hasInstalledReference);
        $customTitle = $this->normalizeCustomTitle($customTitle);
        $titleSource = $customTitle !== null ? 'custom' : 'ai';
        $referenceCount = ($attachTemplateReference ? 1 : 0)
            + 1
            + ($hasInstalledReference ? 1 : 0)
            + ($hasStoreLogo ? 1 : 0);

        $prompt = $this->buildPrompt($store, $template, $hasInstalledReference, $customTitle, $attachTemplateReference);
        $negativePrompt = trim((string) ($template->negative_prompt ?? ''));

        $generation = Generation::query()->create([
            'user_id' => $user->id,
            'store_id' => $store->id,
            'template_id' => $template->id,
            'product_image_1_path' => $sourceOne,
            'product_image_2_path' => $sourceTwo,
            'reference_image_path' => $template->example_image_path,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt ?: null,
            'aspect_ratio' => $aspectRatio,
            'output_quality' => $quality,
            'model' => $model,
            'status' => 'queued',
            'metadata' => [
                'source' => 'dashboard_generator',
                'template_id' => $template->id,
                'store_id' => $store->id,
                'requested_image_count' => $imageCount,
                'generation_mode' => $generationMode,
                'has_template_reference' => $hasTemplateReference,
                'template_reference_attached' => $attachTemplateReference,
                'has_product_reference' => true,
                'has_installed_reference' => $hasInstalledReference,
                'has_store_logo' => $hasStoreLogo,
                'use_installed_reference' => $hasInstalledReference,
                'reference_count' => $referenceCount,
                'progress' => 4,
                'progress_stage' => 'Menunggu worker queue',
                'queued_at' => now()->toIso8601String(),
            ],
        ]);

        $this->activity()->processing(
            action: 'generate_openai_image',
            category: 'generator',
            title: 'Generate AI image masuk antrean.',
            description: sprintf(
                'Generation #%d siap diproses di background. %d reference image akan digunakan.',
                $generation->id,
                $referenceCount
            ),
            metadata: [
                'source' => 'generator_queue',
                'generation_id' => $generation->id,
                'store_id' => $store->id,
                'template_id' => $template->id,
                'model' => $model,
                'reference_count' => $referenceCount,
                'has_template_reference' => $hasTemplateReference,
                'template_reference_attached' => $attachTemplateReference,
            ],
            durationMs: $this->durationMs($startedAt),
        );

        return $generation->fresh(['store', 'template', 'generatedImages']);
    }

    /**
     * Process a previously queued generation from a queue worker.
     */
    public function processQueuedGeneration(Generation $generation): Generation
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        $generation->refresh();
        if ($generation->status === 'cancelled') {
            return $generation->fresh(['generatedImages', 'store', 'template']);
        }

        $generation->loadMissing(['store', 'template']);
        $store = $generation->store;
        $template = $generation->template;
        $startedAt = microtime(true);
        $httpErrorLogged = false;

        $metadata = $generation->metadata ?? [];
        $hasInstalledReference = ! empty($generation->product_image_2_path);
        $hasTemplateReference = $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);
        $generationMode = (string) ($metadata['generation_mode'] ?? $this->generationMode($hasInstalledReference));
        $customTitle = $this->normalizeCustomTitle($metadata['custom_title'] ?? $metadata['final_title'] ?? null);
        $titleSource = $customTitle !== null ? 'custom' : (string) ($metadata['title_source'] ?? 'ai');
        $referenceCount = ($attachTemplateReference ? 1 : 0)
            + 1
            + ($hasInstalledReference ? 1 : 0)
            + ($hasStoreLogo ? 1 : 0);

        $generation->update([
            'status' => 'processing',
            'started_at' => now(),
            'metadata' => array_merge($metadata, [
                'generation_mode' => $generationMode,
                'has_template_reference' => $hasTemplateReference,
                'template_reference_attached' => $attachTemplateReference,
                'has_product_reference' => true,
                'has_installed_reference' => $hasInstalledReference,
                'has_store_logo' => $hasStoreLogo,
                'reference_count' => $referenceCount,
                'progress' => 10,
                'progress_stage' => 'Menyiapkan input',
                'started_at' => now()->toIso8601String(),
            ]),
        ]);

        $this->activity()->processing(
            action: 'generate_openai_image',
            category: 'generator',
            title: 'Background generation dimulai.',
            description: 'Worker mulai memproses Generation #' . $generation->id . '.',
            metadata: [
                'source' => 'openai_image_queue',
                'generation_id' => $generation->id,
                'store_id' => $store?->id,
                'template_id' => $template?->id,
            ],
        );

        try {
            if (! $store || ! $template) {
                throw new RuntimeException('Store atau Template generation tidak ditemukan.');
            }

            $credential = $this->apiPool()->acquire($generation->user_id);
            $apiKey = $credential['key'] ?? null;
            if (! filled($apiKey)) {
                throw new RuntimeException('Belum ada Vercel AI Gateway API Key yang tersedia.');
            }

            $model = $this->normalizeGatewayImageModel((string) $generation->model);
            if ($model === '') {
                throw new RuntimeException('Model image Vercel AI Gateway belum dipilih.');
            }
            if ($generation->model !== $model) {
                $generation->update(['model' => $model]);
            }

            $productOnePath = Storage::disk('public')->path($generation->product_image_1_path);
            $productTwoPath = $generation->product_image_2_path
                ? Storage::disk('public')->path($generation->product_image_2_path)
                : null;

            if (! is_readable($productOnePath)) {
                throw new RuntimeException('Gambar utama tidak dapat dibaca dari storage.');
            }
            if ($productTwoPath && ! is_readable($productTwoPath)) {
                throw new RuntimeException('Foto referensi pemasangan tidak dapat dibaca dari storage.');
            }

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            if ($customTitle === null) {
                $autoTitle = $this->resolveAutomaticProductTitle(
                    apiKey: $apiKey,
                    productPath: $productOnePath,
                );

                if ($autoTitle !== null) {
                    $customTitle = $autoTitle;
                    $titleSource = 'ai';
                    $resolvedPrompt = $this->buildPrompt(
                        $store,
                        $template,
                        $hasInstalledReference,
                        $customTitle,
                        $attachTemplateReference
                    );

                    $generation->update([
                        'prompt' => $resolvedPrompt,
                        'metadata' => array_merge($generation->metadata ?? [], [
                            'final_title' => $customTitle,
                            'title_source' => 'ai',
                            'custom_title' => null,
                            'title_model' => config('services.vercel.title_model', self::DEFAULT_TITLE_MODEL),
                            'title_engine' => 'motorcycle_part_vision_v2',
                        ]),
                    ]);
                }
            } else {
                $generation->update([
                    'metadata' => array_merge($generation->metadata ?? [], [
                        'final_title' => $customTitle,
                        'title_source' => 'custom',
                        'custom_title' => $customTitle,
                    ]),
                ]);
            }

            $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 25,
                'progress_stage' => 'Menyiapkan reference image',
            ])]);

            // Keep the reference order stable so the prompt can assign each image a role:
            // 1 = Template master, 2 = product, 3 = installed reference (optional), 4 = logo.
            $attachments = [];

            if ($attachTemplateReference) {
                $templateReferencePath = $this->templateReferencePath($template);
                if ($templateReferencePath) {
                    $attachments[] = [$templateReferencePath, basename($templateReferencePath)];
                }
            }

            $attachments[] = [$productOnePath, basename($productOnePath)];

            if ($productTwoPath) {
                $attachments[] = [$productTwoPath, basename($productTwoPath)];
            }

            if ($hasStoreLogo) {
                $logoPath = Storage::disk('public')->path($store->logo_path);
                if (is_readable($logoPath)) {
                    $attachments[] = [$logoPath, basename($logoPath)];
                }
            }

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 35,
                'progress_stage' => 'Mengirim request ke OpenAI',
                'attachment_count' => count($attachments),
            ])]);

            $this->activity()->processing(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Mengirim request ke Vercel AI Gateway.',
                description: sprintf('Vercel AI Gateway POST /v1/images/edits dengan %d attachment, model %s.', count($attachments), $model),
                metadata: [
                    'source' => 'openai_image_queue',
                    'generation_id' => $generation->id,
                    'endpoint' => '/v1/images/edits',
                    'attachment_count' => count($attachments),
                    'model' => $model,
                    'image_count' => (int) (($generation->metadata ?? [])['requested_image_count'] ?? 1),
                ],
            );

            $payload = [
                'model' => $model,
                'prompt' => (string) $generation->prompt,
                'n' => max(1, min(4, (int) (($generation->metadata ?? [])['requested_image_count'] ?? 1))),
                'size' => $this->mapSize((string) $generation->aspect_ratio),
                'quality' => $this->mapQuality((string) $generation->output_quality),
                'output_format' => 'png',
            ];
            if (filled($generation->negative_prompt)) {
                $payload['prompt'] .= "\n\nAvoid: " . $generation->negative_prompt;
            }

            [$response, $usedCredential] = $this->postImageEditsWithFailover(
                userId: $generation->user_id,
                preferredCredential: $credential,
                attachments: $attachments,
                payload: $payload,
                generationId: $generation->id,
                source: 'openai_image_queue',
            );
            $apiKey = $usedCredential['key'];

            $generation->update([
                'metadata' => array_merge($generation->metadata ?? [], [
                    'gateway_key_id' => $usedCredential['id'] ?? null,
                    'gateway_key_name' => $usedCredential['name'] ?? null,
                    'gateway_key_source' => $usedCredential['source'] ?? null,
                ]),
            ]);

            if (! $response->successful()) {
                $error = $this->extractError($response);
                $httpErrorLogged = true;
                $this->activity()->error(
                    action: 'generate_openai_image',
                    category: 'generator',
                    title: 'Vercel AI Gateway image generation gagal.',
                    description: $error,
                    metadata: [
                        'source' => 'openai_image_queue',
                        'generation_id' => $generation->id,
                        'endpoint' => '/v1/images/edits',
                        'model' => $model,
                        'response_body' => $this->safeResponseBody($response),
                    ],
                    durationMs: $this->durationMs($startedAt),
                    httpStatus: $response->status(),
                );
                throw new RuntimeException($error);
            }

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 78,
                'progress_stage' => 'Memproses hasil OpenAI',
            ])]);

            $data = $response->json('data', []);
            if (! is_array($data) || $data === []) {
                throw new RuntimeException('Vercel AI Gateway tidak mengembalikan gambar.');
            }

            $saved = 0;
            foreach ($data as $index => $item) {
                $generation->refresh();
                if ($generation->status === 'cancelled') {
                    return $generation->fresh(['generatedImages', 'store', 'template']);
                }
                $binary = $this->resolveImageBinary($item);
                if ($binary === null || $binary === '') {
                    throw new RuntimeException('Vercel AI Gateway mengembalikan item image #' . ($index + 1) . ' tanpa binary image.');
                }

                $filename = 'generation-' . $generation->id . '-' . ($index + 1) . '-' . uniqid() . '.png';
                $path = 'generations/output/' . now()->format('Y/m') . '/' . $filename;
                Storage::disk('public')->put($path, $binary);
                [$width, $height] = $this->imageDimensions(Storage::disk('public')->path($path));

                GeneratedImage::query()->create([
                    'generation_id' => $generation->id,
                    'image_path' => $path,
                    'image_url' => Storage::disk('public')->url($path),
                    'model' => $model,
                    'format' => 'png',
                    'width' => $width,
                    'height' => $height,
                    'metadata' => [
                        'openai_index' => $index,
                        'title_source' => $titleSource,
                        'final_title' => $customTitle,
                        'custom_title' => $titleSource === 'custom' ? $customTitle : null,
                        'source' => 'images/edits',
                        'size' => $this->mapSize((string) $generation->aspect_ratio),
                        'quality' => $this->mapQuality((string) $generation->output_quality),
                        'stored_at' => now()->toIso8601String(),
                    ],
                    'is_primary' => $saved === 0,
                    'is_favorite' => false,
                ]);
                $saved++;
                $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                    'progress' => min(96, 82 + (int) floor(($saved / max(1, count($data))) * 14)),
                    'progress_stage' => 'Menyimpan hasil ' . $saved . '/' . count($data),
                    'saved_images' => $saved,
                ])]);
            }

            if ($saved === 0) throw new RuntimeException('Tidak ada image yang berhasil disimpan.');

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            $generation->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'progress' => 100,
                    'progress_stage' => 'Selesai',
                    'saved_images' => $saved,
                    'completed_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->activity()->success(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generate AI image via Vercel berhasil.',
                description: sprintf('%d gambar berhasil dibuat dan disimpan. Generation #%d.', $saved, $generation->id),
                metadata: [
                    'source' => 'openai_image_queue',
                    'generation_id' => $generation->id,
                    'store_id' => $store->id,
                    'template_id' => $template->id,
                    'model' => $model,
                    'saved_images' => $saved,
                ],
                durationMs: $this->durationMs($startedAt),
                httpStatus: $response->status(),
            );

            return $generation->fresh(['generatedImages', 'store', 'template']);
        } catch (Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'progress' => 100,
                    'progress_stage' => 'Gagal',
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);

            if (! $httpErrorLogged) {
                $this->activity()->error(
                    action: 'generate_openai_image',
                    category: 'generator',
                    title: 'Generate AI image gagal.',
                    description: $e->getMessage(),
                    metadata: [
                        'source' => 'openai_image_queue',
                        'generation_id' => $generation->id,
                        'model' => $generation->model,
                        'exception' => get_class($e),
                    ],
                    durationMs: $this->durationMs($startedAt),
                );
            }
            throw $e;
        }
    }

    /**
     * Build a strict, reusable prompt for both generator modes.
     *
     * The Template prompt remains the visual/creative source of truth.
     * The system rules below control reference roles and prevent the model
     * from inventing an installed scene when no installed reference exists.
     */
    private function buildPrompt(
        Store $store,
        Template $template,
        bool $hasInstalledReference = false,
        ?string $customTitle = null,
        ?bool $templateReferenceAttached = null,
    ): string
    {
        $hasTemplateReference = $templateReferenceAttached ?? $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);

        $referenceRoles = [
            'REFERENCE IMAGE CONTRACT — FOLLOW THE ATTACHMENT ORDER EXACTLY:',
            $hasTemplateReference
                ? 'IMAGE 1 — TEMPLATE MASTER REFERENCE: this is the visual master for the selected Template.'
                : 'NO TEMPLATE MASTER REFERENCE IMAGE IS AVAILABLE: use the Template prompt as the layout/style source of truth.',
            $hasTemplateReference
                ? '- Preserve the Template master reference visual system: composition, layout structure, hierarchy, framing, background treatment, decorative elements, graphic motifs, badge/callout placement, typography placement, spacing, proportions and overall visual language.'
                : '- Build the visual system from the Template prompt, but keep that system stable across generations using the same Template.',
            $hasTemplateReference
                ? '- The products, packaging and product-specific content visible inside the Template master are EXAMPLES ONLY. Do not copy those products into the new result.'
                : '- Do not invent a different visual system between generations of the same Template.',
            $hasTemplateReference
                ? '- Replace the master reference product content with the actual product from the next image while preserving the master composition as closely as practical.'
                : null,
            $hasTemplateReference
                ? 'IMAGE 2 — PRIMARY PRODUCT REFERENCE:'
                : 'IMAGE 1 — PRIMARY PRODUCT REFERENCE:',
            '- This image is the authoritative source for the actual product identity.',
            '- Preserve the exact product identity, recognizable shape, proportions, geometry, colors, materials, surface finish, markings, labels, packaging details and important physical features.',
            '- Never copy the product identity from the Template master reference when it conflicts with the actual product reference.',
            '- Do not replace the product with another product, redesign it, merge it with a different product, or invent missing product details.',
            $hasInstalledReference
                ? ($hasTemplateReference ? 'IMAGE 3 — INSTALLED / IN-USE REFERENCE:' : 'IMAGE 2 — INSTALLED / IN-USE REFERENCE:')
                : 'NO INSTALLED / IN-USE REFERENCE:',
            $hasInstalledReference
                ? '- Use this image only for factual installation, orientation, placement, scale, fitment and real-world usage context.'
                : '- There is NO installed-product image. Do not infer an installation scene from the product category, Template master, Template prompt, memory or common usage.',
            $hasInstalledReference
                ? '- The installed reference must never override the Template master layout or the actual product identity.'
                : '- Do not fabricate a motorcycle, vehicle, rider, hand, mechanic, workshop, garage, road scene, mounting demonstration, installation scene or fictional usage environment.',
            $hasStoreLogo
                ? ($hasTemplateReference
                    ? ($hasInstalledReference ? 'IMAGE 4 — OFFICIAL STORE LOGO:' : 'IMAGE 3 — OFFICIAL STORE LOGO:')
                    : ($hasInstalledReference ? 'IMAGE 3 — OFFICIAL STORE LOGO:' : 'IMAGE 2 — OFFICIAL STORE LOGO:'))
                : 'NO STORE LOGO IMAGE:',
            $hasStoreLogo
                ? '- Treat the supplied Store logo as the authoritative branding asset. Preserve its recognizable logo design, proportions and identity. Do not redesign, replace, invent or distort it.'
                : '- No Store logo image is supplied. Do not invent a logo or fabricate brand marks.',
        ];

        $modeRules = $hasInstalledReference
            ? [
                'GENERATION MODE: PRODUCT + INSTALLED REFERENCE',
                '- The Template master reference remains the design/layout authority when available.',
                '- The actual product reference remains the product-identity authority.',
                '- Use the installed reference only to understand factual real-world installation or usage.',
                '- Keep the same Template composition, hierarchy, graphic language and visual rhythm even when the installed reference changes.',
                '- Do not let the installed reference redesign the Template.',
            ]
            : [
                'GENERATION MODE: PRODUCT-ONLY — TEMPLATE DESIGN REFERENCE + ZERO PRODUCT FABRICATION',
                '- There is NO installed/in-use reference. The actual product image is the ONLY authority for the product identity, geometry, materials and visible details.',
                '- The selected Template master image IS attached when available and is the visual master for composition, layout, typography placement, graphic hierarchy, background treatment, badges, icons, framing and overall design language.',
                '- CRITICAL: copy the Template MASTER DESIGN SYSTEM, NOT its example product. Replace the example product/content with the actual supplied product.',
                '- The Template prompt is also mandatory creative direction and must be combined with the Template master image.',
                '- NEVER inherit a motorcycle, scooter, vehicle body, wheel, road, rider, mechanic, hand, workshop, garage, showroom, engine bay or installation environment from the Template unless such an environment is explicitly required by the Template prompt AND supported by an installed reference.',
                '- NEVER create an installation scene merely because the Template example contains one.',
                '- NEVER place the actual product on, inside, attached to, mounted on, held by or being used on a motorcycle or vehicle.',
                '- NEVER create a contextual product photograph that suggests how the product is installed.',
                '- The final background must be a non-installation commercial product background consistent with the Template design language.',
                '- The product may be isolated, enlarged, cropped, arranged or layered only when the arrangement can be created from the actual PRIMARY PRODUCT REFERENCE without inventing unseen geometry.',
                '- ZERO-FABRICATION RULE: do not create a new physical view, unseen side, imagined mounting point, reconstructed macro, synthetic close-up or fictional product detail.',
                '',
                'STRICT DETAIL / CALLOUT RULE — SOURCE-PIXELS ONLY:',
                '- A detail/callout is allowed ONLY when it can be represented from pixels visibly present in the PRIMARY PRODUCT REFERENCE.',
                '- The detail must be a literal crop/enlargement of the actual product image, not a newly photographed or newly rendered version of the product.',
                '- NEVER place a detail crop against a motorcycle, vehicle, road, workshop or installation scene.',
                '- NEVER create a new perspective, hidden side, reconstructed thread, connector, screw head, engraving, internal structure or other unseen feature.',
                '- If the source image does not provide enough visible pixels for a trustworthy detail, OMIT the detail/callout instead of generating one.',
                '',
                'ABSOLUTE PRODUCT-ONLY NEGATIVE LIST:',
                '- NO motorcycle.',
                '- NO scooter.',
                '- NO vehicle.',
                '- NO wheel, body panel or vehicle background.',
                '- NO installed product.',
                '- NO mounting demonstration.',
                '- NO rider, mechanic, hand or person using the product.',
                '- NO garage, workshop, showroom or road.',
                '- NO fictional usage environment.',
                '- NO AI-generated macro/detail replacement.',
                '- NO invented product angle.',
                '- NO photographic content copied from the Template example.',
            ];

        $parts = [
            'ROLE:',
            'You are a professional commercial product-art director creating a premium marketplace product image.',
            '',
            'TEMPLATE CONSISTENCY — CRITICAL:',
            '- Every generation that uses the SAME Template must look like part of the SAME design series.',
            '- Treat the selected Template as a reusable fixed design system, not as a loose inspiration.',
            '- When a Template master image exists, use it as the PRIMARY VISUAL STYLE REFERENCE for the composition.',
            '- Recreate the Template master composition as closely as practical: same hierarchy, major zones, headline placement, product presentation area, decorative motifs, badges/icons, spacing rhythm, framing, background treatment and overall premium advertising feel.',
            '- Replace only the example product/content with the actual supplied product. Do not copy the Template example product.',
            '- Do not redesign, reinterpret, randomize or significantly rearrange the Template for a new product.',
            '- Keep the same composition logic, visual hierarchy, typography placement, graphic motifs, decorative language, border/frame treatment, callout style, badge/icon zones, spacing rhythm, background style and overall art direction.',
            '- Only the product-specific content, product imagery and factual installation context (when supplied) should change.',
            '- Changing the product reference must NOT cause a new layout, new background concept, new graphic system, new typography style or new composition.',
            '',
            'SELECTED TEMPLATE PROMPT INJECTION — HIGH PRIORITY:',
            '- The following instructions are injected directly from the selected Template record.',
            '- EXECUTE these Template instructions as the creative design direction for this generation.',
            '- The Template prompt controls the visual treatment, design language, composition preferences, graphic elements, typography direction and marketing presentation requested by the Template.',
            '- Do not discard, summarize, weaken or silently replace the Template prompt.',
            '- Resolve conflicts by preserving product/reference/branding truth first, then execute the Template prompt as fully as possible.',
            '- If the Template prompt requests attractive graphic elements, badges, callouts, technical lines, panels, textures or other visual details, actually include them when they are compatible with the supplied references.',
            '- The Template prompt is NOT optional:',
            '--- BEGIN SELECTED TEMPLATE PROMPT ---',
            trim((string) $template->prompt),
            '--- END SELECTED TEMPLATE PROMPT ---',
            '',
            ...$referenceRoles,
            '',
            ...$modeRules,
            '',
            'STORE BRANDING CONTEXT:',
            'Store: ' . ($store->brand_name ?: $store->name),
            'Marketplace: ' . ($store->marketplace ?: 'Marketplace'),
            'Brand description: ' . ($store->description ?: 'Use the selected store visual identity consistently.'),
            $hasStoreLogo
                ? 'The official Store logo is supplied as an image reference. It is the ONLY authoritative store identity. Use that exact logo; the Template logo is layout-only and must never replace it.'
                : 'No Store logo asset is configured. Do not invent one.',
            '',
            'BRANDING PRIORITY — ABSOLUTE:',
            '- SUPPLIED STORE LOGO is authoritative for store identity.',
            '- Any logo visible in the Template master is reference-only and must NOT be copied.',
            '- Never invent, redraw, approximate, merge or substitute a Store logo.',
            '- Preserve the supplied Store logo identity, proportions and recognizable details.',
            '',
            'GLOBAL PRODUCT RULES:',
            '- Product identity and reference fidelity have higher priority than creative embellishment.',
            '- Never replace the supplied product with a visually similar generic product.',
            '- Never copy a different product from the Template master reference.',
            '- Never invent or reconstruct unseen product geometry just to satisfy a composition, callout, label or decorative element.',
            '- Do not add random text, prices, discounts, specifications, badges, watermarks, logos or unrelated objects unless explicitly requested by the Template.',
            '- Keep the product visually clean, commercially attractive and suitable for an online marketplace.',
            '- Follow the Template master/reference and the injected Template prompt for composition, visual hierarchy, typography direction, color direction, graphic elements and aspect ratio.',
            '- The final design should have deliberate visual richness: use tasteful graphic accents, callout frames, badges, separators, technical motifs and depth elements when requested by the Template and supported by the available space.',
            '- Never simplify a rich Template into a plain poster merely because the product is different.',
            '- Use realistic lighting, believable materials and clean edges.',
            '- Avoid accidental duplicate products, malformed geometry, melted details, distorted logos and invented accessories.',
            '',

            '',
            'PRODUCT TITLE / HEADLINE RULES:',
            $customTitle !== null
                ? '- CUSTOM TITLE IS AUTHORITATIVE: use this exact user-provided title as the main product headline: "' . $customTitle . '".'
                : '- NO CUSTOM TITLE IS PRE-RESOLVED: infer the product title strictly from the PRIMARY PRODUCT REFERENCE. When the application has supplied a resolved title later in the workflow, that resolved title becomes authoritative.',
            $customTitle !== null
                ? '- Do not replace, paraphrase, translate, expand, shorten, or invent product-name words. You may only adjust line breaks, capitalization or typographic treatment when needed to fit the Template.'
                : '- The automatic title must describe only what is actually supported by the primary product reference. Do not invent model numbers, specifications, materials, compatibility, features or brand claims that are not visible or clearly supported.',
            '- Keep the title concise and commercially useful, normally 2–6 words.',
            '- The title is content, not a new design instruction. Keep the Template master layout, title position, typography hierarchy and visual treatment unchanged.',
            '- If the Template master has a dedicated headline area, place the final product title there while preserving the master hierarchy.',
            '- If multiple output images are requested for the same product, keep the same final product title across those variants; do not invent a different product name for the same product.',
            '',
            'FINAL PRODUCT-ONLY COMPLIANCE CHECK:',
            '- Before producing the final image, verify that no motorcycle, scooter, vehicle or installation scene exists anywhere in the composition.',
            '- If the Template example contains a motorcycle or installed product, IGNORE that photographic content completely.',
            '- If a detail cannot be sourced from visible product pixels, remove the detail instead of inventing it.',
            '- The actual product reference must remain the only source of product geometry and identity.',
            '',
            'SUNBURST — COMMERCIAL ART DIRECTION:',
            '- Favor a clean, bold, high-contrast marketplace composition that is immediately readable at thumbnail size.',
            '- Treat the Template master image as the composition blueprint: preserve its major visual zones and design rhythm before adding creative variation.',
            '- Make the product large enough to be the obvious hero subject.',
            '- Use controlled cinematic lighting, realistic reflections, crisp edges, premium dark/automotive tones and restrained accent colors derived from the Template.',
            '- Use layered depth: background atmosphere behind the product, a strong hero product plane, and restrained foreground graphic accents.',
            '- Prefer precise reference-image editing over freeform reinterpretation; when the Template shows a specific graphic element, preserve its placement and visual role.',
            '- Keep typography short, bold and highly legible; never fill the composition with paragraphs.',
            '- Use 2–4 concise supporting visual callouts only when supported by the product reference or explicitly defined by the Template prompt.',
            '- Prefer premium automotive aftermarket advertising aesthetics: sporty, technical, clean, aggressive but not cluttered.',
            '- Do not turn the image into a generic AI poster; it must look like a real professional marketplace creative built directly from the selected Template master and prompt.',
            '',
            'OUTPUT REQUIREMENTS:',
            '- Produce a polished, premium, marketplace-ready commercial image.',
            '- Preserve Template-to-Template consistency above creative variation.',
            '- Prioritize clarity of the actual product over decorative effects.',
            '- Keep the composition intentional and uncluttered.',
            '- Respect the requested aspect ratio.',
            '',
            'FINAL COMPOSITION CHECK:',
            '- The selected Template design must be recognizable in the final composition.',
            '- The supplied product must remain the unmistakable hero product.',
            '- The Store logo must be used only as official branding when supplied.',
            '- Do not create a collage or side-by-side comparison of references.',
            '- Produce ONE cohesive final advertisement.',
        ];

        return implode("\n", array_filter(
            $parts,
            static fn ($part) => $part !== null && trim((string) $part) !== ''
        ));
    }


    /**
     * Analyze ONLY the primary product image and return a concise catalog title.
     *
     * This is deliberately separate from image generation so Recent Generations
     * can show the actual product name instead of the Template name.
     */
    private function resolveAutomaticProductTitle(string $apiKey, string $productPath): ?string
    {
        if (! is_readable($productPath)) {
            return null;
        }

        $mime = mime_content_type($productPath) ?: 'image/jpeg';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (! in_array($mime, $allowedMimes, true)) {
            $mime = 'image/jpeg';
        }

        $binary = @file_get_contents($productPath);

        if ($binary === false || $binary === '') {
            return null;
        }

        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($binary);

        $instruction = <<<'PROMPT'
You are an expert motorcycle aftermarket product-identification and catalog-title engine.

TASK
Analyze ONLY the supplied PRIMARY PRODUCT REFERENCE IMAGE and identify what physical motorcycle modification/accessory PART is actually shown.

This catalog contains MANY different products. It is NOT limited to bolts or fasteners.

Possible product families include, but are not limited to:
- body cover / body panel / trim
- bracket / mounting bracket / holder
- license plate holder
- windshield / visor
- mirror / mirror adapter
- brake or clutch lever
- handlebar / grip
- footstep / footrest
- guard / protector
- lamp / LED / turn signal / stop lamp
- engine cover
- CVT-related part
- exhaust-related accessory
- suspension accessory
- frame/chassis accessory
- clamp / adapter / spacer
- bolt / nut / screw / fastener
- decorative motorcycle accessory
- electronic motorcycle accessory
- other motorcycle modification parts visible in the image.

IDENTIFICATION METHOD
First identify the product internally, then produce the title.

1. EXAMINE THE ACTUAL OBJECT
Use the complete image to determine what the product physically is:
- overall silhouette and shape
- geometry and proportions
- mounting holes and mounting points
- brackets, tabs, clamps and adapters
- connectors or wiring
- screws, bolts, nuts and fasteners
- visible mechanical structure
- surface/material appearance ONLY when visually supported
- packaging presentation
- labels, stickers and printed markings.

2. READ VISIBLE TEXT
Carefully inspect all readable text on:
- packaging
- labels
- stickers
- product markings
- brand names
- product names
- model names
- part numbers
- SKU/product codes
- motorcycle compatibility lists.

Text is evidence, but a code/SKU must NOT automatically be treated as the product name.

3. DETERMINE THE PRODUCT CATEGORY
Do NOT default to a generic word such as "Aksesoris Motor" when the physical product can be identified more specifically.

For example:
- If the image clearly shows a mounting bracket, identify it as a bracket.
- If it clearly shows a body panel, identify it as a body cover/panel.
- If it clearly shows a footrest, identify it as a footstep/footrest.
- If it clearly shows a lever, identify it as a lever.
- If it clearly shows a bolt/fastener, identify it as a bolt/fastener.
- If it clearly shows another motorcycle part, use the most accurate supported product category.

4. DETERMINE MOTORCYCLE COMPATIBILITY
Only include a motorcycle model when there is reliable evidence such as:
- explicit compatibility text in the image
- clearly printed motorcycle model
- clearly visible model-specific labeling
- unmistakably model-specific physical evidence.

Examples of valid compatibility wording may include:
- Vario 125
- Vario 150
- Vario 160
- PCX 150
- PCX 160
- ADV 150
- ADV 160
- Scoopy
- NMAX
- Aerox
- Lexi
- other clearly supported motorcycle models.

IMPORTANT:
- NEVER guess compatibility from appearance alone.
- NEVER infer a motorcycle model merely because the part looks similar to a known model.
- NEVER add "Universal" just because no model is visible.
- Use "Universal" ONLY when the image/text actually supports that the product is universal.
- If compatibility is unknown, simply omit the motorcycle model from the title.

5. HANDLE BRAND / MODEL / PART NUMBER CORRECTLY
Preserve a recognizable brand or model when clearly visible and useful.
Do not mistake:
- a brand for the product type,
- a SKU for a product name,
- a motorcycle model for the product itself,
- marketing text for a technical specification.

6. SPECIFICATION SAFETY
Never invent:
- material
- size
- color
- quantity
- compatibility
- motorcycle model
- technical specification
- feature
- performance claim
- installation method
- brand
- product variant.

Only include such information when it is clearly visible or explicitly supported by the image.

7. PRODUCT-FIRST RULE
The PRIMARY PRODUCT REFERENCE IMAGE is the ONLY authority for identifying the product.

Do NOT use:
- Template name
- Template prompt
- Template master product
- marketing layout
- imagined installation
- common knowledge about motorcycle accessories
- a guessed motorcycle model
- an imagined use case

to identify the product.

8. TITLE CONSTRUCTION
Create a concise Indonesian marketplace-style title.

Prefer this structure when supported:

[ACCURATE PRODUCT TYPE] + [IMPORTANT IDENTIFIER/DESCRIPTOR] + [MOTORCYCLE MODEL IF RELIABLY KNOWN]

Examples:
- Bracket Dudukan Plat Nomor Vario 160
- Cover Body Depan Vario 125
- Handgrip Motor
- Baut Titanium Fairing Motor
- Bracket Windshield NMAX
- Handle Rem CNC Vario 160
- Spion Lipat Motor
- Cover CVT PCX 160

These are examples of structure only. Do NOT copy them unless the image actually shows that product.

If the exact product name is clearly printed, prefer the real product name.
If the exact name is not printed, use the most specific product category that the physical object supports.

9. ACCURACY OVER MARKETING
Do not add sales language such as:
- premium
- racing
- terbaik
- original
- murah
- keren
- custom
unless that information is explicitly part of the actual product identity visible in the reference.

The title must identify the PRODUCT, not advertise it.

10. FINAL CHECK BEFORE OUTPUT
Internally verify:
- What physical part is this?
- Is the product category supported by the visible object?
- Is the motorcycle model explicitly/reliably supported?
- Did I accidentally invent compatibility?
- Did I accidentally turn a brand/SKU into a product name?
- Did I use the Template or imagined installation?
- Is there a more specific product category visible in the image?

If any detail is uncertain, REMOVE that detail instead of guessing.

OUTPUT
Return ONLY one final product title.
No explanation.
No quotation marks.
No bullets.
No JSON.
No prefix.
No suffix.
PROMPT;

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->connectTimeout(10)
                ->timeout(45)
                ->post(self::BASE_URL . '/responses', [
                    'model' => config('services.vercel.title_model', self::DEFAULT_TITLE_MODEL),
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $instruction],
                            ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                        ],
                    ]],
                    'max_output_tokens' => 40,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $title = trim((string) $response->json('output_text', ''));

            if ($title === '') {
                $parts = $response->json('output', []);
                if (is_array($parts)) {
                    foreach ($parts as $output) {
                        foreach (($output['content'] ?? []) as $content) {
                            if (($content['type'] ?? null) === 'output_text' && filled($content['text'] ?? null)) {
                                $title = trim((string) $content['text']);
                                break 2;
                            }
                        }
                    }
                }
            }

            $title = preg_replace('/^[\s"\']+|[\s"\']+$/u', '', $title ?? '');
            $title = preg_replace('/\s+/u', ' ', $title ?? '');
            $title = trim((string) $title);

            if ($title === '') {
                return null;
            }

            // Keep history/headline safe even if the model returns an unexpectedly long sentence.
            return mb_substr($title, 0, 120);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve the credential used by Vercel AI Gateway.
     *
     * AI_GATEWAY_API_KEY is preferred so production can keep the gateway
     * credential outside the database. The existing OpenAiSetting value is
     * kept as a backwards-compatible fallback for the current Settings UI.
     */
    private function gatewayApiKey(?string $storedKey, ?int $userId = null): ?string
    {
        $credential = $this->apiPool()->acquire($userId, $storedKey);

        return $credential['key'] ?? null;
    }

    private function apiPool(): VercelGatewayKeyPool
    {
        return app(VercelGatewayKeyPool::class);
    }

    /**
     * Send an image edit request and automatically fail over across the Vercel
     * API-key pool when the current key is exhausted, invalid, rate-limited,
     * or temporarily unavailable.
     *
     * @return array{0: Response, 1: array{id:int|null,key:string,name:string,source:string}}
     */
    private function postImageEditsWithFailover(
        int $userId,
        ?array $preferredCredential,
        array $attachments,
        array $payload,
        int $generationId,
        string $source,
    ): array {
        $lastError = null;
        $attemptedIds = [];
        $maxAttempts = max(1, $this->apiPool()->totalCount($userId) + 1);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt === 1 && is_array($preferredCredential) && filled($preferredCredential['key'] ?? null)) {
                $credential = $preferredCredential;
            } else {
                $credential = $this->apiPool()->acquire($userId, null, $attemptedIds);
            }

            if (! $credential || ($credential['id'] !== null && in_array($credential['id'], $attemptedIds, true))) {
                break;
            }

            if ($credential['id'] !== null) {
                $attemptedIds[] = $credential['id'];
            }

            $request = Http::acceptJson()
                ->withToken($credential['key'])
                ->connectTimeout(15)
                ->timeout(240);

            $handles = [];
            try {
                foreach ($attachments as [$path, $filename]) {
                    $handle = fopen($path, 'rb');
                    if ($handle === false) {
                        throw new RuntimeException('File input tidak dapat dibuka: ' . $filename);
                    }
                    $handles[] = $handle;
                    $request = $request->attach('image[]', $handle, $filename);
                }

                $response = $request->post(self::BASE_URL . '/images/edits', $payload);
            } finally {
                foreach ($handles as $handle) {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                }
            }

            if ($response->successful()) {
                $this->apiPool()->reportSuccess($credential['id'], $userId);

                return [$response, $credential];
            }

            $detail = $this->extractError($response);
            $classification = $this->apiPool()->reportFailure(
                $credential['id'],
                $userId,
                $response,
                $detail,
            );

            $lastError = $detail;

            if (! $classification['retry']) {
                throw new RuntimeException($detail);
            }

            $this->activity()->warning(
                action: 'generate_openai_image',
                category: 'api',
                title: 'Automatic API failover dijalankan.',
                description: sprintf(
                    'Key "%s" tidak dapat digunakan (%s). Mencoba API key berikutnya.',
                    $credential['name'],
                    $classification['reason'],
                ),
                metadata: [
                    'source' => $source,
                    'generation_id' => $generationId,
                    'api_key_id' => $credential['id'],
                    'api_key_name' => $credential['name'],
                    'classification' => $classification,
                    'http_status' => $response->status(),
                ],
                httpStatus: $response->status(),
            );
        }

        throw new RuntimeException(
            $lastError ?: 'Semua Vercel AI Gateway API key yang tersedia gagal digunakan.'
        );
    }

    /**
     * AI Gateway image models use creator/model IDs.
     * Keep compatibility with the existing UI/database values.
     */
    private function normalizeGatewayImageModel(string $model): string
    {
        $model = trim($model);

        if ($model === '' || strcasecmp($model, 'OpenAI Image Generation') === 0) {
            return self::DEFAULT_IMAGE_MODEL;
        }

        if (str_starts_with($model, 'openai/')) {
            return $model;
        }

        if (str_starts_with($model, 'gpt-image-')) {
            return 'openai/' . $model;
        }

        return $model;
    }

    private function normalizeCustomTitle(?string $customTitle): ?string
    {
        $title = trim((string) $customTitle);

        return $title !== '' ? mb_substr($title, 0, 120) : null;
    }

    private function generationMode(bool $hasInstalledReference): string
    {
        return $hasInstalledReference
            ? 'product_with_installed_reference'
            : 'product_only';
    }

    private function hasTemplateReference(Template $template): bool
    {
        return $this->templateReferencePath($template) !== null;
    }

    private function templateReferencePath(Template $template): ?string
    {
        if (! $template->example_image_path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($template->example_image_path)) {
            return null;
        }

        $path = $disk->path($template->example_image_path);
        return is_readable($path) ? $path : null;
    }

    private function hasStoreLogo(Store $store): bool
    {
        if (! $store->logo_path) {
            return false;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($store->logo_path)) {
            return false;
        }

        return is_readable($disk->path($store->logo_path));
    }

    private function mapSize(string $aspectRatio): string
    {
        return match ($aspectRatio) {
            '16:9' => '1536x1024',
            '4:5', '3:4', '9:16' => '1024x1536',
            default => '1024x1024',
        };
    }

    private function mapQuality(string $quality): string
    {
        return $quality === 'high' ? 'high' : 'medium';
    }

    private function resolveImageBinary(array $item): ?string
    {
        if (! empty($item['b64_json'])) {
            $binary = base64_decode($item['b64_json'], true);

            if ($binary === false) {
                throw new RuntimeException('Vercel AI Gateway mengembalikan b64_json yang tidak valid.');
            }

            return $binary;
        }

        if (! empty($item['url'])) {
            $response = Http::timeout(120)->get($item['url']);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Gagal mengambil hasil image dari URL Vercel AI Gateway. HTTP ' . $response->status() . '.'
                );
            }

            return $response->body();
        }

        return null;
    }

    private function imageDimensions(string $path): array
    {
        $size = @getimagesize($path);

        return [$size[0] ?? null, $size[1] ?? null];
    }

    private function extractError(Response $response): string
    {
        $message = $response->json('error.message');

        if (is_string($message) && trim($message) !== '') {
            return trim($message) . ' (HTTP ' . $response->status() . ')';
        }

        return 'Vercel AI Gateway image generation gagal. HTTP ' . $response->status() . '.';
    }

    private function safeResponseBody(Response $response): ?string
    {
        $body = trim($response->body());

        if ($body === '') {
            return null;
        }

        // Never expose the API key or authorization header in Activity Logs.
        return mb_substr($body, 0, 1000);
    }

    private function activity(): ActivityLogService
    {
        return app(ActivityLogService::class);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
