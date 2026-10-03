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
                // Hanya sembunyikan generation yang secara eksplisit metadata.hidden = true.
                // JSON key yang tidak ada tetap harus tampil.
                $q->whereNull('metadata')
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.hidden') IS NULL")
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.hidden') <> true");
            })
            ->when(trim($this->historySearch) !== '', function ($q) {
                $keyword = '%' . trim($this->historySearch) . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->whereHas('store', fn ($store) => $store->where('name', 'like', $keyword))
                        ->orWhereHas('template', fn ($template) => $template->where('name', 'like', $keyword))
                        ->orWhere('id', 'like', $keyword);
                });
            })
            ->latest()
            ->limit(12)
            ->get();
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
            return app(\App\Services\OpenAiService::class)->availableImageModels();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function mount(): void
    {
        $settings = \App\Models\OpenAiSetting::query()->first();

        $this->model = $settings?->model ?: '';
        $this->aspectRatio = $settings?->default_aspect_ratio ?: '1:1';
        $this->quality = $settings?->default_quality ?: 'high';

        $latest = Generation::query()
            ->whereIn('status', ['completed', 'success', 'succeeded'])
            ->latest()
            ->value('id');

        $this->latestGenerationId = $latest ? (int) $latest : null;

        if ($this->model === '' && ! empty($this->availableModels)) {
            $this->model = $this->availableModels[0]['id'];
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
                'customTitle' => ['nullable', 'string', 'max:120'],
                'model' => ['required', 'string', 'max:150'],
                'aspectRatio' => ['required', 'in:1:1,4:5,3:4,16:9,9:16'],
                'quality' => ['required', 'in:standard,high'],
                'imageCount' => ['required', 'integer', 'min:1', 'max:4'],
            ], [
                'selectedStoreId.required' => 'Pilih Store terlebih dahulu.',
                'selectedTemplateId.required' => 'Pilih Template terlebih dahulu.',
                'imageOne.required' => 'Gambar utama wajib diupload.',
                'imageTwo.image' => 'Foto referensi pemasangan harus berupa gambar yang valid.',
                'imageTwo.mimes' => 'Foto referensi harus JPG, PNG, atau WEBP.',
                'imageTwo.max' => 'Foto referensi maksimal 10MB.',
                'model.required' => 'Model OpenAI belum tersedia. Pastikan API Key sudah tersimpan.',
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

        <section class="rms-generator-workspace">

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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->selectedStore): ?>
                                <span class="rms-generator-store-logo">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->selectedStore->logo_path): ?>
                                        <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($this->selectedStore->logo_path)); ?>" alt="">
                                    <?php else: ?>
                                        <b><?php echo e(strtoupper(substr($this->selectedStore->name, 0, 1))); ?></b>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                                <span class="rms-generator-store-copy">
                                    <strong><?php echo e($this->selectedStore->name); ?></strong>
                                    <small><?php echo e($this->selectedStore->marketplace ?: 'Marketplace'); ?></small>
                                </span>
                            <?php else: ?>
                                <span class="rms-generator-store-placeholder">Pilih Store</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                                <b><?php echo e($this->stores->count()); ?></b>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <button type="button"
                                    class="<?php echo e((int)$selectedStoreId === (int)$store->id ? 'selected' : ''); ?>"
                                    wire:click="selectStore(<?php echo e($store->id); ?>)"
                                    x-on:click="open=false">
                                    <span class="rms-generator-option-logo">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($store->logo_path): ?>
                                            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path)); ?>" alt="">
                                        <?php else: ?>
                                            <b><?php echo e(strtoupper(substr($store->name, 0, 1))); ?></b>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </span>
                                    <span>
                                        <strong><?php echo e($store->name); ?></strong>
                                        <small><?php echo e($store->marketplace ?: 'Marketplace'); ?> · <?php echo e($store->templates->count()); ?> template</small>
                                    </span>
                                    <i><?php echo e((int)$selectedStoreId === (int)$store->id ? '✓' : ''); ?></i>
                                </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <div class="rms-generator-empty-mini">Belum ada Store aktif.</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="rms-generator-template-heading">
                        <label class="rms-generator-field-label">Template <em>*</em></label>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->selectedStore): ?>
                            <span><?php echo e($this->templates->count()); ?> tersedia</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="rms-generator-search">
                        <span>⌕</span>
                        <input type="text" wire:model.live.debounce.250ms="templateSearch"
                            placeholder="<?php echo e($this->selectedStore ? 'Cari template...' : 'Pilih Store terlebih dahulu'); ?>"
                            <?php if(!$this->selectedStore): echo 'disabled'; endif; ?>>
                    </div>

                    <div class="rms-generator-chips rms-filter-drag">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['all' => 'All', 'promo' => 'Promo', 'product' => 'Product', 'lifestyle' => 'Lifestyle', 'detail' => 'Detail']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <button type="button"
                                class="rms-filter-chip <?php echo e($templateCategory === $key ? 'active' : ''); ?>"
                                wire:click="$set('templateCategory','<?php echo e($key); ?>')"
                                <?php if(!$this->selectedStore): echo 'disabled'; endif; ?>>
                                <?php echo e($label); ?>

                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>

                    <div class="rms-generator-template-grid">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <button type="button"
                                class="rms-generator-template-card <?php echo e((int)$selectedTemplateId === (int)$template->id ? 'selected' : ''); ?>"
                                wire:click="toggleTemplate(<?php echo e($template->id); ?>)" x-on:click="$nextTick(() => activeStep = (selectedTemplateId === <?php echo e($template->id); ?>) ? 2 : 1)">
                                <div class="rms-generator-template-image">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($template->example_image_path): ?>
                                        <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($template->example_image_path)); ?>" alt="">
                                    <?php else: ?>
                                        <div class="rms-generator-template-placeholder">AI</div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int)$selectedTemplateId === (int)$template->id): ?>
                                        <i>✓</i>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="rms-generator-template-copy">
                                    <strong><?php echo e($template->name); ?></strong>
                                    <small><?php echo e($template->aspect_ratio ?: '1:1'); ?></small>
                                </div>
                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="rms-generator-no-template">
                                <span>✦</span>
                                <strong><?php echo e($this->selectedStore ? 'Belum ada template aktif' : 'Pilih Store terlebih dahulu'); ?></strong>
                                <small><?php echo e($this->selectedStore ? 'Tambahkan template dari Template Library.' : 'Template akan muncul otomatis setelah Store dipilih.'); ?></small>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imageOne): ?>
                                <div class="rms-upload-preview-wrap">
                                    <img src="<?php echo e($imageOne->temporaryUrl()); ?>" alt="">
                                    <div class="rms-upload-preview-shade"></div>
                                    <button type="button" class="rms-generator-remove" wire:click="clearImageOne">×</button>
                                    <span class="rms-upload-complete">✓ Uploaded</span>
                                </div>
                            <?php else: ?>
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
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div class="rms-generator-upload-box rms-upload-reference" :class="{ 'has-image': imageTwo, 'is-disabled': !useInstalledReference }">
                            <div class="rms-upload-label-row">
                                <span class="rms-upload-number">02</span>
                                <div><strong>Foto Produk Terpasang</strong><small>Referensi bentuk, posisi, dan penggunaan part pada motor.</small></div>
                                <span class="rms-upload-badge optional">OPTIONAL</span>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imageTwo): ?>
                                <div class="rms-upload-preview-wrap">
                                    <img src="<?php echo e($imageTwo->temporaryUrl()); ?>" alt="">
                                    <div class="rms-upload-preview-shade"></div>
                                    <button type="button" class="rms-generator-remove" wire:click="clearImageTwo">×</button>
                                    <span class="rms-upload-complete">✓ Reference ready</span>
                                </div>
                            <?php elseif($useInstalledReference): ?>
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
                            <?php else: ?>
                                <div class="rms-upload-disabled-state">
                                    <span>✦</span>
                                    <strong>Mode tanpa foto terpasang</strong>
                                    <small>AI akan membuat konteks visual berdasarkan produk dan template.</small>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <button type="button" class="<?php echo e($useInstalledReference ? 'active' : ''); ?>" wire:click="setReferenceMode(true)">Gunakan Foto Terpasang</button>
                            <button type="button" class="<?php echo e(! $useInstalledReference ? 'active' : ''); ?>" wire:click="setReferenceMode(false)">Tanpa Foto Terpasang</button>
                        </div>
                    </div>

                    <div
                        class="rms-custom-title-card <?php echo e($useCustomTitle ? 'is-active' : ''); ?>"
                        x-data="{ open: <?php if ((object) ('useCustomTitle') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('useCustomTitle'->value()); ?>')<?php echo e('useCustomTitle'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('useCustomTitle'); ?>')<?php endif; ?>.live }"
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
                                x-on:click="open = !open"
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
                                    <span><?php echo e(mb_strlen($customTitle)); ?>/120</span>
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

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['customTitle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="rms-custom-title-error"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="rms-reference-flow">
                        <span class="rms-flow-title">REFERENCE YANG AKAN DIGUNAKAN AI</span>
                        <div class="rms-flow-items">
                            <div class="rms-flow-item ready"><b>01</b><span>Product</span><small>Uploaded</small></div>
                            <i>+</i>
                            <div class="rms-flow-item <?php echo e(! $useInstalledReference ? 'muted' : ($imageTwo ? 'ready' : '')); ?>"><b>02</b><span>Installed</span><small><?php echo e(! $useInstalledReference ? 'Skipped' : ($imageTwo ? 'Uploaded' : 'Optional')); ?></small></div>
                            <i>+</i>
                            <div class="rms-flow-item ready"><b>03</b><span>Store Logo</span><small>Automatic</small></div>
                        </div>
                    </div>

                    

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
                                <span class="rms-custom-select-label">Model OpenAI</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b><?php echo e($model ?: 'Model belum tersedia'); ?></b>
                                        <small><?php echo e(count($this->availableModels)); ?> model image tersedia dari OpenAI</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->availableModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $openAiModel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button type="button" class="<?php echo e($model === $openAiModel['id'] ? 'selected' : ''); ?>" wire:click="selectModel('<?php echo e(addslashes($openAiModel['id'])); ?>')" x-on:click="open=false">
                                            <span class="rms-option-model">AI</span>
                                            <span>
                                                <strong><?php echo e($openAiModel['id']); ?></strong>
                                                <small><?php echo e($openAiModel['owned_by'] ?? 'OpenAI'); ?></small>
                                            </span>
                                            <i><?php echo e($model === $openAiModel['id'] ? '✓' : ''); ?></i>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        <div class="rms-generator-empty-mini">Model image tidak ditemukan dari OpenAI. Cek API Key dan koneksi.</div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Aspect Ratio</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b x-text="aspectRatioLabel('<?php echo e($aspectRatio); ?>')"></b>
                                        <small>Canvas output</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                                        '1:1' => ['1:1 (Square)', 'Perfect for marketplace & catalog'],
                                        '4:5' => ['4:5 (Portrait)', 'Social & product feed'],
                                        '3:4' => ['3:4 (Portrait)', 'Portrait product visual'],
                                        '16:9' => ['16:9 (Landscape)', 'Banner & marketplace hero'],
                                        '9:16' => ['9:16 (Story)', 'Story & vertical content'],
                                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button type="button" class="<?php echo e($aspectRatio === $value ? 'selected' : ''); ?>" x-on:click="setLivewireValue('aspectRatio','<?php echo e($value); ?>'); open=false">
                                            <span class="rms-option-ratio"><?php echo e($value); ?></span>
                                            <span><strong><?php echo e($meta[0]); ?></strong><small><?php echo e($meta[1]); ?></small></span>
                                            <i><?php echo e($aspectRatio === $value ? '✓' : ''); ?></i>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>

                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Quality</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b x-text="qualityLabel('<?php echo e($quality); ?>')"></b>
                                        <small>Output quality</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <button type="button" class="<?php echo e($quality === 'standard' ? 'selected' : ''); ?>" x-on:click="setLivewireValue('quality','standard'); open=false">
                                        <span class="rms-option-quality standard">S</span>
                                        <span><strong>Standard</strong><small>Balanced speed & quality</small></span>
                                        <i><?php echo e($quality === 'standard' ? '✓' : ''); ?></i>
                                    </button>
                                    <button type="button" class="<?php echo e($quality === 'high' ? 'selected' : ''); ?>" x-on:click="setLivewireValue('quality','high'); open=false">
                                        <span class="rms-option-quality high">H</span>
                                        <span><strong>High Quality</strong><small>Maximum visual detail</small></span>
                                        <i><?php echo e($quality === 'high' ? '✓' : ''); ?></i>
                                    </button>
                                </div>
                            </div>

                            <div class="rms-custom-select full" x-data="{ open:false }" x-on:click.outside="open=false">
                                <span class="rms-custom-select-label">Jumlah Gambar</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" x-on:click="open=!open">
                                    <span>
                                        <b><?php echo e($imageCount); ?> <?php echo e($imageCount === 1 ? 'Gambar' : 'Gambar'); ?></b>
                                        <small>Jumlah hasil yang akan dibuat</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [1,2,3,4]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button type="button" class="<?php echo e((int)$imageCount === $count ? 'selected' : ''); ?>" x-on:click="setLivewireValue('imageCount', <?php echo e($count); ?>); open=false">
                                            <span class="rms-option-count"><?php echo e($count); ?></span>
                                            <span><strong><?php echo e($count); ?> <?php echo e($count === 1 ? 'Gambar' : 'Gambar'); ?></strong><small><?php echo e($count === 1 ? 'Satu hasil utama' : $count . ' variasi hasil'); ?></small></span>
                                            <i><?php echo e((int)$imageCount === $count ? '✓' : ''); ?></i>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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

        <section
            class="rms-generator-history rms-generator-history-bottom"
            <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'generation-history'; ?>wire:key="generation-history"
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
            }">
            <div class="rms-generator-history-head">
                <div class="rms-history-title">
                    <span>◷</span>
                    <div>
                        <strong>Recent Generations</strong>
                        <small>Generate berikutnya bisa langsung dibuat tanpa menunggu proses sebelumnya selesai.</small>
                    </div>
                </div>
                <div class="rms-history-count"><i></i><?php echo e($this->recentGenerations->count()); ?> result</div>
            </div>

            <div class="rms-history-toolbar">
                <div class="rms-history-search">
                    <span>⌕</span>
                    <input type="text" wire:model.live.debounce.400ms="historySearch" placeholder="Cari generation, store, template...">
                </div>
                <div class="rms-history-filter-group">
                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Status</span><b><?php echo e($historyStatus === 'all' ? 'All Status' : ucfirst($historyStatus)); ?></b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['all'=>'All Status','queued'=>'Queued','processing'=>'Processing','completed'=>'Completed','failed'=>'Failed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <button type="button" class="<?php echo e($historyStatus === $value ? 'selected' : ''); ?>" wire:click="$set('historyStatus','<?php echo e($value); ?>')" x-on:click="open=false">
                                    <span><?php echo e($label); ?></span><i><?php echo e($historyStatus === $value ? '✓' : ''); ?></i>
                                </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>

                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Store</span><b><?php echo e($historyStoreId ? optional($this->stores->firstWhere('id',$historyStoreId))->name : 'All Stores'); ?></b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            <button type="button" class="<?php echo e(! $historyStoreId ? 'selected' : ''); ?>" wire:click="$set('historyStoreId',null)" x-on:click="open=false">
                                <span>All Stores</span><i><?php echo e(! $historyStoreId ? '✓' : ''); ?></i>
                            </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <button type="button" class="<?php echo e((int)$historyStoreId === (int)$store->id ? 'selected' : ''); ?>" wire:click="$set('historyStoreId',<?php echo e($store->id); ?>)" x-on:click="open=false">
                                    <span><?php echo e($store->name); ?></span><i><?php echo e((int)$historyStoreId === (int)$store->id ? '✓' : ''); ?></i>
                                </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>

                    <div class="rms-history-filter" x-data="{ open:false }" x-on:click.outside="open=false">
                        <button type="button" x-on:click="open=!open" :class="{ 'is-open': open }">
                            <span>Template</span><b><?php echo e($historyTemplateId ? optional($this->historyTemplates->firstWhere('id',$historyTemplateId))->name : 'All Templates'); ?></b><i>⌄</i>
                        </button>
                        <div class="rms-history-filter-menu" x-show="open" x-transition.opacity.scale.origin.top.right x-cloak>
                            <button type="button" class="<?php echo e(! $historyTemplateId ? 'selected' : ''); ?>" wire:click="$set('historyTemplateId',null)" x-on:click="open=false">
                                <span>All Templates</span><i><?php echo e(! $historyTemplateId ? '✓' : ''); ?></i>
                            </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->historyTemplates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <button type="button" class="<?php echo e((int)$historyTemplateId === (int)$template->id ? 'selected' : ''); ?>" wire:click="$set('historyTemplateId',<?php echo e($template->id); ?>)" x-on:click="open=false">
                                    <span><?php echo e($template->name); ?></span><i><?php echo e((int)$historyTemplateId === (int)$template->id ? '✓' : ''); ?></i>
                                </button>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rms-generator-history-list rms-generator-history-grid"
                <?php if($this->hasActiveGenerations): ?>
                    wire:poll.4s.visible
                <?php endif; ?>
            >
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->recentGenerations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $generation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
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
                        $started = $generation->started_at;
                        $elapsed = $started ? max(1, $started->diffInSeconds(now())) : 0;
                        $eta = ($status === 'processing' && $progress > 0)
                            ? max(5, (int) ceil($elapsed * (100 - $progress) / $progress)) : null;
                        $etaLabel = $eta !== null
                            ? ($eta < 60 ? '± ' . $eta . ' detik' : '± ' . ceil($eta / 60) . ' menit')
                            : '± 20–60 detik / gambar';
                    ?>

                    <article <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'generation-history-'.e($generation->id).''; ?>wire:key="generation-history-<?php echo e($generation->id); ?>" class="rms-generator-history-item rms-generation-card status-<?php echo e($status); ?>">
                        <div class="rms-generation-card-head">
                            <div class="rms-history-item-meta"><span><?php echo e(optional($generation->created_at)->format('d M Y · H:i')); ?></span><small>#<?php echo e($generation->id); ?></small></div>
                            <span class="rms-history-status <?php echo e($status); ?>"><i></i><?php echo e($statusLabel); ?></span>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($status === 'completed' && $hero): ?>
                            <?php
                                $heroPreview = [
                                    'id' => (int) $hero->id,
                                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path),
                                    'format' => strtoupper($hero->format ?: 'PNG'),
                                    'width' => (int) $hero->width, 'height' => (int) $hero->height,
                                    'title' => data_get($generation->metadata, 'custom_title'),
                                    'template' => $generation->template?->name ?? 'Generated Image',
                                    'store' => $generation->store?->name ?? 'Store',
                                ];
                            ?>
                            <button type="button" class="rms-generation-image" style="aspect-ratio: <?php echo e($ratio); ?>" x-on:click="openPreview(<?php echo e(\Illuminate\Support\Js::from($heroPreview)); ?>)">
                                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path)); ?>" alt="Generated image" loading="lazy">
                                <span class="rms-generation-image-overlay"><b>⌕</b> Lihat preview</span>
                            </button>
                        <?php elseif(in_array($status, ['queued','processing'])): ?>
                            <div class="rms-generation-processing-visual">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hero): ?>
                                    <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($hero->image_path)); ?>" alt="Processing preview" loading="lazy">
                                <?php else: ?>
                                    <div class="rms-processing-placeholder"><span class="rms-processing-orbit"></span><b><?php echo e($progress); ?>%</b><small>AI PROCESSING</small></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="rms-processing-progress-ring" style="--progress: <?php echo e($progress); ?>%"><span><?php echo e($progress); ?>%</span></div>
                            </div>
                            <div class="rms-generation-eta"><span class="rms-generation-eta-clock">◷</span><div><strong>Estimasi waktu selesai</strong><b><?php echo e($etaLabel); ?></b></div></div>
                            <div class="rms-generation-stage">
                                <div class="rms-generation-stage-item active"><i></i><span><?php echo e($stage); ?></span></div>
                                <div class="rms-generation-stage-item <?php echo e($progress >= 78 ? 'active' : ''); ?>"><i></i><span>Membuat gambar</span></div>
                                <div class="rms-generation-stage-item <?php echo e($progress >= 96 ? 'active' : ''); ?>"><i></i><span>Menyimpan hasil</span></div>
                            </div>
                        <?php elseif($status === 'failed'): ?>
                            <div class="rms-generation-state rms-generation-state-error"><span>!</span><div><strong>Generate gagal</strong><small><?php echo e(\Illuminate\Support\Str::limit($generation->error_message ?: 'Terjadi error saat memproses generation.', 180)); ?></small></div></div>
                        <?php else: ?>
                            <div class="rms-generation-state rms-generation-state-cancelled"><span>×</span><div><strong>Generation dibatalkan</strong><small>Proses dihentikan oleh pengguna.</small></div></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php
                            $generationTitle = data_get($generation->metadata, 'custom_title');
                            $generationTitleSource = data_get($generation->metadata, 'title_source', 'ai');
                        ?>
                        <div class="rms-generation-card-info">
                            <div>
                                <strong><?php echo e($generationTitle ?: ($generation->template?->name ?? 'Generated Image')); ?></strong>
                                <small>
                                    <?php echo e($generation->store?->name ?? 'Store'); ?> · <?php echo e($generation->model); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($generationTitle): ?>
                                        · Custom title
                                    <?php else: ?>
                                        · AI title
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </small>
                            </div>
                            <span><?php echo e($generation->generatedImages->count()); ?> image<?php echo e($generation->generatedImages->count() === 1 ? '' : 's'); ?></span>
                        </div>

                        <div class="rms-generation-actions">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($status === 'completed'): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hero): ?>
                                    <button type="button" x-on:click="openPreview(<?php echo e(\Illuminate\Support\Js::from($heroPreview)); ?>)" class="rms-generation-action primary-light">Lihat Detail</button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <button type="button" wire:click="retryGeneration(<?php echo e($generation->id); ?>)" class="rms-generation-action">↻ Buat Lagi</button>
                                <button type="button" x-on:click="askConfirm('delete', <?php echo e($generation->id); ?>, 'Hapus generation?', 'Generation ini beserta file hasilnya akan dihapus. Tindakan ini tidak dapat dibatalkan.')" class="rms-generation-action danger-icon" title="Hapus">⌫</button>
                            <?php elseif(in_array($status, ['queued','processing'])): ?>
                                <button type="button" x-on:click="askConfirm('cancel', <?php echo e($generation->id); ?>, 'Batalkan generation?', 'Proses yang sedang berjalan akan dihentikan dan dipindahkan ke status cancelled.')" class="rms-generation-action danger">◉ Batalkan</button>
                                <button type="button" x-on:click="askConfirm('retry', <?php echo e($generation->id); ?>, 'Restart generation?', 'Proses saat ini akan dibatalkan lalu generation baru akan dibuat ulang.')" class="rms-generation-action">↻ Retry</button>
                                <button type="button" x-on:click="askConfirm('delete', <?php echo e($generation->id); ?>, 'Hapus generation?', 'Generation ini akan dihapus dari Recent Generations.')" class="rms-generation-action danger-icon" title="Hapus">⌫</button>
                            <?php elseif($status === 'failed' || $status === 'cancelled'): ?>
                                <button type="button" wire:click="retryGeneration(<?php echo e($generation->id); ?>)" class="rms-generation-action primary">↻ Retry</button>
                                <span class="rms-generation-action" style="cursor:default;color:#8a8f99">Error detail</span>
                                <button type="button" x-on:click="askConfirm('delete', <?php echo e($generation->id); ?>, 'Hapus generation?', 'Generation ini akan dihapus dari Recent Generations.')" class="rms-generation-action danger-icon" title="Hapus">⌫</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </article>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rms-history-empty"><span>✦</span><strong>Belum ada generation</strong><small>Hasil baru akan muncul di sini. Kamu bisa menjalankan beberapa generation tanpa menunggu satu per satu.</small></div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

        
            <div
                x-cloak
                x-show.important="confirmOpen"
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

        </section>
    </div>

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
</style>

    
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

<style>

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
   RECENT GENERATIONS — FINAL VISUAL OVERRIDE
   4 cards desktop / 2 tablet / 1 mobile
   Matches the intended compact SaaS history layout.
   ============================================================ */

.rms-generator-history-bottom{
    position:relative;
    margin-top:18px;
    border:1px solid #e4e5e9 !important;
    border-radius:22px !important;
    background:#fff !important;
    overflow:hidden;
    box-shadow:0 10px 35px rgba(20,20,25,.045);
}

.rms-generator-history-bottom .rms-generator-history-head{
    min-height:70px;
    padding:14px 18px !important;
    border-bottom:1px solid #ececef;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
}

.rms-generator-history-bottom .rms-history-title{
    display:flex;
    align-items:center;
    gap:11px;
}

.rms-generator-history-bottom .rms-history-title > span{
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    flex:none;
    border-radius:12px;
    background:#fff0f1;
    color:#ef233c;
    font-size:18px;
}

.rms-generator-history-bottom .rms-history-title strong{
    display:block;
    color:#17181b;
    font-size:15px;
    line-height:1.2;
    font-weight:850;
}

.rms-generator-history-bottom .rms-history-title small{
    display:block;
    margin-top:3px;
    color:#9297a1;
    font-size:9px;
    line-height:1.3;
}

.rms-generator-history-bottom .rms-history-count{
    min-width:62px;
    height:28px;
    padding:0 10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    border:1px solid #e5e7eb;
    border-radius:999px;
    background:#fff;
    color:#777d87;
    font-size:9px;
    font-weight:800;
    text-transform:uppercase;
}

.rms-generator-history-bottom .rms-history-count i{
    width:6px;
    height:6px;
    border-radius:50%;
    background:#18b96b;
    box-shadow:0 0 0 3px #e9faf2;
}

.rms-generator-history-bottom .rms-history-toolbar{
    padding:11px 18px !important;
    border-bottom:1px solid #ececef;
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:9px;
    background:#fff;
}

.rms-generator-history-bottom .rms-history-search{
    min-width:0;
    height:38px;
    border:1px solid #e0e2e7 !important;
    border-radius:11px !important;
    background:#fff;
}

.rms-generator-history-bottom .rms-history-search input{
    height:100%;
    font-size:10px !important;
}

.rms-generator-history-bottom .rms-history-filter-group{
    display:flex;
    gap:8px;
}

.rms-generator-history-bottom .rms-history-filter > button{
    min-width:122px;
    height:38px;
    padding:0 11px !important;
    border:1px solid #e0e2e7 !important;
    border-radius:11px !important;
    background:#fff !important;
}

.rms-generator-history-grid{
    display:grid !important;
    grid-template-columns:repeat(4,minmax(0,1fr)) !important;
    gap:12px !important;
    padding:14px 18px 18px !important;
    align-items:start;
    background:#fafbfc;
}

.rms-generation-card{
    min-width:0 !important;
    padding:10px !important;
    border:1px solid #e2e4e9 !important;
    border-radius:17px !important;
    background:#fff !important;
    box-shadow:0 3px 13px rgba(20,20,25,.035) !important;
    transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;
}

.rms-generation-card:hover{
    transform:translateY(-2px);
    border-color:#d7d9df !important;
    box-shadow:0 12px 28px rgba(20,20,25,.085) !important;
}

.rms-generation-card-head{
    min-height:25px;
    margin-bottom:8px !important;
}

.rms-history-item-meta{
    min-width:0;
    display:flex;
    align-items:center;
    gap:5px;
}

.rms-history-item-meta span,
.rms-history-item-meta small{
    color:#999ea7 !important;
    font-size:8px !important;
    white-space:nowrap;
}

.rms-history-status{
    flex:none;
    min-height:21px;
    padding:0 8px !important;
    border-radius:999px !important;
    font-size:8px !important;
    font-weight:850 !important;
    letter-spacing:.04em;
}

.rms-generation-image,
.rms-generation-processing-visual{
    width:100% !important;
    border-radius:12px !important;
}

.rms-generation-image{
    aspect-ratio:auto;
    min-height:0;
    background:#f4f5f7;
}

.rms-generation-image img{
    width:100% !important;
    height:auto !important;
    min-height:0;
    max-height:390px;
    object-fit:cover !important;
}

.rms-generation-image-overlay{
    left:8px !important;
    right:8px !important;
    bottom:8px !important;
    padding:7px 9px !important;
    font-size:9px !important;
}

.rms-generation-processing-visual{
    min-height:190px !important;
}

.rms-generation-processing-visual > img{
    width:100% !important;
    height:190px !important;
}

.rms-processing-placeholder{
    height:190px !important;
}

.rms-processing-progress-ring{
    width:52px !important;
    height:52px !important;
    right:10px !important;
    top:10px !important;
}

.rms-processing-progress-ring:after{
    width:39px !important;
    height:39px !important;
}

.rms-processing-progress-ring span{
    font-size:9px !important;
}

.rms-generation-eta{
    margin-top:8px !important;
    padding:8px 9px !important;
    border-radius:10px !important;
}

.rms-generation-eta-clock{
    width:25px !important;
    height:25px !important;
    border-radius:8px !important;
    font-size:13px !important;
}

.rms-generation-eta strong{
    font-size:8px !important;
}

.rms-generation-eta b{
    font-size:10px !important;
}

.rms-generation-stage{
    gap:5px !important;
    padding:8px 1px 1px !important;
}

.rms-generation-stage-item{
    font-size:8px !important;
}

.rms-generation-card-info{
    padding:9px 1px 8px !important;
    gap:8px;
}

.rms-generation-card-info strong{
    font-size:11px !important;
}

.rms-generation-card-info small{
    font-size:8px !important;
}

.rms-generation-card-info > span{
    font-size:8px !important;
}

.rms-generation-actions{
    display:grid !important;
    grid-template-columns:repeat(3,minmax(0,1fr)) !important;
    gap:6px !important;
}

.rms-generation-action{
    min-width:0 !important;
    min-height:34px !important;
    padding:0 7px !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    border:1px solid #dfe2e7 !important;
    border-radius:9px !important;
    background:#fff !important;
    color:#25272b !important;
    font-size:8px !important;
    font-weight:800 !important;
    text-decoration:none !important;
    transition:all .18s ease;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.rms-generation-action:hover{
    border-color:#c9ccd3 !important;
    background:#f8f9fa !important;
    transform:translateY(-1px);
}

.rms-generation-action.primary,
.rms-generation-action.primary-light{
    border-color:#ef233c !important;
    background:#ef233c !important;
    color:#fff !important;
    box-shadow:0 5px 14px rgba(239,35,60,.16);
}

.rms-generation-action.danger{
    border-color:#ffd1d6 !important;
    background:#fff5f6 !important;
    color:#df1e35 !important;
}

.rms-generation-action.danger-icon{
    color:#ef233c !important;
    font-size:13px !important;
}

.rms-generation-state{
    min-height:190px !important;
    padding:15px !important;
}

.rms-generation-state strong{
    font-size:11px !important;
}

.rms-generation-state small{
    font-size:8px !important;
}

.rms-history-empty{
    grid-column:1 / -1 !important;
    min-height:250px !important;
    margin:0 !important;
    border:0 !important;
    background:#fff !important;
    border-radius:14px !important;
}

@media (max-width:1200px){
    .rms-generator-history-grid{
        grid-template-columns:repeat(3,minmax(0,1fr)) !important;
    }
}

@media (max-width:900px){
    .rms-generator-history-bottom .rms-history-toolbar{
        grid-template-columns:1fr;
    }

    .rms-generator-history-bottom .rms-history-filter-group{
        overflow-x:auto;
        padding-bottom:2px;
    }

    .rms-generator-history-bottom .rms-history-filter{
        flex:1 0 145px;
    }

    .rms-generator-history-grid{
        grid-template-columns:repeat(2,minmax(0,1fr)) !important;
        padding:12px !important;
    }
}

@media (max-width:560px){
    .rms-generator-history-bottom{
        border-radius:17px !important;
    }

    .rms-generator-history-bottom .rms-generator-history-head{
        padding:12px !important;
    }

    .rms-generator-history-bottom .rms-history-title small{
        display:none;
    }

    .rms-generator-history-grid{
        grid-template-columns:1fr !important;
        padding:10px !important;
    }

    .rms-generation-actions{
        grid-template-columns:repeat(3,minmax(0,1fr)) !important;
    }
}

/* ============================================================
   MODAL LAYER HARDENING
   Prevent dashboard/sidebar stacking contexts from winning.
   ============================================================ */
.rms-generator-preview-modal-custom{
    position:fixed!important;
    inset:0!important;
    z-index:2147482000!important;
    isolation:isolate;
}
.rms-generator-preview-modal-custom .rms-preview-dialog-custom{
    position:relative;
    z-index:2;
}
.rms-generator-preview-modal-custom .rms-preview-backdrop{
    position:absolute;
    inset:0;
    z-index:1;
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
            inset: 0 !important;
            z-index: 2147483000 !important;
            width: 100vw !important;
            min-width: 100vw !important;
            min-height: 100dvh !important;
            height: 100dvh !important;
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



/* CONFIRM MODAL — keep it inside the Alpine history scope.
   Fixed positioning + important display prevents ancestor layout rules
   from hiding/clipping it. */
.rms-confirm-modal[x-cloak] { display:none!important; }
.rms-confirm-modal[style*="display: none"] { display:none!important; }
.rms-confirm-modal[style*="display: flex"] { display:flex!important; }

</style>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\livewire\dashboard\generator\index.blade.php ENDPATH**/ ?>