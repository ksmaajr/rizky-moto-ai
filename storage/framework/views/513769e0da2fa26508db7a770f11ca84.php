<div class="rms-template-page" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'template-library-root'; ?>wire:key="template-library-root">
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
            <span><?php echo e($this->totalTemplates); ?> total templates</span>
            <span><?php echo e($this->activeTemplates); ?> active</span>
        </div>
    </section>

    <section class="rms-template-kpis rms-template-reveal" style="--reveal-delay:.06s">
        <article>
            <span class="rms-template-kpi-icon red">▤</span>
            <div><small>TOTAL TEMPLATES</small><strong><?php echo e($this->totalTemplates); ?></strong><em>Across all stores</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon green">✓</span>
            <div><small>ACTIVE TEMPLATES</small><strong><?php echo e($this->activeTemplates); ?></strong><em>Currently active</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon dark">○</span>
            <div><small>DRAFT / INACTIVE</small><strong><?php echo e($this->draftTemplates); ?></strong><em>Not published yet</em></div>
        </article>
        <article>
            <span class="rms-template-kpi-icon violet">✦</span>
            <div><small>TOTAL GENERATIONS</small><strong><?php echo e(number_format($this->totalGenerations)); ?></strong><em>Visuals generated</em></div>
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
                    <?php
                        $selectedStore = $storeFilter !== 'all'
                            ? $this->stores->firstWhere('id', (int) $storeFilter)
                            : null;
                    ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedStore): ?>
                        <span class="rms-store-mini-logo">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedStore->logo_path): ?>
                                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($selectedStore->logo_path)); ?>" alt="">
                            <?php else: ?>
                                <b><?php echo e(strtoupper(substr($selectedStore->name, 0, 1))); ?></b>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </span>
                        <span class="rms-store-filter-label"><?php echo e($selectedStore->name); ?></span>
                    <?php else: ?>
                        <span class="rms-store-mini-logo all">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="18" height="7" rx="1.5"/>
                            </svg>
                        </span>
                        <span class="rms-store-filter-label">All Stores</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <b class="rms-select-chevron">⌄</b>
                </button>

                <div class="rms-template-select-menu rms-store-filter-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <div class="rms-store-menu-heading">
                        <span>FILTER BY STORE</span>
                        <small><?php echo e($this->stores->count()); ?> connected</small>
                    </div>

                    <button type="button" class="<?php echo e($storeFilter === 'all' ? 'selected' : ''); ?>" @click="$wire.set('storeFilter','all'); open=false">
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

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <button type="button" class="<?php echo e((string) $storeFilter === (string) $store->id ? 'selected' : ''); ?>" @click="$wire.set('storeFilter','<?php echo e($store->id); ?>'); open=false">
                            <span class="rms-store-option">
                                <span class="rms-store-option-logo">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($store->logo_path): ?>
                                        <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path)); ?>" alt="">
                                    <?php else: ?>
                                        <b><?php echo e(strtoupper(substr($store->name, 0, 1))); ?></b>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </span>
                                <span>
                                    <strong><?php echo e($store->name); ?></strong>
                                    <small><?php echo e($store->marketplace ?: 'Marketplace'); ?></small>
                                </span>
                            </span>
                            <i>✓</i>
                        </button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            <div class="rms-template-select" x-data="{ open:false }" @click.outside="open=false">
                <button type="button" @click="open=!open">
                    <span><?php echo e($statusFilter === 'all' ? 'All Status' : ($statusFilter === 'active' ? 'Active' : 'Draft / Inactive')); ?></span><b>⌄</b>
                </button>
                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <button type="button" @click="$wire.set('statusFilter','all'); open=false" class="<?php echo e($statusFilter === 'all' ? 'selected' : ''); ?>">All Status <i>✓</i></button>
                    <button type="button" @click="$wire.set('statusFilter','active'); open=false" class="<?php echo e($statusFilter === 'active' ? 'selected' : ''); ?>">Active <i>✓</i></button>
                    <button type="button" @click="$wire.set('statusFilter','draft'); open=false" class="<?php echo e($statusFilter === 'draft' ? 'selected' : ''); ?>">Draft / Inactive <i>✓</i></button>
                </div>
            </div>

            <div class="rms-template-select" x-data="{ open:false }" @click.outside="open=false">
                <button type="button" @click="open=!open">
                    <span><?php echo e(match($sortBy) { 'name' => 'Name A–Z', 'usage' => 'Most Used', 'oldest' => 'Oldest', default => 'Terbaru' }); ?></span><b>⌄</b>
                </button>
                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                    <button type="button" @click="$wire.set('sortBy','latest'); open=false" class="<?php echo e($sortBy === 'latest' ? 'selected' : ''); ?>">Terbaru <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','oldest'); open=false" class="<?php echo e($sortBy === 'oldest' ? 'selected' : ''); ?>">Oldest <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','name'); open=false" class="<?php echo e($sortBy === 'name' ? 'selected' : ''); ?>">Name A–Z <i>✓</i></button>
                    <button type="button" @click="$wire.set('sortBy','usage'); open=false" class="<?php echo e($sortBy === 'usage' ? 'selected' : ''); ?>">Most Used <i>✓</i></button>
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
            <strong><i></i> <?php echo e($this->templates->count()); ?> RESULT<?php echo e($this->templates->count() === 1 ? '' : 'S'); ?></strong>
        </div>

        <div class="rms-template-results" wire:loading.class="is-updating" wire:target="search,storeFilter,statusFilter,sortBy,resetFilters">
            <div class="rms-template-local-loading" wire:loading.flex wire:target="search,storeFilter,statusFilter,sortBy,resetFilters">
                <div class="rms-template-spinner"></div>
                <span>Updating templates...</span>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->templates->isEmpty()): ?>
                <div class="rms-template-empty" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'template-empty-state'; ?>wire:key="template-empty-state">
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
            <?php else: ?>
                <div class="rms-template-grid">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <article class="rms-template-card rms-template-card-v2" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'template-'.e($template->id).''; ?>wire:key="template-<?php echo e($template->id); ?>">
                            <div class="rms-template-preview rms-template-preview-v2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($template->example_image_path): ?>
                                    <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($template->example_image_path)); ?>" alt="<?php echo e($template->name); ?>">
                                <?php else: ?>
                                    <div class="rms-template-preview-placeholder">
                                        <span>RMS</span>
                                        <b>AI TEMPLATE</b>
                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <div class="rms-template-preview-shade"></div>

                                <div class="rms-template-status <?php echo e($template->is_active ? 'active' : 'draft'); ?>">
                                    <i></i><?php echo e($template->is_active ? 'Active' : 'Draft'); ?>

                                </div>

                                <div class="rms-template-card-menu" x-data="{ open:false }" @click.outside="open=false">
                                    <button type="button" @click="open=!open" aria-label="Template menu">•••</button>
                                    <div class="rms-template-menu rms-template-menu-v2" x-show="open" x-transition.opacity.scale.origin.top.right style="display:none">
                                        <div class="rms-template-menu-label">TEMPLATE ACTIONS</div>
                                        <button type="button" @click="$wire.openEdit(<?php echo e($template->id); ?>); open=false">
                                            <span>✎</span> Edit Template
                                        </button>
                                        <button type="button" @click="$wire.duplicate(<?php echo e($template->id); ?>); open=false">
                                            <span>⧉</span> Duplicate
                                        </button>
                                        <button type="button" @click="$wire.toggleStatus(<?php echo e($template->id); ?>); open=false">
                                            <span><?php echo e($template->is_active ? '◌' : '✓'); ?></span>
                                            <?php echo e($template->is_active ? 'Set Draft' : 'Activate'); ?>

                                        </button>
                                        <div class="rms-template-menu-divider"></div>
                                        <button type="button" class="danger" @click="$wire.confirmDelete(<?php echo e($template->id); ?>); open=false">
                                            <span>⌫</span> Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="rms-template-preview-bottom">
                                    <span class="rms-template-ratio-badge"><?php echo e(strtoupper($template->aspect_ratio ?? '1:1')); ?></span>
                                    <span class="rms-template-preview-tag">AI READY</span>
                                </div>
                            </div>

                            <div class="rms-template-card-body rms-template-card-body-v2">
                                <div class="rms-template-store-identity">
                                    <span class="rms-template-store-logo">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($template->store?->logo_path): ?>
                                            <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($template->store->logo_path)); ?>" alt="">
                                        <?php else: ?>
                                            <b><?php echo e(strtoupper(substr($template->store?->name ?? 'S', 0, 1))); ?></b>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </span>
                                    <span>
                                        <small>STORE</small>
                                        <strong><?php echo e($template->store?->name ?? 'Unknown Store'); ?></strong>
                                    </span>
                                    <em><?php echo e($template->store?->marketplace ?: 'Marketplace'); ?></em>
                                </div>

                                <div class="rms-template-card-title-row rms-template-card-title-row-v2">
                                    <div>
                                        <h3><?php echo e($template->name); ?></h3>
                                    </div>
                                </div>

                                <p class="rms-template-description rms-template-description-v2">
                                    <?php echo e($template->description ?: 'Template visual marketplace siap digunakan oleh AI Generator.'); ?>

                                </p>

                                <div class="rms-template-card-meta rms-template-card-meta-v2">
                                    <span>
                                        <i>✦</i>
                                        <b><?php echo e(ucfirst($template->output_quality ?? 'high')); ?></b>
                                        <small>Quality</small>
                                    </span>
                                    <span>
                                        <i>▣</i>
                                        <b><?php echo e(strtoupper($template->aspect_ratio ?? '1:1')); ?></b>
                                        <small>Aspect Ratio</small>
                                    </span>
                                    <span>
                                        <i>◫</i>
                                        <b><?php echo e(number_format($template->generations_count)); ?></b>
                                        <small>Generations</small>
                                    </span>
                                    <span>
                                        <i>↗</i>
                                        <b>#<?php echo e(str_pad((string) $template->sort_order, 2, '0', STR_PAD_LEFT)); ?></b>
                                        <small>Order</small>
                                    </span>
                                </div>

                                <div class="rms-template-card-actions rms-template-card-actions-v2">
                                    <button type="button" wire:click="openEdit(<?php echo e($template->id); ?>)">
                                        <span>Manage Template</span>
                                        <b>→</b>
                                    </button>
                                    <button type="button" class="ghost" wire:click="toggleStatus(<?php echo e($template->id); ?>)">
                                        <?php echo e($template->is_active ? 'Pause' : 'Activate'); ?>

                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    <button type="button" class="rms-template-create-card" wire:click="openCreate">
                        <span>+</span>
                        <strong>Create New Template</strong>
                        <small>Tambahkan preset visual baru untuk Store dan AI Generator.</small>
                        <em>Create template <b>→</b></em>
                    </button>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
        <template x-teleport="<?php echo e('body'); ?>">
        <div class="rms-template-modal-layer" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'template-form-modal'; ?>wire:key="template-form-modal" x-data @keydown.escape.window="$wire.closeForm()">
            <div class="rms-template-modal-backdrop" wire:click="closeForm"></div>
            <section class="rms-template-modal" role="dialog" aria-modal="true">
                <div class="rms-template-modal-head">
                    <div>
                        <span><i></i> <?php echo e($editing ? 'EDIT TEMPLATE' : 'NEW TEMPLATE'); ?></span>
                        <h2><?php echo e($editing ? 'Edit Template.' : 'Create Template.'); ?></h2>
                        <p><?php echo e($editing ? 'Perbarui konfigurasi preset visual.' : 'Buat preset visual yang siap dipakai AI Generator.'); ?></p>
                    </div>
                    <button type="button" wire:click="closeForm">×</button>
                </div>

                <form wire:submit="save" class="rms-template-form-scroll">
                    <div class="rms-template-form-grid">
                        <label class="rms-template-field">
                            <span>Template Name *</span>
                            <input type="text" wire:model="templateName" placeholder="Contoh: Promo Helm & Apparel">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <div class="rms-template-field">
                            <span>Store *</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                <?php
                                    $formStore = $templateStoreId ? $this->stores->firstWhere('id', (int) $templateStoreId) : null;
                                ?>
                                <button type="button" class="rms-template-select-trigger" @click="open=!open" :aria-expanded="open.toString()">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($formStore): ?>
                                        <span class="rms-store-mini-logo">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($formStore->logo_path): ?>
                                                <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($formStore->logo_path)); ?>" alt="">
                                            <?php else: ?>
                                                <b><?php echo e(strtoupper(substr($formStore->name, 0, 1))); ?></b>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </span>
                                        <span class="rms-store-filter-label"><?php echo e($formStore->name); ?></span>
                                    <?php else: ?>
                                        <span class="rms-store-filter-label">Pilih Store</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <b class="rms-select-chevron">⌄</b>
                                </button>
                                <div class="rms-template-select-menu rms-store-filter-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <div class="rms-store-menu-heading">
                                        <span>SELECT STORE</span>
                                        <small>Template owner</small>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button type="button" class="<?php echo e((int) $templateStoreId === (int) $store->id ? 'selected' : ''); ?>" @click="$wire.set('templateStoreId',<?php echo e($store->id); ?>); open=false">
                                            <span class="rms-store-option">
                                                <span class="rms-store-option-logo">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($store->logo_path): ?>
                                                        <img src="<?php echo e(\Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path)); ?>" alt="">
                                                    <?php else: ?>
                                                        <b><?php echo e(strtoupper(substr($store->name, 0, 1))); ?></b>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </span>
                                                <span>
                                                    <strong><?php echo e($store->name); ?></strong>
                                                    <small><?php echo e($store->marketplace ?: 'Marketplace'); ?></small>
                                                </span>
                                            </span>
                                            <i>✓</i>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateStoreId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <label class="rms-template-field full">
                            <span>Description</span>
                            <textarea wire:model="templateDescription" rows="3" placeholder="Deskripsi singkat template..."></textarea>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateDescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label class="rms-template-field full">
                            <span>AI Prompt *</span>
                            <textarea wire:model="templatePrompt" rows="6" placeholder="Instruksi utama untuk menghasilkan visual..."></textarea>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templatePrompt'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label class="rms-template-field full">
                            <span>Negative Prompt</span>
                            <textarea wire:model="templateNegativePrompt" rows="4" placeholder="Elemen yang harus dihindari..."></textarea>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateNegativePrompt'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <div class="rms-template-field">
                            <span>Aspect Ratio</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                <button type="button" @click="open=!open"><span><?php echo e($templateAspectRatio); ?></span><b>⌄</b></button>
                                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['1:1','4:5','3:4','4:3','16:9','9:16']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ratio): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button type="button" @click="$wire.set('templateAspectRatio','<?php echo e($ratio); ?>'); open=false"><?php echo e($ratio); ?> <i><?php echo e($ratio === $templateAspectRatio ? '✓' : ''); ?></i></button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="rms-template-field">
                            <span>Output Quality</span>
                            <div class="rms-template-select rms-template-select-form" x-data="{ open:false }" @click.outside="open=false">
                                <button type="button" @click="open=!open"><span><?php echo e(ucfirst($templateOutputQuality)); ?></span><b>⌄</b></button>
                                <div class="rms-template-select-menu" x-show="open" x-transition.opacity.scale.origin.top style="display:none">
                                    <button type="button" @click="$wire.set('templateOutputQuality','standard'); open=false">Standard <i><?php echo e($templateOutputQuality === 'standard' ? '✓' : ''); ?></i></button>
                                    <button type="button" @click="$wire.set('templateOutputQuality','high'); open=false">High <i><?php echo e($templateOutputQuality === 'high' ? '✓' : ''); ?></i></button>
                                </div>
                            </div>
                        </div>

                        <label class="rms-template-field">
                            <span>Sort Order</span>
                            <input type="number" min="0" max="9999" wire:model="templateSortOrder">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateSortOrder'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <div class="rms-template-field">
                            <span>Status</span>
                            <button type="button" class="rms-template-status-toggle <?php echo e($templateActive ? 'on' : ''); ?>" wire:click="$toggle('templateActive')">
                                <i></i><strong><?php echo e($templateActive ? 'Active' : 'Draft'); ?></strong>
                            </button>
                        </div>

                        <div class="rms-template-field full">
                            <span>Example / Reference Image</span>
                            <div class="rms-template-upload" x-data="{ uploading:false, progress:0 }" x-on:livewire-upload-start="uploading=true" x-on:livewire-upload-finish="uploading=false" x-on:livewire-upload-error="uploading=false" x-on:livewire-upload-progress="progress=$event.detail.progress">
                                <div class="rms-template-upload-preview">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($templateExampleImage): ?>
                                        <img src="<?php echo e($templateExampleImage->temporaryUrl()); ?>" alt="Preview">
                                    <?php elseif($templateExistingImage): ?>
                                        <img src="<?php echo e(Storage::disk('public')->url($templateExistingImage)); ?>" alt="Current image">
                                    <?php else: ?>
                                        <span>IMG</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['templateExampleImage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="rms-template-form-footer">
                        <button type="button" class="secondary" wire:click="closeForm">Batal</button>
                        <button type="submit" class="primary" wire:loading.attr="disabled" wire:target="save,templateExampleImage">
                            <span wire:loading.remove wire:target="save"><?php echo e($editing ? 'Simpan Perubahan' : 'Create Template'); ?></span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
        </template>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showDeleteModal): ?>
        <template x-teleport="<?php echo e('body'); ?>">
        <div class="rms-template-modal-layer rms-template-delete-layer" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'template-delete-modal'; ?>wire:key="template-delete-modal" x-data @keydown.escape.window="$wire.closeDelete()">
            <div class="rms-template-modal-backdrop" wire:click="closeDelete"></div>
            <section class="rms-template-delete-modal" role="dialog" aria-modal="true">
                <div class="rms-template-danger-icon">!</div>
                <span class="rms-template-danger-label">DANGER ZONE</span>
                <h2>Hapus Template?</h2>
                <p>Template <strong><?php echo e($deletingName); ?></strong> akan dihapus dari library. Jika template sudah digunakan oleh generation, penghapusan akan dibatalkan.</p>
                <div class="rms-template-delete-actions">
                    <button type="button" class="secondary" wire:click="closeDelete">Batal</button>
                    <button type="button" class="danger" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">
                        <span wire:loading.remove wire:target="delete">Hapus Template</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </section>
        </div>
        </template>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\livewire\dashboard\templates\index.blade.php ENDPATH**/ ?>