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
            <div><small>TOTAL STORES</small><strong>{{ $this->totalStores }}</strong><em>All marketplace identities</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon green">✓</span>
            <div><small>ACTIVE</small><strong>{{ $this->activeStores }}</strong><em>Currently connected</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon dark">○</span>
            <div><small>INACTIVE</small><strong>{{ $this->inactiveStores }}</strong><em>Paused identities</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon violet">▦</span>
            <div><small>TEMPLATES</small><strong>{{ $this->totalTemplates }}</strong><em>Across all stores</em></div>
        </article>
        <article>
            <span class="stores-kpi-icon orange">⌁</span>
            <div><small>GENERATIONS</small><strong>{{ $this->totalGenerations }}</strong><em>Visuals generated</em></div>
        </article>
    </section>

    <section class="stores-toolbar">
        <label class="stores-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search store, brand, marketplace...">
            <kbd>⌘ K</kbd>
        </label>

        <div class="stores-filter-group">
            <button type="button" class="{{ $status === 'all' ? 'active' : '' }}" wire:click="$set('status','all')">All</button>
            <button type="button" class="{{ $status === 'active' ? 'active' : '' }}" wire:click="$set('status','active')">Active</button>
            <button type="button" class="{{ $status === 'inactive' ? 'active' : '' }}" wire:click="$set('status','inactive')">Inactive</button>
            <button type="button" wire:click="resetFilters">Reset ↺</button>
        </div>
    </section>

    <div class="stores-section-heading">
        <div>
            <span>CONNECTED STORES</span>
            <h2>Your marketplace workspace</h2>
        </div>
        <strong><i></i> {{ $this->stores->count() }} RESULT</strong>
    </div>

    @if ($this->stores->isEmpty())
        <div class="stores-empty">
            <div>⌕</div>
            <strong>Tidak ada Store ditemukan.</strong>
            <span>Coba ubah kata pencarian atau filter.</span>
            <button type="button" wire:click="openCreate">Create Store</button>
        </div>
    @else
        <section class="stores-grid">
            @foreach ($this->stores as $store)
                <article class="store-crud-card" wire:key="store-{{ $store->id }}">
                    <div class="store-card-cover" style="--store-brand: {{ $store->brand_color ?: '#dc2626' }}">
                        <div class="store-card-grid"></div>
                        <div class="store-card-orb"></div>

                        <div class="store-card-top">
                            <span><i></i>{{ strtoupper($store->marketplace ?: 'MARKETPLACE') }}</span>

                            <div class="store-card-menu">
                                <button type="button" wire:click="openEdit({{ $store->id }})" title="Edit">✎</button>
                                <button type="button" wire:click="confirmDelete({{ $store->id }})" title="Delete">×</button>
                            </div>
                        </div>

                        <div class="store-card-logo">
                            <img
                                src="{{ $store->logo_path ? asset('storage/'.$store->logo_path) : asset('images/logo-rizky-moto-shop.png') }}"
                                alt="{{ $store->brand_name ?: $store->name }}"
                            >
                        </div>
                    </div>

                    <div class="store-card-content">
                        <div class="store-card-heading">
                            <div>
                                <span class="store-status {{ $store->is_active ? 'active' : 'inactive' }}">
                                    <i></i>{{ $store->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                                <h3>{{ $store->name }}</h3>
                                <p>{{ $store->description ?: 'Marketplace identity' }}</p>
                            </div>
                            <b>#{{ str_pad((string) $store->id, 2, '0', STR_PAD_LEFT) }}</b>
                        </div>

                        <div class="store-identity-row">
                            <span>{{ strtoupper(substr($store->brand_name ?: $store->name, 0, 2)) }}</span>
                            <div>
                                <strong>{{ $store->brand_name ?: $store->name }}</strong>
                                <small>{{ $store->marketplace ?: 'Marketplace Store' }}</small>
                            </div>
                            <em class="{{ $store->is_active ? 'online' : 'offline' }}">
                                <i></i>{{ $store->is_active ? 'Connected' : 'Offline' }}
                            </em>
                        </div>

                        <div class="store-stats">
                            <div><small>Templates</small><strong>{{ $store->templates_count }}</strong></div>
                            <div><small>Generations</small><strong>{{ $store->generations_count }}</strong></div>
                            <div><small>Brand</small><strong style="color: {{ $store->brand_color ?: '#dc2626' }}">● Ready</strong></div>
                        </div>

                        <div class="store-actions">
                            <button type="button" wire:click="openEdit({{ $store->id }})">Manage Store <span>→</span></button>
                            <button type="button" wire:click="toggleStatus({{ $store->id }})" title="Toggle status">
                                {{ $store->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach

            <button type="button" class="store-create-card" wire:click="openCreate">
                <span>+</span>
                <strong>Add New Store</strong>
                <small>Hubungkan marketplace baru dan siapkan brand identity.</small>
                <em>Create store →</em>
            </button>
        </section>
    @endif


    {{-- CREATE / EDIT MODAL --}}
    @if ($showForm)
        <div class="stores-modal-backdrop" wire:click.self="cancelForm">
            <div class="stores-modal" wire:key="store-form-modal">
                <div class="stores-modal-header">
                    <div>
                        <span>{{ $editing ? 'EDIT STORE' : 'NEW STORE' }}</span>
                        <h2>{{ $editing ? 'Update Store.' : 'Create Store.' }}</h2>
                        <p>{{ $editing ? 'Perbarui identitas dan konfigurasi Store.' : 'Tambahkan identitas marketplace baru.' }}</p>
                    </div>
                    <button type="button" wire:click="cancelForm">×</button>
                </div>

                <form wire:submit="save" class="stores-form">
                    <div class="stores-form-grid">
                        <label>
                            <span>Store Name *</span>
                            <input wire:model="name" type="text" placeholder="Contoh: Rizky Moto Shop">
                            @error('name') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Marketplace</span>
                            <input wire:model="marketplace" type="text" placeholder="Shopee, Tokopedia, TikTok Shop...">
                            @error('marketplace') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Brand Name</span>
                            <input wire:model="brand_name" type="text" placeholder="Nama brand yang ditampilkan">
                            @error('brand_name') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Brand Color</span>
                            <div class="color-field">
                                <input wire:model="brand_color" type="text" placeholder="#dc2626">
                                <span style="background: {{ $brand_color ?: '#dc2626' }}"></span>
                            </div>
                            @error('brand_color') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label class="form-full">
                            <span>Description</span>
                            <textarea wire:model="description" rows="3" placeholder="Deskripsi singkat Store..."></textarea>
                            @error('description') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Sort Order</span>
                            <input wire:model="sort_order" type="number" min="0">
                            @error('sort_order') <small class="form-error">{{ $message }}</small> @enderror
                        </label>

                        <label>
                            <span>Status</span>
                            <button type="button" class="form-status-toggle {{ $is_active ? 'on' : '' }}" wire:click="$toggle('is_active')">
                                <span></span>
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </label>

                        <label class="form-full">
                            <span>Store Logo</span>
                            <div class="logo-upload">
                                <div class="logo-preview">
                                    @if ($logo)
                                        <img src="{{ $logo->temporaryUrl() }}" alt="Preview">
                                    @elseif ($currentLogo)
                                        <img src="{{ asset('storage/'.$currentLogo) }}" alt="Current logo">
                                    @else
                                        <img src="{{ asset('images/logo-rizky-moto-shop.png') }}" alt="Default logo">
                                    @endif
                                </div>
                                <div>
                                    <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp">
                                    <small>PNG, JPG, WEBP · maksimal 5 MB</small>
                                </div>
                            </div>
                            @error('logo') <small class="form-error">{{ $message }}</small> @enderror
                        </label>
                    </div>

                    <div class="stores-modal-actions">
                        <button type="button" class="secondary" wire:click="cancelForm">Batal</button>
                        <button type="submit" class="primary">
                            <span wire:loading.remove wire:target="save">{{ $editing ? 'Simpan Perubahan' : 'Create Store' }}</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif


    {{-- DELETE CONFIRMATION --}}
    @if ($showDelete)
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
    @endif


    <div class="stores-page-loading" wire:loading.flex wire:target="save,delete,toggleStatus">
        <div>
            <span class="stores-spinner"></span>
            <strong>Processing Store</strong>
            <small>Please wait...</small>
        </div>
    </div>

    <style>
        .stores-page{display:flex;flex-direction:column;gap:22px;padding-bottom:44px;color:#18181b}
        .stores-page-header{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;padding:28px 30px;border:1px solid #e4e4e7;border-radius:25px;background:#fff;box-shadow:0 16px 45px rgba(24,24,27,.045);position:relative;overflow:hidden}
        .stores-page-header:after{content:"";position:absolute;width:330px;height:330px;right:-120px;top:-170px;border-radius:50%;background:radial-gradient(circle,rgba(220,38,38,.1),transparent 68%);pointer-events:none}
        .stores-eyebrow{display:inline-flex;align-items:center;gap:7px;color:#991b1b;font-size:10px;font-weight:900;letter-spacing:.16em}
        .stores-eyebrow i,.store-status i,.store-identity-row em i,.stores-section-heading>strong i{width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;box-shadow:0 0 0 4px rgba(34,197,94,.08)}
        .stores-eyebrow i{background:#dc2626;box-shadow:0 0 0 4px rgba(220,38,38,.08)}
        .stores-page-header h1{margin:12px 0 5px;font-size:clamp(38px,4vw,57px);line-height:.95;letter-spacing:-.055em;font-weight:950}
        .stores-page-header h1 span{color:#a1a1aa}
        .stores-page-header p{margin:0;color:#71717a;font-size:14px;line-height:1.6}
        .stores-primary-button{position:relative;z-index:2;display:flex;align-items:center;gap:11px;min-width:185px;padding:11px 13px;border:0;border-radius:15px;color:#fff;background:#18181b;cursor:pointer;box-shadow:0 12px 30px rgba(24,24,27,.15);transition:.25s}
        .stores-primary-button:hover{background:#dc2626;transform:translateY(-2px)}
        .stores-primary-button>b{display:grid;place-items:center;width:39px;height:39px;border-radius:11px;background:rgba(255,255,255,.1);font-size:22px;font-weight:300}
        .stores-primary-button span{display:grid;gap:2px;text-align:left}.stores-primary-button strong{font-size:11px}.stores-primary-button small{font-size:9px;color:#a1a1aa}.stores-primary-button em{margin-left:auto;font-style:normal;color:#a1a1aa}
        .stores-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
        .stores-kpis article{position:relative;display:flex;gap:12px;min-height:120px;padding:17px;border:1px solid #e4e4e7;border-radius:19px;background:#fff;box-shadow:0 10px 30px rgba(24,24,27,.035)}
        .stores-kpi-icon{display:grid;width:37px;height:37px;flex:0 0 37px;place-items:center;border-radius:12px;font-weight:900}.stores-kpi-icon.red{color:#dc2626;background:#fef2f2}.stores-kpi-icon.green{color:#16a34a;background:#f0fdf4}.stores-kpi-icon.dark{color:#3f3f46;background:#f4f4f5}.stores-kpi-icon.violet{color:#7c3aed;background:#f5f3ff}.stores-kpi-icon.orange{color:#ea580c;background:#fff7ed}
        .stores-kpis article div{display:grid;align-content:start;gap:3px}.stores-kpis small{font-size:8px;letter-spacing:.13em;color:#a1a1aa;font-weight:900}.stores-kpis strong{font-size:29px;line-height:1;letter-spacing:-.04em}.stores-kpis em{font-size:9px;color:#a1a1aa;font-style:normal}.stores-kpis label{position:absolute;right:13px;top:14px;color:#16a34a;font-size:7px;font-weight:900}.stores-kpis label i{display:inline-block;width:5px;height:5px;margin-right:4px;border-radius:50%;background:#22c55e}
        .stores-toolbar{display:flex;gap:10px;padding:10px;border:1px solid #e4e4e7;border-radius:18px;background:#fff}
        .stores-search{display:flex;align-items:center;gap:9px;flex:1;min-width:200px;height:42px;padding:0 12px;border:1px solid #e4e4e7;border-radius:12px;background:#fafafa}.stores-search svg{width:17px;color:#a1a1aa}.stores-search input{width:100%;border:0;outline:0;background:transparent;font-size:13px;color:#27272a}.stores-search kbd{font-size:9px;padding:4px 6px;border:1px solid #e4e4e7;border-radius:6px;color:#a1a1aa;background:#fff;white-space:nowrap}
        .stores-filter-group{display:flex;gap:6px}.stores-filter-group button{height:42px;padding:0 14px;border:1px solid #e4e4e7;border-radius:11px;background:#fff;color:#71717a;font-size:11px;font-weight:800;cursor:pointer}.stores-filter-group button.active{color:#fff;border-color:#18181b;background:#18181b}.stores-filter-group button:hover:not(.active){background:#f4f4f5}
        .stores-section-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}.stores-section-heading>div>span{font-size:9px;letter-spacing:.16em;color:#a1a1aa;font-weight:900}.stores-section-heading h2{margin:5px 0 0;font-size:24px;letter-spacing:-.035em;font-weight:900}.stores-section-heading>strong{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid #e4e4e7;border-radius:999px;background:#fff;color:#71717a;font-size:9px}
        .stores-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .store-crud-card{overflow:hidden;border:1px solid #e4e4e7;border-radius:23px;background:#fff;box-shadow:0 12px 35px rgba(24,24,27,.045);transition:.25s}.store-crud-card:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(24,24,27,.08)}
        .store-card-cover{height:145px;position:relative;overflow:hidden;background:radial-gradient(circle at 80% 20%,color-mix(in srgb,var(--store-brand) 28%,transparent),transparent 32%),linear-gradient(135deg,#18181b,#27272a)}
        .store-card-grid{position:absolute;inset:0;opacity:.22;background-image:linear-gradient(120deg,transparent 0 47%,#fff 48%,transparent 49%),linear-gradient(60deg,transparent 0 47%,#fff 48%,transparent 49%);background-size:58px 58px}
        .store-card-orb{position:absolute;right:-35px;bottom:-95px;width:235px;height:235px;border:1px solid color-mix(in srgb,var(--store-brand) 50%,transparent);border-radius:50%;box-shadow:0 0 0 25px rgba(255,255,255,.015)}
        .store-card-top{position:relative;z-index:2;display:flex;justify-content:space-between;padding:14px}.store-card-top>span{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border:1px solid rgba(255,255,255,.12);border-radius:999px;color:#e4e4e7;background:rgba(255,255,255,.07);font-size:7px;font-weight:900;letter-spacing:.1em}.store-card-top>span i{width:5px;height:5px;border-radius:50%;background:var(--store-brand)}
        .store-card-menu{display:flex;gap:5px}.store-card-menu button{width:29px;height:29px;border:1px solid rgba(255,255,255,.12);border-radius:9px;color:#fff;background:rgba(255,255,255,.07);cursor:pointer}
        .store-card-logo{position:absolute;z-index:3;left:17px;bottom:-25px;display:grid;width:82px;height:82px;place-items:center;border:5px solid #fff;border-radius:21px;overflow:hidden;background:#09090b;box-shadow:0 10px 25px rgba(0,0,0,.2)}.store-card-logo img{width:90%;height:90%;object-fit:contain}
        .store-card-content{padding:39px 18px 18px}.store-card-heading{display:flex;justify-content:space-between;gap:12px}.store-card-heading h3{margin:7px 0 2px;font-size:20px;letter-spacing:-.025em;font-weight:900}.store-card-heading p{margin:0;color:#a1a1aa;font-size:11px}.store-card-heading>b{color:#d4d4d8;font-size:20px}.store-status{display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-size:8px;font-weight:900}.store-status.inactive{color:#a1a1aa}.store-status.inactive i{background:#a1a1aa;box-shadow:none}
        .store-identity-row{display:flex;align-items:center;gap:10px;margin-top:17px;padding:10px;border:1px solid #f4f4f5;border-radius:13px;background:#fafafa}.store-identity-row>span{display:grid;width:32px;height:32px;place-items:center;border-radius:9px;color:#fff;background:#18181b;font-size:9px;font-weight:900}.store-identity-row div{display:grid;gap:2px}.store-identity-row strong{font-size:11px}.store-identity-row small{font-size:9px;color:#a1a1aa}.store-identity-row em{margin-left:auto;font-size:8px;font-style:normal;font-weight:900;color:#16a34a}.store-identity-row em.offline{color:#a1a1aa}.store-identity-row em i{width:5px;height:5px;box-shadow:none}
        .store-stats{display:grid;grid-template-columns:repeat(3,1fr);margin-top:13px;border-top:1px solid #f4f4f5;border-bottom:1px solid #f4f4f5}.store-stats>div{padding:12px 8px;border-right:1px solid #f4f4f5}.store-stats>div:last-child{border-right:0}.store-stats small{display:block;color:#a1a1aa;font-size:8px;font-weight:800;text-transform:uppercase}.store-stats strong{display:block;margin-top:4px;font-size:13px}
        .store-actions{display:flex;gap:8px;margin-top:13px}.store-actions button{height:40px;border-radius:11px;font-size:10px;font-weight:900;cursor:pointer}.store-actions button:first-child{display:flex;align-items:center;justify-content:space-between;flex:1;padding:0 13px;border:0;color:#fff;background:#18181b}.store-actions button:first-child:hover{background:#dc2626}.store-actions button:last-child{padding:0 12px;border:1px solid #e4e4e7;color:#52525b;background:#fff}
        .store-create-card{min-height:400px;border:1px dashed #d4d4d8;border-radius:23px;background:radial-gradient(circle at 50% 0,rgba(220,38,38,.08),transparent 45%),#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:30px;text-align:center;cursor:pointer;transition:.25s}.store-create-card:hover{transform:translateY(-4px);border-color:#dc2626}.store-create-card>span{display:grid;width:66px;height:66px;place-items:center;border:1px solid #e4e4e7;border-radius:20px;color:#dc2626;background:#fff;font-size:28px;box-shadow:0 10px 25px rgba(24,24,27,.07)}.store-create-card strong{margin-top:18px;font-size:16px}.store-create-card small{max-width:280px;margin-top:7px;color:#a1a1aa;font-size:10px;line-height:1.6}.store-create-card em{margin-top:18px;color:#dc2626;font-size:10px;font-weight:900;font-style:normal}
        .stores-empty{padding:70px 20px;border:1px dashed #d4d4d8;border-radius:22px;background:#fff;text-align:center}.stores-empty>div{font-size:34px;color:#a1a1aa}.stores-empty strong{display:block;margin-top:10px;font-size:18px}.stores-empty span{display:block;margin-top:5px;color:#a1a1aa;font-size:12px}.stores-empty button{margin-top:18px;padding:10px 15px;border:0;border-radius:10px;color:#fff;background:#18181b;font-weight:800}
        .stores-modal-backdrop{position:fixed;z-index:1000;inset:0;display:grid;place-items:center;padding:20px;background:rgba(9,9,11,.46);backdrop-filter:blur(10px);animation:storesFade .2s ease}.stores-modal{width:min(720px,100%);max-height:min(88vh,820px);overflow:auto;border:1px solid rgba(255,255,255,.7);border-radius:24px;background:#fff;box-shadow:0 30px 90px rgba(0,0,0,.22);animation:storesModal .25s ease}.stores-modal-header{display:flex;justify-content:space-between;gap:20px;padding:24px;border-bottom:1px solid #f4f4f5}.stores-modal-header>div>span,.stores-confirm-modal>span{color:#dc2626;font-size:8px;font-weight:900;letter-spacing:.16em}.stores-modal-header h2,.stores-confirm-modal h2{margin:7px 0 4px;font-size:26px;letter-spacing:-.04em}.stores-modal-header p,.stores-confirm-modal p{margin:0;color:#a1a1aa;font-size:11px;line-height:1.6}.stores-modal-header>button{width:35px;height:35px;border:1px solid #e4e4e7;border-radius:10px;background:#fff;color:#71717a;font-size:20px;cursor:pointer}
        .stores-form{padding:22px}.stores-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.stores-form label{display:grid;gap:7px}.stores-form label.form-full{grid-column:1/-1}.stores-form label>span{font-size:10px;font-weight:900;color:#52525b}.stores-form input,.stores-form textarea{width:100%;border:1px solid #e4e4e7;border-radius:11px;padding:11px 12px;outline:0;background:#fafafa;font:inherit;font-size:12px;color:#27272a}.stores-form input:focus,.stores-form textarea:focus{border-color:#a1a1aa;box-shadow:0 0 0 3px rgba(24,24,27,.04)}.stores-form textarea{resize:vertical}.form-error{color:#dc2626;font-size:9px}.color-field{position:relative}.color-field>input{padding-right:45px}.color-field>span{position:absolute;right:11px;top:50%;width:20px;height:20px;border-radius:7px;transform:translateY(-50%);border:1px solid #e4e4e7}.form-status-toggle{display:flex;align-items:center;gap:8px;width:max-content;padding:9px 12px;border:1px solid #e4e4e7;border-radius:10px;background:#fafafa;font-size:11px;font-weight:800;cursor:pointer}.form-status-toggle span{width:26px;height:15px;border-radius:999px;background:#d4d4d8;position:relative}.form-status-toggle span:after{content:"";position:absolute;top:2px;left:2px;width:11px;height:11px;border-radius:50%;background:#fff;transition:.2s}.form-status-toggle.on{color:#16a34a;border-color:#bbf7d0;background:#f0fdf4}.form-status-toggle.on span{background:#22c55e}.form-status-toggle.on span:after{left:13px}.logo-upload{display:flex;align-items:center;gap:14px;padding:12px;border:1px dashed #d4d4d8;border-radius:13px;background:#fafafa}.logo-preview{display:grid;width:64px;height:64px;place-items:center;overflow:hidden;border:1px solid #e4e4e7;border-radius:13px;background:#18181b}.logo-preview img{width:90%;height:90%;object-fit:contain}.logo-upload input{font-size:11px}.logo-upload small{display:block;margin-top:5px;color:#a1a1aa;font-size:9px}.stores-modal-actions{display:flex;justify-content:flex-end;gap:8px;padding:16px 22px;border-top:1px solid #f4f4f5}.stores-modal-actions button{height:40px;padding:0 15px;border-radius:10px;font-size:10px;font-weight:900;cursor:pointer}.stores-modal-actions .secondary{border:1px solid #e4e4e7;background:#fff;color:#52525b}.stores-modal-actions .primary{border:0;background:#18181b;color:#fff}.stores-modal-actions .danger{border:0;background:#dc2626;color:#fff}.stores-confirm-modal{width:min(440px,100%);padding:26px;border-radius:23px;background:#fff;box-shadow:0 30px 90px rgba(0,0,0,.22);animation:storesModal .25s ease}.confirm-icon{display:grid;width:45px;height:45px;place-items:center;border-radius:14px;color:#dc2626;background:#fef2f2;font-weight:950;font-size:19px;margin-bottom:14px}
        .stores-page-loading{position:fixed;z-index:1100;inset:0;align-items:center;justify-content:center;background:rgba(244,244,245,.4);backdrop-filter:blur(5px)}.stores-page-loading>div{display:grid;justify-items:center;gap:7px;padding:18px 23px;border:1px solid #e4e4e7;border-radius:16px;background:#fff;box-shadow:0 20px 50px rgba(24,24,27,.12)}.stores-spinner{width:27px;height:27px;border:3px solid #e4e4e7;border-top-color:#dc2626;border-radius:50%;animation:storesSpin .75s linear infinite}.stores-page-loading strong{font-size:11px}.stores-page-loading small{font-size:9px;color:#a1a1aa}
        @keyframes storesSpin{to{transform:rotate(360deg)}}@keyframes storesFade{from{opacity:0}to{opacity:1}}@keyframes storesModal{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:none}}
        @media(max-width:1100px){.stores-kpis{grid-template-columns:repeat(3,1fr)}.stores-grid{grid-template-columns:1fr}}
        @media(max-width:760px){.stores-page-header{align-items:stretch;flex-direction:column;padding:21px}.stores-primary-button{width:100%}.stores-kpis{grid-template-columns:1fr 1fr}.stores-toolbar{flex-direction:column}.stores-filter-group{display:grid;grid-template-columns:repeat(4,1fr)}.stores-filter-group button{padding:0 7px}.stores-section-heading{align-items:flex-start;flex-direction:column}.stores-form-grid{grid-template-columns:1fr}.stores-form label.form-full{grid-column:auto}}
        @media(max-width:480px){.stores-page-header h1{font-size:34px}.stores-page-header p{font-size:12px}.stores-kpis{grid-template-columns:1fr 1fr}.stores-kpis article{min-height:108px;padding:13px}.stores-kpis strong{font-size:25px}.stores-search kbd{display:none}.stores-filter-group{grid-template-columns:1fr 1fr}.store-card-content{padding:38px 14px 14px}.stores-modal-backdrop{padding:10px}.stores-modal-header{padding:19px}.stores-form{padding:17px}.stores-modal-actions{padding:13px 17px}.logo-upload{align-items:flex-start;flex-direction:column}}
        @media(prefers-reduced-motion:reduce){.store-crud-card,.store-create-card,.stores-primary-button,.stores-modal,.stores-confirm-modal{transition:none!important;animation:none!important}}
    </style>
</div>
