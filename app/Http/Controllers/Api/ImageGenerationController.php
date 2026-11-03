<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOpenAiImageJob;
use App\Models\ApiKey;
use App\Models\GeneratedImage;
use App\Models\Generation;
use App\Models\Store;
use App\Models\Template;
use App\Services\AI\ProviderManager;
use App\Services\OpenAiImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageGenerationController extends Controller
{
    public function catalogStores(Request $request)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:generate');

        $stores = Store::query()
            ->where('is_active', true)
            ->when($apiKey->store_id !== null, fn ($query) => $query->whereKey($apiKey->store_id))
            ->withCount(['templates' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'marketplace', 'brand_name']);

        return response()->json(['data' => $stores]);
    }

    public function catalogTemplates(Request $request, int $store)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:generate');

        abort_if($apiKey->store_id !== null && (int) $apiKey->store_id !== $store, 403);

        $storeRecord = Store::query()->whereKey($store)->where('is_active', true)->firstOrFail();
        $templates = Template::query()
            ->where('store_id', $storeRecord->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'store_id', 'name', 'slug', 'description', 'aspect_ratio', 'output_quality']);

        return response()->json([
            'store' => ['id' => $storeRecord->id, 'name' => $storeRecord->name],
            'data' => $templates,
        ]);
    }

    public function catalogModels(Request $request, ProviderManager $providers)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:generate');

        $models = collect($providers->availableModels($apiKey->user_id));
        $preferred = app(OpenAiImageService::class)->defaultImageModel();
        $default = $models->contains(fn (array $model) => ($model['id'] ?? null) === $preferred)
            ? $preferred
            : ($models->first()['id'] ?? null);

        return response()->json([
            'provider' => $providers->activeProviderName($apiKey->user_id),
            'default_model' => $default,
            'data' => $models->values(),
        ]);
    }

    public function store(Request $request, OpenAiImageService $imageService, ProviderManager $providers)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:generate');

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'template_id' => ['required', 'integer', 'exists:templates,id'],
            'product_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'installed_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'model' => ['nullable', 'string', 'max:150'],
            'custom_title' => ['nullable', 'string', 'max:120'],
            'image_count' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        $store = Store::query()->whereKey($validated['store_id'])->where('is_active', true)->firstOrFail();
        $template = Template::query()
            ->whereKey($validated['template_id'])
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->firstOrFail();

        if ($apiKey->store_id !== null && (int) $apiKey->store_id !== (int) $store->id) {
            return response()->json(['message' => 'This API key is not permitted to use the selected Store.'], 403);
        }

        $availableModels = $providers->availableModels($apiKey->user_id);
        $preferredModel = $imageService->defaultImageModel();
        $defaultModel = collect($availableModels)->contains(fn (array $item) => ($item['id'] ?? null) === $preferredModel)
            ? $preferredModel
            : ($availableModels[0]['id'] ?? '');
        $model = trim((string) ($validated['model'] ?? $defaultModel));
        if ($model === '' || ! collect($availableModels)->pluck('id')->contains($model)) {
            throw ValidationException::withMessages([
                'model' => ['The selected model is not available for the active AI provider.'],
            ]);
        }

        $generation = $imageService->queueGeneration(
            user: $apiKey->user,
            store: $store,
            template: $template,
            imageOne: $request->file('product_image'),
            imageTwo: $request->file('installed_image'),
            model: $model,
            aspectRatio: '1:1',
            quality: 'low',
            imageCount: (int) ($validated['image_count'] ?? 1),
            customTitle: $validated['custom_title'] ?? null,
            source: 'api',
            apiKeyId: $apiKey->id,
        );

        $queue = $providers->queueForGeneration($model, $apiKey->user_id);

        try {
            GenerateOpenAiImageJob::dispatch($generation->id)->onQueue($queue);
        } catch (\Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => 'Generation was created but could not be queued.',
                'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'progress' => 100,
                    'progress_stage' => 'Queue dispatch failed',
                    'queue_dispatch_failed' => true,
                ]),
            ]);
            report($exception);

            return response()->json(['message' => 'Generation could not be queued.', 'generation_id' => $generation->id], 503);
        }

        $generation->update([
            'metadata' => array_merge($generation->metadata ?? [], [
                'queue' => $queue,
                'dispatched_at' => now()->toIso8601String(),
            ]),
        ]);

        return response()->json([
            'id' => $generation->id,
            'status' => $generation->status,
            'created_at' => $generation->created_at?->toIso8601String(),
            'status_url' => route('api.v1.images.generations.show', ['generation' => $generation->id]),
        ], 202);
    }

    public function show(Request $request, int $generation)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:read');

        $record = Generation::query()
            ->whereKey($generation)
            ->where('user_id', $apiKey->user_id)
            ->with(['store', 'template', 'generatedImages'])
            ->firstOrFail();

        if ($apiKey->store_id !== null && (int) $apiKey->store_id !== (int) $record->store_id) {
            return response()->json(['message' => 'This API key is not permitted to access this generation.'], 403);
        }

        $metadata = $record->metadata ?? [];

        return response()->json([
            'id' => $record->id,
            'status' => $record->status,
            'progress' => (int) ($metadata['progress'] ?? 0),
            'progress_stage' => $metadata['progress_stage'] ?? null,
            'error' => $record->status === 'failed' ? $record->error_message : null,
            'store' => ['id' => $record->store?->id, 'name' => $record->store?->name],
            'template' => ['id' => $record->template?->id, 'name' => $record->template?->name],
            'model' => $record->model,
            'title' => $metadata['requested_custom_title'] ?? $metadata['final_title'] ?? null,
            'source' => $metadata['request_channel'] ?? $metadata['source'] ?? 'website',
            'images' => $record->generatedImages->map(fn (GeneratedImage $image) => [
                'id' => $image->id,
                'url' => route('api.v1.images.generations.images.show', ['generation' => $record->id, 'image' => $image->id]),
            ])->values(),
            'created_at' => $record->created_at?->toIso8601String(),
            'completed_at' => $record->completed_at?->toIso8601String(),
        ]);
    }

    public function showImage(Request $request, int $generation, int $image)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:read');

        $record = Generation::query()
            ->whereKey($generation)
            ->where('user_id', $apiKey->user_id)
            ->firstOrFail();

        if ($apiKey->store_id !== null && (int) $apiKey->store_id !== (int) $record->store_id) {
            abort(403);
        }

        $generatedImage = GeneratedImage::query()
            ->whereKey($image)
            ->where('generation_id', $record->id)
            ->firstOrFail();

        abort_unless($generatedImage->image_path && Storage::disk('public')->exists($generatedImage->image_path), 404);

        return Storage::disk('public')->response($generatedImage->image_path, basename($generatedImage->image_path), [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function cancel(Request $request, int $generation)
    {
        $apiKey = $this->apiKey($request);
        $this->requireScope($apiKey, 'images:cancel');

        $record = Generation::query()
            ->whereKey($generation)
            ->where('user_id', $apiKey->user_id)
            ->firstOrFail();

        if ($apiKey->store_id !== null && (int) $apiKey->store_id !== (int) $record->store_id) {
            return response()->json(['message' => 'This API key is not permitted to cancel this generation.'], 403);
        }

        if (! in_array($record->status, ['queued', 'pending'], true)) {
            return response()->json([
                'message' => 'Only generations that have not started can be cancelled safely.',
                'status' => $record->status,
            ], 409);
        }

        $record->update([
            'status' => 'cancelled',
            'completed_at' => now(),
            'metadata' => array_merge($record->metadata ?? [], [
                'progress_stage' => 'Cancelled by API',
                'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);

        return response()->json(['id' => $record->id, 'status' => 'cancelled']);
    }

    private function apiKey(Request $request): ApiKey
    {
        return $request->attributes->get('api_key');
    }

    private function requireScope(ApiKey $apiKey, string $scope): void
    {
        abort_unless($apiKey->allows($scope), 403, 'API key is missing required scope: ' . $scope);
    }
}
