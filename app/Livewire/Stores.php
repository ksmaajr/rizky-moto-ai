<?php

namespace App\Livewire;

use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Stores extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $status = 'all';

    public bool $showForm = false;
    public bool $showDelete = false;
    public bool $editing = false;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $deleteName = '';
    public int $deleteTemplates = 0;
    public int $deleteGenerations = 0;

    public array $marketplaces = [
        'shopee' => 'Shopee',
        'tokopedia' => 'Tokopedia',
        'tiktok_shop' => 'TikTok Shop',
        'lazada' => 'Lazada',
        'blibli' => 'Blibli',
        'bukalapak' => 'Bukalapak',
        'other' => 'Marketplace Lainnya',
    ];

    public string $name = '';
    public string $marketplace = 'shopee';
    public string $brand_name = '';
    public string $brand_color = '#E11D48';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public $logo = null;
    public ?string $currentLogo = null;

    /** Only the Store result area gets a refresh animation. */
    public int $renderVersion = 0;

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
            ->orderByDesc('created_at')
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

    public function updatedSearch(): void
    {
        $this->renderVersion++;
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            return;
        }

        $this->status = $status;
        $this->renderVersion++;
    }

    public function setMarketplace(string $marketplace): void
    {
        if (! array_key_exists($marketplace, $this->marketplaces)) {
            return;
        }

        $this->marketplace = $marketplace;
        $this->resetValidation('marketplace');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->renderVersion++;
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->editing = false;
        $this->showForm = true;

        $this->dispatch('store-modal-opened', mode: 'create');
    }

    public function openEdit(int $id): void
    {
        $store = Store::findOrFail($id);

        $this->editingId = $store->id;
        $this->name = (string) $store->name;
        $this->marketplace = (string) ($store->marketplace ?: 'shopee');
        $this->brand_name = (string) ($store->brand_name ?: '');
        $this->brand_color = (string) ($store->brand_color ?: '#E11D48');
        $this->description = (string) ($store->description ?: '');
        $this->is_active = (bool) $store->is_active;
        $this->sort_order = (int) ($store->sort_order ?? 0);
        $this->currentLogo = $store->logo_path;
        $this->logo = null;

        $this->editing = true;
        $this->showForm = true;

        $this->resetValidation();

        $this->dispatch('store-modal-opened', mode: 'edit');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'marketplace' => ['required', 'string', 'max:100'],
            'brand_name' => ['nullable', 'string', 'max:150'],
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999999'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.required' => 'Nama Store wajib diisi.',
            'marketplace.required' => 'Marketplace wajib dipilih atau diisi.',
            'brand_color.regex' => 'Brand color harus HEX, contoh #E11D48.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo hanya JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 5 MB.',
        ]);

        $isEditing = $this->editing;
        $store = $this->editingId
            ? Store::findOrFail($this->editingId)
            : new Store();

        $oldLogo = $store->logo_path;

        $store->name = trim($this->name);
        $store->marketplace = trim($this->marketplace);
        $store->brand_name = trim($this->brand_name) ?: null;
        $store->brand_color = strtoupper($this->brand_color);
        $store->description = trim($this->description) ?: null;
        $store->is_active = $this->is_active;
        $store->sort_order = $this->sort_order;

        if ($this->logo) {
            $store->logo_path = $this->logo->store('stores', 'public');
        }

        $store->save();
        $this->renderVersion++;

        if ($this->logo && $oldLogo && $oldLogo !== $store->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->renderVersion++;

        $this->dispatch(
            'toast',
            type: 'success',
            title: $isEditing ? 'Store diperbarui' : 'Store dibuat',
            message: $isEditing
                ? 'Perubahan Store berhasil disimpan.'
                : 'Store baru berhasil ditambahkan.'
        );
    }

    public function confirmDelete(int $id): void
    {
        $store = Store::withCount(['templates', 'generations'])->findOrFail($id);

        $this->deleteId = $store->id;
        $this->deleteName = (string) $store->name;
        $this->deleteTemplates = (int) $store->templates_count;
        $this->deleteGenerations = (int) $store->generations_count;
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

        if ($store->logo_path && Storage::disk('public')->exists($store->logo_path)) {
            Storage::disk('public')->delete($store->logo_path);
        }

        $name = $store->name;
        $store->delete();
        $this->renderVersion++;

        $this->showDelete = false;
        $this->deleteId = null;
        $this->deleteName = '';
        $this->deleteTemplates = 0;
        $this->deleteGenerations = 0;
        $this->renderVersion++;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Store dihapus',
            message: $name . ' berhasil dihapus dari workspace.'
        );
    }

    public function toggleStatus(int $id): void
    {
        $store = Store::findOrFail($id);
        $store->is_active = ! $store->is_active;
        $store->save();
        $this->renderVersion++;

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
        $this->deleteName = '';
        $this->deleteTemplates = 0;
        $this->deleteGenerations = 0;
    }

    private function resetForm(): void
    {
        $this->resetValidation();

        $this->editingId = null;
        $this->name = '';
        $this->marketplace = 'shopee';
        $this->brand_name = '';
        $this->brand_color = '#E11D48';
        $this->description = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->logo = null;
        $this->currentLogo = null;
    }

    public function render()
    {
        return view('livewire.stores');
    }
}
