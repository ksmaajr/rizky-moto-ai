@props([
    'options' => [],
    'label' => null,
    'placeholder' => 'Pilih opsi',
    'model' => null,
    'searchable' => true,
    'clearable' => false,
    'disabled' => false,
    'description' => null,
])

@php
    $normalizedOptions = collect($options)->map(function ($option, $key) {
        if (is_string($option) || is_numeric($option)) {
            return [
                'value' => (string) $option,
                'label' => (string) $option,
                'description' => null,
                'image' => null,
                'badge' => null,
                'color' => null,
                'disabled' => false,
            ];
        }

        $option = is_object($option) ? (array) $option : $option;

        return [
            'value' => (string) ($option['value'] ?? $option['id'] ?? $key),
            'label' => (string) ($option['label'] ?? $option['name'] ?? $option['title'] ?? $key),
            'description' => $option['description'] ?? null,
            'image' => $option['image'] ?? $option['logo'] ?? null,
            'badge' => $option['badge'] ?? null,
            'color' => $option['color'] ?? null,
            'disabled' => (bool) ($option['disabled'] ?? false),
        ];
    })->values()->all();

    $modelValue = $model ? data_get($this, $model) : null;
@endphp

<div
    x-data="universalDropdown({
        value: @js($modelValue),
        options: @js($normalizedOptions),
        searchable: @js($searchable),
        clearable: @js($clearable),
        disabled: @js($disabled),
        model: @js($model),
    })"
    x-on:keydown.escape.window="close()"
    x-on:click.outside="close()"
    class="relative w-full"
>
    @if($label)
        <div class="mb-2 flex items-end justify-between gap-3">
            <div>
                <label class="block text-xs font-bold text-zinc-800">{{ $label }}</label>
                @if($description)
                    <p class="mt-0.5 text-[10px] text-zinc-400">{{ $description }}</p>
                @endif
            </div>
        </div>
    @endif

    <button
        type="button"
        x-ref="trigger"
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="open(); focusNext()"
        x-on:keydown.arrow-up.prevent="open(); focusPrevious()"
        x-on:keydown.enter.prevent="open()"
        :disabled="disabled"
        class="group flex min-h-[54px] w-full items-center gap-3 rounded-2xl border bg-white px-3.5 text-left shadow-sm outline-none transition-all duration-200"
        :class="[
            openState
                ? 'border-rose-400 ring-4 ring-rose-500/10 shadow-lg shadow-rose-500/5'
                : 'border-zinc-200 hover:border-zinc-300 hover:shadow-md',
            disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
        ]"
    >
        <template x-if="selected && selected.image">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50">
                <img :src="selected.image" :alt="selected.label" class="h-full w-full object-contain p-1">
            </span>
        </template>

        <template x-if="selected && !selected.image">
            <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                :style="selected.color ? `background:${selected.color}18;color:${selected.color}` : ''"
                :class="selected.color ? '' : 'bg-zinc-100 text-zinc-600'"
            >
                <span x-text="selected.label?.slice(0,1)?.toUpperCase()"></span>
            </span>
        </template>

        <span class="min-w-0 flex-1">
            <span
                class="block truncate text-sm font-bold"
                :class="selected ? 'text-zinc-900' : 'text-zinc-400'"
                x-text="selected ? selected.label : placeholder"
            ></span>

            <template x-if="selected && selected.description">
                <span class="mt-0.5 block truncate text-[10px] text-zinc-400" x-text="selected.description"></span>
            </template>
        </span>

        <template x-if="selected && selected.badge">
            <span class="hidden rounded-full bg-zinc-100 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-zinc-500 sm:inline-flex" x-text="selected.badge"></span>
        </template>

        <template x-if="clearable && selected">
            <span
                x-on:click.stop="clear()"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </span>
        </template>

        <svg
            class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200"
            :class="openState ? 'rotate-180 text-rose-500' : ''"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div
        x-show="openState"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-[.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-[.98]"
        class="absolute left-0 right-0 z-[120] mt-2 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-2 shadow-2xl shadow-zinc-900/10"
        style="display:none"
    >
        <template x-if="searchable && options.length > 5">
            <div class="mb-2 relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-4-4"/>
                </svg>
                <input
                    x-ref="search"
                    x-model="search"
                    x-on:keydown.arrow-down.prevent="focusNext()"
                    x-on:keydown.arrow-up.prevent="focusPrevious()"
                    x-on:keydown.enter.prevent="selectFocused()"
                    type="text"
                    placeholder="Cari pilihan..."
                    class="h-10 w-full rounded-xl border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs font-medium text-zinc-800 outline-none transition focus:border-rose-300 focus:bg-white focus:ring-4 focus:ring-rose-500/5"
                >
            </div>
        </template>

        <div class="max-h-72 overflow-y-auto pr-0.5">
            <template x-for="(option, index) in filteredOptions" :key="option.value">
                <button
                    type="button"
                    :disabled="option.disabled"
                    x-on:click="select(option)"
                    x-on:mouseenter="focusedIndex = index"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition"
                    :class="[
                        option.disabled ? 'cursor-not-allowed opacity-40' : 'cursor-pointer',
                        focusedIndex === index ? 'bg-zinc-100' : 'hover:bg-zinc-50',
                        isSelected(option) ? 'bg-rose-50/80' : ''
                    ]"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-100 bg-zinc-50"
                        :style="option.color && !option.image ? `background:${option.color}18;color:${option.color}` : ''"
                    >
                        <template x-if="option.image">
                            <img :src="option.image" :alt="option.label" class="h-full w-full object-contain p-1">
                        </template>
                        <template x-if="!option.image">
                            <span class="text-xs font-black" x-text="option.label?.slice(0,1)?.toUpperCase()"></span>
                        </template>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-zinc-800" x-text="option.label"></span>
                        <template x-if="option.description">
                            <span class="mt-0.5 block truncate text-[10px] text-zinc-400" x-text="option.description"></span>
                        </template>
                    </span>

                    <template x-if="option.badge">
                        <span class="hidden rounded-full bg-zinc-100 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-zinc-500 sm:inline-flex" x-text="option.badge"></span>
                    </template>

                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg transition"
                        :class="isSelected(option) ? 'bg-rose-600 text-white scale-100' : 'scale-75 text-transparent'"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </span>
                </button>
            </template>

            <div x-show="filteredOptions.length === 0" class="px-4 py-8 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-4-4"/>
                    </svg>
                </div>
                <p class="mt-2 text-xs font-bold text-zinc-600">Tidak ditemukan</p>
                <p class="mt-1 text-[10px] text-zinc-400">Coba kata kunci lain.</p>
            </div>
        </div>
    </div>
</div>

@once
<script>
window.universalDropdown = window.universalDropdown || function(config) {
    return {
        value: config.value ?? null, options: Array.isArray(config.options) ? config.options : [],
        searchable: !!config.searchable, clearable: !!config.clearable, disabled: !!config.disabled, model: config.model || null,
        openState:false, search:'', focusedIndex:-1,
        get selected(){return this.options.find(o=>String(o.value)===String(this.value))||null},
        get filteredOptions(){const q=this.search.trim().toLowerCase();if(!q)return this.options;return this.options.filter(o=>String(o.label||'').toLowerCase().includes(q)||String(o.description||'').toLowerCase().includes(q)||String(o.badge||'').toLowerCase().includes(q))},
        toggle(){if(this.disabled)return;this.openState?this.close():this.open()},
        open(){this.openState=true;this.focusedIndex=Math.max(0,this.filteredOptions.findIndex(o=>String(o.value)===String(this.value)));this.$nextTick(()=>{if(this.searchable&&this.options.length>5&&this.$refs.search)this.$refs.search.focus()})},
        close(){this.openState=false;this.search=''},
        select(o){if(!o||o.disabled)return;this.value=o.value;this.close();if(this.model&&this.$wire)this.$wire.set(this.model,o.value)},
        clear(){this.value=null;this.close();if(this.model&&this.$wire)this.$wire.set(this.model,null)},
        isSelected(o){return this.selected&&String(this.selected.value)===String(o.value)},
        focusNext(){const l=this.filteredOptions;if(!l.length)return;this.openState=true;this.focusedIndex=Math.min(this.focusedIndex+1,l.length-1)},
        focusPrevious(){const l=this.filteredOptions;if(!l.length)return;this.openState=true;this.focusedIndex=Math.max(this.focusedIndex-1,0)},
        selectFocused(){const o=this.filteredOptions[this.focusedIndex];if(o)this.select(o)}
    };
};
</script>
@endonce
