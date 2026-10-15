<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\Generation;
use App\Models\Store;
use App\Models\Template;
use App\Models\User;
use App\Services\AI\DTO\ImageGenerationRequest;
use App\Services\AI\DTO\ReferenceImage;
use App\Services\AI\ProviderManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OpenAiImageService
{
    private const BASE_URL = 'https://ai-gateway.vercel.sh/v1';

    private const DEFAULT_IMAGE_MODEL = 'openai/gpt-image-2.5-sunburst';

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

        // Enforce Low server-side; ignore UI/template quality overrides.
        $quality = 'low';
        $startedAt = microtime(true);
        $httpErrorLogged = false;
        $store = $this->resolveStoreForTemplate($store, $template);
        $hasInstalledReference = (bool) $imageTwo;
        $hasTemplateReference = $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);
        $generationMode = $this->generationMode($hasInstalledReference);
        $customTitle = $this->normalizeCustomTitle($customTitle);
        // Keep the submitted custom title immutable throughout this request.
        // The same value is also persisted in generation metadata for queued processing.
        $requestedCustomTitle = $customTitle;
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
                'requested_custom_title' => $customTitle,
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
                'requested_custom_title' => $customTitle,
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

            if ($requestedCustomTitle !== null) {
                // The user-supplied title is immutable. Rebuild the prompt here in the
                // worker so an old/stale queued prompt can never cause an AI-generated
                // headline to replace the user's exact title.
                $customTitle = $requestedCustomTitle;
                $titleSource = 'custom';
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
                        'requested_custom_title' => $requestedCustomTitle,
                        'final_title' => $requestedCustomTitle,
                        'title_source' => 'custom',
                        'custom_title' => $requestedCustomTitle,
                    ]),
                ]);
            } elseif ($customTitle === null) {
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

            // Build one normalized reference contract for every model/provider.
            // The service owns the semantic roles; the transport adapter decides
            // whether the selected model receives multiple inputs or one fallback board.
            $referenceManifest = $this->buildUniversalReferenceManifest(
                store: $store,
                template: $template,
                productPath: $productOnePath,
                installedPath: ($imageTwo && $productTwoPath) ? $productTwoPath : null,
            );

            $referenceTransport = $this->prepareReferenceTransport(
                model: $model,
                references: $referenceManifest,
                generationId: $generation->id,
            );
            $attachments = $referenceTransport['attachments'];

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
                    'reference_transport' => $referenceTransport['transport'],
                    'reference_manifest' => $this->publicReferenceManifest($referenceManifest),
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

            $references = array_map(
                static fn (array $reference): ReferenceImage => new ReferenceImage(
                    role: $reference['role'],
                    path: $reference['path'],
                    filename: $reference['filename'],
                ),
                $referenceManifest,
            );

            $providerResult = app(ProviderManager::class)->generate(
                new ImageGenerationRequest(
                    model: $model,
                    prompt: $prompt,
                    references: $references,
                    imageCount: $imageCount,
                    size: $this->mapSize($aspectRatio),
                    quality: $this->mapQuality($quality),
                    outputFormat: 'png',
                    userId: $user->id,
                    generationId: $generation->id,
                    metadata: [
                        'attachments' => $attachments,
                        'negative_prompt' => $negativePrompt,
                        'aspect_ratio' => $aspectRatio,
                        'source' => 'openai_image_service',
                    ],
                ),
            );

            $providerMetadata = $providerResult->metadata;
            $generation->update([
                'metadata' => array_merge($generation->metadata ?? [], [
                    'provider' => $providerResult->provider,
                    'model' => $providerResult->model,
                    'gateway' => $providerResult->provider === 'vercel' ? 'vercel-ai-gateway' : $providerResult->provider,
                    'gateway_key_id' => $providerMetadata['gateway_key_id'] ?? null,
                    'gateway_key_name' => $providerMetadata['gateway_key_name'] ?? null,
                    'gateway_key_source' => $providerMetadata['gateway_key_source'] ?? null,
                ]),
            ]);

            $data = $providerResult->images;
            $responseStatus = (int) ($providerMetadata['http_status'] ?? 200);

            $durationMs = $this->durationMs($startedAt);

            if (! is_array($data) || $data === []) {
                throw new RuntimeException('AI image provider tidak mengembalikan gambar.');
            }

            $this->activity()->processing(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Response AI image provider diterima.',
                description: sprintf(
                    '%s mengembalikan %d item image. Server mulai menyimpan hasil.',
                    $providerResult->provider,
                    count($data)
                ),
                metadata: [
                    'source' => 'openai_image_service',
                    'generation_id' => $generation->id,
                    'provider' => $providerResult->provider,
                    'model' => $providerResult->model,
                    'response_items' => count($data),
                ],
                durationMs: $durationMs,
                httpStatus: $responseStatus,
            );

            $saved = 0;

            // Cancellation is cooperative: never commit provider output after the
            // user has cancelled the generation while the provider request was in flight.
            $generation->refresh();
            if ($generation->status === 'cancelled') {
                $this->activity()->processing(
                    action: 'generation_cancelled',
                    category: 'generator',
                    title: 'Hasil provider diabaikan karena generation dibatalkan.',
                    description: 'Provider selesai, tetapi generation sudah dibatalkan sebelum hasil disimpan.',
                    metadata: [
                        'generation_id' => $generation->id,
                        'provider' => $providerResult->provider,
                        'model' => $providerResult->model,
                    ],
                );

                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

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

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                $this->activity()->processing(
                    action: 'generation_cancelled',
                    category: 'generator',
                    title: 'Generation tetap dibatalkan.',
                    description: 'Hasil yang sudah tersimpan tidak mengubah status generation menjadi completed.',
                    metadata: [
                        'generation_id' => $generation->id,
                        'saved_images' => $saved,
                    ],
                );

                return $generation->fresh(['generatedImages', 'store', 'template']);
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
                title: 'Generate AI image via ' . ($providerName === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway') . ' berhasil.',
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
                httpStatus: $responseStatus,
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
        // Persist Low regardless of legacy Template/client-submitted quality values.
        $quality = 'low';
        $startedAt = microtime(true);
        $store = $this->resolveStoreForTemplate($store, $template);

        $model = trim($model);
        if ($model === '') {
            throw new RuntimeException('Model image belum dipilih.');
        }

        // Resolve the provider before persisting the queued generation so the
        // selected Settings provider is authoritative from the first step.
        $providerManager = app(ProviderManager::class);
        $provider = $providerManager->providerFor($model, $user->id);

        if ($provider->name() === 'vercel' && ! $this->apiPool()->hasAvailableKey($user->id)) {
            throw new RuntimeException('Belum ada Vercel AI Gateway API Key yang tersedia. Buka Settings → AI Gateway.');
        }

        if ($provider->name() === 'agentkit' && ! app(\App\Services\AgentAiCredentialPool::class)->hasAvailableCredential($user->id)) {
            throw new RuntimeException('Belum ada Agent AI credential aktif. Buka Settings → AI Provider → Agent AI.');
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
        // Keep the submitted custom title immutable throughout this request.
        // The same value is also persisted in generation metadata for queued processing.
        $requestedCustomTitle = $customTitle;
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
                'title_source' => $titleSource,
                'custom_title' => $requestedCustomTitle,
                'requested_custom_title' => $requestedCustomTitle,
                'final_title' => $requestedCustomTitle,
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
                'Generation #%d siap diproses via %s. %d reference image akan digunakan.',
                $generation->id,
                $provider->name() === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway',
                $referenceCount
            ),
            metadata: [
                'source' => 'generator_queue',
                'generation_id' => $generation->id,
                'store_id' => $store->id,
                'template_id' => $template->id,
                'model' => $model,
                'provider' => $provider->name(),
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
        if ($store && $template) {
            $store = $this->resolveStoreForTemplate($store, $template);
        }
        $startedAt = microtime(true);
        $httpErrorLogged = false;

        $metadata = $generation->metadata ?? [];
        $hasInstalledReference = ! empty($generation->product_image_2_path);
        $hasTemplateReference = $this->hasTemplateReference($template);
        $attachTemplateReference = $hasTemplateReference;
        $hasStoreLogo = $this->hasStoreLogo($store);
        $generationMode = (string) ($metadata['generation_mode'] ?? $this->generationMode($hasInstalledReference));
        $requestedCustomTitle = $this->normalizeCustomTitle($metadata['requested_custom_title'] ?? null);
        $customTitle = $requestedCustomTitle ?? $this->normalizeCustomTitle($metadata['custom_title'] ?? $metadata['final_title'] ?? null);
        $titleSource = $requestedCustomTitle !== null
            ? 'custom'
            : ($customTitle !== null ? (string) ($metadata['title_source'] ?? 'ai') : 'ai');
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

            // Resolve the provider again inside the worker. The queued generation
            // must never assume Vercel merely because this service historically
            // handled OpenAI image requests.
            $providerManager = app(ProviderManager::class);
            $provider = $providerManager->providerFor((string) $generation->model, $generation->user_id);
            $providerName = $provider->name();

            $credential = $providerName === 'vercel'
                ? $this->apiPool()->acquire($generation->user_id)
                : null;
            $apiKey = $credential['key'] ?? null;

            $model = (string) $generation->model;
            if ($providerName === 'vercel') {
                $model = $this->normalizeGatewayImageModel($model);
            }

            if ($model === '') {
                throw new RuntimeException('Model image belum dipilih untuk provider aktif.');
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

            if ($customTitle === null && $providerName === 'vercel' && filled($apiKey)) {
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

            // Build one normalized reference contract for every model/provider.
            // The service owns the semantic roles; the transport adapter decides
            // whether the selected model receives multiple inputs or one fallback board.
            $referenceManifest = $this->buildUniversalReferenceManifest(
                store: $store,
                template: $template,
                productPath: $productOnePath,
                installedPath: $productTwoPath,
            );

            $referenceTransport = $this->prepareReferenceTransport(
                model: $model,
                references: $referenceManifest,
                generationId: $generation->id,
            );
            $attachments = $referenceTransport['attachments'];

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 35,
                'progress_stage' => 'Mengirim request ke OpenAI',
                'attachment_count' => count($attachments),
                'reference_transport' => $referenceTransport['transport'],
                'reference_manifest' => $this->publicReferenceManifest($referenceManifest),
            ])]);

            $this->activity()->processing(
                action: 'generate_openai_image',
                category: 'generator',
                title: $providerName === 'agentkit' ? 'Mengirim request ke Agent AI.' : 'Mengirim request ke Vercel AI Gateway.',
                description: sprintf('%s memproses %d attachment, model %s.', $providerName === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway', count($attachments), $model),
                metadata: [
                    'source' => 'openai_image_queue',
                    'generation_id' => $generation->id,
                    'endpoint' => '/v1/images/edits',
                    'attachment_count' => count($attachments),
                    'reference_transport' => $referenceTransport['transport'],
                    'reference_manifest' => $this->publicReferenceManifest($referenceManifest),
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

            $references = array_map(
                static fn (array $reference): ReferenceImage => new ReferenceImage(
                    role: $reference['role'],
                    path: $reference['path'],
                    filename: $reference['filename'],
                ),
                $referenceManifest,
            );

            $providerResult = app(ProviderManager::class)->generate(
                new ImageGenerationRequest(
                    model: $model,
                    prompt: (string) $generation->prompt,
                    references: $references,
                    imageCount: (int) (($generation->metadata ?? [])['requested_image_count'] ?? 1),
                    size: $this->mapSize((string) $generation->aspect_ratio),
                    quality: $this->mapQuality((string) $generation->output_quality),
                    outputFormat: 'png',
                    userId: $generation->user_id,
                    generationId: $generation->id,
                    metadata: [
                        'attachments' => $attachments,
                        'negative_prompt' => (string) $generation->negative_prompt,
                        'aspect_ratio' => (string) $generation->aspect_ratio,
                        'source' => 'openai_image_queue',
                    ],
                ),
            );

            $providerMetadata = $providerResult->metadata;
            $generation->update([
                'metadata' => array_merge($generation->metadata ?? [], [
                    'provider' => $providerResult->provider,
                    'gateway_key_id' => $providerMetadata['gateway_key_id'] ?? null,
                    'gateway_key_name' => $providerMetadata['gateway_key_name'] ?? null,
                    'gateway_key_source' => $providerMetadata['gateway_key_source'] ?? null,
                ]),
            ]);

            $generation->refresh();
            if ($generation->status === 'cancelled') {
                return $generation->fresh(['generatedImages', 'store', 'template']);
            }

            $generation->update(['metadata' => array_merge($generation->metadata ?? [], [
                'progress' => 78,
                'progress_stage' => 'Memproses hasil ' . ($providerName === 'agentkit' ? 'Agent AI' : 'Vercel'),
            ])]);

            $data = $providerResult->images;
            if (! is_array($data) || $data === []) {
                throw new RuntimeException(($providerName === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway') . ' tidak mengembalikan gambar.');
            }

            $saved = 0;
            foreach ($data as $index => $item) {
                $generation->refresh();
                if ($generation->status === 'cancelled') {
                    return $generation->fresh(['generatedImages', 'store', 'template']);
                }
                $binary = $this->resolveImageBinary($item);
                if ($binary === null || $binary === '') {
                    throw new RuntimeException(($providerName === 'agentkit' ? 'Agent AI' : 'Vercel AI Gateway') . ' mengembalikan item image #' . ($index + 1) . ' tanpa binary image.');
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
                httpStatus: (int) ($providerMetadata['http_status'] ?? 200),
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
    ): string {
        $hasTemplateReference = $templateReferenceAttached ?? $this->hasTemplateReference($template);
        $hasStoreLogo = $this->hasStoreLogo($store);

        $parts = [
            'ROLE: Create one premium, marketplace-ready commercial product advertisement. Follow the selected Template as a fixed design system.',
            '',
            'REFERENCE ROLES — semantic roles are authoritative; never confuse or substitute them:',
            $hasTemplateReference
                ? 'TEMPLATE MASTER: visual authority for layout, composition, hierarchy, palette, typography placement, background, frames, badges, motifs and spacing. Reuse its design system as closely as practical; replace its example product and example text with the current product/content.'
                : 'No Template master image is attached. Derive the design from the selected Template prompt and keep it consistent across generations.',
            'PRIMARY PRODUCT: the sole authority for product identity. Preserve its real shape, geometry, proportions, color, material, finish, markings, packaging and visible details. Never replace, redesign, merge, or invent unseen product features.',
            $hasInstalledReference
                ? 'INSTALLED REFERENCE: use only for truthful installation, orientation, placement, scale, fitment and real-world context. The installed product must be visibly represented as the hero view, and the supplied product/packaging must also appear as a supporting visual. Keep the Template design system.'
                : 'NO INSTALLED REFERENCE: do not invent or imply installation. No motorcycle, scooter, vehicle, wheel, rider, person, hand, mechanic, road, garage, workshop, showroom, mounting scene or usage environment. Use only the supplied product image and visible source pixels; omit unsupported details or callouts.',
            $hasStoreLogo
                ? 'OFFICIAL STORE LOGO: use the dedicated Store logo reference as the exclusive source for the exact logo artwork, wordmark, shapes, colors, proportions and spacing. Place the actual artwork visibly; never redraw, typeset, approximate, extract, copy or blend a logo from product/installed photos or the Template. Preserve manufacturer branding only on the physical product/packaging.'
                : 'STORE LOGO MISSING: do not invent a logo; generation must stop until the selected Store has a valid logo.',
            '',
            'TEMPLATE PROMPT — mandatory visual direction:',
            trim((string) $template->prompt),
            '',
            'TITLE:',
            $customTitle !== null
                ? 'Exact required main headline: "' . $customTitle . '". Preserve its wording and spelling; do not translate, paraphrase, shorten, expand or replace it. Ignore conflicting example titles in the Template. Only line breaks and typographic styling may adapt.'
                : 'If a resolved title is supplied by the application, use it. Otherwise infer a concise accurate title from the primary product image only. Do not invent specifications, model numbers, compatibility, benefits, certifications, prices, discounts or factual claims.',
            '',
            'BRAND CONTEXT:',
            'Store: ' . ($store->brand_name ?: $store->name),
            'Marketplace: ' . ($store->marketplace ?: 'Marketplace'),
            'Brand description: ' . ($store->description ?: 'Follow the selected Store visual identity.'),
            '',
            'COMPOSITION AND CONSISTENCY:',
            'Keep every generation using this Template recognizable as the same design series: preserve major zones, hierarchy, typography character, palette, graphic language, frames, badge/callout style, background treatment and spacing. Adapt only product-specific content and truthful installation context. Use one cohesive composition, never a collage or side-by-side comparison. Keep the product prominent, clean, realistic and readable at thumbnail size; use decorative details only when compatible with the Template and source.',
            '',
            'REFERENCE TRANSPORT:',
            'References may arrive separately or as one labeled universal reference board. If a board is supplied, treat each labeled panel as its own reference and never reproduce the board/collage. Semantic roles take priority over attachment order.',
            '',
            'FINAL CHECK:',
            'Correct product identity; Template design preserved; exact custom title if supplied; official Store logo used only from its dedicated image; installed view included only when a reference exists; product/packaging visible; no invented physical details or unsupported factual claims; one polished advertisement in the requested aspect ratio.',
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
     * Guarantee that the Store used for branding belongs to the selected Template.
     * The Template is the source of truth because each Template is owned by exactly
     * one Store. The caller-provided Store is retained only when it matches.
     */
    private function resolveStoreForTemplate(Store $store, Template $template): Store
    {
        $template->loadMissing('store');
        $templateStore = $template->store;

        if (! $templateStore) {
            throw new RuntimeException('Template belum memiliki Store pemilik.');
        }

        if ((int) $templateStore->id !== (int) $store->id) {
            throw new RuntimeException(
                'Store tidak cocok dengan Template. Generation dibatalkan untuk mencegah logo Store yang salah.'
            );
        }

        return $templateStore;
    }

    /**
     * Build the canonical, provider-neutral reference manifest.
     *
     * Roles are semantic and never depend on the model's multipart field names:
     * template = visual/layout authority
     * product = exact product identity authority
     * installed = factual installation/usage authority
     * store_logo = official store branding authority
     *
     * @return array<int,array{role:string,path:string,filename:string}>
     */
    private function buildUniversalReferenceManifest(
        Store $store,
        Template $template,
        string $productPath,
        ?string $installedPath = null,
    ): array {
        $references = [];

        if ($this->hasTemplateReference($template)) {
            $path = $this->templateReferencePath($template);
            if ($path) {
                $references[] = [
                    'role' => 'template',
                    'path' => $path,
                    'filename' => 'template-master-' . basename($path),
                ];
            }
        }

        if (! is_readable($productPath)) {
            throw new RuntimeException('Primary product reference tidak dapat dibaca.');
        }

        $references[] = [
            'role' => 'product',
            'path' => $productPath,
            'filename' => 'product-reference-' . basename($productPath),
        ];

        if ($installedPath !== null && is_readable($installedPath)) {
            $references[] = [
                'role' => 'installed',
                'path' => $installedPath,
                'filename' => 'installed-reference-' . basename($installedPath),
            ];
        }

        // Branding is mandatory: fail closed rather than silently asking the model
        // to invent a logo when the selected Store's original asset is missing.
        if (! $this->hasStoreLogo($store)) {
            throw new RuntimeException(
                'Logo resmi Store tidak ditemukan atau tidak dapat dibaca. Periksa logo_path pada Store pemilik Template sebelum generate.'
            );
        }

        $logoPath = Storage::disk('public')->path($store->logo_path);
        $references[] = [
            'role' => 'store_logo',
            'path' => $logoPath,
            'filename' => 'store-logo-' . basename($logoPath),
        ];

        return $references;
    }

    /**
     * Decide how the canonical references are transported to the selected model.
     *
     * Models documented to accept multiple reference images receive the references
     * individually. Unknown/ambiguous models use a single labeled reference board,
     * which preserves all roles instead of silently dropping the second/third image.
     * This makes the service safe when new providers/models are added later.
     *
     * @return array{transport:string,attachments:array<int,array{0:string,1:string,2:string,3:bool}>}
     */
    private function prepareReferenceTransport(
        string $model,
        array $references,
        int $generationId,
    ): array {
        if ($this->modelSupportsMultipleReferenceImages($model)) {
            return [
                'transport' => 'multiple_reference_images',
                'attachments' => array_map(
                    static fn (array $reference): array => [
                        $reference['path'],
                        $reference['filename'],
                        $reference['role'],
                        false,
                    ],
                    $references
                ),
            ];
        }

        $boardPath = $this->createUniversalReferenceBoard($references, $generationId);

        return [
            'transport' => 'single_reference_board',
            'attachments' => [[
                $boardPath,
                'universal-reference-board-' . $generationId . '.png',
                'reference_board',
                true,
            ]],
        ];
    }

    private function modelSupportsMultipleReferenceImages(string $model): bool
    {
        $model = strtolower(trim($model));

        // These families are explicitly exposed by AI Gateway with multiple reference
        // image support in their current model pages/playground.
        return str_starts_with($model, 'openai/gpt-image-')
            || str_starts_with($model, 'google/gemini-3-pro-image')
            || str_starts_with($model, 'google/gemini-3.1-flash-image')
            || str_starts_with($model, 'bytedance/seedream-5.0-lite');
    }

    /**
     * Create a neutral 2x2 reference board for models where multi-image transport
     * is not known to be supported. Each panel is labeled with its semantic role.
     */
    private function createUniversalReferenceBoard(array $references, int $generationId): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('PHP GD extension diperlukan untuk fallback universal reference board.');
        }

        $directory = storage_path('app/generation-reference-boards');
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder reference board tidak dapat dibuat.');
        }

        $canvasWidth = 2400;
        $canvasHeight = 1800;
        $gap = 32;
        $header = 86;
        $panelWidth = (int) (($canvasWidth - ($gap * 3)) / 2);
        $panelHeight = (int) (($canvasHeight - ($gap * 3) - $header) / 2);

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        $background = imagecolorallocate($canvas, 20, 24, 28);
        $panelBackground = imagecolorallocate($canvas, 245, 245, 245);
        $text = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 170, 178, 186);
        imagefill($canvas, 0, 0, $background);

        imagestring($canvas, 5, 32, 22, 'UNIVERSAL REFERENCE BOARD — DO NOT TREAT AS A SINGLE PRODUCT PHOTO', $text);
        imagestring($canvas, 3, 32, 50, 'TEMPLATE = STYLE · PRODUCT = IDENTITY · INSTALLED = USAGE · STORE LOGO = BRANDING', $muted);

        $positions = [
            [0, 0], [1, 0], [0, 1], [1, 1],
        ];

        foreach ($positions as $index => [$column, $row]) {
            $x = $gap + ($column * ($panelWidth + $gap));
            $y = $header + $gap + ($row * ($panelHeight + $gap));
            imagefilledrectangle($canvas, $x, $y, $x + $panelWidth, $y + $panelHeight, $panelBackground);

            if (! isset($references[$index])) {
                continue;
            }

            $reference = $references[$index];
            $source = $this->loadReferenceImage($reference['path']);
            if ($source === null) {
                continue;
            }

            $label = strtoupper(str_replace('_', ' ', $reference['role']));
            $labelHeight = 42;
            $labelBg = imagecolorallocate($canvas, 15, 18, 22);
            imagefilledrectangle($canvas, $x, $y, $x + $panelWidth, $y + $labelHeight, $labelBg);
            imagestring($canvas, 5, $x + 16, $y + 11, $label, $text);

            $srcWidth = imagesx($source);
            $srcHeight = imagesy($source);
            $availableTop = $y + $labelHeight + 10;
            $availableHeight = $panelHeight - $labelHeight - 20;

            $scale = min(
                ($panelWidth - 20) / max(1, $srcWidth),
                $availableHeight / max(1, $srcHeight)
            );
            $dstWidth = max(1, (int) floor($srcWidth * $scale));
            $dstHeight = max(1, (int) floor($srcHeight * $scale));
            $dstX = $x + (int) floor(($panelWidth - $dstWidth) / 2);
            $dstY = $availableTop + (int) floor(($availableHeight - $dstHeight) / 2);

            imagecopyresampled(
                $canvas,
                $source,
                $dstX,
                $dstY,
                0,
                0,
                $dstWidth,
                $dstHeight,
                $srcWidth,
                $srcHeight
            );

            imagedestroy($source);
        }

        $path = $directory . '/reference-board-' . $generationId . '-' . uniqid('', true) . '.png';
        if (! imagepng($canvas, $path, 6)) {
            imagedestroy($canvas);
            throw new RuntimeException('Universal reference board gagal dibuat.');
        }

        imagedestroy($canvas);

        return $path;
    }

    /**
     * @return resource|null
     */
    private function loadReferenceImage(string $path)
    {
        $mime = strtolower((string) @mime_content_type($path));

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            'image/gif' => @imagecreatefromgif($path) ?: null,
            default => null,
        };
    }

    private function publicReferenceManifest(array $references): array
    {
        return array_map(
            static fn (array $reference): array => [
                'role' => $reference['role'],
                'filename' => $reference['filename'],
            ],
            $references
        );
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
        return 'low';
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

    private function activity(): ActivityLogService
    {
        return app(ActivityLogService::class);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
