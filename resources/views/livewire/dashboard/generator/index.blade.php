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

    public $imageOne = null;
    public $imageTwo = null;

    public string $aspectRatio = '1:1';
    public string $quality = 'high';
    public int $imageCount = 1;

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
            ->latest()
            ->limit(8)
            ->get();
    }

    public function selectStore(int $storeId): void
    {
        $this->selectedStoreId = $storeId;
        $this->selectedTemplateId = null;
        $this->templateSearch = '';
    }

    public function toggleTemplate(int $templateId): void
    {
        $this->selectedTemplateId = $this->selectedTemplateId === $templateId
            ? null
            : $templateId;
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
    x-init="init()"
    @keydown.escape.window="closeAllMenus()"
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

            <div class="rms-generator-stepbar">
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
                    <span><strong>Preview & Generate</strong><small>Atur lalu generate.</small></span>
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

                    <div class="rms-generator-store-select" x-data="{ open:false }" @click.outside="open=false">
                        <button type="button" class="rms-generator-store-trigger" :class="{ 'is-open': open }" @click="open=!open">
                            @if ($this->selectedStore)
                                <span class="rms-generator-store-logo">
                                    @if ($this->selectedStore->logo_path)
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

                            @forelse ($this->stores as $store)
                                <button type="button"
                                    class="{{ (int)$selectedStoreId === (int)$store->id ? 'selected' : '' }}"
                                    wire:click="selectStore({{ $store->id }})"
                                    @click="open=false">
                                    <span class="rms-generator-option-logo">
                                        @if ($store->logo_path)
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
                        @if ($this->selectedStore)
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
                        @foreach (['all' => 'All', 'promo' => 'Promo', 'product' => 'Product', 'lifestyle' => 'Lifestyle', 'detail' => 'Detail'] as $key => $label)
                            <button type="button"
                                class="rms-filter-chip {{ $templateCategory === $key ? 'active' : '' }}"
                                wire:click="$set('templateCategory','{{ $key }}')"
                                @disabled(!$this->selectedStore)>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="rms-generator-template-grid">
                        @forelse ($this->templates as $template)
                            <button type="button"
                                class="rms-generator-template-card {{ (int)$selectedTemplateId === (int)$template->id ? 'selected' : '' }}"
                                wire:click="toggleTemplate({{ $template->id }})" @click="$nextTick(() => activeStep = (selectedTemplateId === {{ $template->id }}) ? 2 : 1)">
                                <div class="rms-generator-template-image">
                                    @if ($template->example_image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($template->example_image_path) }}" alt="">
                                    @else
                                        <div class="rms-generator-template-placeholder">AI</div>
                                    @endif
                                    @if ((int)$selectedTemplateId === (int)$template->id)
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
                            <h2>Upload Gambar</h2>
                            <p>Upload dua gambar produk sesuai kebutuhan Template.</p>
                        </div>
                        <div class="rms-upload-requirement"><i></i> 2 gambar produk</div>
                    </div>

                    <div class="rms-generator-upload-grid">
                        <div class="rms-generator-upload-box" :class="{ 'has-image': imageOne }">
                            @if ($imageOne)
                                <img src="{{ $imageOne->temporaryUrl() }}" alt="">
                                <button type="button" class="rms-generator-remove" wire:click="clearImageOne">×</button>
                                <span class="rms-upload-complete">✓ Uploaded</span>
                            @else
                                <div class="rms-generator-upload-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 20h14"/></svg>
                                </div>
                                <strong>Gambar Utama</strong>
                                <small>Foto produk utama dengan<br>background yang jelas.</small>
                                <label class="rms-generator-upload-button">
                                    <span>↑</span> Pilih Gambar
                                    <input type="file" wire:model="imageOne" accept="image/png,image/jpeg,image/webp">
                                </label>
                                <em>JPG, PNG, WEBP · Maks. 10MB</em>
                            @endif
                        </div>

                        <div class="rms-generator-upload-box" :class="{ 'has-image': imageTwo }">
                            @if ($imageTwo)
                                <img src="{{ $imageTwo->temporaryUrl() }}" alt="">
                                <button type="button" class="rms-generator-remove" wire:click="clearImageTwo">×</button>
                                <span class="rms-upload-complete">✓ Uploaded</span>
                            @else
                                <div class="rms-generator-upload-icon">
                                    <svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 20h14"/></svg>
                                </div>
                                <strong>Gambar Kedua</strong>
                                <small>Detail, variasi, atau sudut<br>produk yang berbeda.</small>
                                <label class="rms-generator-upload-button">
                                    <span>↑</span> Pilih Gambar
                                    <input type="file" wire:model="imageTwo" accept="image/png,image/jpeg,image/webp">
                                </label>
                                <em>JPG, PNG, WEBP · Maks. 10MB</em>
                            @endif
                        </div>
                    </div>

                    <div class="rms-generator-result-preview">
                        <div class="rms-generator-subhead">
                            <div>
                                <strong>Preview Hasil Generate</strong>
                                <span>Hasil visual terbaru akan muncul di sini setelah proses generate selesai.</span>
                            </div>
                            <span class="rms-preview-status">
                                <i></i>
                                Latest result
                            </span>
                        </div>

                        @php
                            $latestGeneration = $this->recentGenerations
                                ->filter(fn ($generation) => in_array($generation->status, ['completed', 'success', 'succeeded'], true))
                                ->first();

                            $latestImages = $latestGeneration?->generatedImages
                                ?->where('is_primary', true)
                                ->take(2)
                                ?? collect();

                            if ($latestImages->isEmpty() && $latestGeneration) {
                                $latestImages = $latestGeneration->generatedImages->take(2);
                            }
                        @endphp

                        <div class="rms-generator-result-grid">
                            @forelse ($latestImages as $image)
                                <div class="rms-generator-result-card">
                                    <img
                                        src="{{ $image->image_url ?: \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}"
                                        alt="Generated image preview"
                                    >
                                    <div class="rms-result-overlay">
                                        <span>GENERATED</span>
                                        <b>✦</b>
                                    </div>
                                </div>
                            @empty
                                <div class="rms-generator-result-empty">
                                    <span class="rms-result-empty-icon">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h13A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5z"/>
                                            <circle cx="8.5" cy="9" r="1.2"/>
                                            <path d="m5.5 17 4.5-4.5 3 3 2-2 3.5 3.5"/>
                                        </svg>
                                    </span>
                                    <strong>Belum ada hasil generate</strong>
                                    <small>Preview akan tampil otomatis setelah generate berhasil dan statusnya selesai.</small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rms-generator-settings">
                        <div class="rms-generator-subhead">
                            <div>
                                <strong>Pengaturan Tambahan</strong>
                                <span>Sesuaikan output sebelum generate</span>
                            </div>
                            <button type="button" class="rms-settings-collapse" @click="settingsOpen = !settingsOpen" :class="{ 'is-open': settingsOpen }">
                                <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </div>

                        <div class="rms-generator-settings-grid" x-show="settingsOpen" x-transition.opacity>
                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" @click.outside="open=false">
                                <span class="rms-custom-select-label">Aspect Ratio</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" @click="open=!open">
                                    <span>
                                        <b x-text="aspectRatioLabel('{{ $aspectRatio }}')"></b>
                                        <small>Canvas output</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @foreach ([
                                        '1:1' => ['1:1 (Square)', 'Perfect for marketplace & catalog'],
                                        '4:5' => ['4:5 (Portrait)', 'Social & product feed'],
                                        '3:4' => ['3:4 (Portrait)', 'Portrait product visual'],
                                        '16:9' => ['16:9 (Landscape)', 'Banner & marketplace hero'],
                                        '9:16' => ['9:16 (Story)', 'Story & vertical content'],
                                    ] as $value => $meta)
                                        <button type="button" class="{{ $aspectRatio === $value ? 'selected' : '' }}" @click="setLivewireValue('aspectRatio','{{ $value }}'); open=false">
                                            <span class="rms-option-ratio">{{ $value }}</span>
                                            <span><strong>{{ $meta[0] }}</strong><small>{{ $meta[1] }}</small></span>
                                            <i>{{ $aspectRatio === $value ? '✓' : '' }}</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rms-custom-select rms-custom-select-enhanced" x-data="{ open:false }" @click.outside="open=false">
                                <span class="rms-custom-select-label">Quality</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" @click="open=!open">
                                    <span>
                                        <b x-text="qualityLabel('{{ $quality }}')"></b>
                                        <small>Output quality</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <button type="button" class="{{ $quality === 'standard' ? 'selected' : '' }}" @click="setLivewireValue('quality','standard'); open=false">
                                        <span class="rms-option-quality standard">S</span>
                                        <span><strong>Standard</strong><small>Balanced speed & quality</small></span>
                                        <i>{{ $quality === 'standard' ? '✓' : '' }}</i>
                                    </button>
                                    <button type="button" class="{{ $quality === 'high' ? 'selected' : '' }}" @click="setLivewireValue('quality','high'); open=false">
                                        <span class="rms-option-quality high">H</span>
                                        <span><strong>High Quality</strong><small>Maximum visual detail</small></span>
                                        <i>{{ $quality === 'high' ? '✓' : '' }}</i>
                                    </button>
                                </div>
                            </div>

                            <div class="rms-custom-select full" x-data="{ open:false }" @click.outside="open=false">
                                <span class="rms-custom-select-label">Jumlah Gambar</span>
                                <button type="button" class="rms-custom-select-trigger" :class="{ 'is-open': open }" @click="open=!open">
                                    <span>
                                        <b x-text="imageCount + ' Gambar'"></b>
                                        <small>Jumlah hasil yang akan dibuat</small>
                                    </span>
                                    <i><svg viewBox="0 0 24 24"><path d="m7 9 5 5 5-5"/></svg></i>
                                </button>
                                <div class="rms-custom-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @foreach ([1,2,3,4] as $count)
                                        <button type="button" class="{{ (int)$imageCount === $count ? 'selected' : '' }}" @click="setLivewireValue('imageCount', {{ $count }}); open=false">
                                            <span class="rms-option-count">{{ $count }}</span>
                                            <span><strong>{{ $count }} {{ $count === 1 ? 'Gambar' : 'Gambar' }}</strong><small>{{ $count === 1 ? 'Satu hasil utama' : $count . ' variasi hasil' }}</small></span>
                                            <i>{{ (int)$imageCount === $count ? '✓' : '' }}</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="rms-generator-generate" @click="generateVisual()">
                        <span class="rms-generate-icon">✦</span>
                        <span>
                            <strong>Generate Gambar</strong>
                            <small>Buat visual dengan konfigurasi saat ini</small>
                        </span>
                        <b>
                            <svg viewBox="0 0 24 24"><path d="M5 12h13m-5-5 5 5-5 5"/></svg>
                        </b>
                    </button>
                </section>
            </div>
        </section>

        <aside class="rms-generator-history" :class="{ 'is-hidden': !historyOpen }">
            <div class="rms-generator-history-head">
                <div class="rms-history-title">
                    <span>◷</span>
                    <div>
                        <strong>Riwayat Generate</strong>
                        <small>Hasil gambar yang pernah kamu generate.</small>
                    </div>
                </div>
                <button type="button" class="rms-history-view">Lihat Semua <b>→</b></button>
            </div>

            <div class="rms-generator-history-list">
                @forelse ($this->recentGenerations as $generation)
                    <article class="rms-generator-history-item">
                        <div class="rms-history-item-meta">
                            <span>◷ {{ optional($generation->created_at)->format('d M, H:i') }}</span>
                            <button type="button">•••</button>
                        </div>
                        <strong>{{ $generation->template?->name ?? 'Generated Image' }} <small>— {{ $generation->store?->name ?? 'Store' }}</small></strong>
                        <div class="rms-history-images">
                            @forelse ($generation->generatedImages->take(2) as $image)
                                <img src="{{ $image->image_url ?: \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path) }}" alt="">
                            @empty
                                <div class="rms-history-placeholder">GENERATING</div>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="rms-history-empty">
                        <span>✦</span>
                        <strong>Belum ada hasil generate</strong>
                        <small>Hasil generate berikutnya akan muncul di panel ini.</small>
                    </div>
                @endforelse
            </div>
        </aside>

        <button type="button"
            class="rms-history-toggle"
            :class="{ 'is-collapsed': !historyOpen }"
            @click="toggleHistory()"
            :aria-label="historyOpen ? 'Sembunyikan riwayat' : 'Tampilkan riwayat'">
            <span class="rms-toggle-open" x-show="historyOpen">
                <svg viewBox="0 0 24 24"><path d="m15 5-7 7 7 7"/></svg>
            </span>
            <span class="rms-toggle-closed" x-show="!historyOpen">
                <svg viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg>
            </span>
        </button>
    </div>
</div>
