<?php

use App\Models\Store;
use App\Models\Template;
use App\Models\Generation;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $selectedStoreId = null;
    public ?int $selectedTemplateId = null;
    public string $templateSearch = '';
    public string $templateCategory = 'all';
    public bool $historyOpen = true;
    public bool $useInstalledReference = true;
    public bool $useCustomTitle = false;
    public string $customTitle = '';
    public string $historyStatus = 'all';
    public string $historySearch = '';
    public ?int $historyStoreId = null;
    public ?int $historyTemplateId = null;

    public $imageOne = null;
    public $imageTwo = null;

    public string $model = '';
    public string $aspectRatio = '1:1';
    public string $quality = 'high';
    public int $imageCount = 1;
    public bool $isGenerating = false;
    public ?int $latestGenerationId = null;

    public function getStoresProperty()
    {
        return Store::query()
            ->where('is_active', true)
            ->with('templates')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedStoreProperty(): ?Store
    {
        return $this->selectedStoreId
            ? $this->stores->firstWhere('id', $this->selectedStoreId)
            : null;
    }

    public function getTemplatesProperty()
    {
        if (!$this->selectedStoreId) {
            return collect();
        }

        return Template::query()
            ->where('store_id', $this->selectedStoreId)
            ->where('is_active', true)
            ->when($this->templateSearch !== '', function ($query) {
                $keyword = '%' . trim($this->templateSearch) . '%';

                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword);
                });
            })
            ->when($this->templateCategory !== 'all', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->templateCategory . '%')
                        ->orWhere('description', 'like', '%' . $this->templateCategory . '%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getSelectedTemplateProperty(): ?Template
    {
        return $this->selectedTemplateId
            ? $this->templates->firstWhere('id', $this->selectedTemplateId)
            : null;
    }

    public function getRecentGenerationsProperty()
    {
        return Generation::query()
            ->with(['store', 'template', 'generatedImages'])
            ->where('user_id', auth()->id())
            ->when($this->historyStatus !== 'all', fn ($q) => $q->where('status', $this->historyStatus))
            ->when($this->historyStoreId, fn ($q) => $q->where('store_id', $this->historyStoreId))
            ->when($this->historyTemplateId, fn ($q) => $q->where('template_id', $this->historyTemplateId))
            ->where(function ($q) {
                /*
                 * IMPORTANT:
                 * JSON metadata is normally non-null and does not contain
                 * "hidden". Using metadata->hidden != true alone can make
                 * MySQL exclude rows where the JSON key is missing.
                 * Only records explicitly marked hidden=true should disappear.
                 */
                $q->whereNull('metadata')
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.hidden') IS NULL")
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.hidden') <> true");
            })
            ->when(trim($this->historySearch) !== '', function ($q) {
                $keyword = '%' . trim($this->historySearch) . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->whereHas('store', fn ($store) => $store->where('name', 'like', $keyword))
                        ->orWhereHas('template', fn ($template) => $template->where('name', 'like', $keyword))
                        ->orWhere('id', 'like', $keyword)
                        ->orWhere('metadata->final_title', 'like', $keyword)
                        ->orWhere('metadata->custom_title', 'like', $keyword);
                });
            })
            // Tampilkan seluruh generation milik user yang belum dihapus.
            // Jangan batasi history hanya ke 12 item karena generation lama
            // harus tetap muncul di Recent Generations.
            ->latest()
            ->get();
    }

    public function generationEstimate(Generation $generation): array
    {
        static $baselineSeconds = null;
        static $workerCount = null;

        $metadata = $generation->metadata ?? [];
        $requestedImages = max(1, (int) data_get($metadata, 'requested_image_count', 1));

        if ($baselineSeconds === null) {
            $durations = Generation::query()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['completed', 'success', 'succeeded'])
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->limit(24)
                ->get(['started_at', 'completed_at', 'metadata'])
                ->map(function (Generation $item): ?float {
                    $seconds = $item->started_at && $item->completed_at
                        ? $item->started_at->diffInSeconds($item->completed_at)
                        : 0;

                    if ($seconds <= 0) {
                        $durationMs = (int) data_get($item->metadata, 'duration_ms', 0);
                        $seconds = $durationMs > 0 ? (int) ceil($durationMs / 1000) : 0;
                    }

                    $count = max(1, (int) data_get($item->metadata, 'requested_image_count', 1));

                    return $seconds > 0 ? max(5, $seconds / $count) : null;
                })
                ->filter(fn ($seconds) => $seconds !== null)
                ->sort()
                ->values();

            if ($durations->isEmpty()) {
                $baselineSeconds = 45.0;
            } else {
                $middle = (int) floor($durations->count() / 2);
                $baselineSeconds = $durations->count() % 2
                    ? (float) $durations->get($middle)
                    : ((float) $durations->get(max(0, $middle - 1)) + (float) $durations->get($middle)) / 2;
            }

            $baselineSeconds = max(15.0, min(180.0, $baselineSeconds));
        }

        if ($workerCount === null) {
            try {
                $workerCount = max(1, (int) app(\App\Services\QueueWorkerManager::class)->status()['running_count']);
            } catch (\Throwable) {
                $workerCount = 1;
            }
        }

        $totalExpected = max(15, (int) ceil($baselineSeconds * $requestedImages));
        $status = (string) $generation->status;

        if ($status === 'queued') {
            $processingAhead = Generation::query()
                ->whereIn('status', ['queued', 'processing'])
                ->where(function ($query) use ($generation) {
                    $query->where('created_at', '<', $generation->created_at)
                        ->orWhere(function ($nested) use ($generation) {
                            $nested->where('created_at', $generation->created_at)
                                ->where('id', '<', $generation->id);
                        });
                })
                ->count();

            $batchesAhead = (int) ceil(($processingAhead + 1) / max(1, $workerCount));
            $etaSeconds = max(10, (int) ceil($baselineSeconds * $batchesAhead));

            return [
                'seconds' => $etaSeconds,
                'label' => $this->formatEtaLabel($etaSeconds),
                'confidence' => 'historical',
                'basis' => 'historical',
            ];
        }

        if ($status !== 'processing' || ! $generation->started_at) {
            return [
                'seconds' => null,
                'label' => 'Menghitung estimasi…',
                'confidence' => null,
                'basis' => 'waiting',
            ];
        }

        $elapsed = max(0, $generation->started_at->diffInSeconds(now()));

        /*
         * Progress is a pipeline stage, not a time percentage.
         * Therefore ETA is based on historical duration + current elapsed time,
         * not on the visual progress value.
         */
        $remaining = max(5, $totalExpected - $elapsed);

        if ($elapsed > ($totalExpected * 1.35)) {
            $remaining = max(5, (int) ceil($baselineSeconds * .25));
        }

        return [
            'seconds' => $remaining,
            'label' => $this->formatEtaLabel($remaining),
            'confidence' => 'historical',
            'basis' => 'historical',
        ];
    }

    protected function formatEtaLabel(?int $seconds): string
    {
        if ($seconds === null) {
            return 'Menghitung estimasi…';
        }

        if ($seconds < 60) {
            return '± ' . max(5, $seconds) . ' detik';
        }

        $minutes = (int) ceil($seconds / 60);

        return '± ' . $minutes . ' menit';
    }

    public function getHasActiveGenerationsProperty(): bool
    {
        return Generation::query()
            ->whereIn('status', ['queued', 'processing'])
            ->exists();
    }

    public function getHistoryTemplatesProperty()
    {
        return Template::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function getLatestGenerationProperty(): ?Generation
    {
        if ($this->latestGenerationId) {
            return Generation::query()
                ->with(['store', 'template', 'generatedImages'])
                ->find($this->latestGenerationId);
        }

        return Generation::query()
            ->with(['store', 'template', 'generatedImages'])
            ->whereIn('status', ['completed', 'success', 'succeeded'])
            ->latest()
            ->first();
    }

    public function getAvailableModelsProperty(): array
    {
        try {
            return app(\App\Services\OpenAiImageService::class)->availableImageModels();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function mount(): void
    {
        $settings = \App\Models\OpenAiSetting::query()->first();
        $imageService = app(\App\Services\OpenAiImageService::class);

        // DEFAULT_IMAGE_MODEL dari OpenAiImageService adalah source of truth.
        // Settings lama tetap dipakai untuk aspect ratio dan quality saja.
        $this->model = $imageService->defaultImageModel();
        $this->aspectRatio = $settings?->default_aspect_ratio ?: '1:1';
        $this->quality = $settings?->default_quality ?: 'high';

        $latest = Generation::query()
            ->whereIn('status', ['completed', 'success', 'succeeded'])
            ->latest()
            ->value('id');

        $this->latestGenerationId = $latest ? (int) $latest : null;

        // Pastikan default service memang tersedia di katalog Gateway.
        $availableIds = collect($this->availableModels)->pluck('id');
        if (! $availableIds->contains($this->model)) {
            $this->model = $availableIds->first() ?: $imageService->defaultImageModel();
        }
    }

    public function selectStore(int $storeId): void
    {
        $this->selectedStoreId = $storeId;
        $this->selectedTemplateId = null;
        $this->templateSearch = '';
    }

    public function toggleTemplate(int $templateId): void
    {
        $template = Template::query()
            ->where('id', $templateId)
            ->where('store_id', $this->selectedStoreId)
            ->where('is_active', true)
            ->firstOrFail();

        if ($this->selectedTemplateId === $templateId) {
            $this->selectedTemplateId = null;
            return;
        }

        $this->selectedTemplateId = $templateId;

        // Template adalah source of truth untuk default generator settings.
        $this->aspectRatio = $template->aspect_ratio ?: '1:1';
        $this->quality = $template->output_quality ?: 'high';
    }

    public function generate(): void
    {
        $startedAt = microtime(true);
        $activity = app(\App\Services\ActivityLogService::class);

        if (! $this->useInstalledReference) {
            $this->imageTwo = null;
        }

        if (! $this->useCustomTitle) {
            $this->customTitle = '';
        } else {
            $this->customTitle = trim($this->customTitle);
        }

        try {
            $this->validate([
                'selectedStoreId' => ['required', 'integer', 'exists:stores,id'],
                'selectedTemplateId' => ['required', 'integer', 'exists:templates,id'],
                'imageOne' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                'imageTwo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                'customTitle' => $this->useCustomTitle
                    ? ['required', 'string', 'max:120']
                    : ['nullable', 'string', 'max:120'],
                'model' => ['required', 'string', 'max:150'],
                'aspectRatio' => ['required', 'in:1:1,4:5,3:4,16:9,9:16'],
                'quality' => ['required', 'in:standard,high'],
                'imageCount' => ['required', 'integer', 'min:1', 'max:4'],
            ], [
                'selectedStoreId.required' => 'Pilih Store terlebih dahulu.',
                'selectedTemplateId.required' => 'Pilih Template terlebih dahulu.',
                'imageOne.required' => 'Gambar utama wajib diupload.',
                'customTitle.required' => 'Isi judul custom terlebih dahulu atau matikan opsi Judul Produk.',
                'imageTwo.image' => 'Foto referensi pemasangan harus berupa gambar yang valid.',
                'imageTwo.mimes' => 'Foto referensi harus JPG, PNG, atau WEBP.',
                'imageTwo.max' => 'Foto referensi maksimal 10MB.',
                'model.required' => 'Model Vercel AI Gateway belum tersedia. Pastikan koneksi Gateway dapat diakses.',
            ]);

            $template = Template::query()
                ->whereKey($this->selectedTemplateId)
                ->where('store_id', $this->selectedStoreId)
                ->where('is_active', true)
                ->firstOrFail();

            $this->aspectRatio = $template->aspect_ratio ?: $this->aspectRatio;
            $this->quality = $template->output_quality ?: $this->quality;

            $generation = app(\App\Services\OpenAiImageService::class)->queueGeneration(
                user: auth()->user(),
                store: $this->selectedStore,
                template: $template,
                imageOne: $this->imageOne,
                imageTwo: $this->useInstalledReference ? $this->imageTwo : null,
                customTitle: $this->useCustomTitle ? $this->customTitle : null,
                model: $this->model,
                aspectRatio: $this->aspectRatio,
                quality: $this->quality,
                imageCount: $this->imageCount,
            );

            \App\Jobs\GenerateOpenAiImageJob::dispatch($generation->id);

            $this->latestGenerationId = $generation->id;
            $this->isGenerating = false;

            $this->dispatch(
                'toast',
                type: 'success',
                title: 'Generation masuk antrean',
                message: 'Kamu bisa langsung membuat generate lain. Proses berjalan di Recent History.'
            );

            $this->dispatch('activity-log-refresh');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = $e->validator->errors()->first();

            $activity->error(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generate AI image gagal pada validasi.',
                description: $message,
                metadata: [
                    'source' => 'generator_component',
                    'reason' => 'validation',
                    'store_id' => $this->selectedStoreId,
                    'template_id' => $this->selectedTemplateId,
                ],
                durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            );

            $this->dispatch('activity-log-refresh');
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $activity->error(
                action: 'generate_openai_image',
                category: 'generator',
                title: 'Generation gagal dimasukkan ke antrean.',
                description: $e->getMessage(),
                metadata: [
                    'source' => 'generator_component',
                    'reason' => 'queue_dispatch',
                    'store_id' => $this->selectedStoreId,
                    'template_id' => $this->selectedTemplateId,
                ],
                durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            );

            $this->dispatch('activity-log-refresh');
            $this->dispatch('toast', type: 'error', title: 'Tidak bisa memulai generate', message: $e->getMessage());
        }
    }

    protected function ownedGeneration(int $generationId): Generation
    {
        return Generation::query()->whereKey($generationId)->where('user_id', auth()->id())->firstOrFail();
    }

    public function cancelGeneration(int $generationId): void
    {
        $generation = $this->ownedGeneration($generationId);
        if (! in_array($generation->status, ['queued', 'processing'], true)) return;
        $generation->update([
            'status' => 'cancelled', 'error_message' => null, 'completed_at' => now(),
            'metadata' => array_merge($generation->metadata ?? [], [
                'progress_stage' => 'Dibatalkan', 'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);
        $this->dispatch('toast', type: 'success', title: 'Generation dibatalkan', message: 'Proses tidak akan dilanjutkan.');
    }

    public function retryGeneration(int $generationId): void
    {
        $generation = $this->ownedGeneration($generationId);
        if (in_array($generation->status, ['queued', 'processing'], true)) {
            $generation->update([
                'status' => 'cancelled', 'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'progress_stage' => 'Dibatalkan untuk retry', 'cancelled_at' => now()->toIso8601String(),
                ]),
            ]);
        } elseif (! in_array($generation->status, ['failed', 'cancelled', 'completed'], true)) return;

        $metadata = array_merge($generation->metadata ?? [], [
            'progress' => 4, 'progress_stage' => 'Menunggu worker queue',
            'queued_at' => now()->toIso8601String(), 'retry_of' => $generation->id,
        ]);
        unset($metadata['started_at'], $metadata['completed_at'], $metadata['failed_at'], $metadata['cancelled_at']);

        $retry = $generation->replicate();
        $retry->status = 'queued';
        $retry->error_message = null;
        $retry->started_at = null;
        $retry->completed_at = null;
        $retry->metadata = $metadata;
        $retry->save();
        \App\Jobs\GenerateOpenAiImageJob::dispatch($retry->id);
        $this->latestGenerationId = $retry->id;
        $this->dispatch('toast', type: 'success', title: 'Retry dimulai', message: 'Generation baru masuk antrean.');
    }

    public function deleteGeneration(int $generationId): void
    {
        $generation = $this->ownedGeneration($generationId);
        $active = in_array($generation->status, ['queued', 'processing'], true);

        if ($active) {
            $generation->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'metadata' => array_merge($generation->metadata ?? [], [
                    'progress_stage' => 'Dihapus oleh pengguna',
                    'cancelled_at' => now()->toIso8601String(),
                    'hidden' => true,
                ]),
            ]);
        } else {
            foreach ($generation->generatedImages as $image) {
                if ($image->image_path) \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
            }
            foreach ([$generation->product_image_1_path, $generation->product_image_2_path] as $path) {
                if ($path) \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
            $generation->delete();
        }

        if ($this->latestGenerationId === $generationId) $this->latestGenerationId = null;
        $this->dispatch('toast', type: 'success', title: 'Generation dihapus', message: $active ? 'Generation dihentikan dan disembunyikan dari history.' : 'History dan file hasilnya sudah dihapus.');
    }

    public function setReferenceMode(bool $enabled): void
    {
        $this->useInstalledReference = $enabled;

        if (! $enabled) {
            $this->imageTwo = null;
        }
    }

    public function setCustomTitleMode(bool $enabled): void
    {
        $this->useCustomTitle = $enabled;

        if (! $enabled) {
            $this->customTitle = '';
        }
    }

    public function selectModel(string $model): void
    {
        if (! collect($this->availableModels)->pluck('id')->contains($model)) {
            return;
        }

        $this->model = $model;
    }

    public function clearImageOne(): void
    {
        $this->imageOne = null;
    }

    public function clearImageTwo(): void
    {
        $this->imageTwo = null;
    }
};
?>

<div
    class="rms-generator-page"
    x-data="rmsImageGenerator()"
    x-on:keydown.escape.window="
        previewOpen
            ? closePreview()
            : closeAllMenus()
    "
>
    <div class="rms-generator-head">
        <div>
            <span class="rms-generator-eyebrow"><i></i> AI CREATIVE TOOL</span>
            <h1>AI Image Generator<span>.</span></h1>
            <p>Buat visual produk menggunakan Store dan Template yang sudah disiapkan.</p>
        </div>

        <div class="rms-generator-head-note">
            <span class="rms-generator-note-icon">✦</span>
            <div>
                <strong>Template based generation</strong>
                <small>Pilih Store terlebih dahulu untuk melihat template yang tersedia.</small>
            </div>
        </div>
    </div>

    <div class="rms-generator-layout" :class="{ 'history-collapsed': !historyOpen }">

        <section class="rms-generator-workspace rms-generator-workspace-animated">

            <div class="rms-generator-stepbar rms-generator-stepbar-v2">
                <div class="rms-generator-step"
                    :class="{ 'is-active': activeStep === 1, 'is-complete': activeStep > 1 }">
                    <b><span x-show="activeStep <= 1">1</span><span x-show="activeStep > 1">✓</span></b>
                    <span><strong>Pilih Store & Template</strong><small>Pilih Store lalu preset visual.</small></span>
                </div>
                <i :class="{ 'is-filled': activeStep > 1 }"></i>
                <div class="rms-generator-step"
                    :class="{ 'is-active': activeStep === 2, 'is-complete': activeStep > 2 }">
                    <b><span x-show="activeStep <= 2">2</span><span x-show="activeStep > 2">✓</span></b>
                    <span><strong>Upload Gambar</strong><small>Masukkan foto produk.</small></span>
                </div>
                <i :class="{ 'is-filled': activeStep > 2 }"></i>
                <div class="rms-generator-step"
                    :class="{ 'is-active': activeStep === 3 }">
                    <b>3</b>
                    <span><strong>Pengaturan & Generate</strong><small>Atur output lalu mulai.</small></span>
                </div>
            </div>

            <div class="rms-generator-context-strip">
                <div class="rms-generator-context-intro">
                    <span class="rms-context-spark">✦</span>
                    <div>
                        <strong>Generation setup</strong>
                        <small>Semua aset di bawah akan dikunci untuk generation ini.</small>
                    </div>
                </div>

                <div class="rms-generator-context-items">
                    <div class="rms-context-item {{ $this->selectedStore ? 'is-ready' : '' }}">
                        <span class="rms-context-icon">
                            @if($this->selectedStore?->logo_path)
                                <img src="{{ IlluminateSupportFacadesStorage::disk('public')->url($this->selectedStore->logo_path) }}" alt="">
                            @else
                                <b>{{ $this->selectedStore ? strtoupper(substr($this->selectedStore->name, 0, 1)) : 'S' }}</b>
                            @endif
                        </span>
                        <div>
                            <small>STORE</small>
                            <strong>{{ $this->selectedStore?->name ?? 'Belum dipilih' }}</strong>
                        </div>
                    </div>

                    <span class="rms-context-arrow">→</span>

                    <div class="rms-context-item {{ $this->selectedTemplate ? 'is-ready' : '' }}">
                        <span class="rms-context-icon rms-context-template-icon">✦</span>
                        <div>
                            <small>TEMPLATE</small>
                            <strong>{{ $this->selectedTemplate?->name ?? 'Belum dipilih' }}</strong>
                        </div>
                    </div>

                    <span class="rms-context-arrow">→</span>

                    <div class="rms-context-item is-reference">
                        <span class="rms-context-icon rms-context-reference-icon">03</span>
                        <div>
                            <small>REFERENCE SET</small>
                            <strong x-text="useInstalledReference ? 'Product + Installed + Logo' : 'Product + Logo'"></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rms-generator-columns">

                <section id="generator-step-1" class="rms-generator-card rms-generator-selection">
                    <div class="rms-generator-card-head">
                        <div>
                            <span>STEP 01</span>
                            <h2>Pilih Store & Template</h2>
                            <p>Template akan menyesuaikan dengan Store yang dipilih.</p>
                        </div>
                    </div>

                    <label class="rms-generator-field-label">Store <em>*</em></label>

                    <div class="rms-generator-store-select" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" class="rms-generator-store-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                            @if($this->selectedStore)
                                <span class="rms-generator-store-logo">
                                    @if($this->selectedStore->logo_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($this->selectedStore->logo_path) }}" alt="">
                                    @else
                                        <b>{{ strtoupper(substr($this->selectedStore->name, 0, 1)) }}</b>
                                    @endif
                                </span>
                                <span class="rms-generator-store-copy">
                                    <strong>{{ $this->selectedStore->name }}</strong>
                                    <small>{{ $this->selectedStore->marketplace ?: 'Marketplace' }}</small>
                                </span>
                            @else
                                <span class="rms-generator-store-placeholder">Pilih Store</span>
                            @endif
                            <span class="rms-generator-chevron" :class="{ 'is-open': open }">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 9 5 5 5-5"/></svg>
                            </span>
                        </button>

                        <div class="rms-generator-store-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                            <div class="rms-generator-menu-head">
                                <div>
                                    <span>SELECT STORE</span>
                                    <small>Template owner</small>
                                </div>
                                <b>{{ $this->stores->count() }}</b>
                            </div>

                            @forelse($this->stores as $store)
                                <button type="button"
                                    class="{{ (int)$selectedStoreId === (int)$store->id ? 'selected' : '' }}"
                                    wire:click="selectStore({{ $store->id }})"
                                    x-on:click="open=false">
                                    <span class="rms-generator-option-logo">
                                        @if($store->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path) }}" alt="">
                                        @else
                                            <b>{{ strtoupper(substr($store->name, 0, 1)) }}</b>
                                        @endif
                                    </span>
                                    <span>
                                        <strong>{{ $store->name }}</strong>
                                        <small>{{ $store->marketplace ?: 'Marketplace' }} · {{ $store->templates->count() }} template</small>
                                    </span>
                                    <i>{{ (int)$selectedStoreId === (int)$store->id ? '✓' : '' }}</i>
                                </button>
                            @empty
                                <div class="rms-generator-empty-mini">Belum ada Store aktif.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rms-generator-template-heading">
                        <label class="rms-generator-field-label">Template <em>*</em></label>
                        @if($this->selectedStore)
                            <span>{{ $this->templates->count() }} tersedia</span>
                        @endif
                    </div>

                    <div class="rms-generator-search">
                        <span>⌕</span>
                        <input type="text" wire:model.live.debounce.250ms="templateSearch"
                            placeholder="{{ $this->selectedStore ? 'Cari template...' : 'Pilih Store terlebih dahulu' }}"
                            @disabled(!$this->selectedStore)>
                    </div>

                    <div class="rms-generator-chips rms-filter-drag">
                        @foreach(['all' => 'All', 'promo' => 'Promo', 'product' => 'Product', 'lifestyle' => 'Lifestyle', 'detail' => 'Detail'] as $key => $label)
                            <button type="button"
                                class="rms-filter-chip {{ $templateCategory === $key ? 'active' : '' }}"
                                wire:click="$set('templateCategory','{{ $key }}')"
                                @disabled(!$this->selectedStore)>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="rms-generator-template-grid">
                        @forelse($this->templates as $template)
                            <button type="button"
                                class="rms-generator-template-card {{ (int)$selectedTemplateId === (int)$template->id ? 'selected' : '' }}"
                                wire:click="toggleTemplate({{ $template->id }})" x-on:click="$nextTick(() => activeStep = (selectedTemplateId === {{ $template->id }}) ? 2 : 1)">
                                <div class="rms-generator-template-image">
                                    @if($template->example_image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($template->example_image_path) }}" alt="">
                                    @else
                                        <div class="rms-generator-template-placeholder">AI</div>
                                    @endif
                                    @if((int)$selectedTemplateId === (int)$template->id)
                                        <i>✓</i>
                                    @endif
                                </div>
                                <div class="rms-generator-template-copy">
                                    <strong>{{ $template->name }}</strong>
                                    <small>{{ $template->aspect_ratio ?: '1:1' }}</small>
                                </div>
                            </button>
                        @empty
                            <div class="rms-generator-no-template">
                                <span>✦</span>
                                <strong>{{ $this->selectedStore ? 'Belum ada template aktif' : 'Pilih Store terlebih dahulu' }}</strong>
                                <small>{{ $this->selectedStore ? 'Tambahkan template dari Template Library.' : 'Template akan muncul otomatis setelah Store dipilih.' }}</small>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section id="generator-step-2" class="rms-generator-card rms-generator-upload">
                    <div class="rms-generator-card-head">
                        <div>
                            <span>STEP 02</span>
                            <h2>Upload Gambar Produk</h2>
                            <p>Foto produk wajib. Foto terpasang bersifat opsional sebagai referensi penggunaan.</p>
                        </div>
                        <div class="rms-upload-requirement" :class="{ 'optional-mode': !useInstalledReference }">
                            <i></i> <span x-text="useInstalledReference ? '3 reference images' : '2 reference images'"></span>
                        </div>
                    </div>

                    <div class="rms-generator-upload-grid rms-generator-upload-grid-v2">
                        <div class="rms-generator-upload-box rms-upload-primary" :class="{ 'has-image': imageOne }">
                            <div class="rms-upload-label-row">
                                <span class="rms-upload-number">01</span>
                                <div><strong>Foto Produk Utama <em>*</em></strong><small>Packaging / part motor sebagai sumber identitas produk.</small></div>
                                <span class="rms-upload-badge required">REQUIRED</span>
                            </div>
                            @if($imageOne)
                                <div class="rms-upload-preview-wrap">
                                    <img src="{{ $imageOne->temporaryUrl() }}" alt="">
                                    <div class="rms-upload-preview-shade"></div>
                                    <button type="button" class="rms-generator-remove" wire:click="clearImageOne">×</button>
                                    <span class="rms-upload-complete">✓ Uploaded</span>
                                </div>
                            @else
                                <div class="rms-generator-upload-placeholder">
                                    <div class="rms-generator-upload-icon">
                                        <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 20h14"/></svg>
                                    </div>
                                    <strong>Upload foto produk</strong>
                                    <small>Gunakan foto yang paling jelas dan tajam.</small>
                                    <label class="rms-generator-upload-button">
                                        <span>↑</span> Pilih Gambar
                                        <input type="file" wire:model="imageOne" accept="image/png,image/jpeg,image/webp">
                                    </label>
                                    <em>JPG, PNG, WEBP · Maks. 10MB</em>
                                </div>
                            @endif
                        </div>

                        <div class="rms-generator-upload-box rms-upload-reference" :class="{ 'has-image': imageTwo, 'is-disabled': !useInstalledReference }">
                            <div class="rms-upload-label-row">
                                <span class="rms-upload-number">02</span>
                                <div><strong>Foto Produk Terpasang</strong><small>Referensi bentuk, posisi, dan penggunaan part pada motor.</small></div>
                                <span class="rms-upload-badge optional">OPTIONAL</span>
                            </div>
                            @if($imageTwo)
                                <div class="rms-upload-preview-wrap">
                                    <img src="{{ $imageTwo->temporaryUrl() }}" alt="">
                                    <div class="rms-upload-preview-shade"></div>
                                    <button type="button" class="rms-generator-remove" wire:click="clearImageTwo">×</button>
                                    <span class="rms-upload-complete">✓ Reference ready</span>
                                </div>
                            @elseif($useInstalledReference)
                                <div class="rms-generator-upload-placeholder">
                                    <div class="rms-generator-upload-icon reference">
                                        <svg viewBox="0 0 24 24"><path d="M4 17.5V6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5Z"/><circle cx="9" cy="9" r="1.5"/><path d="m5.5 17 4.2-4.2 3 3 2.3-2.3 3.5 3.5"/></svg>
                                    </div>
                                    <strong>Tambahkan foto terpasang</strong>
                                    <small>Direkomendasikan jika tersedia untuk referensi penggunaan.</small>
                                    <label class="rms-generator-upload-button secondary">
                                        <span>↑</span> Pilih Foto
                                        <input type="file" wire:model="imageTwo" accept="image/png,image/jpeg,image/webp">
                                    </label>
                                    <em>JPG, PNG, WEBP · Maks. 10MB</em>
                                </div>
                            @else
                                <div class="rms-upload-disabled-state">
                                    <span>✦</span>
                                    <strong>Mode tanpa foto terpasang</strong>
                                    <small>AI akan membuat konteks visual berdasarkan produk dan template.</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rms-reference-mode">
                        <div>
                            <span class="rms-reference-icon">✦</span>
                            <div>
                                <strong>Gunakan foto contoh produk?</strong>
                                <small>Pilih sesuai aset yang dimiliki client. Logo Store akan ditambahkan otomatis.</small>
                            </div>
                        </div>
                        <div class="rms-reference-toggle">
                            <button type="button" class="{{ $useInstalledReference ? 'active' : '' }}" wire:click="setReferenceMode(true)">Gunakan Foto Terpasang</button>
                            <button type="button" class="{{ ! $useInstalledReference ? 'active' : '' }}" wire:click="setReferenceMode(false)">Tanpa Foto Terpasang</button>
                        </div>
                    </div>

                    <div
                        class="rms-custom-title-card {{ $useCustomTitle ? 'is-active' : '' }}"
                        x-data="{ open: @entangle('useCustomTitle').live }"
                        :class="{ 'is-active': open }"
                    >
                        <div class="rms-custom-title-head">
                            <div class="rms-custom-title-identity">
                                <span class="rms-custom-title-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v13.5A2.5 2.5 0 0 1 16.5 19H7.75A2.75 2.75 0 0 1 5 16.25V5.5Z"/>
                                        <path d="M8 7h7M8 10.5h7M8 14h4"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="rms-custom-title-label">
                                        <strong>Judul Produk</strong>
                                        <span>OPTIONAL</span>
                                    </div>
                                    <small>Gunakan judul sendiri atau biarkan AI menentukan otomatis dari produk.</small>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="rms-custom-title-switch"
                                :class="{ 'is-on': open }"
                                x-on:click="open = !open; $wire.setCustomTitleMode(open)"
                                :aria-pressed="open.toString()"
                                aria-label="Aktifkan judul custom"
                            >
                                <span class="rms-custom-title-switch-track">
                                    <span class="rms-custom-title-switch-knob"></span>
                                </span>
                                <span class="rms-custom-title-switch-state" x-text="open ? 'ON' : 'OFF'">OFF</span>
                            </button>
                        </div>

                        <div
                            class="rms-custom-title-body"
                            x-show="open"
                            x-transition:enter="transition ease-out duration-350"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-220"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            x-cloak
                        >
                            <div class="rms-custom-title-input-wrap">
                                <div class="rms-custom-title-input-head">
                                    <label for="custom-product-title">Judul custom</label>
                                    <span>{{ mb_strlen($customTitle) }}/120</span>
                                </div>

                                <div class="rms-custom-title-input-shell">
                                    <span class="rms-custom-title-input-prefix">Aa</span>
                                    <input
                                        id="custom-product-title"
                                        type="text"
                                        wire:model.live.debounce.250ms="customTitle"
                                        maxlength="120"
                                        autocomplete="off"
                                        placeholder="Contoh: Baut Body Motor Universal"
                                    >
                                    <span class="rms-custom-title-input-status">Custom</span>
                                </div>

                                <div class="rms-custom-title-hint">
                                    <span>✓</span>
                                    <span>Judul ini menjadi headline utama. Layout, style, logo, dan komposisi tetap mengikuti Template.</span>
                                </div>

                                @error('customTitle')
                                    <div class="rms-custom-title-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rms-reference-flow">
                        <span class="rms-flow-title">REFERENCE YANG AKAN DIGUNAKAN AI</span>
                        <div class="rms-flow-items">
                            <div class="rms-flow-item ready"><b>01</b><span>Product</span><small>Uploaded</small></div>
                            <i>+</i>
                            <div class="rms-flow-item {{ ! $useInstalledReference ? 'muted' : ($imageTwo ? 'ready' : '') }}"><b>02</b><span>Installed</span><small>{{ ! $useInstalledReference ? 'Skipped' : ($imageTwo ? 'Uploaded' : 'Optional') }}</small></div>
                            <i>+</i>
                            <div class="rms-flow-item ready"><b>03</b><span>Store Logo</span><small>Automatic</small></div>
                        </div>
                    </div>

                    {{-- Preview hasil dipusatkan di Recent Generations. Modal preview tetap tersedia saat card hasil diklik. --}}

                        <div class="rms-generator-settings">
                        <div class="rms-generator-subhead">
                            <div>
                                <strong>Pengaturan Tambahan</strong>
                                <span>Sesuaikan output sebelum generate</span>
                            </div>
                            <button type="button" class="rms-settings-collapse" x-on:click="settingsOpen = !settingsOpen" :class="{ 'is-open': settingsOpen }">
                                <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </div>

                        <div class="rms-generator-settings-grid" x-show="settingsOpen" x-transition.opacity>
                            <div class="rms-custom-select rms-custom-select-enhanced full" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Model Vercel AI Gateway</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b>{{ $model ?: 'Model belum tersedia' }}</b>
                                        <small>{{ count($this->availableModels) }} model image tersedia dari Vercel AI Gateway</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @forelse($this->availableModels as $openAiModel)
                                        <button type="button" class="{{ $model === $openAiModel['id'] ? 'selected' : '' }}" wire:click="selectModel('{{ addslashes($openAiModel['id']) }}')" x-on:click="open=false">
                                            <span class="rms-option-model">AI</span>
                                            <span>
                                                <strong>{{ $openAiModel['id'] }}</strong>
                                                <small>{{ $openAiModel['owned_by'] ?? 'Vercel AI Gateway' }}</small>
                                            </span>
                                            <i>{{ $model === $openAiModel['id'] ? '✓' : '' }}</i>
                                        </button>
                                    @empty
                                        <div class="rms-generator-empty-mini">Model image tidak ditemukan dari Vercel AI Gateway. Cek koneksi Gateway.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Aspect Ratio</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b x-text="aspectRatioLabel('{{ $aspectRatio }}')"></b>
                                        <small>Canvas output</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @foreach([
                                        '1:1' => ['1:1 (Square)', 'Perfect for marketplace & catalog'],
                                        '4:5' => ['4:5 (Portrait)', 'Social & product feed'],
                                        '3:4' => ['3:4 (Portrait)', 'Portrait product visual'],
                                        '16:9' => ['16:9 (Landscape)', 'Banner & marketplace hero'],
                                        '9:16' => ['9:16 (Story)', 'Story & vertical content'],
                                    ] as $value => $meta)
                                        <button type="button" class="{{ $aspectRatio === $value ? 'selected' : '' }}" x-on:click="setLivewireValue('aspectRatio','{{ $value }}'); open=false">
                                            <span class="rms-option-ratio">{{ $value }}</span>
                                            <span><strong>{{ $meta[0] }}</strong><small>{{ $meta[1] }}</small></span>
                                            <i>{{ $aspectRatio === $value ? '✓' : '' }}</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Quality</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b x-text="qualityLabel('{{ $quality }}')"></b>
                                        <small>Output quality</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <button type="button" class="{{ $quality === 'standard' ? 'selected' : '' }}" x-on:click="setLivewireValue('quality','standard'); open=false">
                                        <span class="rms-option-quality standard">S</span>
                                        <span><strong>Standard</strong><small>Balanced speed & quality</small></span>
                                        <i>{{ $quality === 'standard' ? '✓' : '' }}</i>
                                    </button>
                                    <button type="button" class="{{ $quality === 'high' ? 'selected' : '' }}" x-on:click="setLivewireValue('quality','high'); open=false">
                                        <span class="rms-option-quality high">H</span>
                                        <span><strong>High Quality</strong><small>Maximum visual detail</small></span>
                                        <i>{{ $quality === 'high' ? '✓' : '' }}</i>
                                    </button>
                                </div>
                            </div>

                            <div class="rms-custom-select full" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Jumlah Gambar</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b>{{ $imageCount }} {{ $imageCount === 1 ? 'Gambar' : 'Gambar' }}</b>
                                        <small>Jumlah hasil yang akan dibuat</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @foreach([1,2,3,4] as $count)
                                        <button type="button" class="{{ (int)$imageCount === $count ? 'selected' : '' }}" x-on:click="setLivewireValue('imageCount', {{ $count }}); open=false">
                                            <span class="rms-option-count">{{ $count }}</span>
                                            <span><strong>{{ $count }} {{ $count === 1 ? 'Gambar' : 'Gambar' }}</strong><small>{{ $count === 1 ? 'Satu hasil utama' : $count . ' variasi hasil' }}</small></span>
                                            <i>{{ (int)$imageCount === $count ? '✓' : '' }}</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="rms-generator-generate"
                        x-on:click="generateVisual()"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                        :disabled="generating"
                    >
                        <span class="rms-generate-icon" x-text="generating ? '◌' : '✦'"></span>
                        <span>
                            <strong x-text="generating ? 'Generating...' : 'Generate Gambar'"></strong>
                            <small x-text="generating ? 'OpenAI sedang membuat visual...' : 'Buat visual dengan konfigurasi saat ini'"></small>
                        </span>
                        <b>
                            <svg viewBox="0 0 24 24"><path d="M5 12h13m-5-5 5 5-5 5"/></svg>
                        </b>
                    </button>

                    <div class="rms-generator-eta-card">
                        <span class="rms-generator-eta-icon">◷</span>
                        <div>
                            <strong>Estimasi waktu</strong>
                            <span>± 20–60 detik per gambar</span>
                            <small>Durasi dapat berubah mengikuti antrean dan kompleksitas gambar.</small>
                        </div>
                    </div>
                </section>
            </div>
        </section>


<style>
/* ============================================================
   RMS GENERATOR — MOTION / INTERACTION V3
   Pure CSS animation layer. Tidak mengubah fungsi Livewire/Alpine.
   ============================================================ */

.rms-generator-workspace-animated{
    --rms-red:#ef233c;
    --rms-red-dark:#c9142a;
    --rms-ink:#17181b;
    --rms-muted:#8b9099;
    --rms-line:#e8e9ed;
    --rms-soft:#f7f8fa;
    --rms-shadow:0 18px 55px rgba(20,22,28,.08);
    position:relative;
    isolation:isolate;
}

/* ---------- Ambient background ---------- */
.rms-generator-workspace-animated::before,
.rms-generator-workspace-animated::after{
    content:"";
    position:absolute;
    z-index:-1;
    pointer-events:none;
    border-radius:999px;
    filter:blur(2px);
    opacity:.45;
}
.rms-generator-workspace-animated::before{
    width:280px;height:280px;
    top:30px;right:-90px;
    background:radial-gradient(circle,rgba(239,35,60,.10),transparent 68%);
    animation:rmsAmbientFloat 8s ease-in-out infinite;
}
.rms-generator-workspace-animated::after{
    width:240px;height:240px;
    left:-90px;bottom:180px;
    background:radial-gradient(circle,rgba(120,130,150,.08),transparent 68%);
    animation:rmsAmbientFloat 10s ease-in-out infinite reverse;
}
@keyframes rmsAmbientFloat{
    0%,100%{transform:translate3d(0,0,0) scale(1)}
    50%{transform:translate3d(12px,-14px,0) scale(1.08)}
}

/* ---------- Staggered page entrance ---------- */
.rms-generator-workspace-animated .rms-generator-stepbar{
    animation:rmsFadeUp .55s cubic-bezier(.2,.75,.25,1) both;
}
.rms-generator-workspace-animated .rms-generator-columns{
    animation:rmsFadeUp .65s .08s cubic-bezier(.2,.75,.25,1) both;
}
.rms-generator-workspace-animated .rms-generator-columns > section:first-child{
    animation:rmsCardIn .65s .12s cubic-bezier(.2,.8,.25,1) both;
}
.rms-generator-workspace-animated .rms-generator-columns > section:nth-child(2){
    animation:rmsCardIn .65s .2s cubic-bezier(.2,.8,.25,1) both;
}
@keyframes rmsFadeUp{
    from{opacity:0;transform:translateY(18px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes rmsCardIn{
    from{opacity:0;transform:translateY(24px) scale(.985)}
    to{opacity:1;transform:translateY(0) scale(1)}
}

/* ---------- Step bar ---------- */
.rms-generator-workspace-animated .rms-generator-stepbar{
    position:relative;
}
.rms-generator-workspace-animated .rms-generator-step{
    position:relative;
    transition:transform .3s cubic-bezier(.2,.8,.2,1),filter .3s ease;
}
.rms-generator-workspace-animated .rms-generator-step:hover{
    transform:translateY(-2px);
}
.rms-generator-workspace-animated .rms-generator-step b{
    position:relative;
    transition:transform .35s cubic-bezier(.2,.8,.2,1),box-shadow .35s ease;
}
.rms-generator-workspace-animated .rms-generator-step.is-active b{
    animation:rmsStepPulse 2.2s ease-in-out infinite;
}
.rms-generator-workspace-animated .rms-generator-step.is-complete b{
    animation:rmsCompletePop .45s cubic-bezier(.2,1.5,.4,1) both;
}
@keyframes rmsStepPulse{
    0%,100%{box-shadow:0 0 0 0 rgba(239,35,60,0)}
    50%{box-shadow:0 0 0 7px rgba(239,35,60,.10)}
}
@keyframes rmsCompletePop{
    0%{transform:scale(.72) rotate(-12deg)}
    70%{transform:scale(1.14) rotate(3deg)}
    100%{transform:scale(1) rotate(0)}
}
.rms-generator-workspace-animated .rms-generator-stepbar > i{
    position:relative;
    overflow:hidden;
}
.rms-generator-workspace-animated .rms-generator-stepbar > i.is-filled::after{
    content:"";
    position:absolute;
    inset:0;
    transform:translateX(-100%);
    background:linear-gradient(90deg,transparent,rgba(239,35,60,.45),transparent);
    animation:rmsConnectorFill .7s ease both;
}
@keyframes rmsConnectorFill{
    to{transform:translateX(100%)}
}

/* ---------- Cards ---------- */
.rms-generator-workspace-animated .rms-generator-card{
    position:relative;
    transition:
        transform .35s cubic-bezier(.2,.8,.2,1),
        box-shadow .35s ease,
        border-color .35s ease;
}
.rms-generator-workspace-animated .rms-generator-card::before{
    content:"";
    position:absolute;
    inset:0;
    border-radius:inherit;
    pointer-events:none;
    opacity:0;
    background:linear-gradient(115deg,transparent 20%,rgba(255,255,255,.62) 50%,transparent 80%);
    transform:translateX(-120%);
    transition:opacity .2s ease;
}
.rms-generator-workspace-animated .rms-generator-card:hover{
    transform:translateY(-3px);
    box-shadow:var(--rms-shadow);
}
.rms-generator-workspace-animated .rms-generator-card:hover::before{
    opacity:1;
    animation:rmsCardShine .9s ease both;
}
@keyframes rmsCardShine{
    to{transform:translateX(120%)}
}

/* ---------- Card headings ---------- */
.rms-generator-workspace-animated .rms-generator-card-head > div > span,
.rms-generator-workspace-animated .rms-generator-subhead strong{
    transition:color .25s ease;
}
.rms-generator-workspace-animated .rms-generator-card:hover .rms-generator-card-head > div > span{
    color:var(--rms-red);
}
.rms-generator-workspace-animated .rms-generator-card-head h2{
    transition:letter-spacing .3s ease,transform .3s ease;
}
.rms-generator-workspace-animated .rms-generator-card:hover .rms-generator-card-head h2{
    letter-spacing:-.02em;
    transform:translateX(2px);
}

/* ---------- Store selector ---------- */
.rms-generator-workspace-animated .rms-generator-store-trigger{
    position:relative;
    overflow:hidden;
    transition:
        transform .25s cubic-bezier(.2,.8,.2,1),
        border-color .25s ease,
        box-shadow .25s ease,
        background .25s ease;
}
.rms-generator-workspace-animated .rms-generator-store-trigger::after{
    content:"";
    position:absolute;
    top:0;bottom:0;
    width:55%;
    left:-80%;
    pointer-events:none;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);
    transform:skewX(-18deg);
}
.rms-generator-workspace-animated .rms-generator-store-trigger:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(20,22,28,.08);
}
.rms-generator-workspace-animated .rms-generator-store-trigger:hover::after{
    animation:rmsSweep .8s ease;
}
@keyframes rmsSweep{
    to{left:130%}
}
.rms-generator-workspace-animated .rms-generator-store-logo,
.rms-generator-workspace-animated .rms-generator-option-logo{
    transition:transform .35s cubic-bezier(.2,.9,.25,1),box-shadow .35s ease;
}
.rms-generator-workspace-animated .rms-generator-store-trigger:hover .rms-generator-store-logo{
    transform:scale(1.06) rotate(-2deg);
    box-shadow:0 5px 16px rgba(20,20,25,.12);
}
.rms-generator-workspace-animated .rms-generator-chevron{
    transition:transform .3s ease;
}
.rms-generator-workspace-animated .rms-generator-chevron.is-open{
    transform:rotate(180deg);
}
.rms-generator-workspace-animated .rms-generator-store-menu{
    transform-origin:top center;
    animation:rmsMenuIn .22s cubic-bezier(.2,.8,.2,1) both;
}
@keyframes rmsMenuIn{
    from{opacity:0;transform:translateY(-7px) scale(.98)}
    to{opacity:1;transform:translateY(0) scale(1)}
}
.rms-generator-workspace-animated .rms-generator-store-menu button{
    transition:background .2s ease,transform .2s ease,padding-left .2s ease;
}
.rms-generator-workspace-animated .rms-generator-store-menu button:hover{
    transform:translateX(3px);
}

/* ---------- Search ---------- */
.rms-generator-workspace-animated .rms-generator-search{
    transition:border-color .25s ease,box-shadow .25s ease,transform .25s ease;
}
.rms-generator-workspace-animated .rms-generator-search:focus-within{
    transform:translateY(-1px);
    border-color:rgba(239,35,60,.45);
    box-shadow:0 0 0 4px rgba(239,35,60,.07),0 8px 22px rgba(20,22,28,.05);
}
.rms-generator-workspace-animated .rms-generator-search > span{
    transition:transform .3s ease,color .3s ease;
}
.rms-generator-workspace-animated .rms-generator-search:focus-within > span{
    color:var(--rms-red);
    transform:scale(1.15) rotate(-8deg);
}

/* ---------- Filter chips ---------- */
.rms-generator-workspace-animated .rms-filter-chip{
    position:relative;
    overflow:hidden;
    transition:transform .25s cubic-bezier(.2,.8,.2,1),box-shadow .25s ease,color .25s ease,background .25s ease;
}
.rms-generator-workspace-animated .rms-filter-chip::after{
    content:"";
    position:absolute;
    width:20px;height:20px;
    border-radius:50%;
    background:rgba(255,255,255,.4);
    transform:scale(0);
    left:50%;top:50%;
    translate:-50% -50%;
    pointer-events:none;
}
.rms-generator-workspace-animated .rms-filter-chip:active::after{
    animation:rmsChipRipple .4s ease;
}
.rms-generator-workspace-animated .rms-filter-chip:hover{
    transform:translateY(-2px);
}
@keyframes rmsChipRipple{
    to{transform:scale(8);opacity:0}
}

/* ---------- Template cards ---------- */
.rms-generator-workspace-animated .rms-generator-template-card{
    position:relative;
    overflow:hidden;
    transition:
        transform .35s cubic-bezier(.2,.8,.2,1),
        box-shadow .35s ease,
        border-color .3s ease;
}
.rms-generator-workspace-animated .rms-generator-template-card::before{
    content:"";
    position:absolute;
    z-index:3;
    top:-20%;
    left:-70%;
    width:42%;
    height:140%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.45),transparent);
    transform:skewX(-18deg);
    pointer-events:none;
}
.rms-generator-workspace-animated .rms-generator-template-card:hover{
    transform:translateY(-7px) scale(1.012);
    box-shadow:0 16px 30px rgba(20,22,28,.12);
}
.rms-generator-workspace-animated .rms-generator-template-card:hover::before{
    animation:rmsTemplateSweep .8s ease both;
}
@keyframes rmsTemplateSweep{
    to{left:135%}
}
.rms-generator-workspace-animated .rms-generator-template-image{
    overflow:hidden;
}
.rms-generator-workspace-animated .rms-generator-template-image img{
    transition:transform .6s cubic-bezier(.2,.8,.2,1),filter .4s ease;
}
.rms-generator-workspace-animated .rms-generator-template-card:hover .rms-generator-template-image img{
    transform:scale(1.065);
    filter:saturate(1.08);
}
.rms-generator-workspace-animated .rms-generator-template-card > .rms-generator-template-image > i{
    animation:rmsSelectedBadge .45s cubic-bezier(.2,1.5,.4,1) both;
}
@keyframes rmsSelectedBadge{
    0%{opacity:0;transform:scale(.3) rotate(-20deg)}
    70%{transform:scale(1.18) rotate(4deg)}
    100%{opacity:1;transform:scale(1) rotate(0)}
}

/* ---------- Upload boxes ---------- */
.rms-generator-workspace-animated .rms-generator-upload-box{
    position:relative;
    overflow:hidden;
    transition:
        transform .35s cubic-bezier(.2,.8,.2,1),
        border-color .3s ease,
        box-shadow .35s ease,
        background .3s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-box::after{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    opacity:0;
    border-radius:inherit;
    box-shadow:inset 0 0 0 1px rgba(239,35,60,.35);
    transition:opacity .25s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-box:hover{
    transform:translateY(-4px);
    border-color:#d5d8de;
    box-shadow:0 14px 30px rgba(20,22,28,.08);
}
.rms-generator-workspace-animated .rms-generator-upload-box:hover::after{
    opacity:1;
}
.rms-generator-workspace-animated .rms-generator-upload-icon{
    transition:transform .35s cubic-bezier(.2,.9,.25,1),box-shadow .35s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-box:hover .rms-generator-upload-icon{
    transform:translateY(-4px) scale(1.05) rotate(-2deg);
}
.rms-generator-workspace-animated .rms-generator-upload-button{
    position:relative;
    overflow:hidden;
    transition:transform .25s ease,box-shadow .25s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-button:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(20,22,28,.10);
}
.rms-generator-workspace-animated .rms-generator-upload-button span{
    display:inline-block;
    transition:transform .3s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-button:hover span{
    transform:translateY(-2px);
}
.rms-generator-workspace-animated .rms-upload-preview-wrap{
    animation:rmsPreviewReveal .5s cubic-bezier(.2,.8,.2,1) both;
}
@keyframes rmsPreviewReveal{
    from{opacity:0;transform:scale(.94);filter:blur(4px)}
    to{opacity:1;transform:scale(1);filter:blur(0)}
}
.rms-generator-workspace-animated .rms-upload-complete{
    animation:rmsStatusIn .45s .15s cubic-bezier(.2,1.4,.4,1) both;
}
@keyframes rmsStatusIn{
    from{opacity:0;transform:translateY(8px)}
    to{opacity:1;transform:translateY(0)}
}
.rms-generator-workspace-animated .rms-generator-remove{
    transition:transform .25s ease,background .25s ease,box-shadow .25s ease;
}
.rms-generator-workspace-animated .rms-generator-remove:hover{
    transform:scale(1.08) rotate(6deg);
    box-shadow:0 6px 16px rgba(0,0,0,.16);
}

/* ---------- Reference mode ---------- */
.rms-generator-workspace-animated .rms-reference-mode{
    transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease;
}
.rms-generator-workspace-animated .rms-reference-mode:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(20,22,28,.06);
}
.rms-generator-workspace-animated .rms-reference-icon{
    animation:rmsSparkle 2.4s ease-in-out infinite;
}
@keyframes rmsSparkle{
    0%,100%{transform:scale(1) rotate(0);opacity:.75}
    50%{transform:scale(1.13) rotate(8deg);opacity:1}
}
.rms-generator-workspace-animated .rms-reference-toggle button{
    position:relative;
    overflow:hidden;
    transition:transform .25s ease,background .25s ease,color .25s ease,box-shadow .25s ease;
}
.rms-generator-workspace-animated .rms-reference-toggle button:hover{
    transform:translateY(-1px);
}
.rms-generator-workspace-animated .rms-reference-toggle button.active{
    box-shadow:0 7px 18px rgba(239,35,60,.12);
}
.rms-generator-workspace-animated .rms-flow-item{
    transition:transform .3s cubic-bezier(.2,.8,.2,1),box-shadow .3s ease,opacity .3s ease;
}
.rms-generator-workspace-animated .rms-flow-item.ready{
    animation:rmsFlowReady .55s cubic-bezier(.2,1.2,.3,1) both;
}
.rms-generator-workspace-animated .rms-flow-item:hover{
    transform:translateY(-4px) scale(1.02);
    box-shadow:0 9px 20px rgba(20,22,28,.07);
}
.rms-generator-workspace-animated .rms-flow-items > i{
    animation:rmsPlusFloat 1.8s ease-in-out infinite;
}
@keyframes rmsFlowReady{
    from{opacity:0;transform:translateY(9px) scale(.94)}
    to{opacity:1;transform:translateY(0) scale(1)}
}
@keyframes rmsPlusFloat{
    0%,100%{transform:translateX(0);opacity:.55}
    50%{transform:translateX(2px);opacity:1}
}

/* ---------- Settings ---------- */
.rms-generator-workspace-animated .rms-settings-collapse{
    transition:transform .3s ease,box-shadow .3s ease,background .25s ease;
}
.rms-generator-workspace-animated .rms-settings-collapse:hover{
    transform:translateY(-2px);
    box-shadow:0 7px 18px rgba(20,22,28,.08);
}
.rms-generator-workspace-animated .rms-settings-collapse svg{
    transition:transform .35s cubic-bezier(.2,.8,.2,1);
}
.rms-generator-workspace-animated .rms-settings-collapse.is-open svg{
    transform:rotate(180deg);
}
.rms-generator-workspace-animated .rms-generator-settings-grid{
    transform-origin:top;
}
.rms-generator-workspace-animated .rms-custom-select-trigger{
    transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease;
}
.rms-generator-workspace-animated .rms-custom-select-trigger:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(20,22,28,.06);
}
.rms-generator-workspace-animated .rms-custom-select-trigger.is-open{
    border-color:rgba(239,35,60,.38);
    box-shadow:0 0 0 4px rgba(239,35,60,.06);
}
.rms-generator-workspace-animated .rms-custom-select-trigger > i{
    transition:transform .3s ease;
}
.rms-generator-workspace-animated .rms-custom-select-trigger.is-open > i{
    transform:rotate(180deg);
}
.rms-generator-workspace-animated .rms-custom-select-menu{
    transform-origin:top center;
    animation:rmsMenuIn .22s cubic-bezier(.2,.8,.2,1) both;
}
.rms-generator-workspace-animated .rms-custom-select-menu button{
    transition:transform .2s ease,background .2s ease,padding-left .2s ease;
}
.rms-generator-workspace-animated .rms-custom-select-menu button:hover{
    transform:translateX(4px);
}

/* ---------- Generate CTA ---------- */
.rms-generator-workspace-animated .rms-generator-generate{
    position:relative;
    overflow:hidden;
    isolation:isolate;
    transition:
        transform .3s cubic-bezier(.2,.8,.2,1),
        box-shadow .3s ease,
        filter .3s ease;
}
.rms-generator-workspace-animated .rms-generator-generate::before{
    content:"";
    position:absolute;
    z-index:-1;
    inset:-2px;
    background:linear-gradient(110deg,transparent 18%,rgba(255,255,255,.30) 42%,rgba(255,255,255,.62) 50%,transparent 68%);
    transform:translateX(-120%);
}
.rms-generator-workspace-animated .rms-generator-generate:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 35px rgba(239,35,60,.24);
    filter:saturate(1.08);
}
.rms-generator-workspace-animated .rms-generator-generate:hover::before{
    animation:rmsGenerateShine 1s ease both;
}
@keyframes rmsGenerateShine{
    to{transform:translateX(120%)}
}
.rms-generator-workspace-animated .rms-generator-generate:active{
    transform:translateY(-1px) scale(.985);
}
.rms-generator-workspace-animated .rms-generate-icon{
    display:inline-grid;
    place-items:center;
    transition:transform .35s ease;
}
.rms-generator-workspace-animated .rms-generator-generate:hover .rms-generate-icon{
    transform:rotate(18deg) scale(1.15);
}
.rms-generator-workspace-animated .rms-generator-generate:not([disabled]) .rms-generate-icon{
    animation:rmsGenerateIcon 2.5s ease-in-out infinite;
}
@keyframes rmsGenerateIcon{
    0%,70%,100%{transform:rotate(0) scale(1)}
    78%{transform:rotate(12deg) scale(1.12)}
    86%{transform:rotate(-8deg) scale(1.08)}
}
.rms-generator-workspace-animated .rms-generator-generate > b{
    transition:transform .3s ease;
}
.rms-generator-workspace-animated .rms-generator-generate:hover > b{
    transform:translateX(5px);
}

/* Loading state: don't keep the idle sparkle animation while generating */
.rms-generator-workspace-animated .rms-generator-generate[disabled]{
    cursor:wait;
    filter:saturate(.75);
}
.rms-generator-workspace-animated .rms-generator-generate[disabled] .rms-generate-icon{
    animation:rmsLoadingSpin 1s linear infinite;
}
@keyframes rmsLoadingSpin{
    to{transform:rotate(360deg)}
}

/* ---------- ETA ---------- */
.rms-generator-workspace-animated .rms-generator-eta-card{
    position:relative;
    overflow:hidden;
    transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease;
}
.rms-generator-workspace-animated .rms-generator-eta-card::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(100deg,transparent,rgba(239,35,60,.04),transparent);
    transform:translateX(-100%);
    animation:rmsEtaSweep 4s ease-in-out infinite;
}
@keyframes rmsEtaSweep{
    0%,45%{transform:translateX(-100%)}
    70%,100%{transform:translateX(100%)}
}
.rms-generator-workspace-animated .rms-generator-eta-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 24px rgba(20,22,28,.06);
}
.rms-generator-workspace-animated .rms-generator-eta-icon{
    animation:rmsClockPulse 2s ease-in-out infinite;
}
@keyframes rmsClockPulse{
    0%,100%{transform:scale(1);opacity:.8}
    50%{transform:scale(1.1);opacity:1}
}

/* ---------- Disabled / empty ---------- */
.rms-generator-workspace-animated .rms-generator-upload-box.is-disabled{
    transition:opacity .3s ease,filter .3s ease,transform .3s ease;
}
.rms-generator-workspace-animated .rms-generator-upload-box.is-disabled:hover{
    transform:none;
    box-shadow:none;
}
.rms-generator-workspace-animated .rms-generator-no-template,
.rms-generator-workspace-animated .rms-generator-empty-mini{
    animation:rmsEmptyFloat 3s ease-in-out infinite;
}
@keyframes rmsEmptyFloat{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-3px)}
}

/* ---------- Small polish ---------- */
.rms-generator-workspace-animated button{
    -webkit-tap-highlight-color:transparent;
}
.rms-generator-workspace-animated button:focus-visible,
.rms-generator-workspace-animated input:focus-visible{
    outline:2px solid rgba(239,35,60,.45);
    outline-offset:3px;
}
.rms-generator-workspace-animated img{
    backface-visibility:hidden;
}

/* ---------- Responsive ---------- */
@media (max-width:760px){
    .rms-generator-workspace-animated .rms-generator-card:hover{
        transform:none;
        box-shadow:none;
    }
    .rms-generator-workspace-animated .rms-generator-template-card:hover{
        transform:translateY(-3px) scale(1.005);
    }
    .rms-generator-workspace-animated .rms-generator-upload-box:hover{
        transform:translateY(-2px);
    }
}

/* ---------- Accessibility ---------- */
@media (prefers-reduced-motion:reduce){
    .rms-generator-workspace-animated *,
    .rms-generator-workspace-animated *::before,
    .rms-generator-workspace-animated *::after{
        animation-duration:.01ms!important;
        animation-iteration-count:1!important;
        scroll-behavior:auto!important;
        transition-duration:.01ms!important;
    }
}
</style>



    </div>

    <section
        class="rms-generator-history rms-generator-history-bottom rms-generator-history-mobile-safe"
        wire:key="generation-history"
        x-data="{
            confirmOpen: false,
            confirmTitle: '',
            confirmMessage: '',
            confirmAction: '',
            confirmId: null,
            confirmTone: 'danger',
            askConfirm(action, id, title, message, tone = 'danger') {
                this.confirmAction = action;
                this.confirmId = id;
                this.confirmTitle = title;
                this.confirmMessage = message;
                this.confirmTone = tone;
                this.confirmOpen = true;
                document.body.classList.add('rms-confirm-lock');
            },
            closeConfirm() {
                this.confirmOpen = false;
                this.confirmAction = '';
                this.confirmId = null;
                document.body.classList.remove('rms-confirm-lock');
            },
            runConfirm() {
                if (!this.confirmAction || !this.confirmId) {
                    this.closeConfirm();
                    return;
                }

                const action = this.confirmAction;
                const id = this.confirmId;

                this.closeConfirm();

                if (action === 'delete') {
                    $wire.deleteGeneration(id);
                } else if (action === 'cancel') {
                    $wire.cancelGeneration(id);
                } else if (action === 'retry') {
                    $wire.retryGeneration(id);
                }
            }
        }"
    >
            <div class="rms-generator-history-head">
                <div class="rms-history-title">
                    <span>◷</span>
                    <div>
                        <strong>Recent Generations</strong>
                        <small>Generate berikutnya bisa langsung dibuat tanpa menunggu proses sebelumnya selesai.</small>
                    </div>
                </div>
                <div class="rms-history-count"><i></i>{{ $this->recentGenerations->count() }} result</div>
            </div>

            <div class="rms-history-toolbar">
                <div class="rms-history-search">
                    <span>⌕</span>
                    <input type="text" wire:model.live.debounce.400ms="historySearch" placeholder="Cari generation, store, template...">
                </div>
                <div class="rms-history-filter-group">
                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Status</span><b>{{ $historyStatus === 'all' ? 'All Status' : ucfirst($historyStatus) }}</b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            @foreach(['all'=>'All Status','queued'=>'Queued','processing'=>'Processing','completed'=>'Completed','failed'=>'Failed'] as $value => $label)
                                <button type="button" class="{{ $historyStatus === $value ? 'selected' : '' }}" wire:click="$set('historyStatus','{{ $value }}')" x-on:click="open=false">
                                    <span>{{ $label }}</span><i>{{ $historyStatus === $value ? '✓' : '' }}</i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Store</span><b>{{ $historyStoreId ? optional($this->stores->firstWhere('id',$historyStoreId))->name : 'All Stores' }}</b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            <button type="button" class="{{ ! $historyStoreId ? 'selected' : '' }}" wire:click="$set('historyStoreId',null)" x-on:click="open=false">
                                <span>All Stores</span><i>{{ ! $historyStoreId ? '✓' : '' }}</i>
                            </button>
                            @foreach($this->stores as $store)
                                <button type="button" class="{{ (int)$historyStoreId === (int)$store->id ? 'selected' : '' }}" wire:click="$set('historyStoreId',{{ $store->id }})" x-on:click="open=false">
                                    <span>{{ $store->name }}</span><i>{{ (int)$historyStoreId === (int)$store->id ? '✓' : '' }}</i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Template</span><b>{{ $historyTemplateId ? optional($this->historyTemplates->firstWhere('id',$historyTemplateId))->name : 'All Templates' }}</b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            <button type="button" class="{{ ! $historyTemplateId ? 'selected' : '' }}" wire:click="$set('historyTemplateId',null)" x-on:click="open=false">
                                <span>All Templates</span><i>{{ ! $historyTemplateId ? '✓' : '' }}</i>
                            </button>
                            @foreach($this->historyTemplates as $template)
                                <button type="button" class="{{ (int)$historyTemplateId === (int)$template->id ? 'selected' : '' }}" wire:click="$set('historyTemplateId',{{ $template->id }})" x-on:click="open=false">
                                    <span>{{ $template->name }}</span><i>{{ (int)$historyTemplateId === (int)$template->id ? '✓' : '' }}</i>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rms-generator-history-list rms-generator-history-grid"
                @if($this->hasActiveGenerations)
                    wire:poll.4s.visible
                @endif
            >
                @forelse($this->recentGenerations as $generation)
                    @php
                        $meta = $generation->metadata ?? [];
                        $status = $generation->status;
                        $progress = max(0, min(100, (int) ($meta['progress'] ?? ($status === 'completed' ? 100 : 8))));
                        $stage = $meta['progress_stage'] ?? ($status === 'queued' ? 'Menunggu worker queue' : 'Memproses...');
                        $statusLabel = match($status) {
                            'queued' => 'Queued', 'processing' => 'Processing', 'completed' => 'Completed',
                            'failed' => 'Failed', 'cancelled' => 'Cancelled', default => ucfirst($status),
                        };
                        $images = $generation->generatedImages->take(4);
                        $hero = $images->first();
                        $ratio = $hero && $hero->width && $hero->height
                            ? ((int) $hero->width . ' / ' . (int) $hero->height)
                            : str_replace(':', ' / ', $generation->aspect_ratio ?: '1:1');
                        $estimate = in_array($status, ['queued', 'processing'], true)
                            ? $this->generationEstimate($generation)
                            : ['seconds' => null, 'label' => null, 'basis' => null];
                        $etaLabel = $estimate['label'] ?? 'Menghitung estimasi…';
                        $requestedImages = max(1, (int) data_get($meta, 'requested_image_count', $generation->generatedImages->count() ?: 1));
                        $savedImages = $generation->generatedImages->count();
                    @endphp

                    <article wire:key="generation-history-{{ $generation->id }}" class="rms-generator-history-item rms-generation-card status-{{ $status }}">
                        <div class="rms-generation-card-head">
                            <div class="rms-history-item-meta"><span>{{ optional($generation->created_at)->format('d M Y · H:i') }}</span><small>#{{ $generation->id }}</small></div>
                            <span class="rms-history-status {{ $status }}"><i></i>{{ $statusLabel }}</span>
                        </div>

                        @if($status === 'completed' && $hero)
                            @php
                                $heroPreview = [
                                    'id' => (int) $hero->id,
                                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path),
                                    'format' => strtoupper($hero->format ?: 'PNG'),
                                    'width' => (int) $hero->width, 'height' => (int) $hero->height,
                                    'title' => data_get($generation->metadata, 'final_title')
                                        ?: data_get($generation->metadata, 'custom_title'),
                                    'template' => $generation->template?->name ?? 'Generated Image',
                                    'store' => $generation->store?->name ?? 'Store',
                                ];
                            @endphp
                            <button type="button" class="rms-generation-image" style="aspect-ratio: {{ $ratio }}" x-on:click="openPreview({{ \Illuminate\Support\Js::from($heroPreview) }})">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path) }}" alt="Generated image" loading="lazy">
                                <span class="rms-generation-image-overlay"><b>⌕</b> Lihat preview</span>
                            </button>
                        @elseif(in_array($status, ['queued','processing']))
                            <div class="rms-generation-processing-visual">
                                @if($hero)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path) }}" alt="Processing preview" loading="lazy">
                                @else
                                    <div class="rms-processing-placeholder"><span class="rms-processing-orbit"></span><b>{{ $progress }}%</b><small>AI PROCESSING</small></div>
                                @endif
                                <div class="rms-processing-progress-ring" style="--progress: {{ $progress }}%"><span>{{ $progress }}%</span></div>
                            </div>
                            <div class="rms-generation-eta rms-generation-eta-v3">
                                <span class="rms-generation-eta-clock">◷</span>
                                <div>
                                    <strong>{{ $status === 'queued' ? 'Estimasi antrean' : 'Estimasi tersisa' }}</strong>
                                    <b>{{ $etaLabel }}</b>
                                    <small>Estimasi diperbarui dari durasi generation sebelumnya + kondisi queue.</small>
                                </div>
                            </div>
                            <div class="rms-generation-stage">
                                <div class="rms-generation-stage-item active"><i></i><span>{{ $stage }}</span></div>
                                <div class="rms-generation-stage-item {{ $progress >= 78 ? 'active' : '' }}"><i></i><span>Membuat gambar</span></div>
                                <div class="rms-generation-stage-item {{ $progress >= 96 ? 'active' : '' }}"><i></i><span>Menyimpan hasil</span></div>
                            </div>
                        @elseif($status === 'failed')
                            <div class="rms-generation-state rms-generation-state-error"><span>!</span><div><strong>Generate gagal</strong><small>{{ \Illuminate\Support\Str::limit($generation->error_message ?: 'Terjadi error saat memproses generation.', 180) }}</small></div></div>
                        @else
                            <div class="rms-generation-state rms-generation-state-cancelled"><span>×</span><div><strong>Generation dibatalkan</strong><small>Proses dihentikan oleh pengguna.</small></div></div>
                        @endif

                        @php
                            $generationTitle = data_get($generation->metadata, 'final_title')
                                ?: data_get($generation->metadata, 'custom_title');
                            $titleSource = data_get($generation->metadata, 'title_source', 'ai');
                        @endphp
                        <div class="rms-generation-card-info rms-generation-card-info-v3">
                            <div class="rms-generation-title-block">
                                <strong>{{ $generationTitle ?: ('Generation #' . $generation->id) }}</strong>
                                <small class="rms-generation-template-line">
                                    <span class="rms-meta-label">TEMPLATE</span>
                                    <span>{{ $generation->template?->name ?? 'Template' }}</span>
                                    <span class="rms-meta-separator">·</span>
                                    <span>{{ $generation->store?->name ?? 'Store' }}</span>
                                </small>
                            </div>

                            <div class="rms-generation-meta-grid">
                                <span class="rms-generation-meta-chip">
                                    <i>AI</i>
                                    <b>{{ \Illuminate\Support\Str::afterLast($generation->model, '/') ?: $generation->model }}</b>
                                </span>
                                <span class="rms-generation-meta-chip">
                                    <i>AR</i>
                                    <b>{{ $generation->aspect_ratio ?: '1:1' }}</b>
                                </span>
                                <span class="rms-generation-meta-chip">
                                    <i>Q</i>
                                    <b>{{ ucfirst($generation->output_quality ?: 'standard') }}</b>
                                </span>
                                <span class="rms-generation-meta-chip">
                                    <i>IMG</i>
                                    <b>{{ $savedImages ?: $requestedImages }}/{{ $requestedImages }}</b>
                                </span>
                            </div>
                        </div>

                        <div class="rms-generation-actions rms-generation-actions-v2">
                            @if($status === 'completed')
                                @if($hero)
                                    <button type="button"
                                        x-on:click="openPreview({{ \Illuminate\Support\Js::from($heroPreview) }})"
                                        class="rms-generation-action rms-action-preview">
                                        <span class="rms-action-icon">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.8 12s3.1-5.2 9.2-5.2S21.2 12 21.2 12s-3.1 5.2-9.2 5.2S2.8 12 2.8 12Z"/><circle cx="12" cy="12" r="2.4"/></svg>
                                        </span>
                                        <span class="rms-action-copy">
                                            <strong>Lihat Preview</strong>
                                            <small>Buka hasil gambar</small>
                                        </span>
                                        <svg class="rms-action-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13m-5-5 5 5-5 5"/></svg>
                                    </button>
                                @endif

                                <button type="button"
                                    wire:click="retryGeneration({{ $generation->id }})"
                                    class="rms-generation-action rms-action-retry">
                                    <span class="rms-action-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3.8L3 9m0 0V4.5M3 9h4.5"/><path d="M4 13a8 8 0 0 0 14.9 3.8L21 15m0 0v4.5M21 15h-4.5"/></svg>
                                    </span>
                                    <span class="rms-action-copy">
                                        <strong>Buat Lagi</strong>
                                        <small>Gunakan konfigurasi ini</small>
                                    </span>
                                </button>

                                <button type="button"
                                    x-on:click="askConfirm('delete', {{ $generation->id }}, 'Hapus generation?', 'Generation ini beserta file hasilnya akan dihapus. Tindakan ini tidak dapat dibatalkan.')"
                                    class="rms-generation-action rms-action-delete"
                                    title="Hapus generation"
                                    aria-label="Hapus generation">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4.5h6V7"/><path d="m7 7 .7 12.2a1.8 1.8 0 0 0 1.8 1.7h5a1.8 1.8 0 0 0 1.8-1.7L17 7"/><path d="M10 11v6M14 11v6"/></svg>
                                </button>

                            @elseif(in_array($status, ['queued','processing']))
                                <button type="button"
                                    x-on:click="askConfirm('cancel', {{ $generation->id }}, 'Batalkan generation?', 'Proses yang sedang berjalan akan dihentikan dan dipindahkan ke status cancelled.')"
                                    class="rms-generation-action rms-action-cancel">
                                    <span class="rms-action-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                                    </span>
                                    <span class="rms-action-copy">
                                        <strong>Batalkan</strong>
                                        <small>Hentikan proses</small>
                                    </span>
                                </button>

                                <button type="button"
                                    x-on:click="askConfirm('retry', {{ $generation->id }}, 'Restart generation?', 'Proses saat ini akan dibatalkan lalu generation baru akan dibuat ulang.')"
                                    class="rms-generation-action rms-action-retry">
                                    <span class="rms-action-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3.8L3 9m0 0V4.5M3 9h4.5"/><path d="M4 13a8 8 0 0 0 14.9 3.8L21 15m0 0v4.5M21 15h-4.5"/></svg>
                                    </span>
                                    <span class="rms-action-copy">
                                        <strong>Ulangi</strong>
                                        <small>Mulai ulang generation</small>
                                    </span>
                                </button>

                                <button type="button"
                                    x-on:click="askConfirm('delete', {{ $generation->id }}, 'Hapus generation?', 'Generation ini akan dihapus dari Recent Generations.')"
                                    class="rms-generation-action rms-action-delete"
                                    title="Hapus generation"
                                    aria-label="Hapus generation">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4.5h6V7"/><path d="m7 7 .7 12.2a1.8 1.8 0 0 0 1.8-1.7h5a1.8 1.8 0 0 0 1.8-1.7L17 7"/><path d="M10 11v6M14 11v6"/></svg>
                                </button>

                            @elseif($status === 'failed' || $status === 'cancelled')
                                <button type="button"
                                    wire:click="retryGeneration({{ $generation->id }})"
                                    class="rms-generation-action rms-action-retry rms-action-retry-primary">
                                    <span class="rms-action-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3.8L3 9m0 0V4.5M3 9h4.5"/><path d="M4 13a8 8 0 0 0 14.9 3.8L21 15m0 0v4.5M21 15h-4.5"/></svg>
                                    </span>
                                    <span class="rms-action-copy">
                                        <strong>Generate Ulang</strong>
                                        <small>Coba proses kembali</small>
                                    </span>
                                    <svg class="rms-action-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13m-5-5 5 5-5 5"/></svg>
                                </button>

                                <button type="button"
                                    x-on:click="askConfirm('delete', {{ $generation->id }}, 'Hapus generation?', 'Generation ini akan dihapus dari Recent Generations.')"
                                    class="rms-generation-action rms-action-delete rms-action-delete-wide"
                                    title="Hapus generation"
                                    aria-label="Hapus generation">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V4.5h6V7"/><path d="m7 7 .7 12.2a1.8 1.8 0 0 0 1.8 1.7h5a1.8 1.8 0 0 0 1.8-1.7L17 7"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="rms-action-copy">
                                        <strong>Hapus</strong>
                                        <small>Remove dari history</small>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rms-history-empty"><span>✦</span><strong>Belum ada generation</strong><small>Hasil baru akan muncul di sini. Kamu bisa menjalankan beberapa generation tanpa menunggu satu per satu.</small></div>
                @endforelse
            </div>
        {{-- ============================================================
            CUSTOM CONFIRM POPUP
            IMPORTANT:
            - Di-teleport langsung ke <body> agar selalu berada di atas
              topbar + sidebar + seluruh dashboard.
            - Background full viewport, dark + blur seperti Preview modal.
            - Modal tetap bisa di-scroll pada viewport pendek/mobile.
            ============================================================ --}}
        <template x-teleport="body">
            <div
                x-cloak
                x-show="confirmOpen"
                x-transition.opacity.duration.180ms
                class="rms-confirm-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rms-confirm-title"
                x-on:keydown.escape.window="closeConfirm()"
                x-on:click.self="closeConfirm()"
            >
                <div
                    class="rms-confirm-backdrop"
                    x-on:click="closeConfirm()"
                    aria-hidden="true"
                ></div>

                <div
                    class="rms-confirm-dialog"
                    x-on:click.stop
                >
                    <div class="rms-confirm-icon" :class="'is-' + confirmTone">
                        <svg x-show="confirmAction === 'delete'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7h16"/>
                            <path d="M9 7V4.5h6V7"/>
                            <path d="m7 7 .7 12.2a1.8 1.8 0 0 0 1.8 1.7h5a1.8 1.8 0 0 0 1.8-1.7L17 7"/>
                            <path d="M10 11v6M14 11v6"/>
                        </svg>
                        <svg x-show="confirmAction === 'cancel'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.5"/>
                            <path d="m9 9 6 6m0-6-6 6"/>
                        </svg>
                        <svg x-show="confirmAction === 'retry'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M20 11a8 8 0 0 0-14.9-3.8L3 9m0 0V4.5M3 9h4.5"/>
                            <path d="M4 13a8 8 0 0 0 14.9 3.8L21 15m0 0v4.5M21 15h-4.5"/>
                        </svg>
                    </div>

                    <div class="rms-confirm-copy">
                        <span class="rms-confirm-eyebrow">
                            <i></i>
                            <span x-text="confirmAction === 'delete' ? 'DELETE ACTION' : (confirmAction === 'retry' ? 'RESTART ACTION' : 'CANCEL ACTION')"></span>
                        </span>
                        <h3 id="rms-confirm-title" x-text="confirmTitle"></h3>
                        <p x-text="confirmMessage"></p>
                    </div>

                    <div class="rms-confirm-actions">
                        <button type="button" class="rms-confirm-btn rms-confirm-btn-secondary" x-on:click="closeConfirm()">
                            <span>Batal</span>
                        </button>

                        <button
                            type="button"
                            class="rms-confirm-btn rms-confirm-btn-danger"
                            :class="{ 'is-retry': confirmAction === 'retry', 'is-cancel': confirmAction === 'cancel' }"
                            x-on:click="runConfirm()"
                        >
                            <span x-text="confirmAction === 'delete' ? 'Ya, Hapus' : (confirmAction === 'retry' ? 'Ya, Restart' : 'Ya, Batalkan')"></span>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h13m-5-5 5 5-5 5"/>
                            </svg>
                        </button>
                    </div>

                    <div class="rms-confirm-hint">
                        <span>ESC</span>
                        <small>untuk menutup</small>
                    </div>
                </div>
            </div>
        </template>

        </section>

    <style>
        /* ============================================================
           MOBILE-SAFE RECENT HISTORY + CUSTOM CONFIRM POPUP
           ============================================================ */

        /* Recent Generations sengaja menjadi sibling workspace.
           Ini mencegah parent layout/grid/overflow memotong history di mobile. */
        .rms-generator-history-mobile-safe{
            display:block!important;
            width:100%!important;
            min-width:0!important;
            max-width:100%!important;
            height:auto!important;
            min-height:0!important;
            max-height:none!important;
            visibility:visible!important;
            opacity:1!important;
            overflow:visible!important;
            position:relative!important;
            clear:both;
            box-sizing:border-box;
            margin-top:18px;
            margin-bottom:28px;
            isolation:isolate;
        }

        .rms-generator-history-mobile-safe .rms-generator-history-grid{
            width:100%;
            min-width:0;
        }

        .rms-generator-history-mobile-safe .rms-history-toolbar{
            width:100%;
            min-width:0;
            box-sizing:border-box;
        }

        .rms-generator-history-mobile-safe .rms-history-search{
            min-width:0;
        }

        .rms-generator-history-mobile-safe .rms-generation-card{
            min-width:0;
            max-width:100%;
        }

         /* Custom confirmation */
        /* ============================================================
           CUSTOM CONFIRM MODAL — DARK BLUR + SCROLL SAFE
           - Background benar-benar hitam/translucent + blur.
           - Overlay sendiri bisa di-scroll bila viewport pendek.
           - Card tidak terpotong ketika tinggi konten melebihi viewport.
           - Scroll di dalam card tetap halus.
           ============================================================ */
        .rms-confirm-modal{
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            inset: 0 !important;
            margin: 0 !important;
            z-index: 2147483647 !important;
            width: 100vw !important;
            min-width: 100vw !important;
            max-width: none !important;
            min-height: 100dvh !important;
            height: 100dvh !important;
            max-height: none !important;
            transform: none !important;
            clip: auto !important;
            clip-path: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
            overscroll-behavior: contain;
            isolation: isolate;
            background: rgba(8, 9, 11, 0.76);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }


        /* ============================================================
           CONFIRM MODAL — FORCE ABOVE DASHBOARD CHROME
           The dashboard header/sidebar use their own stacking contexts.
           This modal is teleported to <body>, so keep it in the highest
           possible viewport layer and explicitly reset common clipping/
           positioning properties.
           ============================================================ */
        body > .rms-confirm-modal{
            position:fixed !important;
            inset:0 !important;
            z-index:2147483647 !important;
            width:100vw !important;
            height:100dvh !important;
            min-width:100vw !important;
            min-height:100dvh !important;
            max-width:none !important;
            max-height:none !important;
            margin:0 !important;
            transform:none !important;
            overflow-y:auto !important;
            overflow-x:hidden !important;
        }

        html:has(body > .rms-confirm-modal),
        body:has(> .rms-confirm-modal){
            isolation:isolate;
        }

        .rms-confirm-modal[x-cloak]{
            display: none !important;
        }

        .rms-confirm-backdrop{
            position:absolute;
            inset:0;
            z-index:-1;
            pointer-events:auto;
            background:
                radial-gradient(circle at 50% 45%,rgba(0,0,0,.02),rgba(0,0,0,.34)),
                rgba(3,4,6,.52);
        }

        .rms-confirm-dialog{
            position:relative!important;
            z-index:2!important;
            display:block!important;
            visibility:visible!important;
            pointer-events:auto!important;
            width:min(430px,100%);
            max-width:430px;
            max-height:calc(100dvh - 40px);
            flex:0 0 auto;
            margin:auto;
            padding:26px;
            box-sizing:border-box;
            border:1px solid rgba(255,255,255,.78);
            border-radius:24px;
            background:
                radial-gradient(circle at 92% 0%,rgba(239,35,60,.10),transparent 30%),
                linear-gradient(180deg,#fff 0%,#fbfbfc 100%);
            box-shadow:
                0 35px 100px rgba(0,0,0,.42),
                0 12px 35px rgba(0,0,0,.22);
            overflow-y:auto;
            overflow-x:hidden;
            overscroll-behavior:contain;
            -webkit-overflow-scrolling:touch;
            scrollbar-width:thin;
            scrollbar-color:#cfd1d6 transparent;
            animation:rmsConfirmDialogIn .34s cubic-bezier(.2,.85,.25,1) both;
        }

        .rms-confirm-dialog::-webkit-scrollbar{
            width:7px;
        }

        .rms-confirm-dialog::-webkit-scrollbar-track{
            background:transparent;
        }

        .rms-confirm-dialog::-webkit-scrollbar-thumb{
            border-radius:999px;
            background:#cfd1d6;
        }

        .rms-confirm-dialog::before{
            content:"";
            position:absolute;
            left:-25%;
            top:-70%;
            width:150%;
            height:130%;
            pointer-events:none;
            background:radial-gradient(circle,rgba(239,35,60,.08),transparent 62%);
        }

        .rms-confirm-icon{
            position:relative;
            width:52px;
            height:52px;
            display:grid;
            place-items:center;
            margin-bottom:18px;
            border-radius:16px;
            background:#fff0f2;
            color:#ef233c;
            box-shadow:inset 0 0 0 1px #ffdadd,0 10px 24px rgba(239,35,60,.10);
            animation:rmsConfirmIconIn .5s cubic-bezier(.2,1.4,.35,1) both;
        }

        .rms-confirm-icon svg{
            width:24px;
            height:24px;
            stroke:currentColor;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
            animation:rmsConfirmIconPulse 2.2s ease-in-out infinite;
        }

        .rms-confirm-icon.is-retry{
            background:#fff5e8;
            color:#d77900;
            box-shadow:inset 0 0 0 1px #ffe0b3,0 10px 24px rgba(215,121,0,.10);
        }

        .rms-confirm-icon.is-cancel{
            background:#f2f6fb;
            color:#54708f;
            box-shadow:inset 0 0 0 1px #dbe5ef,0 10px 24px rgba(84,112,143,.08);
        }

        .rms-confirm-copy{
            position:relative;
        }

        .rms-confirm-eyebrow{
            display:flex;
            align-items:center;
            gap:7px;
            margin-bottom:8px;
            color:#ef233c;
            font-size:9px;
            font-weight:900;
            letter-spacing:.15em;
        }

        .rms-confirm-eyebrow i{
            width:6px;
            height:6px;
            border-radius:50%;
            background:#ef233c;
            box-shadow:0 0 0 4px #ffe9ec;
            animation:rmsConfirmDot 1.6s ease-in-out infinite;
        }

        .rms-confirm-copy h3{
            margin:0;
            color:#17181b;
            font-size:21px;
            line-height:1.15;
            letter-spacing:-.035em;
            font-weight:900;
        }

        .rms-confirm-copy p{
            margin:9px 0 0;
            color:#777d87;
            font-size:12px;
            line-height:1.55;
        }

        .rms-confirm-actions{
            position:relative;
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:10px;
            margin-top:24px;
        }

        .rms-confirm-btn{
            min-height:46px;
            border:1px solid #e1e3e8;
            border-radius:13px;
            padding:0 15px;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            cursor:pointer;
            font-size:11px;
            font-weight:900;
            transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease,color .2s ease;
            -webkit-tap-highlight-color:transparent;
        }

        .rms-confirm-btn:active{
            transform:scale(.975);
        }

        .rms-confirm-btn-secondary{
            background:#fff;
            color:#3c4047;
        }

        .rms-confirm-btn-secondary:hover{
            transform:translateY(-2px);
            background:#f8f9fa;
            border-color:#d4d7dd;
            box-shadow:0 8px 18px rgba(20,20,25,.08);
        }

        .rms-confirm-btn-danger{
            position:relative;
            overflow:hidden;
            border-color:#ef233c;
            background:linear-gradient(135deg,#ef233c,#d91831);
            color:#fff;
            box-shadow:0 9px 22px rgba(239,35,60,.22);
        }

        .rms-confirm-btn-danger::before{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(105deg,transparent 25%,rgba(255,255,255,.25) 50%,transparent 75%);
            transform:translateX(-120%);
            animation:rmsConfirmShine 2.8s ease-in-out infinite;
        }

        .rms-confirm-btn-danger:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 28px rgba(239,35,60,.28);
        }

        .rms-confirm-btn-danger.is-retry{
            border-color:#d77900;
            background:linear-gradient(135deg,#e88a12,#c96a00);
            box-shadow:0 9px 22px rgba(215,121,0,.20);
        }

        .rms-confirm-btn-danger.is-cancel{
            border-color:#54708f;
            background:linear-gradient(135deg,#607d9b,#45647f);
            box-shadow:0 9px 22px rgba(84,112,143,.18);
        }

        .rms-confirm-btn svg{
            width:15px;
            height:15px;
            stroke:currentColor;
            stroke-width:2;
            stroke-linecap:round;
            stroke-linejoin:round;
            transition:transform .2s ease;
        }

        .rms-confirm-btn-danger:hover svg{
            transform:translateX(3px);
        }

        .rms-confirm-hint{
            position:relative;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            margin-top:15px;
            color:#a0a5ad;
            font-size:9px;
        }

        .rms-confirm-hint span{
            padding:3px 6px;
            border:1px solid #e1e3e8;
            border-bottom-width:2px;
            border-radius:5px;
            background:#f8f9fa;
            color:#777d87;
            font-size:8px;
            font-weight:900;
        }

        .rms-confirm-hint small{
            font-size:9px;
        }

        .rms-confirm-enter{transition:opacity .18s ease}
        .rms-confirm-enter-start{opacity:0}
        .rms-confirm-enter-end{opacity:1}
        .rms-confirm-leave{transition:opacity .14s ease}
        .rms-confirm-leave-start{opacity:1}
        .rms-confirm-leave-end{opacity:0}

        @keyframes rmsConfirmDialogIn{
            from{opacity:0;transform:translateY(18px) scale(.96)}
            to{opacity:1;transform:translateY(0) scale(1)}
        }

        @keyframes rmsConfirmIconIn{
            0%{opacity:0;transform:translateY(10px) scale(.72) rotate(-10deg)}
            70%{transform:translateY(-2px) scale(1.08) rotate(2deg)}
            100%{opacity:1;transform:none}
        }

        @keyframes rmsConfirmIconPulse{
            0%,100%{transform:scale(1)}
            50%{transform:scale(1.07)}
        }

        @keyframes rmsConfirmDot{
            0%,100%{transform:scale(1);opacity:.75}
            50%{transform:scale(1.35);opacity:1}
        }

        @keyframes rmsConfirmShine{
            0%,55%{transform:translateX(-120%)}
            80%,100%{transform:translateX(120%)}
        }

        .rms-confirm-lock{
            overflow:hidden!important;
            touch-action:none;
        }

        /* Modal tetap menerima scroll walaupun body sedang di-lock. */
        .rms-confirm-modal,
        .rms-confirm-modal .rms-confirm-dialog{
            touch-action:auto;
        }

        @media (max-width:760px){
            .rms-confirm-modal{
                align-items:flex-start;
                padding:16px 14px;
            }

            .rms-confirm-dialog{
                width:min(430px,100%);
                max-height:calc(100dvh - 32px);
                margin:auto;
                padding:22px;
                border-radius:20px;
            }
        }

        @media (max-width:420px){
            .rms-confirm-modal{
                padding:10px;
            }

            .rms-confirm-dialog{
                max-height:calc(100dvh - 20px);
                padding:20px;
                border-radius:18px;
            }

            .rms-confirm-actions{
                grid-template-columns:1fr;
            }
        }

        @media (max-width:900px){
            .rms-generator-history-mobile-safe{
                margin-top:14px;
            }
        }

        @media (max-width:760px){
            .rms-generator-history-mobile-safe{
                display:block!important;
                width:100vw!important;
                max-width:100vw!important;
                margin-left:calc(50% - 50vw)!important;
                margin-right:calc(50% - 50vw)!important;
                padding:0 12px;
                box-sizing:border-box;
                margin-top:12px;
                margin-bottom:22px;
            }

            .rms-generator-history-mobile-safe .rms-generator-history-head{
                padding-left:4px;
                padding-right:4px;
            }

            .rms-generator-history-mobile-safe .rms-history-toolbar{
                display:flex;
                flex-direction:column;
                align-items:stretch;
                gap:9px;
            }

            .rms-generator-history-mobile-safe .rms-history-filter-group{
                display:grid;
                grid-template-columns:repeat(3,minmax(0,1fr));
                gap:7px;
                width:100%;
            }

            .rms-generator-history-mobile-safe .rms-history-filter{
                min-width:0;
            }

            .rms-generator-history-mobile-safe .rms-history-filter > button{
                width:100%;
                min-width:0;
            }

            .rms-generator-history-mobile-safe .rms-history-filter > button b{
                max-width:72px;
                overflow:hidden;
                text-overflow:ellipsis;
                white-space:nowrap;
            }

            .rms-generator-history-mobile-safe .rms-generator-history-grid{
                grid-template-columns:1fr!important;
                gap:12px!important;
            }

            .rms-generator-history-mobile-safe .rms-generation-card{
                width:100%;
                box-sizing:border-box;
            }

            .rms-generation-actions-v2{
                grid-template-columns:1fr 1fr!important;
            }

            .rms-generation-actions-v2 .rms-action-delete,
            .rms-generation-actions-v2 .rms-action-delete-wide{
                grid-column:1 / -1;
                width:100%;
                min-width:0;
                min-height:44px;
            }

            .rms-generation-actions-v2 .rms-generation-action{
                min-height:46px;
            }

            .rms-confirm-modal{
                padding:14px;
                align-items:flex-end;
            }

            .rms-confirm-dialog{
                width:100%;
                border-radius:22px;
                padding:22px 18px 17px;
            }

            .rms-confirm-copy h3{
                font-size:19px;
            }

            .rms-confirm-copy p{
                font-size:11px;
            }

            .rms-confirm-actions{
                gap:8px;
                margin-top:20px;
            }

            .rms-confirm-btn{
                min-height:48px;
            }
        }

        @media (max-width:480px){
            .rms-generator-history-mobile-safe{
                padding-left:9px;
                padding-right:9px;
            }

            .rms-generator-history-mobile-safe .rms-history-filter-group{
                grid-template-columns:1fr;
            }

            .rms-generator-history-mobile-safe .rms-history-filter > button{
                min-height:42px;
            }

            .rms-generation-actions-v2{
                grid-template-columns:1fr!important;
            }

            .rms-generation-actions-v2 .rms-generation-action,
            .rms-generation-actions-v2 .rms-action-delete,
            .rms-generation-actions-v2 .rms-action-delete-wide{
                grid-column:auto;
                width:100%;
            }

            .rms-confirm-dialog{
                border-radius:20px;
                padding:20px 16px 15px;
            }

            .rms-confirm-icon{
                width:48px;
                height:48px;
                border-radius:14px;
            }

            .rms-confirm-copy h3{
                font-size:18px;
            }

            .rms-confirm-actions{
                grid-template-columns:1fr;
            }

            .rms-confirm-btn{
                min-height:46px;
            }

            .rms-confirm-hint{
                display:none;
            }
        }

        @media (prefers-reduced-motion:reduce){
            .rms-confirm-dialog *,
            .rms-confirm-dialog::before,
            .rms-confirm-modal *,
            .rms-confirm-modal *::before,
            .rms-generator-history-mobile-safe *{
                animation-duration:.01ms!important;
                animation-iteration-count:1!important;
                transition-duration:.01ms!important;
            }
        }
    </style>

    <style>
        /* ============================================================
           GENERATED PREVIEW MODAL
           Modal dibuat seperti Edit Template:
           - card terpusat
           - tinggi mengikuti viewport
           - body/media dapat di-scroll
           - sidebar download tetap usable
           ============================================================ */

        .rms-generator-preview-modal-custom {
            position: fixed !important;
            inset: 0 !important;
            z-index: 999999 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
            overscroll-behavior: contain;
            background: rgba(15, 15, 18, 0.72);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .rms-generator-preview-modal-custom[x-cloak] {
            display: none !important;
        }

        .rms-generator-preview-modal-custom .rms-preview-backdrop {
            position: absolute;
            inset: 0;
            background: transparent;
        }

        .rms-preview-dialog-custom {
            position: relative;
            z-index: 1;
            width: min(1180px, 100%);
            height: min(760px, calc(100vh - 48px));
            max-height: calc(100vh - 48px);
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) minmax(340px, 0.8fr);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 24px;
            background: #ffffff;
            box-shadow:
                0 35px 90px rgba(0, 0, 0, 0.28),
                0 8px 30px rgba(0, 0, 0, 0.14);
        }

        .rms-preview-close {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 50;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid rgba(17, 18, 20, 0.10);
            border-radius: 13px;
            background: rgba(255, 255, 255, 0.97);
            color: #17181b;
            cursor: pointer;
            box-shadow:
                0 8px 22px rgba(0, 0, 0, 0.14),
                0 2px 6px rgba(0, 0, 0, 0.08);
            transition:
                background 0.18s ease,
                color 0.18s ease,
                border-color 0.18s ease,
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .rms-preview-close svg {
            width: 18px;
            height: 18px;
            stroke-width: 2.2;
        }

        .rms-preview-close:hover {
            background: #111214;
            color: #fff;
            border-color: #111214;
            transform: scale(1.04);
            box-shadow:
                0 10px 26px rgba(0, 0, 0, 0.20),
                0 3px 8px rgba(0, 0, 0, 0.10);
        }

        .rms-preview-close:active {
            transform: scale(0.96);
        }

        .rms-preview-close:focus-visible {
            outline: 3px solid rgba(220, 38, 38, 0.22);
            outline-offset: 3px;
        }

        .rms-preview-media-custom {
            position: relative;
            min-width: 0;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
            padding: 24px;
            background:
                radial-gradient(circle at 50% 30%, rgba(255,255,255,.06), transparent 45%),
                #17181b;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.28) transparent;
        }

        .rms-preview-media-custom::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .rms-preview-media-custom::-webkit-scrollbar-track {
            background: transparent;
        }

        .rms-preview-media-custom::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(255,255,255,.25);
        }

        .rms-preview-image {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
        }

        .rms-preview-empty {
            width: min(420px, 90%);
            padding: 42px 28px;
            text-align: center;
            color: #fff;
            border: 1px dashed rgba(255,255,255,.2);
            border-radius: 18px;
            background: rgba(255,255,255,.05);
        }

        .rms-preview-empty-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255,255,255,.08);
            font-size: 24px;
        }

        .rms-preview-empty strong,
        .rms-preview-empty small {
            display: block;
        }

        .rms-preview-empty small {
            margin-top: 7px;
            color: rgba(255,255,255,.6);
        }

        .rms-preview-sidebar-custom {
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: #fff;
            border-left: 1px solid #ececef;
            scrollbar-width: thin;
            scrollbar-color: #cfcfd4 transparent;
        }

        .rms-preview-sidebar-custom::-webkit-scrollbar {
            width: 7px;
        }

        .rms-preview-sidebar-custom::-webkit-scrollbar-track {
            background: transparent;
        }

        .rms-preview-sidebar-custom::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #d1d1d5;
        }

        .rms-preview-sidebar-header {
            padding: 30px 28px 22px;
            border-bottom: 1px solid #ececef;
            background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        }

        .rms-preview-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            color: #e3262e;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .18em;
        }

        .rms-preview-kicker::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #e3262e;
            box-shadow: 0 0 0 4px rgba(227,38,46,.08);
        }

        .rms-preview-sidebar-header h3 {
            margin: 0;
            color: #17181b;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 750;
        }

        .rms-preview-sidebar-header p {
            margin: 6px 0 0;
            color: #6d6e74;
            font-size: 13px;
        }

        .rms-preview-mobile-close {
            display: none;
        }

        .rms-preview-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 20px 28px;
            border-bottom: 1px solid #ececef;
        }

        .rms-preview-spec {
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid #e8e8eb;
            border-radius: 12px;
            background: #fafafa;
        }

        .rms-preview-spec span {
            display: block;
            margin-bottom: 6px;
            color: #8a8b91;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .16em;
        }

        .rms-preview-spec strong {
            display: block;
            overflow: hidden;
            color: #242529;
            font-size: 12px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rms-preview-download {
            padding: 22px 28px 28px;
        }

        .rms-preview-download-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 13px;
        }

        .rms-preview-download-header strong {
            color: #1c1d20;
            font-size: 14px;
        }

        .rms-preview-download-header span {
            color: #8a8b91;
            font-size: 10px;
        }

        .rms-preview-download-primary,
        .rms-preview-download-options a {
            text-decoration: none;
            transition: 0.2s ease;
        }

        .rms-preview-download-primary {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            padding: 0 15px;
            border-radius: 12px;
            background: #17181b;
            color: #fff;
        }

        .rms-preview-download-primary:hover {
            background: #2a2b2f;
            transform: translateY(-1px);
        }

        .rms-preview-download-primary > span {
            font-size: 18px;
        }

        .rms-preview-download-primary strong {
            font-size: 12px;
        }

        .rms-preview-download-primary.is-disabled {
            opacity: .45;
            pointer-events: none;
        }

        .rms-preview-download-options {
            display: grid;
            gap: 8px;
            margin-top: 10px;
        }

        .rms-preview-download-options a {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) 18px;
            align-items: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 12px;
            border: 1px solid #e7e7ea;
            border-radius: 11px;
            color: #25262a;
            background: #fff;
        }

        .rms-preview-download-options a:hover {
            border-color: #cfcfd4;
            background: #fafafa;
            transform: translateY(-1px);
        }

        .rms-preview-download-options b {
            color: #d9242c;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .06em;
        }

        .rms-preview-download-options span {
            color: #77787e;
            font-size: 10px;
        }

        .rms-preview-download-options i {
            color: #999aa0;
            font-size: 14px;
            font-style: normal;
            text-align: right;
        }

        @media (max-width: 900px) {
            .rms-generator-preview-modal-custom {
                padding: 18px 12px;
                align-items: flex-start;
            }

            .rms-preview-dialog-custom {
                width: 100%;
                height: calc(100vh - 36px);
                max-height: calc(100vh - 36px);
                min-height: 0;
                grid-template-columns: 1fr;
                grid-template-rows: minmax(280px, 48vh) minmax(0, 1fr);
                border-radius: 20px;
            }

            .rms-preview-media-custom {
                padding: 18px;
                overflow: auto;
            }

            .rms-preview-sidebar-custom {
                border-top: 1px solid #ececef;
                border-left: 0;
            }

            .rms-preview-sidebar-header {
                padding: 22px 20px 17px;
            }

            .rms-preview-specs,
            .rms-preview-download {
                padding-left: 20px;
                padding-right: 20px;
            }

            .rms-preview-close {
                top: 12px;
                right: 12px;
                width: 38px;
                height: 38px;
            }
        }

        @media (max-width: 560px) {
            .rms-generator-preview-modal-custom {
                padding: 8px;
            }

            .rms-preview-dialog-custom {
                height: calc(100vh - 16px);
                max-height: calc(100vh - 16px);
                border-radius: 16px;
                grid-template-rows: minmax(230px, 38vh) minmax(0, 1fr);
            }

            .rms-preview-specs {
                grid-template-columns: 1fr;
            }

            .rms-preview-download-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
        }
    

        /* ============================================================
           GENERATION PAGE / RECENT GENERATIONS UPGRADE
           ============================================================ */
        .rms-generator-eta-card{margin-top:10px;display:flex;align-items:center;gap:11px;padding:11px 13px;border:1px solid #f0e4e4;border-radius:14px;background:linear-gradient(135deg,#fff,#fff8f8)}
        .rms-generator-eta-icon{width:30px;height:30px;display:grid;place-items:center;border-radius:10px;background:#fff0f0;color:#ef233c;font-size:16px;animation:rmsEtaPulse 2s ease-in-out infinite}
        .rms-generator-eta-card div{display:flex;flex-direction:column;gap:2px;min-width:0}.rms-generator-eta-card strong{font-size:11px;font-weight:800;color:#17181b}.rms-generator-eta-card span{font-size:12px;font-weight:800;color:#ef233c}.rms-generator-eta-card small{font-size:9px;color:#8b8f98;line-height:1.35}
        .rms-generator-history-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:start}
        .rms-generation-card{min-width:0;overflow:hidden;padding:14px!important;border:1px solid #e6e7eb!important;border-radius:18px!important;background:#fff;box-shadow:0 5px 20px rgba(20,20,25,.045);transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease}.rms-generation-card:hover{transform:translateY(-2px);box-shadow:0 14px 35px rgba(20,20,25,.09);border-color:#d9dbe0!important}
        .rms-generation-card-head,.rms-generation-card-info{display:flex;align-items:center;justify-content:space-between;gap:10px}.rms-generation-card-head{margin-bottom:10px}.rms-generation-card-info{padding:11px 1px 10px}.rms-generation-card-info>div{min-width:0;display:flex;flex-direction:column;gap:3px}.rms-generation-card-info strong{font-size:13px;line-height:1.25;color:#17181b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.rms-generation-card-info small{font-size:10px;color:#8a8f99;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.rms-generation-card-info>span{font-size:10px;color:#777d87;white-space:nowrap}
        .rms-generation-image{position:relative;display:block;width:100%;overflow:hidden;border:0;padding:0;border-radius:13px;background:#f5f5f6;cursor:pointer}.rms-generation-image img{width:100%;height:100%;display:block;object-fit:cover;transition:transform .45s cubic-bezier(.2,.7,.2,1)}.rms-generation-image:hover img{transform:scale(1.035)}.rms-generation-image-overlay{position:absolute;left:10px;right:10px;bottom:10px;padding:8px 10px;border-radius:10px;background:rgba(17,18,20,.78);backdrop-filter:blur(8px);color:#fff;font-size:10px;font-weight:700;opacity:0;transform:translateY(5px);transition:.22s ease}.rms-generation-image:hover .rms-generation-image-overlay{opacity:1;transform:none}.rms-generation-image-overlay b{margin-right:4px}
        .rms-generation-processing-visual{position:relative;display:grid;place-items:center;min-height:210px;overflow:hidden;border-radius:13px;background:radial-gradient(circle at 50% 30%,#fff,#f6f7fa);border:1px solid #eceef2}.rms-generation-processing-visual>img{width:100%;height:210px;object-fit:cover;filter:saturate(.75) blur(.4px);opacity:.72}.rms-processing-placeholder{width:100%;height:210px;display:grid;place-items:center;align-content:center;gap:5px;color:#1b1d21}.rms-processing-placeholder b{font-size:30px;letter-spacing:-1px}.rms-processing-placeholder small{font-size:9px;letter-spacing:.16em;color:#9297a1;font-weight:800}.rms-processing-orbit{position:absolute;width:92px;height:92px;border:2px solid #e8eaf0;border-top-color:#ef233c;border-radius:50%;animation:rmsSpin 1.4s linear infinite}.rms-processing-progress-ring{position:absolute;right:12px;top:12px;width:58px;height:58px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#ef233c var(--progress),#e7e9ee 0);box-shadow:0 8px 22px rgba(20,20,25,.12)}.rms-processing-progress-ring:after{content:' ';position:absolute;width:45px;height:45px;border-radius:50%;background:#fff}.rms-processing-progress-ring span{position:relative;z-index:1;font-size:10px;font-weight:900;color:#17181b}
        .rms-generation-eta{display:flex;align-items:center;gap:10px;margin-top:10px;padding:10px 11px;border:1px solid #e8ebf0;border-radius:12px;background:#fbfcfd}.rms-generation-eta-clock{width:29px;height:29px;display:grid;place-items:center;border-radius:9px;background:#eef7ff;color:#2677d9;font-size:15px}.rms-generation-eta div{display:flex;flex-direction:column;gap:2px}.rms-generation-eta strong{font-size:9px;color:#818792;text-transform:uppercase;letter-spacing:.08em}.rms-generation-eta b{font-size:12px;color:#17181b}
        .rms-generation-stage{display:flex;flex-direction:column;gap:7px;padding:11px 2px 2px}.rms-generation-stage-item{display:flex;align-items:center;gap:8px;color:#a0a5ad;font-size:10px}.rms-generation-stage-item i{width:7px;height:7px;border-radius:50%;background:#dfe2e7;box-shadow:0 0 0 3px #f5f6f8}.rms-generation-stage-item.active{color:#25272b;font-weight:700}.rms-generation-stage-item.active i{background:#ef233c;box-shadow:0 0 0 3px #fff0f0}
        .rms-generation-state{display:flex;align-items:center;gap:12px;min-height:170px;padding:18px;border-radius:13px}.rms-generation-state>span{width:38px;height:38px;flex:none;display:grid;place-items:center;border-radius:12px;font-size:17px;font-weight:900}.rms-generation-state div{display:flex;flex-direction:column;gap:5px}.rms-generation-state strong{font-size:13px}.rms-generation-state small{font-size:10px;line-height:1.45;color:#7c818a}.rms-generation-state-error{background:#fff7f7;border:1px solid #ffd9d9;color:#e21d2e}.rms-generation-state-error>span{background:#ffe6e7}.rms-generation-state-cancelled{background:#f7f8fa;border:1px solid #e4e6ea;color:#555b65}.rms-generation-state-cancelled>span{background:#e9ebef}
        .rms-generation-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:7px}.rms-generation-action{min-height:36px;padding:0 9px;border:1px solid #e2e4e8;border-radius:10px;background:#fff;color:#26282d;font-size:10px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:5px;cursor:pointer;transition:transform .18s ease,background .18s ease,border-color .18s ease,box-shadow .18s ease,color .18s ease}.rms-generation-action:hover{transform:translateY(-1px);border-color:#cfd2d8;background:#f8f9fa;box-shadow:0 5px 12px rgba(20,20,25,.06)}.rms-generation-action.primary{background:#ef233c;border-color:#ef233c;color:#fff;box-shadow:0 7px 18px rgba(239,35,60,.18)}.rms-generation-action.primary-light{background:#fafafa}.rms-generation-action.danger{color:#e21d2e;border-color:#ffd4d7;background:#fff8f8}.rms-generation-action.danger:hover{background:#fff0f1;border-color:#ffbcc2}.rms-generation-action.danger-icon{color:#e21d2e}.status-processing{border-color:#dceaff!important}.status-failed{border-color:#ffe0e2!important}.status-completed{border-color:#e2eee6!important}
        .rms-generation-card .rms-history-status{flex:none}.rms-generation-card .rms-history-item-meta{display:flex;align-items:center;gap:7px;min-width:0}.rms-generation-card .rms-history-item-meta span{font-size:9px;color:#90959e}.rms-generation-card .rms-history-item-meta small{font-size:9px;color:#b0b4bc}.rms-generation-card .rms-history-status{font-size:9px;padding:5px 8px;border-radius:999px}
        @keyframes rmsSpin{to{transform:rotate(360deg)}}@keyframes rmsEtaPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.06);opacity:.78}}
        @media (max-width:1180px){.rms-generator-history-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media (max-width:760px){.rms-generator-history-grid{grid-template-columns:1fr;gap:12px}.rms-generation-card{padding:12px!important;border-radius:16px!important}.rms-generation-processing-visual,.rms-generation-processing-visual>img{min-height:190px;height:190px}.rms-generation-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.rms-generator-eta-card{padding:10px}.rms-generator-eta-card small{font-size:9px}.rms-generation-card-info strong{font-size:12px}}
        @media (max-width:480px){.rms-generation-actions{grid-template-columns:1fr 1fr}.rms-generation-action{font-size:9px}.rms-generation-state{min-height:145px}.rms-processing-progress-ring{width:52px;height:52px}.rms-processing-progress-ring:after{width:40px;height:40px}}

        /* ============================================================
           RECENT GENERATION ACTIONS — PREMIUM V2
           ============================================================ */
        .rms-generation-actions-v2{
            display:grid!important;
            grid-template-columns:minmax(0,1.35fr) minmax(0,1.15fr) 48px;
            gap:8px;
            align-items:stretch;
            margin-top:2px;
        }
        .rms-generation-actions-v2 .rms-generation-action{
            position:relative;
            min-width:0;
            min-height:50px;
            padding:8px 10px;
            border:1px solid #e4e6eb;
            border-radius:13px;
            background:#fff;
            color:#202228;
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:9px;
            font-size:10px;
            font-weight:800;
            line-height:1.1;
            cursor:pointer;
            overflow:hidden;
            transition:transform .22s cubic-bezier(.2,.7,.2,1),box-shadow .22s ease,border-color .22s ease,background .22s ease,color .22s ease;
        }
        .rms-generation-actions-v2 .rms-generation-action::before{
            content:"";
            position:absolute;
            inset:0;
            pointer-events:none;
            opacity:0;
            background:linear-gradient(115deg,rgba(255,255,255,0),rgba(255,255,255,.24),rgba(255,255,255,0));
            transform:translateX(-100%);
        }
        .rms-generation-actions-v2 .rms-generation-action:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 22px rgba(20,20,25,.10);
        }
        .rms-generation-actions-v2 .rms-generation-action:hover::before{
            opacity:1;
            animation:rmsActionShine .65s ease;
        }
        .rms-generation-actions-v2 .rms-generation-action:active{transform:translateY(0) scale(.985)}
        .rms-generation-actions-v2 .rms-generation-action:focus-visible{
            outline:3px solid rgba(239,35,60,.16);
            outline-offset:2px;
        }
        .rms-action-icon{
            width:32px;
            height:32px;
            flex:0 0 32px;
            display:grid;
            place-items:center;
            border-radius:10px;
            background:#f5f6f8;
            color:#4e535c;
            transition:transform .22s ease,background .22s ease,color .22s ease;
        }
        .rms-action-icon svg,
        .rms-action-arrow,
        .rms-action-delete>svg{
            width:16px;
            height:16px;
            fill:none;
            stroke:currentColor;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .rms-action-copy{
            min-width:0;
            display:flex;
            flex:1;
            flex-direction:column;
            align-items:flex-start;
            gap:3px;
            text-align:left;
        }
        .rms-action-copy strong{
            font-size:10px;
            font-weight:900;
            color:inherit;
            white-space:nowrap;
        }
        .rms-action-copy small{
            max-width:100%;
            overflow:hidden;
            color:#9297a0;
            font-size:8px;
            font-weight:600;
            line-height:1.2;
            white-space:nowrap;
            text-overflow:ellipsis;
        }
        .rms-action-arrow{
            width:14px!important;
            height:14px!important;
            flex:none;
            color:#a0a5ad;
            transition:transform .22s ease,color .22s ease;
        }
        .rms-generation-actions-v2 .rms-action-preview{
            border-color:#ef233c;
            background:linear-gradient(135deg,#ef233c,#ff334b);
            color:#fff;
            box-shadow:0 8px 20px rgba(239,35,60,.20);
        }
        .rms-generation-actions-v2 .rms-action-preview .rms-action-icon{
            background:rgba(255,255,255,.17);
            color:#fff;
        }
        .rms-generation-actions-v2 .rms-action-preview .rms-action-copy small,
        .rms-generation-actions-v2 .rms-action-preview .rms-action-arrow{color:rgba(255,255,255,.72)}
        .rms-generation-actions-v2 .rms-action-preview:hover{
            border-color:#df1931;
            box-shadow:0 13px 28px rgba(239,35,60,.28);
        }
        .rms-generation-actions-v2 .rms-action-preview:hover .rms-action-icon{
            background:rgba(255,255,255,.24);
            transform:scale(1.06);
        }
        .rms-generation-actions-v2 .rms-action-preview:hover .rms-action-arrow{
            color:#fff;
            transform:translateX(3px);
        }
        .rms-generation-actions-v2 .rms-action-retry{
            border-color:#e3e5e9;
            background:#fff;
        }
        .rms-generation-actions-v2 .rms-action-retry:hover{
            border-color:#cfd3da;
            background:#fafbfc;
        }
        .rms-generation-actions-v2 .rms-action-retry:hover .rms-action-icon{
            background:#fff0f1;
            color:#ef233c;
            transform:rotate(-12deg) scale(1.05);
        }
        .rms-generation-actions-v2 .rms-action-retry-primary{
            border-color:#ef233c;
            background:#fff5f6;
            color:#df1d32;
        }
        .rms-generation-actions-v2 .rms-action-retry-primary .rms-action-icon{
            background:#ffe5e8;
            color:#ef233c;
        }
        .rms-generation-actions-v2 .rms-action-cancel{
            border-color:#ffd5d9;
            background:#fff7f8;
            color:#d91f33;
        }
        .rms-generation-actions-v2 .rms-action-cancel .rms-action-icon{
            background:#ffe8eb;
            color:#ef233c;
        }
        .rms-generation-actions-v2 .rms-action-cancel:hover{
            border-color:#ffbfc5;
            background:#fff1f3;
        }
        .rms-generation-actions-v2 .rms-action-delete{
            min-width:48px;
            width:48px;
            padding:0;
            justify-content:center;
            border-color:#f0dfe1;
            background:#fffafb;
            color:#c64a56;
        }
        .rms-generation-actions-v2 .rms-action-delete>svg{width:17px;height:17px}
        .rms-generation-actions-v2 .rms-action-delete:hover{
            border-color:#ef233c;
            background:#fff0f2;
            color:#ef233c;
            box-shadow:0 10px 22px rgba(239,35,60,.12);
        }
        .rms-generation-actions-v2 .rms-action-delete-wide{
            width:auto;
            min-width:150px;
            padding:8px 14px;
            justify-content:flex-start;
        }
        .rms-generation-actions-v2 .rms-action-delete-wide .rms-action-copy strong{color:#c52c3d}
        .rms-generation-actions-v2 .rms-action-delete-wide>svg{flex:0 0 18px}
        @keyframes rmsActionShine{
            from{transform:translateX(-100%)}
            to{transform:translateX(100%)}
        }
        @media (max-width:900px){
            .rms-generation-actions-v2{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 46px}
        }
        @media (max-width:620px){
            .rms-generation-actions-v2{grid-template-columns:1fr 1fr}
            .rms-generation-actions-v2 .rms-action-delete{
                grid-column:1 / -1;
                width:100%;
                min-width:0;
                min-height:44px;
            }
            .rms-generation-actions-v2 .rms-action-delete-wide{width:100%}
        }
        @media (max-width:420px){
            .rms-generation-actions-v2{grid-template-columns:1fr}
            .rms-generation-actions-v2 .rms-generation-action,
            .rms-generation-actions-v2 .rms-action-delete,
            .rms-generation-actions-v2 .rms-action-delete-wide{
                width:100%;
                min-width:0;
            }
        }
/* ============================================================
   CUSTOM PRODUCT TITLE — PREMIUM TOGGLE + SMOOTH REVEAL
   ============================================================ */
.rms-custom-title-card{
    position:relative;
    margin-top:10px;
    overflow:hidden;
    border:1px solid #e7e8ec;
    border-radius:16px;
    background:linear-gradient(180deg,#fff 0%,#fcfcfd 100%);
    box-shadow:0 5px 18px rgba(25,25,30,.035);
    transition:
        border-color .28s ease,
        box-shadow .32s ease,
        background .32s ease;
}
.rms-custom-title-card.is-active{
    border-color:#ffc4ca;
    background:linear-gradient(180deg,#fff 0%,#fffafb 100%);
    box-shadow:0 10px 28px rgba(239,35,60,.075);
}
.rms-custom-title-head{
    min-height:66px;
    padding:12px 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
}
.rms-custom-title-identity{
    min-width:0;
    display:flex;
    align-items:center;
    gap:11px;
}
.rms-custom-title-icon{
    width:38px;
    height:38px;
    flex:none;
    display:grid;
    place-items:center;
    border:1px solid #ececef;
    border-radius:12px;
    background:#f7f7f8;
    color:#777b84;
    transition:
        color .28s ease,
        background .28s ease,
        border-color .28s ease,
        transform .35s cubic-bezier(.22,1,.36,1);
}
.rms-custom-title-card.is-active .rms-custom-title-icon{
    color:#ef233c;
    border-color:#ffd4d8;
    background:#fff0f2;
    transform:scale(1.04);
}
.rms-custom-title-icon svg{
    width:17px;
    height:17px;
    fill:none;
    stroke:currentColor;
    stroke-width:1.8;
    stroke-linecap:round;
    stroke-linejoin:round;
}
.rms-custom-title-label{
    display:flex;
    align-items:center;
    gap:7px;
}
.rms-custom-title-label strong{
    color:#202126;
    font-size:11px;
    line-height:1.25;
    font-weight:850;
}
.rms-custom-title-label span{
    padding:3px 6px;
    border-radius:999px;
    background:#f3f4f6;
    color:#9699a1;
    font-size:7px;
    line-height:1;
    font-weight:850;
    letter-spacing:.08em;
}
.rms-custom-title-card.is-active .rms-custom-title-label span{
    background:#fff0f2;
    color:#ef233c;
}
.rms-custom-title-identity small{
    display:block;
    max-width:620px;
    margin-top:3px;
    color:#9699a1;
    font-size:9px;
    line-height:1.45;
}
.rms-custom-title-switch{
    flex:none;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:4px 5px 4px 4px;
    border:1px solid #e5e6e9;
    border-radius:999px;
    background:#f7f7f8;
    color:#989aa1;
    cursor:pointer;
    transition:
        background .28s ease,
        border-color .28s ease,
        color .28s ease,
        box-shadow .28s ease;
}
.rms-custom-title-switch:hover{
    border-color:#d7d9de;
    background:#f3f4f5;
}
.rms-custom-title-switch.is-on{
    border-color:#ffb7bf;
    background:#fff0f2;
    color:#ef233c;
    box-shadow:0 4px 14px rgba(239,35,60,.08);
}
.rms-custom-title-switch-track{
    position:relative;
    width:34px;
    height:19px;
    display:block;
    border-radius:999px;
    background:#d9dadd;
    transition:background .28s ease;
}
.rms-custom-title-switch.is-on .rms-custom-title-switch-track{
    background:#ef233c;
}
.rms-custom-title-switch-knob{
    position:absolute;
    top:2px;
    left:2px;
    width:15px;
    height:15px;
    border-radius:50%;
    background:#fff;
    box-shadow:0 2px 5px rgba(0,0,0,.16);
    transition:transform .38s cubic-bezier(.22,1,.36,1);
}
.rms-custom-title-switch.is-on .rms-custom-title-switch-knob{
    transform:translateX(15px);
}
.rms-custom-title-switch-state{
    min-width:21px;
    font-size:8px;
    font-weight:850;
    letter-spacing:.05em;
    text-align:center;
}
.rms-custom-title-body{
    padding:0 14px 14px;
}
.rms-custom-title-input-wrap{
    padding:12px;
    border:1px solid #eeeef1;
    border-radius:13px;
    background:#fff;
}
.rms-custom-title-input-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:7px;
}
.rms-custom-title-input-head label{
    color:#35363b;
    font-size:9px;
    font-weight:800;
}
.rms-custom-title-input-head span{
    color:#a0a2a8;
    font-size:8px;
    font-weight:700;
}
.rms-custom-title-input-shell{
    min-height:43px;
    display:flex;
    align-items:center;
    gap:8px;
    padding:0 10px;
    border:1px solid #dedfe4;
    border-radius:11px;
    background:#fff;
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}
.rms-custom-title-input-shell:focus-within{
    border-color:#ef233c;
    box-shadow:0 0 0 3px rgba(239,35,60,.09);
    transform:translateY(-1px);
}
.rms-custom-title-input-prefix{
    width:25px;
    height:25px;
    display:grid;
    place-items:center;
    flex:none;
    border-radius:7px;
    background:#fff0f2;
    color:#ef233c;
    font-size:9px;
    font-weight:850;
}
.rms-custom-title-input-shell input{
    min-width:0;
    flex:1;
    height:40px;
    padding:0;
    border:0;
    outline:0;
    background:transparent;
    color:#202126;
    font-size:11px;
    font-weight:650;
}
.rms-custom-title-input-shell input::placeholder{
    color:#b1b3b9;
    font-weight:500;
}
.rms-custom-title-input-status{
    flex:none;
    padding:4px 7px;
    border-radius:999px;
    background:#f5f5f6;
    color:#858890;
    font-size:7px;
    font-weight:850;
    letter-spacing:.06em;
}
.rms-custom-title-hint{
    margin-top:7px;
    display:flex;
    align-items:flex-start;
    gap:6px;
    color:#92959d;
    font-size:8px;
    line-height:1.45;
}
.rms-custom-title-hint > span:first-child{
    color:#19a765;
    font-weight:900;
}
.rms-custom-title-error{
    margin-top:7px;
    color:#d92335;
    font-size:8px;
    font-weight:700;
}
.rms-custom-title-body[style*="display: none"]{
    display:none !important;
}
@media (max-width:700px){
    .rms-custom-title-head{
        align-items:flex-start;
    }
    .rms-custom-title-identity small{
        max-width:100%;
    }
    .rms-custom-title-switch-state{
        display:none;
    }
}



/* ============================================================
   RMS GENERATOR — PREMIUM WORKSPACE V4
   UX layer: context, metadata hierarchy, processing states,
   responsive behavior and motion.
   ============================================================ */

.rms-generator-context-strip{
    display:grid;
    grid-template-columns:minmax(230px,.72fr) minmax(0,1.9fr);
    gap:14px;
    align-items:center;
    margin:0 0 18px;
    padding:13px 14px;
    border:1px solid rgba(232,233,237,.95);
    border-radius:18px;
    background:linear-gradient(110deg,#fff 0%,#fbfbfc 58%,#fff 100%);
    box-shadow:0 12px 34px rgba(20,22,28,.055);
}
.rms-generator-context-intro{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
}
.rms-context-spark{
    display:grid;
    place-items:center;
    width:35px;
    height:35px;
    flex:0 0 35px;
    border:1px solid rgba(239,35,60,.13);
    border-radius:11px;
    background:#fff5f6;
    color:#ef233c;
    font-size:13px;
    box-shadow:0 7px 18px rgba(239,35,60,.08);
}
.rms-generator-context-intro div{
    display:grid;
    gap:2px;
    min-width:0;
}
.rms-generator-context-intro strong{
    color:#17181b;
    font-size:10px;
    font-weight:900;
    letter-spacing:-.015em;
}
.rms-generator-context-intro small{
    color:#9a9da5;
    font-size:7px;
    line-height:1.35;
}
.rms-generator-context-items{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto minmax(0,1fr) auto minmax(0,1.2fr);
    align-items:center;
    gap:8px;
    min-width:0;
}
.rms-context-item{
    min-width:0;
    min-height:48px;
    display:flex;
    align-items:center;
    gap:9px;
    padding:7px 9px;
    border:1px solid #eceef1;
    border-radius:12px;
    background:#fafbfc;
    transition:border-color .25s ease,background .25s ease,transform .25s cubic-bezier(.22,1,.36,1),box-shadow .25s ease;
}
.rms-context-item.is-ready{
    border-color:#e4e6ea;
    background:#fff;
}
.rms-context-item:hover{
    transform:translateY(-2px);
    border-color:#d8dbe0;
    box-shadow:0 8px 20px rgba(20,22,28,.055);
}
.rms-context-icon{
    width:31px;
    height:31px;
    flex:0 0 31px;
    display:grid;
    place-items:center;
    overflow:hidden;
    border:1px solid #e8e9ed;
    border-radius:9px;
    background:#fff;
    color:#71757e;
    font-size:8px;
    font-weight:900;
}
.rms-context-icon img{
    width:100%;
    height:100%;
    object-fit:contain;
    padding:3px;
}
.rms-context-template-icon{
    color:#ef233c;
    background:#fff5f6;
    border-color:#ffe0e4;
}
.rms-context-reference-icon{
    color:#fff;
    background:#18181b;
    border-color:#18181b;
    font-size:7px;
}
.rms-context-item div{
    display:grid;
    gap:2px;
    min-width:0;
}
.rms-context-item small{
    color:#a1a1aa;
    font-size:5.8px;
    line-height:1;
    letter-spacing:.12em;
    font-weight:950;
}
.rms-context-item strong{
    min-width:0;
    overflow:hidden;
    color:#303137;
    font-size:7.7px;
    line-height:1.25;
    font-weight:850;
    white-space:nowrap;
    text-overflow:ellipsis;
}
.rms-context-arrow{
    color:#c5c7cc;
    font-size:12px;
    font-weight:700;
    transition:transform .3s ease,color .3s ease;
}
.rms-context-items:hover .rms-context-arrow{
    color:#ef233c;
    transform:translateX(1px);
}

/* Better hierarchy for Store / Template / Reference cards */
.rms-generator-selection .rms-generator-store-trigger{
    min-height:66px;
    border-radius:15px;
    background:linear-gradient(135deg,#fff,#fafbfc);
}
.rms-generator-selection .rms-generator-store-copy strong{
    font-size:10px;
    font-weight:900;
}
.rms-generator-selection .rms-generator-store-copy small{
    font-size:7px;
}
.rms-generator-selection .rms-generator-template-heading{
    margin-top:22px;
}
.rms-generator-selection .rms-generator-template-grid{
    scrollbar-width:thin;
}
.rms-generator-selection .rms-generator-template-card{
    border-radius:15px;
    background:#fff;
}
.rms-generator-selection .rms-generator-template-card.selected{
    border-color:rgba(239,35,60,.48);
    box-shadow:0 0 0 3px rgba(239,35,60,.07),0 16px 28px rgba(20,22,28,.08);
}
.rms-generator-selection .rms-generator-template-card.selected .rms-generator-template-copy strong{
    color:#ef233c;
}
.rms-generator-template-copy small{
    display:inline-flex;
    align-items:center;
    width:max-content;
    margin-top:4px;
    padding:3px 6px;
    border:1px solid #eceef1;
    border-radius:999px;
    background:#fafafa;
    color:#858992;
    font-size:6px;
    font-weight:800;
}

/* Upload area: make the hierarchy feel like an asset workstation */
.rms-generator-upload .rms-generator-upload-grid-v2{
    gap:12px;
}
.rms-generator-upload .rms-generator-upload-box{
    border-radius:17px;
    background:linear-gradient(145deg,#fff 0%,#fbfbfc 100%);
}
.rms-generator-upload .rms-upload-primary{
    border-color:#f0cfd4;
    box-shadow:inset 0 0 0 1px rgba(239,35,60,.025);
}
.rms-generator-upload .rms-upload-reference{
    border-color:#e8e9ed;
}
.rms-generator-upload .rms-upload-label-row{
    min-height:70px;
}
.rms-generator-upload .rms-upload-number{
    box-shadow:0 6px 14px rgba(20,22,28,.07);
}
.rms-generator-upload .rms-upload-badge{
    letter-spacing:.08em;
}
.rms-generator-upload .rms-generator-upload-placeholder{
    min-height:275px;
}
.rms-generator-upload .rms-generator-upload-icon{
    width:58px;
    height:58px;
    border-radius:16px;
    box-shadow:0 10px 24px rgba(20,22,28,.06);
}
.rms-generator-upload .rms-generator-upload-button{
    min-height:46px;
    border-radius:13px;
    font-weight:900;
}
.rms-generator-upload .rms-reference-mode{
    margin-top:12px;
    border-radius:15px;
    background:linear-gradient(100deg,#fff,#fafafa);
}
.rms-generator-upload .rms-custom-title-card{
    border-radius:15px;
}

/* Reference board */
.rms-generator-workspace-animated .rms-flow{
    border-radius:17px;
    background:linear-gradient(135deg,#18181b,#242427);
    box-shadow:0 18px 36px rgba(20,20,24,.15);
}
.rms-generator-workspace-animated .rms-flow-item{
    min-height:58px;
    border-color:rgba(255,255,255,.10);
    background:rgba(255,255,255,.055);
    backdrop-filter:blur(8px);
}
.rms-generator-workspace-animated .rms-flow-item.ready{
    border-color:rgba(239,35,60,.36);
    box-shadow:0 7px 20px rgba(0,0,0,.12);
}
.rms-generator-workspace-animated .rms-flow-item .rms-flow-label{
    font-weight:900;
}

/* Settings becomes a clear control surface */
.rms-generator-settings{
    border-radius:18px;
    background:linear-gradient(145deg,#fff,#fbfbfc);
}
.rms-generator-settings-grid{
    gap:10px;
}
.rms-custom-select{
    min-width:0;
}
.rms-custom-select-label{
    letter-spacing:.09em;
}
.rms-custom-select-trigger{
    min-height:62px;
    border-radius:14px;
    background:#fff;
}
.rms-custom-select.full .rms-custom-select-trigger{
    min-height:58px;
}
.rms-generator-generate{
    min-height:68px;
    border-radius:17px;
    box-shadow:0 16px 30px rgba(239,35,60,.18);
}
.rms-generator-eta-card{
    border-radius:15px;
    background:linear-gradient(100deg,#fff,#fafbfc);
}

/* Recent generations */
.rms-generator-history{
    position:relative;
}
.rms-generator-history-grid{
    gap:14px;
}
.rms-generator-history-item{
    position:relative;
    overflow:hidden;
    border-radius:18px;
    border-color:#e7e9ed;
    background:#fff;
    box-shadow:0 12px 34px rgba(20,22,28,.055);
    transition:transform .35s cubic-bezier(.22,1,.36,1),box-shadow .35s ease,border-color .3s ease;
}
.rms-generator-history-item:hover{
    transform:translateY(-4px);
    border-color:#dfe1e5;
    box-shadow:0 20px 44px rgba(20,22,28,.09);
}
.rms-generator-history-item.status-processing,
.rms-generator-history-item.status-queued{
    box-shadow:0 16px 38px rgba(239,35,60,.075);
}
.rms-generation-card-head{
    padding-bottom:9px;
}
.rms-history-status{
    box-shadow:0 5px 14px rgba(20,22,28,.05);
}
.rms-generation-card-info-v3{
    display:grid;
    gap:10px;
    padding-top:13px;
}
.rms-generation-title-block{
    min-width:0;
    display:grid;
    gap:5px;
}
.rms-generation-title-block > strong{
    display:block;
    overflow:hidden;
    color:#17181b;
    font-size:13px;
    line-height:1.25;
    font-weight:950;
    letter-spacing:-.025em;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.rms-generation-template-line{
    display:flex!important;
    align-items:center;
    gap:5px;
    min-width:0;
    color:#777b84!important;
    font-size:7.5px!important;
    line-height:1.35!important;
}
.rms-generation-template-line > span:not(.rms-meta-label){
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.rms-meta-label{
    display:inline-flex;
    align-items:center;
    height:16px;
    padding:0 5px;
    border-radius:5px;
    background:#f5f5f6;
    color:#8b8e95;
    font-size:5.5px;
    font-weight:950;
    letter-spacing:.1em;
}
.rms-meta-separator{
    color:#c4c6ca;
}
.rms-generation-meta-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:6px;
}
.rms-generation-meta-chip{
    min-width:0;
    display:flex;
    align-items:center;
    gap:5px;
    min-height:27px;
    padding:4px 6px;
    border:1px solid #eceef1;
    border-radius:8px;
    background:#fafbfc;
}
.rms-generation-meta-chip i{
    display:grid;
    place-items:center;
    width:17px;
    height:17px;
    flex:0 0 17px;
    border-radius:5px;
    background:#fff;
    color:#a0a3aa;
    font-size:5px;
    font-style:normal;
    font-weight:950;
    box-shadow:0 2px 5px rgba(20,22,28,.04);
}
.rms-generation-meta-chip b{
    min-width:0;
    overflow:hidden;
    color:#555961;
    font-size:6.5px;
    font-weight:850;
    text-overflow:ellipsis;
    white-space:nowrap;
}

/* Processing visual */
.rms-generation-processing-visual{
    position:relative;
    overflow:hidden;
    isolation:isolate;
    border:1px solid #e8eaee;
    border-radius:15px;
    background:
        radial-gradient(circle at 50% 50%,rgba(239,35,60,.055),transparent 32%),
        linear-gradient(145deg,#fbfbfc,#f4f5f7);
}
.rms-generation-processing-visual::before{
    content:"";
    position:absolute;
    z-index:2;
    inset:0;
    pointer-events:none;
    background:linear-gradient(110deg,transparent 18%,rgba(255,255,255,.65) 48%,transparent 72%);
    transform:translateX(-120%);
    animation:rmsProcessingSweep 2.4s ease-in-out infinite;
}
@keyframes rmsProcessingSweep{
    0%,20%{transform:translateX(-120%)}
    70%,100%{transform:translateX(120%)}
}
.rms-processing-placeholder{
    position:relative;
    display:grid;
    place-items:center;
    min-height:265px;
    overflow:hidden;
}
.rms-processing-placeholder::before,
.rms-processing-placeholder::after{
    content:"";
    position:absolute;
    width:150px;
    height:150px;
    border:1px solid rgba(239,35,60,.10);
    border-radius:50%;
    animation:rmsProcessingPulse 2.1s ease-in-out infinite;
}
.rms-processing-placeholder::after{
    width:210px;
    height:210px;
    animation-delay:.35s;
}
@keyframes rmsProcessingPulse{
    0%,100%{transform:scale(.82);opacity:.18}
    50%{transform:scale(1.05);opacity:.65}
}
.rms-processing-orbit{
    position:absolute;
    width:92px;
    height:92px;
    border:2px solid #e3e5e9;
    border-top-color:#ef233c;
    border-right-color:rgba(239,35,60,.35);
    border-radius:50%;
    animation:rmsProcessingOrbit 1.1s linear infinite;
    box-shadow:0 0 0 9px rgba(239,35,60,.025),0 12px 35px rgba(20,22,28,.06);
}
.rms-processing-orbit::after{
    content:"";
    position:absolute;
    top:7px;
    left:50%;
    width:8px;
    height:8px;
    margin-left:-4px;
    border-radius:50%;
    background:#ef233c;
    box-shadow:0 0 14px rgba(239,35,60,.55);
}
@keyframes rmsProcessingOrbit{
    to{transform:rotate(360deg)}
}
.rms-processing-placeholder b{
    position:relative;
    z-index:3;
    color:#202126;
    font-size:29px;
    line-height:1;
    font-weight:950;
    letter-spacing:-.04em;
}
.rms-processing-placeholder small{
    position:absolute;
    z-index:3;
    top:calc(50% + 31px);
    color:#92969e;
    font-size:6px;
    font-weight:950;
    letter-spacing:.16em;
}
.rms-processing-progress-ring{
    position:absolute;
    z-index:4;
    top:12px;
    right:12px;
    display:grid;
    place-items:center;
    width:62px;
    height:62px;
    border-radius:50%;
    background:conic-gradient(#ef233c var(--progress),#e5e7eb 0);
    box-shadow:0 9px 24px rgba(20,22,28,.10);
}
.rms-processing-progress-ring::before{
    content:"";
    position:absolute;
    inset:5px;
    border-radius:50%;
    background:#fff;
}
.rms-processing-progress-ring span{
    position:relative;
    z-index:1;
    color:#292b30;
    font-size:9px;
    font-weight:950;
}
.status-processing .rms-processing-progress-ring{
    animation:rmsRingFloat 2.2s ease-in-out infinite;
}
@keyframes rmsRingFloat{
    0%,100%{transform:translateY(0)}
    50%{transform:translateY(-3px)}
}
.rms-generation-eta-v3{
    position:relative;
    overflow:hidden;
    border-radius:13px;
    background:linear-gradient(110deg,#f8fafc,#fff);
}
.rms-generation-eta-v3::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(100deg,transparent,rgba(239,35,60,.045),transparent);
    transform:translateX(-110%);
    animation:rmsEtaSweepV4 3.4s ease-in-out infinite;
}
@keyframes rmsEtaSweepV4{
    0%,45%{transform:translateX(-110%)}
    75%,100%{transform:translateX(110%)}
}
.rms-generation-eta-v3 > *{
    position:relative;
    z-index:1;
}
.rms-generation-eta-v3 b{
    font-size:10px;
    letter-spacing:-.01em;
}
.rms-generation-eta-v3 small{
    display:block;
    margin-top:3px;
    color:#a0a3aa;
    font-size:6px;
    line-height:1.35;
}
.rms-generation-stage{
    position:relative;
    gap:7px;
}
.rms-generation-stage-item{
    transition:color .3s ease,opacity .3s ease,transform .3s ease;
}
.rms-generation-stage-item.active{
    transform:translateX(2px);
}
.rms-generation-stage-item.active i{
    animation:rmsStagePulse 1.5s ease-in-out infinite;
}
@keyframes rmsStagePulse{
    0%,100%{box-shadow:0 0 0 0 rgba(239,35,60,0)}
    50%{box-shadow:0 0 0 5px rgba(239,35,60,.10)}
}

/* Action hierarchy */
.rms-generation-actions-v2{
    gap:7px;
}
.rms-generation-action{
    min-height:45px;
    border-radius:12px;
    transition:transform .25s cubic-bezier(.22,1,.36,1),box-shadow .25s ease,border-color .2s ease;
}
.rms-generation-action:hover{
    transform:translateY(-2px);
    box-shadow:0 9px 20px rgba(20,22,28,.07);
}
.rms-action-preview{
    box-shadow:0 10px 22px rgba(239,35,60,.12);
}

/* Responsive */
@media(max-width:1100px){
    .rms-generator-context-strip{
        grid-template-columns:1fr;
    }
    .rms-generator-context-items{
        grid-template-columns:minmax(0,1fr) auto minmax(0,1fr) auto minmax(0,1fr);
    }
}
@media(max-width:820px){
    .rms-generator-context-strip{
        padding:11px;
    }
    .rms-generator-context-intro{
        padding:2px 2px 4px;
    }
    .rms-generator-context-items{
        grid-template-columns:1fr 1fr;
        gap:7px;
    }
    .rms-context-arrow{
        display:none;
    }
    .rms-context-item:last-child{
        grid-column:1/-1;
    }
    .rms-generator-selection .rms-generator-template-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .rms-generator-upload-grid-v2{
        grid-template-columns:1fr!important;
    }
    .rms-generator-settings-grid{
        grid-template-columns:1fr 1fr!important;
    }
    .rms-custom-select.full{
        grid-column:1/-1;
    }
    .rms-generation-meta-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}
@media(max-width:620px){
    .rms-generator-context-items{
        grid-template-columns:1fr;
    }
    .rms-context-item:last-child{
        grid-column:auto;
    }
    .rms-generator-selection .rms-generator-template-grid{
        grid-template-columns:1fr 1fr;
        gap:8px;
    }
    .rms-generator-upload .rms-generator-card-head{
        align-items:flex-start;
        gap:9px;
    }
    .rms-generator-upload .rms-upload-label-row{
        min-height:62px;
    }
    .rms-generator-upload .rms-generator-upload-placeholder{
        min-height:235px;
    }
    .rms-generator-settings-grid{
        grid-template-columns:1fr!important;
    }
    .rms-custom-select.full{
        grid-column:auto;
    }
    .rms-generator-generate{
        min-height:62px;
    }
    .rms-generation-card-info-v3{
        gap:8px;
    }
    .rms-generation-title-block > strong{
        font-size:11px;
    }
    .rms-generation-meta-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .rms-generation-processing-visual{
        min-height:0;
    }
    .rms-processing-placeholder{
        min-height:220px;
    }
    .rms-processing-progress-ring{
        width:55px;
        height:55px;
        top:9px;
        right:9px;
    }
    .rms-processing-placeholder b{
        font-size:25px;
    }
    .rms-generation-actions-v2{
        grid-template-columns:1fr 1fr!important;
    }
    .rms-generation-actions-v2 .rms-action-delete{
        grid-column:1/-1;
        width:100%;
    }
}
@media(max-width:390px){
    .rms-generator-selection .rms-generator-template-grid{
        grid-template-columns:1fr;
    }
    .rms-generation-meta-grid{
        grid-template-columns:1fr 1fr;
    }
    .rms-generation-meta-chip{
        min-height:25px;
    }
    .rms-generation-meta-chip b{
        font-size:6px;
    }
}
@media(prefers-reduced-motion:reduce){
    .rms-generator-context-strip *,
    .rms-generator-history-item *,
    .rms-processing-placeholder *,
    .rms-generation-processing-visual::before,
    .rms-generation-eta-v3::after{
        animation:none!important;
        transition:none!important;
    }
}
</style>

    {{-- ============================================================
     CUSTOM GENERATED IMAGE PREVIEW
     Simplified Alpine modal: no x-teleport / x-if nesting.
     ============================================================ --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="previewOpen"
            x-transition.opacity.duration.200ms
            class="rms-generator-preview-modal rms-generator-preview-modal-custom"
            role="dialog"
            aria-modal="true"
            aria-label="Generated image preview"
            x-on:keydown.escape.window="closePreview()"
            x-on:click="closePreview()"
        >
            <div
                class="rms-preview-backdrop"
                x-on:click="closePreview()"
                aria-hidden="true"
            ></div>

            <div
                class="rms-preview-dialog rms-preview-dialog-custom"
                x-on:click.stop
            >
                <button
                    type="button"
                    class="rms-preview-close"
                    aria-label="Tutup preview"
                    title="Tutup preview"
                    x-on:click.prevent.stop="closePreview()"
                >
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-linecap="round"/>
                    </svg>
                </button>

            <div class="rms-preview-media rms-preview-media-custom">
                <div
                    class="rms-preview-empty"
                    x-show="!previewImage || !previewImage.url || previewImageError"
                >
                    <div class="rms-preview-empty-icon">
                        <span>⌁</span>
                    </div>
                    <strong>Preview tidak tersedia</strong>
                    <small>Gambar belum dapat dimuat atau belum dipilih.</small>
                </div>

                <img
                    x-show="previewImage && previewImage.url && !previewImageError"
                    x-transition.opacity
                    :src="previewImage ? previewImage.url : ''"
                    alt="Generated image preview"
                    class="rms-preview-image"
                    x-on:error="handlePreviewImageError()"
                >
            </div>

            <aside class="rms-preview-sidebar rms-preview-sidebar-custom">
                <div class="rms-preview-sidebar-header">
                    <div>
                        <span class="rms-preview-kicker">GENERATED RESULT</span>

                        <h3
                            x-text="previewImage && previewImage.title
                                ? previewImage.title
                                : (previewImage && previewImage.template
                                    ? previewImage.template
                                    : 'Generated Image')"
                        ></h3>

                        <p
                            x-text="previewImage && previewImage.store
                                ? previewImage.store
                                : 'Store'"
                        ></p>
                    </div>

                    <button
                        type="button"
                        class="rms-preview-mobile-close"
                        aria-label="Tutup preview"
                        x-on:click.prevent.stop="closePreview()"
                    >
                        ×
                    </button>
                </div>

                <div class="rms-preview-specs">
                    <div class="rms-preview-spec">
                        <span>FORMAT</span>
                        <strong
                            x-text="previewImage && previewImage.format
                                ? previewImage.format
                                : '—'"
                        ></strong>
                    </div>

                    <div class="rms-preview-spec">
                        <span>RESOLUTION</span>
                        <strong
                            x-text="previewImage && previewImage.width && previewImage.height
                                ? `${previewImage.width} × ${previewImage.height}`
                                : 'Original'"
                        ></strong>
                    </div>
                </div>

                <div class="rms-preview-download">
                    <div class="rms-preview-download-header">
                        <strong>Download</strong>
                        <span>Default: ≤ 2 MB</span>
                    </div>

                    <a
                        class="rms-preview-download-primary"
                        :href="previewImage && previewImage.id ? downloadUrl(2, 85, 'jpg') : '#'"
                        :class="{ 'is-disabled': !previewImage || !previewImage.id }"
                        x-on:click="if (!previewImage || !previewImage.id) $event.preventDefault()"
                    >
                        <span>↓</span>
                        <strong>Optimized · ≤ 2 MB</strong>
                    </a>

                    <div class="rms-preview-download-options">
                        <a
                            :href="previewImage && previewImage.id ? downloadUrl(2, 90, 'jpg') : '#'"
                            x-on:click="if (!previewImage || !previewImage.id) $event.preventDefault()"
                        >
                            <b>JPG</b>
                            <span>High · ≤ 2 MB</span>
                            <i>→</i>
                        </a>

                        <a
                            :href="previewImage && previewImage.id ? downloadUrl(2, 75, 'jpg') : '#'"
                            x-on:click="if (!previewImage || !previewImage.id) $event.preventDefault()"
                        >
                            <b>JPG</b>
                            <span>Balanced · ≤ 2 MB</span>
                            <i>→</i>
                        </a>

                        <a
                            :href="previewImage && previewImage.id ? downloadUrl(3, 90, 'jpg') : '#'"
                            x-on:click="if (!previewImage || !previewImage.id) $event.preventDefault()"
                        >
                            <b>JPG</b>
                            <span>High · ≤ 3 MB</span>
                            <i>→</i>
                        </a>

                        <a
                            :href="previewImage && previewImage.id ? downloadUrl(1, 78, 'webp') : '#'"
                            x-on:click="if (!previewImage || !previewImage.id) $event.preventDefault()"
                        >
                            <b>WEBP</b>
                            <span>Compact · ≤ 1 MB</span>
                            <i>→</i>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
        </div>
    </template>
</div>
