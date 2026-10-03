<?php

use App\Models\Store;
use App\Models\Template;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

new
#[Layout('layouts::app')]
class extends Component
{
    use WithFileUploads;

    public Store $store;

    public string $search = '';

    public string $filterStatus = 'all';

    public bool $showModal = false;

    public bool $editing = false;

    public ?int $editingId = null;

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public string $templateName = '';

    public string $templateDescription = '';

    public $exampleImage = null;

    public ?string $existingExampleImage = null;

    public string $prompt = '';

    public string $negativePrompt = '';

    public string $aspectRatio = '1:1';

    public string $outputQuality = 'high';

    public bool $templateActive = true;

    public int $templateSortOrder = 0;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(Store $store): void
    {
        abort_unless($store->is_active || auth()->check(), 404);

        $this->store = $store;
    }

    /*
    |--------------------------------------------------------------------------
    | Computed
    |--------------------------------------------------------------------------
    */

    public function getTemplatesProperty()
    {
        return Template::query()
            ->where('store_id', $this->store->id)
            ->when(
                $this->search !== '',
                function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                }
            )
            ->when(
                $this->filterStatus === 'active',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $this->filterStatus === 'inactive',
                fn ($query) => $query->where('is_active', false)
            )
            ->withCount('generations')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function getActiveTemplatesProperty(): int
    {
        return Template::where('store_id', $this->store->id)
            ->where('is_active', true)
            ->count();
    }

    public function getTotalGenerationsProperty(): int
    {
        return Template::where('store_id', $this->store->id)
            ->withCount('generations')
            ->get()
            ->sum('generations_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */

    public function openCreateModal(): void
    {
        $this->resetForm();

        $this->editing = false;
        $this->editingId = null;

        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $template = Template::where('store_id', $this->store->id)
            ->findOrFail($id);

        $this->editing = true;
        $this->editingId = $template->id;

        $this->templateName = $template->name;
        $this->templateDescription = $template->description ?? '';
        $this->existingExampleImage = $template->example_image_path;
        $this->exampleImage = null;
        $this->prompt = $template->prompt;
        $this->negativePrompt = $template->negative_prompt ?? '';
        $this->aspectRatio = $template->aspect_ratio ?? '1:1';
        $this->outputQuality = $template->output_quality ?? 'high';
        $this->templateActive = $template->is_active;
        $this->templateSortOrder = $template->sort_order ?? 0;

        $this->resetValidation();

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->resetForm();
        $this->resetValidation();
    }

    protected function resetForm(): void
    {
        $this->templateName = '';
        $this->templateDescription = '';
        $this->exampleImage = null;
        $this->existingExampleImage = null;
        $this->prompt = '';
        $this->negativePrompt = '';
        $this->aspectRatio = '1:1';
        $this->outputQuality = 'high';
        $this->templateActive = true;
        $this->templateSortOrder = 0;
        $this->editingId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    public function saveTemplate(): void
    {
        $this->validate([
            'templateName' => [
                'required',
                'string',
                'max:150',
            ],

            'templateDescription' => [
                'nullable',
                'string',
                'max:500',
            ],

            'exampleImage' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],

            'prompt' => [
                'required',
                'string',
            ],

            'negativePrompt' => [
                'nullable',
                'string',
            ],

            'aspectRatio' => [
                'required',
                'in:1:1,4:5,3:4,4:3,16:9,9:16',
            ],

            'outputQuality' => [
                'required',
                'in:standard,high',
            ],

            'templateSortOrder' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ], [
            'templateName.required' => 'Nama template wajib diisi.',
            'exampleImage.image' => 'Example image harus berupa gambar.',
            'exampleImage.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'exampleImage.max' => 'Ukuran example image maksimal 10 MB.',
            'prompt.required' => 'Prompt wajib diisi.',
        ]);

        $template = $this->editingId
            ? Template::where('store_id', $this->store->id)
                ->findOrFail($this->editingId)
            : new Template();

        if (! $this->editingId) {
            $template->store_id = $this->store->id;
        }

        $template->name = $this->templateName;

        $template->slug = Str::slug($this->templateName);

        $template->description = $this->templateDescription ?: null;

        $template->prompt = $this->prompt;

        $template->negative_prompt = $this->negativePrompt ?: null;

        $template->aspect_ratio = $this->aspectRatio;

        $template->output_quality = $this->outputQuality;

        $template->is_active = $this->templateActive;

        $template->sort_order = $this->templateSortOrder;

        if ($this->exampleImage) {

            if (
                $template->example_image_path &&
                \Storage::disk('public')->exists($template->example_image_path)
            ) {
                \Storage::disk('public')->delete(
                    $template->example_image_path
                );
            }

            $template->example_image_path = $this->exampleImage
                ->store('templates/examples', 'public');
        }

        $template->save();

        $this->dispatch(
            'toast',
            type: 'success',
            title: $this->editing
                ? 'Template diperbarui'
                : 'Template berhasil dibuat',
            message: $this->editing
                ? 'Konfigurasi template berhasil diperbarui.'
                : 'Template baru sudah tersedia untuk generator.'
        );

        $this->closeModal();
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(int $id): void
    {
        $template = Template::where('store_id', $this->store->id)
            ->findOrFail($id);

        $template->update([
            'is_active' => ! $template->is_active,
        ]);

        $this->dispatch(
            'toast',
            type: 'success',
            title: $template->is_active
                ? 'Template diaktifkan'
                : 'Template dinonaktifkan',
            message: $template->name
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate
    |--------------------------------------------------------------------------
    */

    public function duplicateTemplate(int $id): void
    {
        $template = Template::where('store_id', $this->store->id)
            ->findOrFail($id);

        $copy = $template->replicate();

        $copy->name = $template->name . ' Copy';

        $copy->slug = Str::slug(
            $template->name . '-' . uniqid()
        );

        $copy->sort_order = $template->sort_order + 1;

        $copy->is_active = false;

        $copy->save();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Template diduplikasi',
            message: 'Template copy berhasil dibuat.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function deleteTemplate(int $id): void
    {
        $template = Template::where('store_id', $this->store->id)
            ->findOrFail($id);

        if ($template->generations()->exists()) {

            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Template tidak dapat dihapus',
                message: 'Template sudah digunakan oleh generation.'
            );

            return;
        }

        if (
            $template->example_image_path &&
            \Storage::disk('public')->exists($template->example_image_path)
        ) {
            \Storage::disk('public')->delete(
                $template->example_image_path
            );
        }

        $template->delete();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Template dihapus',
            message: 'Template berhasil dihapus.'
        );
    }
};

?>

<div
    x-data="{
        showModal: @entangle('showModal'),
        menu: null
    }"
    class="min-h-screen bg-[#f5f5f5] text-zinc-950"
>

    {{-- TOP BAR --}}
    <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/90 backdrop-blur-xl">

        <div class="mx-auto flex h-[74px] max-w-[1500px] items-center justify-between px-5 sm:px-7">

            <div class="flex items-center gap-4">

                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </a>

                <div>
                    <div class="flex items-center gap-2">

                        <h1 class="text-[17px] font-black tracking-tight">
                            Template Management
                        </h1>

                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-red-600">
                            {{ $store->marketplace }}
                        </span>

                    </div>

                    <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                        <span>{{ $store->name }}</span>

                        <span class="text-zinc-300">•</span>

                        <span>
                            {{ $this->templates->count() }}
                            template
                        </span>
                    </div>
                </div>

            </div>

            <button
                type="button"
                wire:click="openCreateModal"
                class="group inline-flex items-center gap-2 rounded-xl bg-zinc-950 px-4 py-2.5 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-lg hover:shadow-red-500/20"
            >
                <svg class="h-4 w-4 transition group-hover:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>

                Tambah Template
            </button>

        </div>

    </header>


    {{-- CONTENT --}}
    <main class="mx-auto max-w-[1500px] px-5 py-7 sm:px-7">

        {{-- STORE INFO --}}
        <section class="mb-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">

            <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">

                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-950">

                        @if($store->logo_path)

                            <img
                                src="{{ Storage::disk('public')->url($store->logo_path) }}"
                                alt="{{ $store->name }}"
                                class="h-full w-full object-contain"
                            >

                        @else

                            <span class="text-lg font-black text-white">
                                {{ strtoupper(substr($store->name, 0, 1)) }}
                            </span>

                        @endif

                    </div>

                    <div>

                        <div class="flex items-center gap-2">

                            <h2 class="text-lg font-black tracking-tight">
                                {{ $store->name }}
                            </h2>

                            @if($store->is_active)

                                <span class="flex items-center gap-1.5 text-[11px] font-bold text-emerald-600">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>

                            @else

                                <span class="flex items-center gap-1.5 text-[11px] font-bold text-zinc-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-300"></span>
                                    Inactive
                                </span>

                            @endif

                        </div>

                        <p class="mt-1 text-xs text-zinc-500">
                            {{ $store->brand_name ?: $store->name }}
                        </p>

                    </div>

                </div>


                <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 sm:min-w-[310px]">

                    <div class="px-5 py-3">

                        <div class="text-[9px] font-black uppercase tracking-widest text-zinc-400">
                            Active Templates
                        </div>

                        <div class="mt-1 text-xl font-black">
                            {{ $this->activeTemplates }}
                        </div>

                    </div>

                    <div class="border-l border-zinc-200 px-5 py-3">

                        <div class="text-[9px] font-black uppercase tracking-widest text-zinc-400">
                            Generations
                        </div>

                        <div class="mt-1 text-xl font-black">
                            {{ $this->totalGenerations }}
                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- TOOLBAR --}}
        <section class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-sm font-black">
                    Templates
                </h2>

                <p class="mt-1 text-xs text-zinc-500">
                    Template khusus untuk menghasilkan visual produk {{ $store->name }}.
                </p>

            </div>


            <div class="flex flex-col gap-2 sm:flex-row">

                {{-- SEARCH --}}
                <div class="relative">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M20 20l-4-4"/>
                    </svg>

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari template..."
                        class="h-10 w-full rounded-xl border border-zinc-200 bg-white pl-9 pr-4 text-xs font-medium outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-4 focus:ring-red-500/10 sm:w-[230px]"
                    >

                </div>


                {{-- FILTER --}}
                <select
                    wire:model.live="filterStatus"
                    class="h-10 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-bold outline-none transition focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                >
                    <option value="all">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

            </div>

        </section>


        {{-- TEMPLATE GRID --}}
        @if($this->templates->count())

            <section class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">

                @foreach($this->templates as $template)

                    <article
                        wire:key="template-{{ $template->id }}"
                        class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-200/70"
                    >

                        {{-- IMAGE --}}
                        <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">

                            @if($template->example_image_path)

                                <img
                                    src="{{ Storage::disk('public')->url($template->example_image_path) }}"
                                    alt="{{ $template->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                >

                            @else

                                <div class="flex h-full items-center justify-center">

                                    <div class="text-center">

                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-zinc-400 shadow-sm">

                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <path d="M21 15l-5-5L5 21"/>
                                            </svg>

                                        </div>

                                        <div class="mt-2 text-[10px] font-bold text-zinc-400">
                                            No example image
                                        </div>

                                    </div>

                                </div>

                            @endif


                            {{-- STATUS --}}
                            <div class="absolute left-3 top-3">

                                @if($template->is_active)

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black text-emerald-600 shadow-sm backdrop-blur">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>

                                @else

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black text-zinc-500 shadow-sm backdrop-blur">
                                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-300"></span>
                                        Inactive
                                    </span>

                                @endif

                            </div>


                            {{-- ORDER --}}
                            <div class="absolute right-3 top-3 rounded-full bg-zinc-950/80 px-2.5 py-1 text-[9px] font-black text-white backdrop-blur">
                                #{{ str_pad($template->sort_order, 2, '0', STR_PAD_LEFT) }}
                            </div>

                        </div>


                        {{-- BODY --}}
                        <div class="p-5">

                            <div class="flex items-start justify-between gap-4">

                                <div class="min-w-0">

                                    <h3 class="truncate text-sm font-black">
                                        {{ $template->name }}
                                    </h3>

                                    @if($template->description)

                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-zinc-500">
                                            {{ $template->description }}
                                        </p>

                                    @else

                                        <p class="mt-1 text-xs text-zinc-400">
                                            Belum ada deskripsi.
                                        </p>

                                    @endif

                                </div>

                            </div>


                            {{-- CONFIG --}}
                            <div class="mt-4 flex flex-wrap gap-1.5">

                                <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-[9px] font-black text-zinc-600">
                                    {{ $template->aspect_ratio }}
                                </span>

                                <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-[9px] font-black uppercase text-zinc-600">
                                    {{ $template->output_quality }}
                                </span>

                                <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-[9px] font-black text-zinc-600">
                                    {{ $template->generations_count }} generations
                                </span>

                            </div>


                            {{-- ACTIONS --}}
                            <div class="mt-5 flex items-center gap-2 border-t border-zinc-100 pt-4">

                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $template->id }})"
                                    class="flex-1 rounded-xl border border-zinc-200 px-3 py-2 text-[10px] font-black text-zinc-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="duplicateTemplate({{ $template->id }})"
                                    class="rounded-xl border border-zinc-200 px-3 py-2 text-[10px] font-black text-zinc-600 transition hover:bg-zinc-100"
                                    title="Duplicate"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <rect x="8" y="8" width="11" height="11" rx="2"/>
                                        <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $template->id }})"
                                    class="rounded-xl border border-zinc-200 px-3 py-2 text-[10px] font-black text-zinc-600 transition hover:bg-zinc-100"
                                    title="Toggle status"
                                >
                                    @if($template->is_active)
                                        OFF
                                    @else
                                        ON
                                    @endif
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteTemplate({{ $template->id }})"
                                    wire:confirm="Hapus template {{ $template->name }}?"
                                    class="rounded-xl border border-red-100 px-3 py-2 text-[10px] font-black text-red-500 transition hover:bg-red-50"
                                    title="Delete"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path d="M4 7h16"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M6 7l1 13h10l1-13"/>
                                        <path d="M9 7V4h6v3"/>
                                    </svg>
                                </button>

                            </div>

                        </div>

                    </article>

                @endforeach

            </section>

        @else

            {{-- EMPTY --}}
            <section class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-20 text-center">

                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500">

                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <path d="M8 14l2.5-3 2 2 2.5-3 3 4"/>
                    </svg>

                </div>

                <h3 class="mt-5 text-base font-black">
                    Belum ada template
                </h3>

                <p class="mx-auto mt-2 max-w-md text-xs leading-5 text-zinc-500">
                    Buat template pertama untuk {{ $store->name }}.
                    Template akan menyimpan prompt dan konfigurasi khusus untuk generator.
                </p>

                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="mt-6 rounded-xl bg-zinc-950 px-5 py-3 text-xs font-black text-white transition hover:bg-red-600"
                >
                    + Tambah Template
                </button>

            </section>

        @endif

    </main>


    {{-- MODAL --}}
    <div
        x-show="showModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-zinc-950/50 p-4 backdrop-blur-sm"
    >

        <div
            x-show="showModal"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click.outside="$wire.closeModal()"
            class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl"
        >

            {{-- MODAL HEADER --}}
            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5">

                <div>

                    <div class="text-[10px] font-black uppercase tracking-[.18em] text-red-500">
                        {{ $editing ? 'Edit Template' : 'New Template' }}
                    </div>

                    <h2 class="mt-1 text-lg font-black tracking-tight">
                        {{ $editing ? 'Edit konfigurasi template' : 'Buat template baru' }}
                    </h2>

                </div>

                <button
                    type="button"
                    wire:click="closeModal"
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 transition hover:bg-zinc-200 hover:text-zinc-900"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>

            </div>


            {{-- MODAL BODY --}}
            <div class="max-h-[calc(92vh-145px)] overflow-y-auto px-6 py-6">

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[300px_1fr]">

                    {{-- IMAGE --}}
                    <div>

                        <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                            Example Image
                        </label>

                        <label
                            class="group mt-2 flex aspect-[4/3] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 transition hover:border-red-400 hover:bg-red-50/30"
                        >

                            @if($exampleImage)

                                <img
                                    src="{{ $exampleImage->temporaryUrl() }}"
                                    class="h-full w-full object-cover"
                                >

                            @elseif($existingExampleImage)

                                <img
                                    src="{{ Storage::disk('public')->url($existingExampleImage) }}"
                                    class="h-full w-full object-cover"
                                >

                            @else

                                <svg class="h-8 w-8 text-zinc-300 transition group-hover:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>

                                <span class="mt-3 text-xs font-black text-zinc-600">
                                    Upload Example Image
                                </span>

                                <span class="mt-1 text-[10px] text-zinc-400">
                                    JPG, PNG, WEBP • max 10 MB
                                </span>

                            @endif

                            <input
                                type="file"
                                wire:model="exampleImage"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                            >

                        </label>

                        @error('exampleImage')
                            <div class="mt-2 text-[10px] font-bold text-red-500">
                                {{ $message }}
                            </div>
                        @enderror

                        <div
                            wire:loading
                            wire:target="exampleImage"
                            class="mt-2 text-[10px] font-bold text-red-500"
                        >
                            Uploading image...
                        </div>

                    </div>


                    {{-- FORM --}}
                    <div class="space-y-5">

                        {{-- NAME --}}
                        <div>

                            <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                Template Name
                            </label>

                            <input
                                type="text"
                                wire:model="templateName"
                                placeholder="Contoh: Product Studio Premium"
                                class="mt-2 h-11 w-full rounded-xl border border-zinc-200 bg-white px-4 text-xs font-semibold outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                            >

                            @error('templateName')
                                <div class="mt-1.5 text-[10px] font-bold text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- DESCRIPTION --}}
                        <div>

                            <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                Description
                            </label>

                            <input
                                type="text"
                                wire:model="templateDescription"
                                placeholder="Deskripsi singkat template..."
                                class="mt-2 h-11 w-full rounded-xl border border-zinc-200 bg-white px-4 text-xs font-semibold outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                            >

                        </div>


                        {{-- PROMPT --}}
                        <div>

                            <div class="flex items-center justify-between">

                                <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                    Prompt
                                </label>

                                <span class="text-[9px] font-bold text-red-500">
                                    Required
                                </span>

                            </div>

                            <textarea
                                wire:model="prompt"
                                rows="7"
                                placeholder="Tulis instruksi visual untuk AI..."
                                class="mt-2 w-full resize-none rounded-xl border border-zinc-200 bg-white px-4 py-3 text-xs font-medium leading-5 outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                            ></textarea>

                            @error('prompt')
                                <div class="mt-1.5 text-[10px] font-bold text-red-500">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- NEGATIVE PROMPT --}}
                        <div>

                            <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                Negative Prompt
                            </label>

                            <textarea
                                wire:model="negativePrompt"
                                rows="4"
                                placeholder="Hal yang harus dihindari AI..."
                                class="mt-2 w-full resize-none rounded-xl border border-zinc-200 bg-white px-4 py-3 text-xs font-medium leading-5 outline-none transition placeholder:text-zinc-400 focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                            ></textarea>

                        </div>


                        {{-- CONFIG GRID --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                            <div>

                                <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                    Aspect Ratio
                                </label>

                                <select
                                    wire:model="aspectRatio"
                                    class="mt-2 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs font-bold outline-none focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                                >
                                    <option value="1:1">1:1 — Square</option>
                                    <option value="4:5">4:5 — Portrait</option>
                                    <option value="3:4">3:4 — Portrait</option>
                                    <option value="4:3">4:3 — Landscape</option>
                                    <option value="16:9">16:9 — Wide</option>
                                    <option value="9:16">9:16 — Vertical</option>
                                </select>

                            </div>


                            <div>

                                <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                    Quality
                                </label>

                                <select
                                    wire:model="outputQuality"
                                    class="mt-2 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs font-bold outline-none focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                                >
                                    <option value="standard">Standard</option>
                                    <option value="high">High</option>
                                </select>

                            </div>


                            <div>

                                <label class="text-[10px] font-black uppercase tracking-wider text-zinc-500">
                                    Sort Order
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    wire:model="templateSortOrder"
                                    class="mt-2 h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-xs font-bold outline-none focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                                >

                            </div>

                        </div>


                        {{-- STATUS --}}
                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 p-4">

                            <div>

                                <div class="text-xs font-black">
                                    Template Active
                                </div>

                                <div class="mt-1 text-[10px] text-zinc-500">
                                    Template dapat dipilih oleh Product Photo Generator.
                                </div>

                            </div>

                            <input
                                type="checkbox"
                                wire:model="templateActive"
                                class="h-5 w-5 rounded border-zinc-300 text-red-600 focus:ring-red-500"
                            >

                        </label>

                    </div>

                </div>

            </div>


            {{-- FOOTER --}}
            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 bg-zinc-50 px-6 py-4">

                <button
                    type="button"
                    wire:click="closeModal"
                    class="rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-xs font-black text-zinc-600 transition hover:bg-zinc-100"
                >
                    Batal
                </button>

                <button
                    type="button"
                    wire:click="saveTemplate"
                    wire:loading.attr="disabled"
                    wire:target="saveTemplate"
                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-xs font-black text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                >

                    <span
                        wire:loading
                        wire:target="saveTemplate"
                        class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"
                    ></span>

                    <span>
                        <span wire:loading.remove wire:target="saveTemplate">
                            {{ $editing ? 'Simpan Perubahan' : 'Buat Template' }}
                        </span>

                        <span wire:loading wire:target="saveTemplate">
                            Menyimpan...
                        </span>
                    </span>

                </button>

            </div>

        </div>

    </div>

</div>