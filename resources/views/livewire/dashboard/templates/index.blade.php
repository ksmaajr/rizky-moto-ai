<div class="rms-template-page" wire:key="template-library-root">
    <section class="rms-template-hero rms-template-reveal">
        <div class="rms-template-hero-grid"></div>
        <div class="rms-template-hero-glow"></div>
        <span class="rms-template-eyebrow"><i></i> WORKSPACE / TEMPLATES</span>

        <div class="rms-template-hero-row">
            <div>
                <h1>Templates <span>Management.</span></h1>
                <p>Buat, kelola, dan gunakan template untuk menghasilkan konten marketplace dengan AI.</p>
            </div>

            <button type="button" class="rms-template-add" wire:click="openCreate">
                <b>+</b>
                <span>
                    <strong>Create Template</strong>
                    <small>Buat template baru</small>
                </span>
                <em>→</em>
            </button>
        </div>

        <div class="rms-template-meta">
            <span><i></i> Template workspace active</span>
            <b></b>
            <span>{{ $this->totalTemplates }} total templates</span>
            <span>{{ $this->activeTemplates }} active</span>
        </div>
    </section>

    <section class="rms-template-kpis rms-template-reveal" style="--reveal-delay:.06s">
        <article>
            <span class="rms-template-kpi-icon red">▤</span>
            <div><small>TOTAL TEMPLATES</small><strong>{{ $this->totalTemplates }}</strong><em>Across all stores</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon green">✓</span>
            <div><small>ACTIVE TEMPLATES</small><strong>{{ $this->activeTemplates }}</strong><em>Currently active</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon dark">○</span>
            <div><small>DRAFT / INACTIVE</small><strong>{{ $this->draftTemplates }}</strong><em>Not published yet</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon violet">✦</span>
            <div><small>TOTAL GENERATIONS</small><strong>{{ number_format($this->totalGenerations) }}</strong><em>Visuals generated</em></div>
        </article>
    </section>

    <section class="rms-template-toolbar rms-template-reveal" style="--reveal-delay:.1s">
        <label class="rms-template-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search template, store, keyword...">
            <kbd>⌘ K</kbd>
        </label>

        <div class="rms-template-filter-row">
            <div class="rms-template-select rms-template-store-filter" x-data="{ open:false }" @click.outside="open=false">
                <button type="button" class="rms-template-select-trigger" @click="open=!open" :aria-expanded="open.toString()">
                    @php
                        $selectedStore = $storeFilter !== 'all'
                            ? $this->stores->firstWhere('id', (int) $storeFilter)
                            : null;
                    @endphp

                    @if ($selectedStore)
                        <span class="rms-store-mini-logo">
                            @if ($selectedStore->logo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($selectedStore->logo_path) }}" alt="">
                            @else
                                <b>{{ strtoupper(substr($selectedStore->name, 0, 1)) }}</b>
                            @endif
                        </span>
                        <span class="rms-store-filter-label">{{ $selectedStore->name }}</span>
                    @else
                        <span class="rms-store-mini-logo all">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="18" height="7" rx="1.5"/>
                            </svg>
                        </span>
                        <span class="rms-store-filter-label">All Stores</span>
                    @endif
                    <b class="rms-select-chevron">⌄</b>
                </button>

                <div class="rms-template-select-menu rms-store-filter-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <div class="rms-store-menu-heading">
                        <span>FILTER BY STORE</span>
                        <small>{{ $this->stores->count() }} connected</small>
                    </div>

                    <button type="button" class="{{ $storeFilter === 'all' ? 'selected' : '' }}" @click="$wire.set('storeFilter','all'); open=false">
                        <span class="rms-store-option">
                            <span class="rms-store-option-logo all">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                    <rect x="3" y="14" width="18" height="7" rx="1.5"/>
                                </svg>
                            </span>
                            <span>
                                <strong>All Stores</strong>
                                <small>Show templates from every store</small>
                            </span>
                        </span>
                        <i>✓</i>
                    </button>

                    @foreach ($this->stores as $store)
                        <button type="button" class="{{ (string) $storeFilter === (string) $store->id ? 'selected' : '' }}" @click="$wire.set('storeFilter','{{ $store->id }}'); open=false">
                            <span class="rms-store-option">
                                <span class="rms-store-option-logo">
                                    @if ($store->logo_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path) }}" alt="">
                                    @else
                                        <b>{{ strtoupper(substr($store->name, 0, 1)) }}</b>
                                    @endif
                                </span>
                                <span>
                                    <strong>{{ $store->name }}</strong>
                                    <small>{{ $store->marketplace ?: 'Marketplace' }}</small>
                                </span>
                            </span>
                            <i>✓</i>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rms-template-select" x-data="{ open:false }" @click.outside="open=false">
                <button type="button" @click="open=!open">
                    <span>{{ $statusFilter === 'all' ? 'All Status' : ($statusFilter === 'active' ? 'Active' : 'Draft / Inactive') }}</span><b>⌄</b>
                </button>
                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <button type="button" @click="$wire.set('statusFilter','all'); open=false" class="{{ $statusFilter === 'all' ? 'selected' : '' }}">All Status <i>✓</i></button>
                    <button type="button" @click="$wire.set('statusFilter','active'); open=false" class="{{ $statusFilter === 'active' ? 'selected' : '' }}">Active <i>✓</i></button>
                    <button type="button" @click="$wire.set('statusFilter','draft'); open=false" class="{{ $statusFilter === 'draft' ? 'selected' : '' }}">Draft / Inactive <i>✓</i></button>
                </div>
            </div>

            <div class="rms-template-select" x-data="{ open:false }" @click.outside="open=false">
                <button type="button" @click="open=!open">
                    <span>{{ match($sortBy) { 'name' => 'Name A–Z', 'usage' => 'Most Used', 'oldest' => 'Oldest', default => 'Terbaru' } }}</span><b>⌄</b>
                </button>
                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <button type="button" @click="$wire.set('sortBy','latest'); open=false" class="{{ $sortBy === 'latest' ? 'selected' : '' }}">Terbaru <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','oldest'); open=false" class="{{ $sortBy === 'oldest' ? 'selected' : '' }}">Oldest <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','name'); open=false" class="{{ $sortBy === 'name' ? 'selected' : '' }}">Name A–Z <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','usage'); open=false" class="{{ $sortBy === 'usage' ? 'selected' : '' }}">Most Used <i>✓</i></button>
                </div>
            </div>

            <button type="button" class="rms-template-reset" wire:click="resetFilters">Reset ↻</button>
        </div>
    </section>

    <section class="rms-template-library rms-template-reveal" style="--reveal-delay:.14s">
        <div class="rms-template-section-head">
            <div>
                <span>TEMPLATE LIBRARY</span>
                <h2>Your templates</h2>
                <p>Kelola preset visual yang menjadi sumber konfigurasi untuk AI Generator.</p>
            </div>
            <strong><i></i> {{ $this->templates->count() }} RESULT{{ $this->templates->count() === 1 ? '' : 'S' }}</strong>
        </div>

        <div class="rms-template-results" wire:loading.class="is-updating" wire:target="search,storeFilter,statusFilter,sortBy,resetFilters">
            <div class="rms-template-local-loading" wire:loading.flex wire:target="search,storeFilter,statusFilter,sortBy,resetFilters">
                <div class="rms-template-spinner"></div>
                <span>Updating templates...</span>
            </div>

            @if ($this->templates->isEmpty())
                <div class="rms-template-empty" wire:key="template-empty-state">
                    <div class="rms-template-empty-orbit" aria-hidden="true"><i></i><b></b></div>
                    <div class="rms-template-empty-icon">▤</div>
                    <span class="rms-template-empty-kicker"><i></i> TEMPLATE LIBRARY</span>
                    <h3>Tidak ada template yang cocok.</h3>
                    <p>Coba ubah kata pencarian atau filter yang sedang digunakan.</p>
                    <div class="rms-template-empty-actions">
                        <button type="button" class="secondary" wire:click="resetFilters">Reset Filter</button>
                        <button type="button" class="primary" wire:click="openCreate">+ Create Template</button>
                    </div>
                </div>
            @else
                <div class="rms-template-grid">
                    @foreach ($this->templates as $template)
                        <article class="rms-template-card rms-template-card-v2" wire:key="template-{{ $template->id }}">
                            <div class="rms-template-preview rms-template-preview-v2">
                                @if ($template->example_image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($template->example_image_path) }}" alt="{{ $template->name }}">
                                @else
                                    <div class="rms-template-preview-placeholder">
                                        <span>RMS</span>
                                        <b>AI TEMPLATE</b>
                                    </div>
                                @endif

                                <div class="rms-template-preview-shade"></div>

                                <div class="rms-template-status {{ $template->is_active ? 'active' : 'draft' }}">
                                    <i></i>{{ $template->is_active ? 'Active' : 'Draft' }}
                                </div>

                                <div class="rms-template-card-menu" x-data="{ open:false }" @click.outside="open=false">
                                    <button type="button" @click="open=!open" aria-label="Template menu">•••</button>
                                    <div class="rms-template-menu rms-template-menu-v2" x-show="open" x-transition.opacity.scale.origin.top.right style="display:none">
                                        <div class="rms-template-menu-label">TEMPLATE ACTIONS</div>
                                        <button type="button" @click="$wire.openEdit({{ $template->id }}); open=false">
                                            <span>✎</span> Edit Template
                                        </button>
                                        <button type="button" @click="$wire.duplicate({{ $template->id }}); open=false">
                                            <span>⧉</span> Duplicate
                                        </button>
                                        <button type="button" @click="$wire.toggleStatus({{ $template->id }}); open=false">
                                            <span>{{ $template->is_active ? '◌' : '✓' }}</span>
                                            {{ $template->is_active ? 'Set Draft' : 'Activate' }}
                                        </button>
                                        <div class="rms-template-menu-divider"></div>
                                        <button type="button" class="danger" @click="$wire.confirmDelete({{ $template->id }}); open=false">
                                            <span>⌫</span> Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="rms-template-preview-bottom">
                                    <span class="rms-template-ratio-badge">{{ strtoupper($template->aspect_ratio ?? '1:1') }}</span>
                                    <span class="rms-template-preview-tag">AI READY</span>
                                </div>
                            </div>

                            <div class="rms-template-card-body rms-template-card-body-v2">
                                <div class="rms-template-store-identity">
                                    <span class="rms-template-store-logo">
                                        @if ($template->store?->logo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($template->store->logo_path) }}" alt="">
                                        @else
                                            <b>{{ strtoupper(substr($template->store?->name ?? 'S', 0, 1)) }}</b>
                                        @endif
                                    </span>
                                    <span>
                                        <small>STORE</small>
                                        <strong>{{ $template->store?->name ?? 'Unknown Store' }}</strong>
                                    </span>
                                    <em>{{ $template->store?->marketplace ?: 'Marketplace' }}</em>
                                </div>

                                <div class="rms-template-card-title-row rms-template-card-title-row-v2">
                                    <div>
                                        <h3>{{ $template->name }}</h3>
                                    </div>
                                </div>

                                <p class="rms-template-description rms-template-description-v2">
                                    {{ $template->description ?: 'Template visual marketplace siap digunakan oleh AI Generator.' }}
                                </p>

                                <div class="rms-template-card-meta rms-template-card-meta-v2">
                                    <span>
                                        <i>✦</i>
                                        <b>{{ ucfirst($template->output_quality ?? 'high') }}</b>
                                        <small>Quality</small>
                                    </span>
                                    <span>
                                        <i>▣</i>
                                        <b>{{ strtoupper($template->aspect_ratio ?? '1:1') }}</b>
                                        <small>Aspect Ratio</small>
                                    </span>
                                    <span>
                                        <i>◫</i>
                                        <b>{{ number_format($template->generations_count) }}</b>
                                        <small>Generations</small>
                                    </span>
                                    <span>
                                        <i>↗</i>
                                        <b>#{{ str_pad((string) $template->sort_order, 2, '0', STR_PAD_LEFT) }}</b>
                                        <small>Order</small>
                                    </span>
                                </div>

                                <div class="rms-template-card-actions rms-template-card-actions-v2">
                                    <button type="button" wire:click="openEdit({{ $template->id }})">
                                        <span>Manage Template</span>
                                        <b>→</b>
                                    </button>
                                    <button type="button" class="ghost" wire:click="toggleStatus({{ $template->id }})">
                                        {{ $template->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                    <button type="button" class="rms-template-delete-inline" wire:click="confirmDelete({{ $template->id }})" aria-label="Delete {{ $template->name }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M4 7h16"/>
                                            <path d="M9 7V4h6v3"/>
                                            <path d="M7 7l1 13h8l1-13"/>
                                            <path d="M10 11v5M14 11v5"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach

                    <button type="button" class="rms-template-create-card" wire:click="openCreate">
                        <span>+</span>
                        <strong>Create New Template</strong>
                        <small>Tambahkan preset visual baru untuk Store dan AI Generator.</small>
                        <em>Create template <b>→</b></em>
                    </button>
                </div>
            @endif
        </div>
    </section>

    @if ($showForm)
        @teleport('body')
        <div class="rms-template-modal-layer" wire:key="template-form-modal" x-data @keydown.escape.window="$wire.closeForm()">
            <div class="rms-template-modal-backdrop" wire:click="closeForm"></div>
            <section class="rms-template-modal" role="dialog" aria-modal="true">
                <div class="rms-template-modal-head">
                    <div>
                        <span><i></i> {{ $editing ? 'EDIT TEMPLATE' : 'NEW TEMPLATE' }}</span>
                        <h2>{{ $editing ? 'Edit Template.' : 'Create Template.' }}</h2>
                        <p>{{ $editing ? 'Perbarui konfigurasi preset visual.' : 'Buat preset visual yang siap dipakai AI Generator.' }}</p>
                    </div>
                    <button type="button" wire:click="closeForm">×</button>
                </div>

                <form wire:submit="save" class="rms-template-form-scroll">
                    <div class="rms-template-form-grid">
                        <label class="rms-template-field">
                            <span>Template Name *</span>
                            <input type="text" wire:model="templateName" placeholder="Contoh: Promo Helm & Apparel">
                            @error('templateName') <small class="error">{{ $message }}</small> @enderror
                        </label>

                        <div class="rms-template-field">
                            <span>Store *</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                @php
                                    $formStore = $templateStoreId ? $this->stores->firstWhere('id', (int) $templateStoreId) : null;
                                @endphp
                                <button type="button" class="rms-template-select-trigger" @click="open=!open" :aria-expanded="open.toString()">
                                    @if ($formStore)
                                        <span class="rms-store-mini-logo">
                                            @if ($formStore->logo_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($formStore->logo_path) }}" alt="">
                                            @else
                                                <b>{{ strtoupper(substr($formStore->name, 0, 1)) }}</b>
                                            @endif
                                        </span>
                                        <span class="rms-store-filter-label">{{ $formStore->name }}</span>
                                    @else
                                        <span class="rms-store-filter-label">Pilih Store</span>
                                    @endif
                                    <b class="rms-select-chevron">⌄</b>
                                </button>
                                <div class="rms-template-select-menu rms-store-filter-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <div class="rms-store-menu-heading">
                                        <span>SELECT STORE</span>
                                        <small>Template owner</small>
                                    </div>
                                    @foreach ($this->stores as $store)
                                        <button type="button" class="{{ (int) $templateStoreId === (int) $store->id ? 'selected' : '' }}" @click="$wire.set('templateStoreId',{{ $store->id }}); open=false">
                                            <span class="rms-store-option">
                                                <span class="rms-store-option-logo">
                                                    @if ($store->logo_path)
                                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path) }}" alt="">
                                                    @else
                                                        <b>{{ strtoupper(substr($store->name, 0, 1)) }}</b>
                                                    @endif
                                                </span>
                                                <span>
                                                    <strong>{{ $store->name }}</strong>
                                                    <small>{{ $store->marketplace ?: 'Marketplace' }}</small>
                                                </span>
                                            </span>
                                            <i>✓</i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            @error('templateStoreId') <small class="error">{{ $message }}</small> @enderror
                        </div>

                        <label class="rms-template-field full">
                            <span>Description</span>
                            <textarea wire:model="templateDescription" rows="3" placeholder="Deskripsi singkat template..."></textarea>
                            @error('templateDescription') <small class="error">{{ $message }}</small> @enderror
                        </label>

                        <label class="rms-template-field full">
                            <span>AI Prompt *</span>
                            <textarea wire:model="templatePrompt" rows="6" placeholder="Instruksi utama untuk menghasilkan visual..."></textarea>
                            @error('templatePrompt') <small class="error">{{ $message }}</small> @enderror
                        </label>

                        <label class="rms-template-field full">
                            <span>Negative Prompt</span>
                            <textarea wire:model="templateNegativePrompt" rows="4" placeholder="Elemen yang harus dihindari..."></textarea>
                            @error('templateNegativePrompt') <small class="error">{{ $message }}</small> @enderror
                        </label>

                        <div class="rms-template-field">
                            <span>Aspect Ratio</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                <button type="button" @click="open=!open"><span>{{ $templateAspectRatio }}</span><b>⌄</b></button>
                                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    @foreach (['1:1','4:5','3:4','4:3','16:9','9:16'] as $ratio)
                                        <button type="button" @click="$wire.set('templateAspectRatio','{{ $ratio }}'); open=false">{{ $ratio }} <i>{{ $ratio === $templateAspectRatio ? '✓' : '' }}</i></button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="rms-template-field">
                            <span>Output Quality</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                <button type="button" @click="open=!open"><span>{{ ucfirst($templateOutputQuality) }}</span><b>⌄</b></button>
                                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <button type="button" @click="$wire.set('templateOutputQuality','standard'); open=false">Standard <i>{{ $templateOutputQuality === 'standard' ? '✓' : '' }}</i></button>
                                    <button type="button" @click="$wire.set('templateOutputQuality','high'); open=false">High <i>{{ $templateOutputQuality === 'high' ? '✓' : '' }}</i></button>
                                </div>
                            </div>
                        </div>

                        <label class="rms-template-field">
                            <span>Sort Order</span>
                            <input type="number" min="0" max="9999" wire:model="templateSortOrder">
                            @error('templateSortOrder') <small class="error">{{ $message }}</small> @enderror
                        </label>

                        <div class="rms-template-field">
                            <span>Status</span>
                            <button type="button" class="rms-template-status-toggle {{ $templateActive ? 'on' : '' }}" wire:click="$toggle('templateActive')">
                                <i></i><strong>{{ $templateActive ? 'Active' : 'Draft' }}</strong>
                            </button>
                        </div>

                        <div class="rms-template-field full">
                            <span>Example / Reference Image</span>
                            <div class="rms-template-upload" x-data="{ uploading:false, progress:0 }" x-on:livewire-upload-start="uploading=true" x-on:livewire-upload-finish="uploading=false" x-on:livewire-upload-error="uploading=false" x-on:livewire-upload-progress="progress=$event.detail.progress">
                                <div class="rms-template-upload-preview">
                                    @if ($templateExampleImage)
                                        <img src="{{ $templateExampleImage->temporaryUrl() }}" alt="Preview">
                                    @elseif ($templateExistingImage)
                                        <img src="{{ Storage::disk('public')->url($templateExistingImage) }}" alt="Current image">
                                    @else
                                        <span>IMG</span>
                                    @endif
                                </div>
                                <div class="rms-template-upload-copy">
                                    <label>
                                        <strong>Choose image</strong>
                                        <input type="file" wire:model="templateExampleImage" accept="image/png,image/jpeg,image/webp">
                                    </label>
                                    <small>PNG, JPG, WEBP · maksimal 10 MB</small>
                                    <div class="rms-template-upload-progress" x-show="uploading"><i :style="`width:${progress}%`"></i></div>
                                </div>
                            </div>
                            @error('templateExampleImage') <small class="error">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="rms-template-form-footer">
                        <button type="button" class="secondary" wire:click="closeForm">Batal</button>
                        <button type="submit" class="primary" wire:loading.attr="disabled" wire:target="save,templateExampleImage">
                            <span wire:loading.remove wire:target="save">{{ $editing ? 'Simpan Perubahan' : 'Create Template' }}</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
        @endteleport
    @endif

    @if ($showDeleteModal)
        @teleport('body')
        <div class="rms-template-modal-layer rms-template-delete-layer" wire:key="template-delete-modal" x-data @keydown.escape.window="$wire.closeDelete()">
            <div class="rms-template-modal-backdrop" wire:click="closeDelete"></div>
            <section class="rms-template-delete-modal rms-template-delete-modal-v2" role="dialog" aria-modal="true" aria-labelledby="template-delete-title">
                <div class="rms-template-delete-icon-wrap">
                    <span class="rms-template-delete-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7h16"/>
                            <path d="M9 7V4h6v3"/>
                            <path d="M7 7l1 13h8l1-13"/>
                            <path d="M10 11v5M14 11v5"/>
                        </svg>
                    </span>
                </div>
                <span class="rms-template-danger-label rms-template-danger-label-v2"><i></i> DELETE ACTION</span>
                <h2 id="template-delete-title">Hapus template?</h2>
                <p>Template <strong>{{ $deletingName }}</strong> beserta konfigurasi visualnya akan dihapus dari Template Library. <b>Tindakan ini tidak dapat dibatalkan.</b></p>
                <div class="rms-template-delete-actions rms-template-delete-actions-v2">
                    <button type="button" class="secondary" wire:click="closeDelete">
                        <span>Batal</span>
                    </button>
                    <button type="button" class="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                        <span wire:loading.remove wire:target="delete">Ya, Hapus <b>→</b></span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
                <div class="rms-template-delete-esc"><kbd>ESC</kbd><span>untuk menutup</span></div>
            </section>
        </div>
        @endteleport
    @endif
</div>


<style>
    /* V2 — custom template delete confirmation */
    .rms-template-delete-modal-v2{
        position:relative;
        width:min(540px,calc(100vw - 32px));
        padding:34px 34px 28px!important;
        border:1px solid rgba(228,228,231,.9);
        border-radius:26px!important;
        background:
            radial-gradient(circle at 88% 0%,rgba(244,63,94,.08),transparent 42%),
            #fff;
        box-shadow:0 35px 100px rgba(24,24,27,.24);
        overflow:hidden;
        animation:rmsTemplateDeleteIn .32s cubic-bezier(.22,1,.36,1);
    }
    .rms-template-delete-modal-v2:before{
        content:"";
        position:absolute;
        left:0;right:0;top:0;height:3px;
        background:linear-gradient(90deg,#fb7185,#ef233c,#f43f5e);
    }
    .rms-template-delete-icon-wrap{
        display:flex;
        margin-bottom:20px;
    }
    .rms-template-delete-icon{
        display:grid;
        width:64px;height:64px;
        place-items:center;
        border:1px solid rgba(244,63,94,.18);
        border-radius:18px;
        background:rgba(255,241,242,.86);
        color:#ef233c;
        box-shadow:0 10px 26px rgba(244,63,94,.10),inset 0 1px 0 rgba(255,255,255,.95);
    }
    .rms-template-delete-icon svg{width:27px;height:27px}
    .rms-template-danger-label-v2{
        display:flex!important;
        align-items:center;
        gap:7px;
        margin:0 0 10px!important;
        color:#ef233c!important;
        font-size:9px!important;
        letter-spacing:.14em!important;
        font-weight:900!important;
    }
    .rms-template-danger-label-v2 i{
        width:7px;height:7px;border-radius:50%;
        background:#fb7185;
        box-shadow:0 0 0 4px rgba(251,113,133,.11);
    }
    .rms-template-delete-modal-v2 h2{
        margin:0!important;
        color:#18181b;
        font-size:27px!important;
        line-height:1.08!important;
        letter-spacing:-.035em!important;
        font-weight:900!important;
    }
    .rms-template-delete-modal-v2>p{
        margin:12px 0 0!important;
        color:#71717a;
        font-size:12px!important;
        line-height:1.65!important;
    }
    .rms-template-delete-modal-v2>p strong{color:#27272a;font-weight:850}
    .rms-template-delete-modal-v2>p b{color:#52525b;font-weight:750}
    .rms-template-delete-actions-v2{
        display:grid!important;
        grid-template-columns:1fr 1fr;
        gap:12px!important;
        margin-top:28px!important;
    }
    .rms-template-delete-actions-v2 button{
        min-height:54px!important;
        border-radius:14px!important;
        font-size:11px!important;
        font-weight:850!important;
        transition:transform .22s cubic-bezier(.22,1,.36,1),box-shadow .22s ease,background .22s ease,border-color .22s ease!important;
    }
    .rms-template-delete-actions-v2 .secondary{
        border:1px solid rgba(228,228,231,.8)!important;
        background:#fff!important;
        color:#27272a!important;
    }
    .rms-template-delete-actions-v2 .secondary:hover{
        background:#fafafa!important;
        border-color:#d4d4d8!important;
        transform:translateY(-1px);
    }
    .rms-template-delete-actions-v2 .danger{
        border:0!important;
        background:linear-gradient(135deg,#fb344d,#e91532)!important;
        color:#fff!important;
        box-shadow:0 12px 26px rgba(239,35,60,.22)!important;
    }
    .rms-template-delete-actions-v2 .danger:hover{
        transform:translateY(-1px);
        box-shadow:0 16px 32px rgba(239,35,60,.28)!important;
    }
    .rms-template-delete-actions-v2 button:active{
        transform:scale(.985)!important;
    }
    .rms-template-delete-actions-v2 .danger b{
        margin-left:7px;
        font-size:14px;
    }
    .rms-template-delete-esc{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        margin-top:17px;
        color:#a1a1aa;
        font-size:9px;
    }
    .rms-template-delete-esc kbd{
        display:inline-grid;
        min-width:34px;height:25px;
        padding:0 7px;
        place-items:center;
        border:1px solid #e4e4e7;
        border-radius:7px;
        background:#fafafa;
        color:#71717a;
        font-size:8px;
        font-weight:850;
        box-shadow:0 1px 0 #e4e4e7;
    }
    @keyframes rmsTemplateDeleteIn{
        from{opacity:0;transform:translateY(10px) scale(.975)}
        to{opacity:1;transform:none}
    }
    @media(max-width:600px){
        .rms-template-delete-modal-v2{
            width:calc(100vw - 24px);
            padding:27px 22px 23px!important;
            border-radius:22px!important;
        }
        .rms-template-delete-icon{width:56px;height:56px;border-radius:16px}
        .rms-template-delete-icon svg{width:24px;height:24px}
        .rms-template-delete-modal-v2 h2{font-size:23px!important}
        .rms-template-delete-modal-v2>p{font-size:10.5px!important;line-height:1.6!important}
        .rms-template-delete-actions-v2{gap:9px!important;margin-top:22px!important}
        .rms-template-delete-actions-v2 button{min-height:50px!important}
    }
</style>

<style>
.rms-template-card-actions-v2{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 112px 56px!important;
    gap:10px!important;
    align-items:stretch!important;
}
.rms-template-delete-inline{
    width:56px!important;height:54px!important;min-height:54px!important;
    display:grid!important;place-items:center!important;
    border:1px solid rgba(239,68,68,.18)!important;
    border-radius:14px!important;background:#fff!important;color:#ef233c!important;
    cursor:pointer!important;transition:all .22s cubic-bezier(.22,1,.36,1)!important;
}
.rms-template-delete-inline svg{width:18px;height:18px}
.rms-template-delete-inline:hover{
    color:#fff!important;background:#ef233c!important;border-color:#ef233c!important;
    box-shadow:0 10px 24px rgba(239,35,60,.2)!important;transform:translateY(-1px)
}
.rms-template-delete-inline:active{transform:scale(.96)!important}
@media(max-width:720px){
    .rms-template-card-actions-v2{grid-template-columns:minmax(0,1fr) 48px!important}
    .rms-template-card-actions-v2 .ghost{display:none!important}
}
</style>


<style>
/* V27 — template actions aligned to generation-history action language */
.rms-template-card-actions-v2{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 140px 60px!important;
    gap:10px!important;
    align-items:stretch!important;
    min-width:0!important;
}
.rms-template-card-actions-v2 > button{
    min-width:0!important;
    height:54px!important;
    min-height:54px!important;
    margin:0!important;
    padding:0 14px!important;
    box-sizing:border-box!important;
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:7px!important;
    border-radius:14px!important;
    white-space:nowrap!important;
    line-height:1!important;
}
.rms-template-card-actions-v2 > button:first-child{
    padding-left:12px!important;
    padding-right:12px!important;
    justify-content:center!important;
}
.rms-template-card-actions-v2 > button:first-child span{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
}
.rms-template-card-actions-v2 > button:first-child b{
    display:inline-flex!important;
    margin-left:2px!important;
    line-height:1!important;
}
.rms-template-card-actions-v2 > .ghost{
    padding-left:16px!important;
    padding-right:16px!important;
}
.rms-template-delete-inline{
    width:60px!important;
    height:54px!important;
    min-height:54px!important;
    padding:0!important;
    display:grid!important;
    place-items:center!important;
    border:1px solid rgba(239,68,68,.18)!important;
    border-radius:14px!important;
    background:#fff!important;
    color:#ef233c!important;
    box-sizing:border-box!important;
}
.rms-template-delete-inline svg{
    width:18px!important;
    height:18px!important;
    display:block!important;
}
.rms-template-delete-inline:hover{
    color:#fff!important;
    background:#ef233c!important;
    border-color:#ef233c!important;
    box-shadow:0 10px 24px rgba(239,35,60,.2)!important;
    transform:translateY(-1px);
}
.rms-template-delete-inline:active{
    transform:scale(.97)!important;
}
@media(max-width:720px){
    .rms-template-card-actions-v2{
        grid-template-columns:minmax(0,1fr) 54px!important;
        gap:9px!important;
    }
    .rms-template-card-actions-v2 .ghost{display:none!important}
    .rms-template-card-actions-v2 > button:first-child{
        padding-left:16px!important;
        padding-right:14px!important;
    }
    .rms-template-delete-inline{
        width:54px!important;
        height:54px!important;
    }
}
</style>

/* V28 — prevent template action text overflow */
.rms-template-card-actions-v2 > button:first-child{
    min-width:0!important;
    overflow:hidden!important;
    font-size:10px!important;
    letter-spacing:-.01em!important;
}
.rms-template-card-actions-v2 > button:first-child span{
    min-width:0!important;
    overflow:hidden!important;
    text-overflow:ellipsis!important;
}
.rms-template-card-actions-v2 > button:first-child b{
    flex:0 0 auto!important;
}
.rms-template-card-actions-v2 > .ghost{
    width:140px!important;
    min-width:140px!important;
}
@media(max-width:720px){
    .rms-template-card-actions-v2{
        grid-template-columns:minmax(0,1fr) 54px!important;
    }
    .rms-template-card-actions-v2 > .ghost{display:none!important}
    .rms-template-card-actions-v2 > button:first-child{font-size:10px!important}
}
