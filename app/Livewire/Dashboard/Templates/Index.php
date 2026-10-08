<?php

namespace App\Livewire\Dashboard\Templates;

use App\Models\Generation;
use App\Models\Store;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $storeFilter = 'all';
    public string $statusFilter = 'all';
    public string $sortBy = 'latest';

    public bool $showForm = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public ?int $templateStoreId = null;
    public string $templateName = '';
    public string $templateDescription = '';
    public $templateExampleImage = null;
    public ?string $templateExistingImage = null;
    public string $templatePrompt = '';
    public string $templateNegativePrompt = '';
    public string $templateAspectRatio = '1:1';
    public string $templateOutputQuality = 'high';
    public bool $templateActive = true;
    public int $templateSortOrder = 0;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(?string $initialSearch = null): void
    {
        $this->search = trim((string) $initialSearch);
    }

    public function getUserProperty(): ?object
    {
        return auth()->user();
    }

    public function getStoresProperty()
    {
        return Store::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTemplatesProperty()
    {
        return Template::query()
            ->with('store')
            ->withCount('generations')
            ->when($this->search !== '', function ($query) {
                $keyword = '%' . trim($this->search) . '%';

                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhere('slug', 'like', $keyword)
                        ->orWhereHas('store', function ($storeQuery) use ($keyword) {
                            $storeQuery
                                ->where('name', 'like', $keyword)
                                ->orWhere('brand_name', 'like', $keyword)
                                ->orWhere('marketplace', 'like', $keyword);
                        });
                });
            })
            ->when($this->storeFilter !== 'all', fn ($query) => $query->where('store_id', (int) $this->storeFilter))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->statusFilter === 'draft', fn ($query) => $query->where('is_active', false))
            ->when($this->sortBy === 'name', fn ($query) => $query->orderBy('name'))
            ->when($this->sortBy === 'usage', fn ($query) => $query->orderByDesc('generations_count'))
            ->when($this->sortBy === 'oldest', fn ($query) => $query->orderBy('created_at'))
            ->when($this->sortBy === 'latest', fn ($query) => $query->orderByDesc('created_at'))
            ->get();
    }

    public function getTotalTemplatesProperty(): int
    {
        return Template::count();
    }

    public function getActiveTemplatesProperty(): int
    {
        return Template::where('is_active', true)->count();
    }

    public function getDraftTemplatesProperty(): int
    {
        return Template::where('is_active', false)->count();
    }

    public function getTotalGenerationsProperty(): int
    {
        return Generation::count();
    }

    public function updatedSearch(): void
    {
        $this->dispatch('template-content-updated');
    }

    public function updatedStoreFilter(): void
    {
        $this->dispatch('template-content-updated');
    }

    public function updatedStatusFilter(): void
    {
        $this->dispatch('template-content-updated');
    }

    public function updatedSortBy(): void
    {
        $this->dispatch('template-content-updated');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->storeFilter = 'all';
        $this->statusFilter = 'all';
        $this->sortBy = 'latest';

        $this->dispatch('toast', type: 'info', title: 'Filter direset', message: 'Template library kembali menampilkan semua template.');
        $this->dispatch('template-content-updated');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editing = false;
        $this->editingId = null;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $template = Template::findOrFail($id);

        $this->editing = true;
        $this->editingId = $template->id;
        $this->templateStoreId = $template->store_id;
        $this->templateName = $template->name;
        $this->templateDescription = $template->description ?? '';
        $this->templateExistingImage = $template->example_image_path;
        $this->templateExampleImage = null;
        $this->templatePrompt = $template->prompt ?? '';
        $this->templateNegativePrompt = $template->negative_prompt ?? '';
        $this->templateAspectRatio = $template->aspect_ratio ?? '1:1';
        $this->templateOutputQuality = $template->output_quality ?? 'high';
        $this->templateActive = (bool) $template->is_active;
        $this->templateSortOrder = (int) ($template->sort_order ?? 0);

        $this->showForm = true;
        $this->resetValidation();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'templateStoreId' => ['required', 'integer', 'exists:stores,id'],
            'templateName' => ['required', 'string', 'max:150'],
            'templateDescription' => ['nullable', 'string', 'max:500'],
            'templateExampleImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'templatePrompt' => ['required', 'string'],
            'templateNegativePrompt' => ['nullable', 'string'],
            'templateAspectRatio' => ['required', 'in:1:1,4:5,3:4,4:3,16:9,9:16'],
            'templateOutputQuality' => ['required', 'in:standard,high'],
            'templateActive' => ['boolean'],
            'templateSortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'templateStoreId.required' => 'Store wajib dipilih.',
            'templateName.required' => 'Nama template wajib diisi.',
            'templatePrompt.required' => 'Prompt AI wajib diisi.',
            'templateExampleImage.image' => 'File harus berupa gambar.',
            'templateExampleImage.mimes' => 'Format hanya JPG, PNG, atau WEBP.',
            'templateExampleImage.max' => 'Ukuran gambar maksimal 10 MB.',
        ]);

        $template = $this->editingId
            ? Template::findOrFail($this->editingId)
            : new Template();

        $oldImage = $template->example_image_path;
        $wasEditing = $this->editing;

        $template->store_id = $this->templateStoreId;
        $template->name = trim($this->templateName);
        $template->slug = Str::slug($this->templateName);
        $template->description = $this->templateDescription ?: null;
        $template->prompt = $this->templatePrompt;
        $template->negative_prompt = $this->templateNegativePrompt ?: null;
        $template->aspect_ratio = $this->templateAspectRatio;
        $template->output_quality = $this->templateOutputQuality;
        $template->is_active = $this->templateActive;
        $template->sort_order = $this->templateSortOrder;

        if ($this->templateExampleImage) {
            $template->example_image_path = $this->templateExampleImage->store('templates/examples', 'public');
        }

        $template->save();

        if ($this->templateExampleImage && $oldImage && Storage::disk('public')->exists($oldImage)) {
            Storage::disk('public')->delete($oldImage);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->resetValidation();

        $this->dispatch(
            'toast',
            type: 'success',
            title: $wasEditing ? 'Template diperbarui' : 'Template berhasil dibuat',
            message: $wasEditing
                ? 'Konfigurasi template berhasil disimpan.'
                : 'Template baru sudah tersedia di Template Library.'
        );
    }

    public function toggleStatus(int $id): void
    {
        $template = Template::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        $this->dispatch(
            'toast',
            type: 'success',
            title: $template->is_active ? 'Template diaktifkan' : 'Template dinonaktifkan',
            message: $template->name
        );
    }

    public function duplicate(int $id): void
    {
        $template = Template::findOrFail($id);
        $copy = $template->replicate();
        $copy->name = $template->name . ' Copy';
        $copy->slug = Str::slug($copy->name . '-' . uniqid());
        $copy->is_active = false;
        $copy->sort_order = ((int) $template->sort_order) + 1;
        $copy->save();

        $this->dispatch('toast', type: 'success', title: 'Template diduplikasi', message: 'Salinan template berhasil dibuat sebagai Draft.');
    }

    public function confirmDelete(int $id): void
    {
        $template = Template::findOrFail($id);
        $this->deletingId = $template->id;
        $this->deletingName = $template->name;
        $this->showDeleteModal = true;
    }

    public function closeDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $template = Template::findOrFail($this->deletingId);

        if ($template->generations()->exists()) {
            $this->closeDelete();
            $this->dispatch('toast', type: 'warning', title: 'Template tidak dapat dihapus', message: 'Template sudah digunakan oleh generation.');
            return;
        }

        if ($template->example_image_path && Storage::disk('public')->exists($template->example_image_path)) {
            Storage::disk('public')->delete($template->example_image_path);
        }

        $name = $template->name;
        $template->delete();
        $this->closeDelete();

        $this->dispatch('toast', type: 'success', title: 'Template dihapus', message: $name . ' berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editing = false;
        $this->editingId = null;
        $this->templateStoreId = $this->stores->first()?->id;
        $this->templateName = '';
        $this->templateDescription = '';
        $this->templateExampleImage = null;
        $this->templateExistingImage = null;
        $this->templatePrompt = '';
        $this->templateNegativePrompt = '';
        $this->templateAspectRatio = '1:1';
        $this->templateOutputQuality = 'high';
        $this->templateActive = true;
        $this->templateSortOrder = 0;
    }

    public function render()
    {
        return view('livewire.dashboard.templates.index');
    }
}
