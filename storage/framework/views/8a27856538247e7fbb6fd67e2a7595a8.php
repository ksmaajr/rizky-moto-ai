<?php

use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts::app')]
class extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $status = 'all';

    public bool $showForm = false;
    public bool $showDelete = false;
    public bool $editing = false;

    public ?int $editingId = null;
    public ?int $deleteId = null;

    public string $name = '';
    public string $marketplace = '';
    public string $brand_name = '';
    public string $brand_color = '#dc2626';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public $logo = null;
    public ?string $currentLogo = null;

    public function getStoresProperty()
    {
        return Store::query()
            ->withCount(['templates', 'generations'])
            ->when(trim($this->search) !== '', function ($query) {
                $keyword = '%' . trim($this->search) . '%';

                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', $keyword)
                        ->orWhere('brand_name', 'like', $keyword)
                        ->orWhere('marketplace', 'like', $keyword);
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTotalStoresProperty(): int
    {
        return Store::count();
    }

    public function getActiveStoresProperty(): int
    {
        return Store::where('is_active', true)->count();
    }

    public function getInactiveStoresProperty(): int
    {
        return Store::where('is_active', false)->count();
    }

    public function getTotalTemplatesProperty(): int
    {
        return (int) Store::withCount('templates')->get()->sum('templates_count');
    }

    public function getTotalGenerationsProperty(): int
    {
        return (int) Store::withCount('generations')->get()->sum('generations_count');
    }

    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->status = 'all';
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->showForm = true;
        $this->editing = false;
    }

    public function openEdit(int $id): void
    {
        $store = Store::findOrFail($id);

        $this->editingId = $store->id;
        $this->name = (string) $store->name;
        $this->marketplace = (string) ($store->marketplace ?? '');
        $this->brand_name = (string) ($store->brand_name ?? '');
        $this->brand_color = (string) ($store->brand_color ?: '#dc2626');
        $this->description = (string) ($store->description ?? '');
        $this->is_active = (bool) $store->is_active;
        $this->sort_order = (int) ($store->sort_order ?? 0);
        $this->currentLogo = $store->logo_path;
        $this->logo = null;

        $this->editing = true;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'marketplace' => ['nullable', 'string', 'max:100'],
            'brand_name' => ['nullable', 'string', 'max:150'],
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999999'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        $messages = [
            'name.required' => 'Nama Store wajib diisi.',
            'name.max' => 'Nama Store maksimal 150 karakter.',
            'brand_color.regex' => 'Warna brand harus berupa HEX, contoh #dc2626.',
            'logo.image' => 'Logo harus berupa file gambar.',
            'logo.mimes' => 'Logo hanya boleh JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 5 MB.',
        ];

        $this->validate($rules, $messages);

        $store = $this->editingId
            ? Store::findOrFail($this->editingId)
            : new Store();

        $oldLogo = $store->logo_path;

        $store->name = trim($this->name);
        $store->marketplace = trim($this->marketplace) ?: null;
        $store->brand_name = trim($this->brand_name) ?: null;
        $store->brand_color = $this->brand_color;
        $store->description = trim($this->description) ?: null;
        $store->is_active = $this->is_active;
        $store->sort_order = $this->sort_order;

        if ($this->logo) {
            $store->logo_path = $this->logo->store('stores', 'public');
        }

        $store->save();

        if ($this->logo && $oldLogo && $oldLogo !== $store->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        $message = $this->editing
            ? 'Data Store berhasil diperbarui.'
            : 'Store baru berhasil ditambahkan.';

        $this->showForm = false;
        $this->resetForm();

        $this->dispatch(
            'toast',
            type: 'success',
            title: $this->editing ? 'Store diperbarui' : 'Store ditambahkan',
            message: $message
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->showDelete = true;
    }

    public function delete(): void
    {
        $store = Store::findOrFail($this->deleteId);

        if ($store->templates()->exists() || $store->generations()->exists()) {
            $this->showDelete = false;
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Store tidak dapat dihapus',
                message: 'Store masih memiliki Template atau Generation yang terkait.'
            );

            return;
        }

        if ($store->logo_path) {
            Storage::disk('public')->delete($store->logo_path);
        }

        $store->delete();

        $this->showDelete = false;
        $this->deleteId = null;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Store dihapus',
            message: 'Store berhasil dihapus dari workspace.'
        );
    }

    public function toggleStatus(int $id): void
    {
        $store = Store::findOrFail($id);
        $store->is_active = ! $store->is_active;
        $store->save();

        $this->dispatch(
            'toast',
            type: $store->is_active ? 'success' : 'info',
            title: $store->is_active ? 'Store diaktifkan' : 'Store dinonaktifkan',
            message: $store->name . ' sekarang ' . ($store->is_active ? 'aktif.' : 'nonaktif.')
        );
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDelete = false;
        $this->deleteId = null;
    }

    private function resetForm(): void
    {
        $this->resetValidation();

        $this->editingId = null;
        $this->name = '';
        $this->marketplace = '';
        $this->brand_name = '';
        $this->brand_color = '#dc2626';
        $this->description = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->logo = null;
        $this->currentLogo = null;
    }
}
?>

<div class="stores-page" wire:loading.class="is-busy" wire:target="save,delete,toggleStatus,openEdit,openCreate">

    <div class="stores-page-header">
        <div>
            <span class="stores-eyebrow"><i></i> WORKSPACE / STORE MANAGEMENT</span>
            <h1>Store <span>Management.</span></h1>
            <p>Kelola identitas marketplace, brand, logo, dan status Store Rizky Moto Shop.</p>
        </div>

        <button type="button" class="stores-primary-button" wire:click="openCreate">
            <b>+</b>
            <span>
                <strong>Add Store</strong>
                <small>Connect marketplace</small>
            </span>
            <em>→</em>
        </button>
    </div>

    <section class="stores-kpis">
        <article>
            <span class="stores-kpi-icon red">▣</span>
            <div><small>TOTAL STORES</small><strong><?php echo e($this->totalStores); ?></strong><em>All marketplace identities</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon green">✓</span>
            <div><small>ACTIVE</small><strong><?php echo e($this->activeStores); ?></strong><em>Currently connected</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon dark">○</span>
            <div><small>INACTIVE</small><strong><?php echo e($this->inactiveStores); ?></strong><em>Paused identities</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon violet">▦</span>
            <div><small>TEMPLATES</small><strong><?php echo e($this->totalTemplates); ?></strong><em>Across all stores</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon orange">⌁</span>
            <div><small>GENERATIONS</small><strong><?php echo e($this->totalGenerations); ?></strong><em>Visuals generated</em></div>
        </article>
    </section>

    <section class="stores-toolbar">
        <label class="stores-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search store, brand, marketplace...">
            <kbd>⌘ K</kbd>
        </label>

        <div class="stores-filter-group">
            <button type="button" class="<?php echo e($status === 'all' ? 'active' : ''); ?>" wire:click="$set('status','all')">All</button>
            <button type="button" class="<?php echo e($status === 'active' ? 'active' : ''); ?>" wire:click="$set('status','active')">Active</button>
            <button type="button" class="<?php echo e($status === 'inactive' ? 'active' : ''); ?>" wire:click="$set('status','inactive')">Inactive</button>
            <button type="button" wire:click="resetFilters">Reset ↺</button>
        </div>
    </section>

    <div class="stores-section-heading">
        <div>
            <span>CONNECTED STORES</span>
            <h2>Your marketplace workspace</h2>
        </div>
        <strong><i></i> <?php echo e($this->stores->count()); ?> RESULT</strong>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->stores->isEmpty()): ?>
        <div class="store-empty-state-v8" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'stores-empty-state'; ?>wire:key="stores-empty-state">
            <div class="store-empty-orbit" aria-hidden="true">
                <span class="store-empty-orbit-ring ring-one"></span>
                <span class="store-empty-orbit-ring ring-two"></span>
                <span class="store-empty-orbit-dot dot-one"></span>
                <span class="store-empty-orbit-dot dot-two"></span>
            </div>

            <div class="store-empty-icon-v8" aria-hidden="true">
                <span class="store-empty-icon-grid">
                    <i></i><i></i><i></i><i></i>
                </span>
                <span class="store-empty-icon-corner"></span>
            </div>

            <div class="store-empty-copy-v8">
                <span class="store-empty-kicker">
                    <i></i>
                    STORE LIBRARY
                </span>

                <h3>Belum ada Store yang cocok.</h3>

                <p>
                    Tidak ada Store yang sesuai dengan kata pencarian atau filter
                    yang sedang digunakan.
                </p>
            </div>

            <div class="store-empty-actions-v8">
                <button
                    type="button"
                    class="store-empty-reset-v8"
                    wire:click="resetFilters"
                    wire:loading.attr="disabled"
                    wire:target="resetFilters"
                >
                    <span class="store-empty-btn-icon">↻</span>
                    Reset Filter
                </button>

                <button
                    type="button"
                    class="store-empty-create-v8"
                    wire:click="openCreate"
                    wire:loading.attr="disabled"
                    wire:target="openCreate"
                >
                    <span>+</span>
                    Create Store
                    <b>→</b>
                </button>
            </div>

            <div class="store-empty-hint-v8">
                <span class="store-empty-hint-dot"></span>
                Tip: coba hapus filter atau gunakan nama marketplace yang berbeda.
            </div>
        </div>
    <?php else: ?>
        <section class="stores-grid">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <article
                    class="store-crud-card-v3"
                    <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'store-'.e($store->id).''; ?>wire:key="store-<?php echo e($store->id); ?>"
                    style="--store-brand: <?php echo e($store->brand_color ?: '#dc2626'); ?>"
                >
                    <div class="store-v3-cover">
                        <div class="store-v3-cover-grid"></div>
                        <div class="store-v3-cover-glow"></div>
                        <div class="store-v3-cover-orbit"></div>

                        <div class="store-v3-top">
                            <span class="store-v3-market">
                                <i></i>
                                <?php echo e(strtoupper($store->marketplace ?: 'MARKETPLACE')); ?>

                            </span>

                            <div class="store-v3-tools">
                                <button type="button" wire:click="openEdit(<?php echo e($store->id); ?>)" aria-label="Edit Store" title="Edit Store">✎</button>
                                <button type="button" wire:click="confirmDelete(<?php echo e($store->id); ?>)" aria-label="Delete Store" title="Delete Store">×</button>
                            </div>
                        </div>

                        <div class="store-v3-cover-footer">
                            <span>RIZKY MOTO SHOP</span>
                            <span>STORE #<?php echo e(str_pad((string) $store->id, 2, '0', STR_PAD_LEFT)); ?></span>
                        </div>

                        <div class="store-v3-logo">
                            <img
                                src="<?php echo e($store->logo_path ? asset('storage/'.$store->logo_path) : asset('images/logo-rizky-moto-shop.png')); ?>"
                                alt="<?php echo e($store->brand_name ?: $store->name); ?>"
                            >
                        </div>
                    </div>

                    <div class="store-v3-body">
                        <div class="store-v3-title">
                            <div class="store-v3-title-main">
                                <span class="store-v3-status <?php echo e($store->is_active ? 'is-active' : 'is-inactive'); ?>">
                                    <i></i><?php echo e($store->is_active ? 'ACTIVE' : 'INACTIVE'); ?>

                                </span>
                                <h3 title="<?php echo e($store->name); ?>"><?php echo e($store->name); ?></h3>
                                <p title="<?php echo e($store->description ?: 'Marketplace identity'); ?>">
                                    <?php echo e($store->description ?: 'Marketplace identity'); ?>

                                </p>
                            </div>

                            <span class="store-v3-id">
                                #<?php echo e(str_pad((string) $store->id, 2, '0', STR_PAD_LEFT)); ?>

                            </span>
                        </div>

                        <div class="store-v3-identity">
                            <span class="store-v3-initial">
                                <?php echo e(strtoupper(substr($store->brand_name ?: $store->name, 0, 2))); ?>

                            </span>

                            <div class="store-v3-identity-text">
                                <strong><?php echo e($store->brand_name ?: $store->name); ?></strong>
                                <small><?php echo e($store->marketplace ?: 'Marketplace Store'); ?></small>
                            </div>

                            <span class="store-v3-connection <?php echo e($store->is_active ? 'online' : 'offline'); ?>">
                                <i></i>
                                <?php echo e($store->is_active ? 'Connected' : 'Offline'); ?>

                            </span>
                        </div>

                        <div class="store-v3-metrics">
                            <div>
                                <span>Templates</span>
                                <strong><?php echo e($store->templates_count); ?></strong>
                                <small>Configured</small>
                            </div>
                            <div>
                                <span>Generations</span>
                                <strong><?php echo e($store->generations_count); ?></strong>
                                <small>Created</small>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong class="<?php echo e($store->is_active ? 'ready' : 'paused'); ?>">
                                    <?php echo e($store->is_active ? 'Ready' : 'Paused'); ?>

                                </strong>
                                <small><?php echo e($store->is_active ? 'Online' : 'Offline'); ?></small>
                            </div>
                        </div>

                        <div class="store-v3-actions">
                            <button
                                type="button"
                                class="store-v3-manage"
                                wire:click="openEdit(<?php echo e($store->id); ?>)"
                            >
                                <span>Manage Store</span>
                                <span>→</span>
                            </button>

                            <button
                                type="button"
                                class="store-v3-toggle"
                                wire:click="toggleStatus(<?php echo e($store->id); ?>)"
                                title="<?php echo e($store->is_active ? 'Pause Store' : 'Activate Store'); ?>"
                            >
                                <i class="<?php echo e($store->is_active ? 'pause' : 'play'); ?>"></i>
                                <span><?php echo e($store->is_active ? 'Pause' : 'Activate'); ?></span>
                            </button>
                        </div>
                    </div>
                </article>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

            <button type="button" class="store-create-card" wire:click="openCreate">
                <span>+</span>
                <strong>Add New Store</strong>
                <small>Hubungkan marketplace baru dan siapkan brand identity.</small>
                <em>Create store →</em>
            </button>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showForm): ?>
        <template x-teleport="<?php echo e('body'); ?>">
        <div class="stores-modal-backdrop" wire:click.self="cancelForm">
            <div class="stores-modal" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'store-form-modal'; ?>wire:key="store-form-modal">
                <div class="stores-modal-header">
                    <div>
                        <span><?php echo e($editing ? 'EDIT STORE' : 'NEW STORE'); ?></span>
                        <h2><?php echo e($editing ? 'Update Store.' : 'Create Store.'); ?></h2>
                        <p><?php echo e($editing ? 'Perbarui identitas dan konfigurasi Store.' : 'Tambahkan identitas marketplace baru.'); ?></p>
                    </div>
                    <button type="button" wire:click="cancelForm">×</button>
                </div>

                <form wire:submit="save" class="stores-form">
                    <div class="stores-form-grid">
                        <label>
                            <span>Store Name *</span>
                            <input wire:model="name" type="text" placeholder="Contoh: Rizky Moto Shop">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label>
                            <span>Marketplace</span>

                            <div
                                class="rms-select"
                                x-data="{ open: false }"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                            >
                                <button
                                    type="button"
                                    class="rms-select-trigger"
                                    @click="open = !open"
                                >
                                    <span>
                                        <i class="rms-market-dot"></i>
                                        <b><?php echo e($marketplace ? strtoupper($marketplace) : 'Pilih marketplace'); ?></b>
                                    </span>
                                    <em :class="{ 'is-open': open }">⌄</em>
                                </button>

                                <div class="rms-select-menu" x-cloak x-show="open" x-transition>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                                        'shopee' => 'Shopee',
                                        'tokopedia' => 'Tokopedia',
                                        'tiktok shop' => 'TikTok Shop',
                                        'bukalapak' => 'Bukalapak',
                                        'lazada' => 'Lazada',
                                        'blibli' => 'Blibli',
                                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button
                                            type="button"
                                            class="<?php echo e(strtolower($marketplace) === $value ? 'selected' : ''); ?>"
                                            @click="$wire.set('marketplace', <?php echo \Illuminate\Support\Js::from($value)->toHtml() ?>); open = false"
                                        >
                                            <span><?php echo e($label); ?></span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strtolower($marketplace) === $value): ?>
                                                <i>✓</i>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['marketplace'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label>
                            <span>Brand Name</span>
                            <input wire:model="brand_name" type="text" placeholder="Nama brand yang ditampilkan">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['brand_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label>
                            <span>Brand Color</span>
                            <div class="color-field">
                                <input wire:model="brand_color" type="text" placeholder="#dc2626">
                                <span style="background: <?php echo e($brand_color ?: '#dc2626'); ?>"></span>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['brand_color'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label class="form-full">
                            <span>Description</span>
                            <textarea wire:model="description" rows="3" placeholder="Deskripsi singkat Store..."></textarea>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label>
                            <span>Sort Order</span>
                            <input wire:model="sort_order" type="number" min="0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>

                        <label>
                            <span>Status</span>
                            <button type="button" class="form-status-toggle <?php echo e($is_active ? 'on' : ''); ?>" wire:click="$toggle('is_active')">
                                <span></span>
                                <?php echo e($is_active ? 'Active' : 'Inactive'); ?>

                            </button>
                        </label>

                        <label class="form-full">
                            <span>Store Logo</span>
                            <div class="logo-upload">
                                <div class="logo-preview">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($logo): ?>
                                        <img src="<?php echo e($logo->temporaryUrl()); ?>" alt="Preview">
                                    <?php elseif($currentLogo): ?>
                                        <img src="<?php echo e(asset('storage/'.$currentLogo)); ?>" alt="Current logo">
                                    <?php else: ?>
                                        <img src="<?php echo e(asset('images/logo-rizky-moto-shop.png')); ?>" alt="Default logo">
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp">
                                    <small>PNG, JPG, WEBP · maksimal 5 MB</small>
                                </div>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <small class="form-error"><?php echo e($message); ?></small> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </label>
                    </div>

                    <div class="stores-modal-actions">
                        <button type="button" class="secondary" wire:click="cancelForm">Batal</button>
                        <button type="submit" class="primary">
                            <span wire:loading.remove wire:target="save"><?php echo e($editing ? 'Simpan Perubahan' : 'Create Store'); ?></span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </template>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showDelete): ?>
        <template x-teleport="<?php echo e('body'); ?>">
        <div class="stores-modal-backdrop" wire:click.self="cancelDelete">
            <div class="stores-confirm-modal">
                <div class="confirm-icon">!</div>
                <span>DANGER ZONE</span>
                <h2>Hapus Store?</h2>
                <p>Store akan dihapus dari workspace. Store yang masih memiliki Template atau Generation tidak dapat dihapus.</p>

                <div class="stores-modal-actions">
                    <button type="button" class="secondary" wire:click="cancelDelete">Batal</button>
                    <button type="button" class="danger" wire:click="delete">
                        <span wire:loading.remove wire:target="delete">Hapus Store</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
        </template>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    <div class="stores-page-loading" wire:loading.flex wire:target="save,delete,toggleStatus">
        <div>
            <span class="stores-spinner"></span>
            <strong>Processing Store</strong>
            <small>Please wait...</small>
        </div>
    </div>

    <style>
        /* ==========================================================
           RMS STORE MANAGEMENT — CARD V3
           This block intentionally uses unique v3 class names so
           older dashboard/store CSS cannot distort the card.
           ========================================================== */

        .stores-page{
            display:flex;
            flex-direction:column;
            gap:22px;
            padding-bottom:44px;
            color:#18181b;
        }

        /* Header / KPI / toolbar remain clean */
        .stores-page-header{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:28px;
            padding:28px 30px;
            border:1px solid #e4e4e7;
            border-radius:25px;
            background:#fff;
            box-shadow:0 16px 45px rgba(24,24,27,.045);
            position:relative;
            overflow:hidden;
        }
        .stores-page-header:after{
            content:"";
            position:absolute;
            width:330px;height:330px;
            right:-120px;top:-170px;
            border-radius:50%;
            background:radial-gradient(circle,rgba(220,38,38,.10),transparent 68%);
            pointer-events:none;
        }
        .stores-eyebrow{display:inline-flex;align-items:center;gap:7px;color:#991b1b;font-size:10px;font-weight:900;letter-spacing:.16em}
        .stores-eyebrow i{width:7px;height:7px;border-radius:50%;background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.08)}
        .stores-page-header h1{margin:12px 0 5px;font-size:clamp(38px,4vw,57px);line-height:.95;letter-spacing:-.055em;font-weight:950}
        .stores-page-header h1 span{color:#a1a1aa}
        .stores-page-header p{margin:0;color:#71717a;font-size:14px;line-height:1.6}
        .stores-primary-button{position:relative;z-index:2;display:flex;align-items:center;gap:11px;min-width:185px;padding:11px 13px;border:0;border-radius:15px;color:#fff;background:#18181b;cursor:pointer;box-shadow:0 12px 30px rgba(24,24,27,.15);transition:.25s}
        .stores-primary-button:hover{background:#dc2626;transform:translateY(-2px)}
        .stores-primary-button>b{display:grid;place-items:center;width:39px;height:39px;border-radius:11px;background:rgba(255,255,255,.10);font-size:22px;font-weight:300}
        .stores-primary-button span{display:grid;gap:2px;text-align:left}
        .stores-primary-button strong{font-size:11px}
        .stores-primary-button small{font-size:9px;color:#a1a1aa}
        .stores-primary-button em{margin-left:auto;font-style:normal;color:#a1a1aa}

        .stores-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
        .stores-kpis article{display:flex;gap:12px;min-height:120px;padding:17px;border:1px solid #e4e4e7;border-radius:19px;background:#fff;box-shadow:0 10px 30px rgba(24,24,27,.035)}
        .stores-kpi-icon{display:grid;width:37px;height:37px;flex:0 0 37px;place-items:center;border-radius:12px;font-weight:900}
        .stores-kpi-icon.red{color:#dc2626;background:#fef2f2}
        .stores-kpi-icon.green{color:#16a34a;background:#f0fdf4}
        .stores-kpi-icon.dark{color:#3f3f46;background:#f4f4f5}
        .stores-kpi-icon.violet{color:#7c3aed;background:#f5f3ff}
        .stores-kpi-icon.orange{color:#ea580c;background:#fff7ed}
        .stores-kpis article div{display:grid;align-content:start;gap:3px}
        .stores-kpis small{font-size:8px;letter-spacing:.13em;color:#a1a1aa;font-weight:900}
        .stores-kpis strong{font-size:29px;line-height:1;letter-spacing:-.04em}
        .stores-kpis em{font-size:9px;color:#a1a1aa;font-style:normal}

        .stores-toolbar{display:flex;gap:10px;padding:10px;border:1px solid #e4e4e7;border-radius:18px;background:#fff}
        .stores-search{display:flex;align-items:center;gap:9px;flex:1;min-width:200px;height:42px;padding:0 12px;border:1px solid #e4e4e7;border-radius:12px;background:#fafafa}
        .stores-search svg{width:17px;color:#a1a1aa}
        .stores-search input{width:100%;border:0;outline:0;background:transparent;font-size:13px;color:#27272a}
        .stores-search kbd{font-size:9px;padding:4px 6px;border:1px solid #e4e4e7;border-radius:6px;color:#a1a1aa;background:#fff;white-space:nowrap}
        .stores-filter-group{display:flex;gap:6px}
        .stores-filter-group button{height:42px;padding:0 14px;border:1px solid #e4e4e7;border-radius:11px;background:#fff;color:#71717a;font-size:11px;font-weight:800;cursor:pointer}
        .stores-filter-group button.active{color:#fff;border-color:#18181b;background:#18181b}
        .stores-filter-group button:hover:not(.active){background:#f4f4f5}

        .stores-section-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}
        .stores-section-heading>div>span{font-size:9px;letter-spacing:.16em;color:#a1a1aa;font-weight:900}
        .stores-section-heading h2{margin:5px 0 0;font-size:24px;letter-spacing:-.035em;font-weight:900}
        .stores-section-heading>strong{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid #e4e4e7;border-radius:999px;background:#fff;color:#71717a;font-size:9px}
        .stores-section-heading>strong i{width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.08)}

        .stores-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px;
            align-items:stretch;
        }

        /* ==========================================================
           CARD V3
           ========================================================== */

        .store-crud-card-v3{
            position:relative;
            display:flex;
            min-width:0;
            flex-direction:column;
            overflow:hidden;
            border:1px solid #dedee2;
            border-radius:26px;
            background:#fff;
            box-shadow:0 18px 45px rgba(24,24,27,.055);
            transition:transform .28s cubic-bezier(.22,1,.36,1),box-shadow .28s ease,border-color .28s ease;
        }

        .store-crud-card-v3:hover{
            transform:translateY(-5px);
            border-color:#cfcfd4;
            box-shadow:0 28px 65px rgba(24,24,27,.10);
        }

        .store-v3-cover{
            position:relative;
            height:184px;
            flex:0 0 184px;
            overflow:visible;
            isolation:isolate;
            background:
                radial-gradient(circle at 80% 10%,color-mix(in srgb,var(--store-brand) 34%,transparent),transparent 34%),
                linear-gradient(135deg,#121216 0%,#1c1c20 55%,#151519 100%);
        }

        .store-v3-cover-grid{
            position:absolute;
            inset:-30px;
            opacity:.16;
            background-image:
                linear-gradient(120deg,transparent 0 47%,#fff 48%,transparent 49%),
                linear-gradient(60deg,transparent 0 47%,#fff 48%,transparent 49%);
            background-size:58px 58px;
            transform:rotate(-2deg) scale(1.04);
        }

        .store-v3-cover-glow{
            position:absolute;
            left:35%;
            top:-110px;
            width:250px;
            height:250px;
            border-radius:50%;
            background:var(--store-brand);
            opacity:.08;
            filter:blur(38px);
        }

        .store-v3-cover-orbit{
            display:none !important;
        }

        /* Decorative orbit disabled:
           the cover is intentionally clean and the logo may safely overlap
           the cover/content boundary without exposing circular artifacts. */
        .store-v3-cover-glow{
            left:42%;
            top:-80px;
            width:210px;
            height:210px;
            opacity:.055;
            filter:blur(46px);
            pointer-events:none;
        }

        .store-v3-cover:after{
            content:"";
            position:absolute;
            inset:0;
            z-index:1;
            background:linear-gradient(180deg,rgba(0,0,0,0) 25%,rgba(0,0,0,.20) 100%);
            pointer-events:none;
        }

        /* Logo deliberately sits across the cover/content boundary.
           The card itself clips it, while the cover does not. */
        .store-crud-card-v3 > .store-v3-cover{
            overflow:visible;
        }

        .store-v3-logo,
        .store-v3-logo img{
            box-sizing:border-box;
        }

        .store-v3-top{
            position:absolute;
            z-index:5;
            top:14px;left:15px;right:15px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
        }

        .store-v3-market{
            display:inline-flex;
            align-items:center;
            gap:7px;
            max-width:calc(100% - 82px);
            padding:7px 10px;
            border:1px solid rgba(255,255,255,.13);
            border-radius:999px;
            color:#f4f4f5;
            background:rgba(255,255,255,.075);
            backdrop-filter:blur(10px);
            font-size:8px;
            font-weight:900;
            letter-spacing:.11em;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .store-v3-market i{
            width:6px;height:6px;flex:0 0 6px;border-radius:50%;
            background:var(--store-brand);
            box-shadow:0 0 0 4px color-mix(in srgb,var(--store-brand) 16%,transparent);
        }

        .store-v3-tools{
            display:flex;
            gap:6px;
            flex:0 0 auto;
        }

        .store-v3-tools button{
            display:grid;
            place-items:center;
            width:32px;height:32px;
            padding:0;
            border:1px solid rgba(255,255,255,.14);
            border-radius:10px;
            color:#fff;
            background:rgba(255,255,255,.075);
            backdrop-filter:blur(10px);
            cursor:pointer;
            transition:.2s;
        }

        .store-v3-tools button:hover{
            color:#18181b;
            background:#fff;
            transform:translateY(-1px);
        }

        .store-v3-cover-footer{
            position:absolute;
            z-index:4;
            left:17px;right:17px;bottom:17px;
            display:flex;
            justify-content:space-between;
            gap:12px;
            color:rgba(255,255,255,.48);
            font-size:7px;
            font-weight:900;
            letter-spacing:.14em;
        }

        .store-v3-logo{
            position:absolute;
            z-index:20;
            left:18px;
            bottom:-34px;
            display:grid;
            place-items:center;
            width:90px;
            height:90px;
            box-sizing:border-box;
            padding:7px;
            border:5px solid #fff;
            border-radius:24px;
            overflow:hidden;
            background:#09090b;
            box-shadow:
                0 14px 30px rgba(0,0,0,.20),
                0 0 0 1px rgba(0,0,0,.04);
        }

        .store-v3-logo img{
            display:block;
            width:100%;
            height:100%;
            min-width:0;
            min-height:0;
            max-width:100%;
            max-height:100%;
            object-fit:contain;
            object-position:center;
            border-radius:13px;
        }

        .store-v3-body{
            display:flex;
            flex:1 1 auto;
            min-width:0;
            flex-direction:column;
            padding:48px 19px 19px;
        }

        .store-v3-title{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:15px;
            min-width:0;
        }

        .store-v3-title-main{
            min-width:0;
        }

        .store-v3-status{
            display:inline-flex;
            align-items:center;
            gap:7px;
            font-size:8px;
            font-weight:950;
            letter-spacing:.07em;
        }

        .store-v3-status i{
            width:6px;height:6px;border-radius:50%;
            background:#22c55e;
            box-shadow:0 0 0 4px rgba(34,197,94,.08);
        }

        .store-v3-status.is-inactive{color:#a1a1aa}
        .store-v3-status.is-active{color:#16a34a}
        .store-v3-status.is-inactive i{background:#a1a1aa;box-shadow:none}

        .store-v3-title h3{
            margin:8px 0 3px;
            overflow:hidden;
            color:#18181b;
            font-size:23px;
            line-height:1.08;
            letter-spacing:-.04em;
            font-weight:950;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .store-v3-title p{
            max-width:100%;
            margin:0;
            overflow:hidden;
            color:#a1a1aa;
            font-size:10px;
            line-height:1.5;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .store-v3-id{
            flex:0 0 auto;
            color:#d4d4d8;
            font-size:21px;
            line-height:1;
            font-weight:950;
            letter-spacing:-.05em;
        }

        .store-v3-identity{
            display:flex;
            align-items:center;
            gap:10px;
            min-width:0;
            margin-top:18px;
            padding:11px;
            border:1px solid #eeeeF0;
            border-radius:15px;
            background:#fafafa;
        }

        .store-v3-initial{
            display:grid;
            place-items:center;
            width:36px;height:36px;
            flex:0 0 36px;
            border-radius:11px;
            color:#fff;
            background:#18181b;
            font-size:9px;
            font-weight:950;
        }

        .store-v3-identity-text{
            display:grid;
            min-width:0;
            gap:3px;
        }

        .store-v3-identity-text strong,
        .store-v3-identity-text small{
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .store-v3-identity-text strong{font-size:10px;color:#27272a}
        .store-v3-identity-text small{font-size:8px;color:#a1a1aa}

        .store-v3-connection{
            display:inline-flex;
            align-items:center;
            gap:5px;
            margin-left:auto;
            flex:0 0 auto;
            font-size:8px;
            font-weight:900;
        }

        .store-v3-connection i{width:5px;height:5px;border-radius:50%}
        .store-v3-connection.online{color:#16a34a}
        .store-v3-connection.online i{background:#22c55e}
        .store-v3-connection.offline{color:#a1a1aa}
        .store-v3-connection.offline i{background:#a1a1aa}

        .store-v3-metrics{
            display:grid;
            grid-template-columns:repeat(3,minmax(0,1fr));
            margin-top:14px;
            border-top:1px solid #eeeeF0;
            border-bottom:1px solid #eeeeF0;
        }

        .store-v3-metrics>div{
            min-width:0;
            padding:13px 10px;
            border-right:1px solid #eeeeF0;
        }

        .store-v3-metrics>div:last-child{border-right:0}
        .store-v3-metrics span{display:block;color:#a1a1aa;font-size:7px;font-weight:900;letter-spacing:.09em;text-transform:uppercase}
        .store-v3-metrics strong{display:block;margin-top:6px;color:#27272a;font-size:14px;line-height:1;font-weight:950}
        .store-v3-metrics strong.ready{color:#16a34a}
        .store-v3-metrics strong.paused{color:#71717a}
        .store-v3-metrics small{display:block;margin-top:5px;color:#b4b4bb;font-size:7px}

        /* IMPORTANT: grid prevents the action buttons from ever wrapping */
        .store-v3-actions{
            display:grid;
            grid-template-columns:minmax(0,1fr) 104px;
            gap:8px;
            width:100%;
            min-width:0;
            margin-top:14px;
        }

        .store-v3-actions button{
            min-width:0;
            height:43px;
            border-radius:12px;
            font-size:10px;
            font-weight:950;
            cursor:pointer;
            transition:.2s;
        }

        .store-v3-manage{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:0 14px;
            border:0;
            color:#fff;
            background:#18181b;
        }

        .store-v3-manage span:first-child{
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
        }

        .store-v3-manage:hover{
            background:#dc2626;
            transform:translateY(-1px);
        }

        .store-v3-toggle{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:7px;
            padding:0 8px;
            border:1px solid #e4e4e7;
            color:#52525b;
            background:#fff;
            white-space:nowrap;
        }

        .store-v3-toggle:hover{
            border-color:#d4d4d8;
            background:#f4f4f5;
        }

        .store-v3-toggle i{
            display:block;
            width:0;height:0;
            border-top:4px solid transparent;
            border-bottom:4px solid transparent;
            border-left:5px solid #a1a1aa;
        }

        .store-v3-toggle i.pause{
            width:7px;height:9px;
            border:0;
            background:linear-gradient(90deg,#f59e0b 0 35%,transparent 35% 65%,#f59e0b 65% 100%);
        }

        /* ADD CARD */
        .store-create-card{
            min-height:445px;
            border:1px dashed #d4d4d8;
            border-radius:26px;
            background:radial-gradient(circle at 50% 0,rgba(220,38,38,.08),transparent 45%),#fff;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:30px;
            text-align:center;
            cursor:pointer;
            transition:.3s cubic-bezier(.22,1,.36,1);
        }

        .store-create-card:hover{transform:translateY(-5px);border-color:#dc2626;box-shadow:0 25px 60px rgba(220,38,38,.08)}
        .store-create-card>span{display:grid;width:70px;height:70px;place-items:center;border:1px solid #e4e4e7;border-radius:21px;color:#dc2626;background:#fff;font-size:29px;box-shadow:0 12px 28px rgba(24,24,27,.08)}
        .store-create-card strong{margin-top:18px;font-size:16px}
        .store-create-card small{max-width:280px;margin-top:7px;color:#a1a1aa;font-size:10px;line-height:1.6}
        .store-create-card em{margin-top:18px;color:#dc2626;font-size:10px;font-weight:950;font-style:normal}

        /* CUSTOM MARKETPLACE SELECT */
        .rms-select{position:relative}
        [x-cloak]{display:none!important}
        .rms-select-trigger{
            display:flex;
            align-items:center;
            justify-content:space-between;
            width:100%;
            min-height:42px;
            padding:0 12px;
            border:1px solid #e4e4e7;
            border-radius:11px;
            background:#fafafa;
            color:#27272a;
            cursor:pointer;
            text-align:left;
        }
        .rms-select-trigger>span{display:flex;align-items:center;gap:8px;min-width:0}
        .rms-select-trigger b{font-size:12px;font-weight:600}
        .rms-select-trigger em{font-style:normal;color:#a1a1aa;transition:transform .2s}
        .rms-select-trigger em.is-open{transform:rotate(180deg)}
        .rms-market-dot{width:7px;height:7px;border-radius:50%;background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.08)}
        .rms-select-menu{
            position:absolute;
            z-index:1200;
            top:calc(100% + 7px);
            left:0;
            right:0;
            padding:6px;
            border:1px solid #e4e4e7;
            border-radius:14px;
            background:#fff;
            box-shadow:0 20px 45px rgba(24,24,27,.14);
        }
        .rms-select-menu button{
            display:flex;
            align-items:center;
            justify-content:space-between;
            width:100%;
            min-height:38px;
            padding:0 10px;
            border:0;
            border-radius:9px;
            background:transparent;
            color:#52525b;
            font-size:11px;
            font-weight:700;
            cursor:pointer;
            text-align:left;
        }
        .rms-select-menu button:hover{background:#f4f4f5;color:#18181b}
        .rms-select-menu button.selected{background:#fef2f2;color:#dc2626}
        .rms-select-menu button i{font-style:normal;font-weight:950}

        /* MODAL */
        .stores-modal-backdrop{
            position:fixed !important;
            z-index:2147483000 !important;
            inset:0 !important;
            width:100vw !important;
            height:100dvh !important;
            min-height:100vh !important;
            display:flex !important;
            align-items:center !important;
            justify-content:center !important;
            padding:24px !important;
            box-sizing:border-box;
            overflow:hidden !important;
            overscroll-behavior:none !important;
            touch-action:none;
            isolation:isolate;
            background:rgba(8,9,11,.60) !important;
            backdrop-filter:blur(22px) saturate(.72) brightness(.70);
            -webkit-backdrop-filter:blur(22px) saturate(.72) brightness(.70);
            animation:storesBackdropIn .24s ease both;
        }

        .stores-modal-backdrop:before{
            content:"";
            position:absolute;
            inset:0;
            background:rgba(8,9,11,.16);
            pointer-events:none;
        }

        .stores-modal-backdrop:after{
            content:"";
            position:absolute;
            inset:0;
            background:radial-gradient(
                circle at 50% 50%,
                rgba(255,255,255,.025) 0%,
                rgba(255,255,255,0) 52%
            );
            pointer-events:none;
        }

        html:has(.stores-modal-backdrop),
        body:has(.stores-modal-backdrop){
            overflow:hidden !important;
            height:100% !important;
            overscroll-behavior:none !important;
        }

        .stores-modal{
            position:relative;
            z-index:2;
            width:min(820px,calc(100vw - 48px));
            max-height:min(900px,calc(100dvh - 48px));
            margin:0 auto;
            transform:translateZ(0);
            min-height:0;
            display:flex;
            flex-direction:column;
            overflow:hidden;
            overscroll-behavior:contain;
            touch-action:auto;
            border:1px solid rgba(255,255,255,.92);
            border-radius:28px;
            background:#fff;
            box-shadow:
                0 45px 120px rgba(0,0,0,.34),
                0 12px 38px rgba(0,0,0,.14),
                0 0 0 1px rgba(0,0,0,.025);
            animation:storesModalIn .28s cubic-bezier(.22,1,.36,1) both;
        }

        .stores-modal-header{
            position:relative;
            flex:0 0 auto;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:24px;
            padding:28px 30px 24px;
            border-bottom:1px solid #f0f0f2;
            background:
                linear-gradient(180deg,#fff 0%,#fff 72%,#fcfcfd 100%);
        }

        .stores-modal-header:after{
            content:"";
            position:absolute;
            left:30px;
            right:30px;
            bottom:0;
            height:1px;
            background:linear-gradient(90deg,#dc2626,rgba(220,38,38,.12),transparent 72%);
            opacity:.45;
        }

        .stores-modal-header>div{
            min-width:0;
        }

        .stores-modal-header>div>span,
        .stores-confirm-modal>span{
            display:inline-flex;
            align-items:center;
            gap:7px;
            color:#dc2626;
            font-size:9px;
            font-weight:950;
            letter-spacing:.18em;
        }

        .stores-modal-header>div>span:before{
            content:"";
            width:6px;
            height:6px;
            border-radius:50%;
            background:#dc2626;
            box-shadow:0 0 0 4px rgba(220,38,38,.08);
        }

        .stores-modal-header h2{
            margin:9px 0 5px;
            font-size:clamp(28px,3vw,36px);
            line-height:1;
            letter-spacing:-.055em;
            font-weight:900;
            color:#18181b;
        }

        .stores-modal-header p{
            max-width:540px;
            margin:0;
            color:#8b8b95;
            font-size:12px;
            line-height:1.65;
        }

        .stores-modal-header>button{
            flex:0 0 auto;
            width:40px;
            height:40px;
            display:grid;
            place-items:center;
            border:1px solid #e4e4e7;
            border-radius:13px;
            background:#fff;
            color:#52525b;
            font-size:22px;
            line-height:1;
            cursor:pointer;
            transition:.2s ease;
            box-shadow:0 5px 15px rgba(24,24,27,.04);
        }

        .stores-modal-header>button:hover{
            color:#dc2626;
            border-color:#fecaca;
            background:#fff7f7;
            transform:rotate(3deg) translateY(-1px);
        }

        .stores-form{
            flex:1 1 auto;
            min-height:0;
            overflow-y:auto;
            overscroll-behavior:contain;
            touch-action:pan-y;
            scrollbar-width:none;
            -ms-overflow-style:none;
            padding:26px 30px 30px;
            background:#fff;
        }

        .stores-form::-webkit-scrollbar{
            display:none;
            width:0;
            height:0;
        }

        .stores-form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:18px;
        }

        .stores-form label{
            display:grid;
            gap:8px;
            min-width:0;
        }

        .stores-form label.form-full{
            grid-column:1/-1;
        }

        .stores-form label>span{
            font-size:10px;
            font-weight:900;
            letter-spacing:.01em;
            color:#3f3f46;
        }

        .stores-form input,
        .stores-form textarea{
            width:100%;
            min-width:0;
            box-sizing:border-box;
            border:1px solid #e4e4e7;
            border-radius:13px;
            padding:13px 14px;
            outline:0;
            background:#fafafa;
            font:inherit;
            font-size:12px;
            color:#27272a;
            transition:border-color .2s ease,box-shadow .2s ease,background .2s ease,transform .2s ease;
        }

        .stores-form input:hover,
        .stores-form textarea:hover{
            border-color:#d4d4d8;
            background:#fff;
        }

        .stores-form input:focus,
        .stores-form textarea:focus{
            border-color:#a1a1aa;
            background:#fff;
            box-shadow:0 0 0 4px rgba(24,24,27,.045);
        }

        .stores-form textarea{
            min-height:112px;
            resize:vertical;
        }

        .form-error{
            color:#dc2626;
            font-size:9px;
            font-weight:700;
        }

        .color-field{
            position:relative;
        }

        .color-field>input{
            padding-right:52px;
        }

        .color-field>span{
            position:absolute;
            right:12px;
            top:50%;
            width:22px;
            height:22px;
            border-radius:7px;
            transform:translateY(-50%);
            border:1px solid #e4e4e7;
            box-shadow:0 2px 7px rgba(0,0,0,.08);
        }

        .form-status-toggle{
            display:inline-flex;
            align-items:center;
            gap:9px;
            width:max-content;
            min-height:45px;
            box-sizing:border-box;
            padding:9px 13px;
            border:1px solid #e4e4e7;
            border-radius:13px;
            background:#fafafa;
            color:#52525b;
            font-size:11px;
            font-weight:850;
            cursor:pointer;
            transition:.2s ease;
        }

        .form-status-toggle:hover{
            border-color:#d4d4d8;
            background:#fff;
        }

        .form-status-toggle span{
            width:29px;
            height:17px;
            border-radius:999px;
            background:#d4d4d8;
            position:relative;
            transition:.2s ease;
        }

        .form-status-toggle span:after{
            content:"";
            position:absolute;
            top:2px;
            left:2px;
            width:13px;
            height:13px;
            border-radius:50%;
            background:#fff;
            box-shadow:0 1px 3px rgba(0,0,0,.15);
            transition:.2s ease;
        }

        .form-status-toggle.on{
            color:#15803d;
            border-color:#bbf7d0;
            background:#f0fdf4;
        }

        .form-status-toggle.on span{
            background:#22c55e;
        }

        .form-status-toggle.on span:after{
            left:14px;
        }

        .logo-upload{
            display:flex;
            align-items:center;
            gap:15px;
            min-width:0;
            padding:14px;
            border:1px dashed #d4d4d8;
            border-radius:15px;
            background:#fafafa;
            transition:.2s ease;
        }

        .logo-upload:hover{
            border-color:#a1a1aa;
            background:#fff;
        }

        .logo-preview{
            flex:0 0 68px;
            display:grid;
            width:68px;
            height:68px;
            place-items:center;
            overflow:hidden;
            border:1px solid #e4e4e7;
            border-radius:15px;
            background:#18181b;
            box-shadow:0 8px 20px rgba(0,0,0,.10);
        }

        .logo-preview img{
            display:block;
            width:100%;
            height:100%;
            padding:7px;
            box-sizing:border-box;
            object-fit:contain;
            object-position:center;
        }

        .logo-upload input{
            min-width:0;
            max-width:100%;
            font-size:11px;
        }

        .logo-upload small{
            display:block;
            margin-top:6px;
            color:#a1a1aa;
            font-size:9px;
        }

        .stores-modal-actions{
            flex:0 0 auto;
            display:flex;
            justify-content:flex-end;
            align-items:center;
            gap:9px;
            padding:16px 30px;
            border-top:1px solid #f0f0f2;
            background:#fff;
            box-shadow:0 -10px 24px rgba(24,24,27,.025);
        }

        .stores-modal-actions button{
            min-width:108px;
            height:42px;
            padding:0 17px;
            border-radius:12px;
            font-size:10px;
            font-weight:900;
            cursor:pointer;
            transition:.2s ease;
        }

        .stores-modal-actions button:hover{
            transform:translateY(-1px);
        }

        .stores-modal-actions .secondary{
            border:1px solid #e4e4e7;
            background:#fff;
            color:#52525b;
        }

        .stores-modal-actions .secondary:hover{
            border-color:#d4d4d8;
            background:#fafafa;
        }

        .stores-modal-actions .primary{
            border:0;
            background:#18181b;
            color:#fff;
            box-shadow:0 8px 20px rgba(24,24,27,.13);
        }

        .stores-modal-actions .primary:hover{
            background:#dc2626;
            box-shadow:0 10px 24px rgba(220,38,38,.20);
        }

        .stores-modal-actions .danger{
            border:0;
            background:#dc2626;
            color:#fff;
            box-shadow:0 8px 20px rgba(220,38,38,.16);
        }

        .stores-modal-actions .danger:hover{
            background:#b91c1c;
        }

        /* Delete confirmation: compact, intentional and centered.
           It uses the same full-viewport backdrop, so there is no white edge. */
        .stores-confirm-modal{
            position:relative;
            z-index:2;
            width:min(470px,calc(100vw - 48px));
            margin:0 auto;
            padding:30px;
            border:1px solid rgba(255,255,255,.92);
            border-radius:28px;
            background:#fff;
            box-shadow:
                0 45px 120px rgba(0,0,0,.34),
                0 12px 35px rgba(0,0,0,.12);
            animation:storesModalIn .28s cubic-bezier(.22,1,.36,1) both;
        }

        .confirm-icon{
            display:grid;
            width:54px;
            height:54px;
            place-items:center;
            border-radius:17px;
            color:#dc2626;
            background:linear-gradient(145deg,#fff1f2,#fef2f2);
            border:1px solid #fecdd3;
            font-weight:950;
            font-size:21px;
            margin-bottom:18px;
            box-shadow:0 0 0 8px rgba(220,38,38,.045);
        }

        .stores-confirm-modal h2{
            margin:9px 0 7px;
            font-size:30px;
            line-height:1;
            letter-spacing:-.05em;
            color:#18181b;
        }

        .stores-confirm-modal p{
            max-width:390px;
            margin:0;
            color:#8b8b95;
            font-size:11px;
            line-height:1.7;
        }

        .stores-confirm-modal .stores-modal-actions{
            margin:22px -30px -30px;
            padding:15px 30px;
        }

        .stores-page-loading{
            position:fixed;
            z-index:1100;
            inset:0;
            align-items:center;
            justify-content:center;
            background:rgba(244,244,245,.4);
            backdrop-filter:blur(5px);
        }

        .stores-page-loading>div{
            display:grid;
            justify-items:center;
            gap:7px;
            padding:18px 23px;
            border:1px solid #e4e4e7;
            border-radius:16px;
            background:#fff;
            box-shadow:0 20px 50px rgba(24,24,27,.12);
        }

        .stores-spinner{
            width:27px;
            height:27px;
            border:3px solid #e4e4e7;
            border-top-color:#dc2626;
            border-radius:50%;
            animation:storesSpin .75s linear infinite;
        }

        .stores-page-loading strong{font-size:11px}
        .stores-page-loading small{font-size:9px;color:#a1a1aa}

        @keyframes storesSpin{to{transform:rotate(360deg)}}
        @keyframes storesFade{from{opacity:0}to{opacity:1}}
        @keyframes storesBackdropIn{from{opacity:0}to{opacity:1}}
        @keyframes storesModalIn{
            from{opacity:0;transform:translateY(14px) scale(.965)}
            to{opacity:1;transform:translateY(0) scale(1)}
        }

.stores-page-loading{position:fixed;z-index:1100;inset:0;align-items:center;justify-content:center;background:rgba(244,244,245,.4);backdrop-filter:blur(5px)}
        .stores-page-loading>div{display:grid;justify-items:center;gap:7px;padding:18px 23px;border:1px solid #e4e4e7;border-radius:16px;background:#fff;box-shadow:0 20px 50px rgba(24,24,27,.12)}
        .stores-spinner{width:27px;height:27px;border:3px solid #e4e4e7;border-top-color:#dc2626;border-radius:50%;animation:storesSpin .75s linear infinite}
        .stores-page-loading strong{font-size:11px}
        .stores-page-loading small{font-size:9px;color:#a1a1aa}

        @keyframes storesSpin{to{transform:rotate(360deg)}}
        @keyframes storesFade{from{opacity:0}to{opacity:1}}
        @keyframes storesModal{from{opacity:0;transform:translateY(12px) scale(.97)}to{opacity:1;transform:none}}

        @media(max-width:1100px){
            .stores-kpis{grid-template-columns:repeat(3,1fr)}
            .stores-grid{grid-template-columns:1fr}
        }

        @media(max-width:760px){
            .stores-page-header{align-items:stretch;flex-direction:column;padding:21px}
            .stores-primary-button{width:100%}
            .stores-kpis{grid-template-columns:1fr 1fr}
            .stores-toolbar{flex-direction:column}
            .stores-filter-group{display:grid;grid-template-columns:repeat(4,1fr)}
            .stores-filter-group button{padding:0 7px}
            .stores-section-heading{align-items:flex-start;flex-direction:column}

            .store-crud-card-v3{border-radius:22px}
            .store-v3-cover{height:158px;flex-basis:158px}
            .store-v3-body{padding:43px 15px 15px}
            .store-v3-title h3{font-size:20px}
            .store-v3-id{font-size:18px}
            .store-v3-actions{grid-template-columns:minmax(0,1fr) 92px}
            .store-v3-toggle{font-size:9px}
            .stores-form-grid{grid-template-columns:1fr}
            .stores-form label.form-full{grid-column:auto}

            .stores-modal-backdrop{
                align-items:center !important;
                justify-content:center !important;
                padding:12px !important;
            }
            .stores-modal{max-height:calc(100dvh - 20px);border-radius:21px;margin:auto 0}
        }

        @media(max-width:480px){
            .stores-page-header h1{font-size:34px}
            .stores-page-header p{font-size:12px}
            .stores-kpis{grid-template-columns:1fr 1fr}
            .stores-kpis article{min-height:108px;padding:13px}
            .stores-kpis strong{font-size:25px}
            .stores-search kbd{display:none}
            .stores-filter-group{grid-template-columns:1fr 1fr}

            .store-v3-cover{height:145px;flex-basis:145px}
            .store-v3-top{top:11px;left:11px;right:11px}
            .store-v3-cover-footer{left:12px;right:12px;bottom:12px}
            .store-v3-logo{
                left:13px;
                width:76px;
                height:76px;
                bottom:-29px;
                padding:6px;
                border-width:4px;
                border-radius:19px;
            }

            .store-v3-logo img{
                border-radius:11px;
            }
            .store-v3-body{padding:38px 12px 12px}
            .store-v3-title{gap:8px}
            .store-v3-title h3{font-size:19px}
            .store-v3-title p{font-size:9px}
            .store-v3-id{font-size:16px}
            .store-v3-identity{padding:9px}
            .store-v3-metrics>div{padding:11px 7px}
            .store-v3-metrics strong{font-size:13px}
            .store-v3-actions{grid-template-columns:minmax(0,1fr) 88px;gap:6px}
            .store-v3-actions button{height:40px;font-size:9px}
            .store-v3-manage{padding:0 10px}
            .store-v3-toggle{padding:0 5px;gap:5px}
            .store-create-card{min-height:330px;border-radius:22px}
            .stores-modal-backdrop{
                padding:10px !important;
            }
            .stores-modal-header{padding:18px}
            .stores-modal-header h2{font-size:23px}
            .stores-form{padding:16px}
            .stores-modal-actions{padding:12px 16px}
            .logo-upload{align-items:flex-start;flex-direction:column}
            .stores-confirm-modal{width:100%;padding:23px;border-radius:22px}
        }

        @media(prefers-reduced-motion:reduce){
            .store-crud-card-v3,.store-create-card,.stores-primary-button,.stores-modal,.stores-confirm-modal{
                transition:none!important;
                animation:none!important;
            }
        }
    
        @media(max-width:760px){
            .stores-modal-backdrop{
                align-items:center !important;
                justify-content:center !important;
                padding:12px !important;
            }

            .stores-modal{
                width:min(100%,calc(100vw - 24px));
                max-height:calc(100dvh - 24px);
                margin:0 auto;
                border-radius:25px;
            }

            .stores-modal-header{
                padding:22px 18px 19px;
                gap:14px;
            }

            .stores-modal-header:after{
                left:18px;
                right:18px;
            }

            .stores-modal-header h2{
                font-size:27px;
            }

            .stores-modal-header p{
                font-size:11px;
            }

            .stores-modal-header>button{
                width:38px;
                height:38px;
                border-radius:12px;
            }

            .stores-form{
                padding:20px 18px 22px;
                -webkit-overflow-scrolling:touch;
            }

            .stores-form-grid{
                grid-template-columns:1fr;
                gap:15px;
            }

            .stores-form label.form-full{
                grid-column:auto;
            }

            .stores-form textarea{
                min-height:100px;
            }

            .logo-upload{
                align-items:flex-start;
                flex-wrap:wrap;
            }

            .logo-upload input{
                flex:1 1 190px;
            }

            .stores-modal-actions{
                padding:13px 18px;
            }

            .stores-modal-actions button{
                flex:1 1 0;
                min-width:0;
            }

            .stores-confirm-modal{
                width:100%;
                padding:24px 20px;
                border-radius:24px;
            }

            .stores-confirm-modal h2{
                font-size:27px;
            }

            .stores-confirm-modal .stores-modal-actions{
                margin:20px -20px -24px;
                padding:13px 20px;
            }
        }

        @media(max-width:430px){
            .stores-modal-backdrop{
                padding:0;
            }

            .stores-modal{
                max-height:100dvh;
                border-radius:24px 24px 0 0;
            }

            .stores-modal-header{
                padding:20px 16px 17px;
            }

            .stores-modal-header:after{
                left:16px;
                right:16px;
            }

            .stores-form{
                padding:18px 16px 20px;
            }

            .stores-modal-actions{
                padding:12px 16px;
            }

            .stores-confirm-modal{
                border-radius:24px 24px 0 0;
                padding:22px 16px;
            }

            .stores-confirm-modal .stores-modal-actions{
                margin:18px -16px -22px;
                padding:12px 16px;
            }
        }


/* =========================================================
   STORE EMPTY STATE V8
   ========================================================= */

.store-empty-state-v8{
    position:relative;
    min-height:360px;
    width:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:58px 28px 46px;
    overflow:hidden;
    isolation:isolate;
    border:1px solid rgba(24,24,27,.075);
    border-radius:28px;
    background:
        radial-gradient(circle at 50% 42%, rgba(220,38,38,.055), transparent 31%),
        linear-gradient(180deg,#fff 0%,#fcfcfd 100%);
    box-shadow:
        0 18px 45px rgba(17,24,39,.045),
        inset 0 1px 0 rgba(255,255,255,.95);
    animation:storeEmptyRevealV8 .55s cubic-bezier(.22,1,.36,1) both;
}

.store-empty-state-v8::before{
    content:"";
    position:absolute;
    width:340px;
    height:340px;
    left:50%;
    top:46%;
    transform:translate(-50%,-50%);
    border-radius:50%;
    background:radial-gradient(circle,rgba(220,38,38,.075),rgba(220,38,38,0) 68%);
    filter:blur(10px);
    z-index:-3;
    animation:storeEmptyAmbientV8 4.5s ease-in-out infinite;
}

.store-empty-state-v8::after{
    content:"";
    position:absolute;
    inset:0;
    background-image:
        linear-gradient(rgba(24,24,27,.022) 1px,transparent 1px),
        linear-gradient(90deg,rgba(24,24,27,.022) 1px,transparent 1px);
    background-size:28px 28px;
    mask-image:linear-gradient(to bottom,transparent 0%,black 22%,black 78%,transparent 100%);
    pointer-events:none;
    z-index:-4;
}

.store-empty-orbit{
    position:absolute;
    left:50%;
    top:43%;
    width:240px;
    height:160px;
    transform:translate(-50%,-50%);
    pointer-events:none;
    z-index:-1;
}

.store-empty-orbit-ring{
    position:absolute;
    left:50%;
    top:50%;
    border:1px solid rgba(220,38,38,.09);
    border-radius:50%;
    transform:translate(-50%,-50%);
}

.store-empty-orbit-ring.ring-one{
    width:210px;
    height:92px;
    transform:translate(-50%,-50%) rotate(-12deg);
    animation:storeEmptyOrbitV8 7s linear infinite;
}

.store-empty-orbit-ring.ring-two{
    width:160px;
    height:66px;
    border-color:rgba(24,24,27,.055);
    transform:translate(-50%,-50%) rotate(18deg);
    animation:storeEmptyOrbitV8Reverse 9s linear infinite;
}

.store-empty-orbit-dot{
    position:absolute;
    width:5px;
    height:5px;
    border-radius:50%;
    background:#dc2626;
    box-shadow:0 0 0 5px rgba(220,38,38,.07);
}

.store-empty-orbit-dot.dot-one{
    left:27px;
    top:37px;
    animation:storeEmptyDotV8 2.8s ease-in-out infinite;
}

.store-empty-orbit-dot.dot-two{
    right:22px;
    bottom:28px;
    width:4px;
    height:4px;
    background:#18181b;
    box-shadow:0 0 0 5px rgba(24,24,27,.045);
    animation:storeEmptyDotV8 3.4s .5s ease-in-out infinite;
}

.store-empty-icon-v8{
    position:relative;
    width:82px;
    height:82px;
    display:grid;
    place-items:center;
    margin-bottom:24px;
    border:1px solid rgba(24,24,27,.09);
    border-radius:24px;
    background:rgba(255,255,255,.9);
    box-shadow:
        0 20px 38px rgba(24,24,27,.09),
        0 0 0 8px rgba(255,255,255,.55);
    animation:storeEmptyIconV8 .7s .08s cubic-bezier(.22,1,.36,1) both;
}

.store-empty-icon-v8::before{
    content:"";
    position:absolute;
    inset:9px;
    border:1px dashed rgba(24,24,27,.085);
    border-radius:18px;
}

.store-empty-icon-grid{
    position:relative;
    z-index:2;
    display:grid;
    grid-template-columns:repeat(2,13px);
    gap:7px;
}

.store-empty-icon-grid i{
    display:block;
    width:13px;
    height:13px;
    border:2px solid #18181b;
    border-radius:4px;
    background:#fff;
}

.store-empty-icon-grid i:nth-child(2){
    border-color:#dc2626;
}

.store-empty-icon-grid i:nth-child(4){
    background:#18181b;
    border-color:#18181b;
}

.store-empty-icon-corner{
    position:absolute;
    right:12px;
    top:11px;
    width:7px;
    height:7px;
    border-radius:50%;
    background:#dc2626;
    box-shadow:0 0 0 5px rgba(220,38,38,.07);
    animation:storeEmptyPulseV8 2s ease-in-out infinite;
}

.store-empty-copy-v8{
    position:relative;
    z-index:2;
    max-width:560px;
    text-align:center;
    animation:storeEmptyCopyV8 .6s .15s cubic-bezier(.22,1,.36,1) both;
}

.store-empty-kicker{
    display:inline-flex;
    align-items:center;
    gap:7px;
    margin-bottom:11px;
    color:#dc2626;
    font-size:9px;
    line-height:1;
    font-weight:800;
    letter-spacing:.19em;
    text-transform:uppercase;
}

.store-empty-kicker i{
    width:5px;
    height:5px;
    border-radius:50%;
    background:#dc2626;
    box-shadow:0 0 0 4px rgba(220,38,38,.07);
}

.store-empty-copy-v8 h3{
    margin:0;
    color:#18181b;
    font-size:26px;
    line-height:1.16;
    font-weight:800;
    letter-spacing:-.045em;
}

.store-empty-copy-v8 p{
    max-width:510px;
    margin:10px auto 0;
    color:#8b8b95;
    font-size:13px;
    line-height:1.7;
}

.store-empty-actions-v8{
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    margin-top:23px;
    animation:storeEmptyActionsV8 .6s .22s cubic-bezier(.22,1,.36,1) both;
}

.store-empty-actions-v8 button{
    min-height:42px;
    border-radius:12px;
    padding:0 15px;
    border:1px solid rgba(24,24,27,.10);
    font:inherit;
    font-size:12px;
    font-weight:750;
    cursor:pointer;
    transition:
        transform .22s ease,
        box-shadow .22s ease,
        background .22s ease,
        border-color .22s ease;
}

.store-empty-reset-v8{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#52525b;
    background:#fff;
}

.store-empty-reset-v8:hover{
    transform:translateY(-2px);
    border-color:rgba(24,24,27,.18);
    box-shadow:0 10px 22px rgba(24,24,27,.08);
}

.store-empty-btn-icon{
    font-size:15px;
    line-height:1;
}

.store-empty-create-v8{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:#fff;
    background:#18181b;
    border-color:#18181b !important;
    box-shadow:0 9px 20px rgba(24,24,27,.13);
}

.store-empty-create-v8 span{
    color:#ff4b4b;
    font-size:17px;
    line-height:1;
}

.store-empty-create-v8 b{
    margin-left:3px;
    font-size:13px;
    font-weight:600;
    transition:transform .2s ease;
}

.store-empty-create-v8:hover{
    transform:translateY(-2px);
    box-shadow:0 13px 28px rgba(24,24,27,.18);
}

.store-empty-create-v8:hover b{
    transform:translateX(3px);
}

.store-empty-hint-v8{
    display:flex;
    align-items:center;
    gap:7px;
    margin-top:19px;
    color:#a1a1aa;
    font-size:10px;
    line-height:1.5;
    animation:storeEmptyHintV8 .6s .3s ease both;
}

.store-empty-hint-dot{
    width:4px;
    height:4px;
    flex:0 0 4px;
    border-radius:50%;
    background:#a1a1aa;
}

@keyframes storeEmptyRevealV8{
    from{opacity:0;transform:translateY(12px) scale(.985)}
    to{opacity:1;transform:none}
}

@keyframes storeEmptyIconV8{
    from{opacity:0;transform:translateY(14px) scale(.86) rotate(-4deg)}
    to{opacity:1;transform:none}
}

@keyframes storeEmptyCopyV8{
    from{opacity:0;transform:translateY(10px)}
    to{opacity:1;transform:none}
}

@keyframes storeEmptyActionsV8{
    from{opacity:0;transform:translateY(9px)}
    to{opacity:1;transform:none}
}

@keyframes storeEmptyHintV8{
    from{opacity:0}
    to{opacity:1}
}

@keyframes storeEmptyAmbientV8{
    0%,100%{transform:translate(-50%,-50%) scale(.92);opacity:.72}
    50%{transform:translate(-50%,-50%) scale(1.08);opacity:1}
}

@keyframes storeEmptyOrbitV8{
    from{transform:translate(-50%,-50%) rotate(-12deg)}
    to{transform:translate(-50%,-50%) rotate(348deg)}
}

@keyframes storeEmptyOrbitV8Reverse{
    from{transform:translate(-50%,-50%) rotate(18deg)}
    to{transform:translate(-50%,-50%) rotate(-342deg)}
}

@keyframes storeEmptyDotV8{
    0%,100%{transform:translateY(0);opacity:.55}
    50%{transform:translateY(-6px);opacity:1}
}

@keyframes storeEmptyPulseV8{
    0%,100%{transform:scale(1);opacity:.85}
    50%{transform:scale(1.22);opacity:1}
}

@media (max-width:700px){
    .store-empty-state-v8{
        min-height:330px;
        padding:48px 18px 38px;
        border-radius:22px;
    }

    .store-empty-icon-v8{
        width:72px;
        height:72px;
        margin-bottom:21px;
        border-radius:21px;
    }

    .store-empty-copy-v8 h3{
        font-size:22px;
        letter-spacing:-.035em;
    }

    .store-empty-copy-v8 p{
        max-width:330px;
        font-size:12px;
        line-height:1.65;
    }

    .store-empty-actions-v8{
        width:100%;
        flex-direction:column;
        gap:8px;
        margin-top:20px;
    }

    .store-empty-actions-v8 button{
        width:100%;
        justify-content:center;
    }

    .store-empty-hint-v8{
        max-width:310px;
        justify-content:center;
        text-align:center;
        margin-top:16px;
    }

    .store-empty-orbit{
        transform:translate(-50%,-50%) scale(.78);
    }
}

@media (prefers-reduced-motion:reduce){
    .store-empty-state-v8,
    .store-empty-icon-v8,
    .store-empty-copy-v8,
    .store-empty-actions-v8,
    .store-empty-hint-v8,
    .store-empty-orbit-ring,
    .store-empty-orbit-dot,
    .store-empty-icon-corner{
        animation:none !important;
    }
}

</style>
</div>
<?php /**PATH F:\Website\rizky-tools-ai\resources\views\livewire\stores.blade.php ENDPATH**/ ?>