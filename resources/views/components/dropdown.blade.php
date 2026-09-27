@props([
    'label' => null,
    'placeholder' => 'Pilih opsi...',
    'options' => [],
    'model' => null,
    'searchable' => false,
    'disabled' => false,
    'required' => false,
    'description' => null,
])

@php
    $normalizedOptions = collect($options)->map(function ($option) {
        if (is_string($option) || is_numeric($option)) {
            return [
                'value' => (string) $option,
                'label' => (string) $option,
                'description' => null,
                'icon' => null,
                'image' => null,
                'color' => 'red',
                'disabled' => false,
            ];
        }

        return [
            'value' => (string) ($option['value'] ?? ''),
            'label' => $option['label'] ?? $option['name'] ?? '',
            'description' => $option['description'] ?? null,
            'icon' => $option['icon'] ?? null,
            'image' => $option['image'] ?? null,
            'color' => $option['color'] ?? 'red',
            'disabled' => $option['disabled'] ?? false,
        ];
    })->values()->toArray();
@endphp


<div
    x-data="universalDropdown({
        options: @js($normalizedOptions),
        model: @js($model),
        searchable: @js($searchable),
        disabled: @js($disabled),
        initialValue: @js($attributes->get('value')),
    })"
    x-init="init()"
    @click.outside="close()"
    @keydown.escape.window="close()"
    @keydown.arrow-down.prevent="keyboardMove(1)"
    @keydown.arrow-up.prevent="keyboardMove(-1)"
    @keydown.enter.prevent="keyboardSelect()"
    class="relative w-full"
>

    {{-- LABEL --}}
    @if($label)
        <div class="mb-1.5 flex items-center justify-between gap-3">

            <label class="block text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-500">
                {{ $label }}

                @if($required)
                    <span class="ml-0.5 text-red-500">*</span>
                @endif
            </label>

            @if($description)
                <span class="text-[9px] text-zinc-400">
                    {{ $description }}
                </span>
            @endif

        </div>
    @endif


    {{-- HIDDEN LIVEWIRE VALUE --}}
    @if($model)
        <input
            type="hidden"
            x-ref="livewireInput"
            value=""
        >
    @endif


    {{-- TRIGGER --}}
    <button
        type="button"
        @click="toggle()"
        @keydown.arrow-down.prevent="openDropdown(); keyboardMove(1)"
        @keydown.arrow-up.prevent="openDropdown(); keyboardMove(-1)"
        :disabled="disabled"
        class="group flex h-11 w-full items-center gap-3 rounded-xl border bg-white px-3 text-left outline-none transition-all duration-200"
        :class="{
            'border-red-400 ring-4 ring-red-500/10': open,
            'border-zinc-200 hover:border-zinc-300': !open && !disabled,
            'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-60': disabled
        }"
    >

        {{-- SELECTED ICON / IMAGE --}}
        <template x-if="selected && selected.image">
            <img
                :src="selected.image"
                :alt="selected.label"
                class="h-7 w-7 shrink-0 rounded-lg object-cover"
            >
        </template>

        <template x-if="selected && !selected.image">
            <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition-all duration-200"
                :class="colorClass(selected?.color)"
            >
                <span
                    x-text="getInitial(selected?.label)"
                ></span>
            </span>
        </template>

        <template x-if="!selected">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <rect
                        x="4"
                        y="4"
                        width="16"
                        height="16"
                        rx="3"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />

                    <path
                        d="M8 15l2.5-2.5L13 15l2.5-3L18 15"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </span>
        </template>


        {{-- TEXT --}}
        <span class="min-w-0 flex-1">

            <span
                x-show="selected"
                x-cloak
                class="block truncate text-xs font-semibold text-zinc-700"
                x-text="selected?.label"
            ></span>

            <span
                x-show="!selected"
                class="block truncate text-xs font-medium text-zinc-400"
            >
                {{ $placeholder }}
            </span>

            <span
                x-show="selected?.description"
                x-cloak
                class="mt-0.5 block truncate text-[9px] text-zinc-400"
                x-text="selected?.description"
            ></span>

        </span>


        {{-- CHEVRON --}}
        <span
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-all duration-300"
            :class="open ? 'rotate-180 bg-red-50 text-red-500' : 'group-hover:bg-zinc-100'"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
            >
                <path
                    d="m6 9 6 6 6-6"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>

    </button>


    {{-- DROPDOWN --}}
    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-[.97]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-[.98]"
        class="absolute left-0 right-0 top-full z-[100] mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white p-1.5 shadow-[0_20px_50px_rgba(0,0,0,.12)]"
    >

        {{-- SEARCH --}}
        @if($searchable)

            <div class="mb-1.5 p-1">

                <div class="relative">

                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="6.5"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />

                        <path
                            d="m16 16 4 4"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>

                    <input
                        x-ref="search"
                        x-model="search"
                        @keydown.arrow-down.prevent="keyboardMove(1)"
                        @keydown.arrow-up.prevent="keyboardMove(-1)"
                        type="text"
                        placeholder="Cari..."
                        class="h-9 w-full rounded-lg border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-700 outline-none transition focus:border-red-300 focus:bg-white focus:ring-4 focus:ring-red-500/5"
                    >

                </div>

            </div>

        @endif


        {{-- OPTIONS --}}
        <div class="max-h-64 overflow-y-auto">

            <template x-if="filteredOptions.length === 0">

                <div class="px-3 py-8 text-center">

                    <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400">

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                cx="11"
                                cy="11"
                                r="6.5"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />

                            <path
                                d="m16 16 4 4"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>

                    </div>

                    <p class="text-xs font-semibold text-zinc-500">
                        Tidak ditemukan
                    </p>

                    <p class="mt-0.5 text-[9px] text-zinc-400">
                        Coba kata kunci lain.
                    </p>

                </div>

            </template>


            <template
                x-for="(option, index) in filteredOptions"
                :key="option.value"
            >

                <button
                    type="button"
                    @click="select(option)"
                    :disabled="option.disabled"
                    class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2.5 text-left transition-all duration-150"
                    :class="{
                        'bg-red-50': isSelected(option),
                        'bg-zinc-50': keyboardIndex === index && !isSelected(option) && !option.disabled,
                        'opacity-40 cursor-not-allowed': option.disabled,
                        'hover:bg-zinc-50': !option.disabled && !isSelected(option)
                    }"
                >

                    {{-- OPTION IMAGE --}}
                    <template x-if="option.image">

                        <img
                            :src="option.image"
                            :alt="option.label"
                            class="h-9 w-9 shrink-0 rounded-lg object-cover"
                        >

                    </template>


                    {{-- OPTION ICON / INITIAL --}}
                    <template x-if="!option.image">

                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold transition-transform duration-200 group-hover:scale-105"
                            :class="colorClass(option.color)"
                            x-text="getInitial(option.label)"
                        ></span>

                    </template>


                    {{-- OPTION INFO --}}
                    <span class="min-w-0 flex-1">

                        <span
                            class="block truncate text-xs font-semibold"
                            :class="
                                isSelected(option)
                                    ? 'text-red-600'
                                    : 'text-zinc-700'
                            "
                            x-text="option.label"
                        ></span>

                        <span
                            x-show="option.description"
                            class="mt-0.5 block truncate text-[9px] text-zinc-400"
                            x-text="option.description"
                        ></span>

                    </span>


                    {{-- SELECTED CHECK --}}
                    <span
                        x-show="isSelected(option)"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-50"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-white"
                    >

                        <svg
                            class="h-3 w-3"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="m5 12 4 4L19 6"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                    </span>

                </button>

            </template>

        </div>

    </div>

</div>


<script>
    function universalDropdown(config) {
        return {
            open: false,

            options: config.options ?? [],

            model: config.model ?? null,

            searchable: config.searchable ?? false,

            disabled: config.disabled ?? false,

            value: config.initialValue ?? null,

            search: '',

            keyboardIndex: -1,

            init() {
                /*
                 * Jika komponen dipakai di dalam Livewire,
                 * ambil nilai awal dari property Livewire.
                 */
                if (this.model && typeof this.$wire !== 'undefined') {
                    const livewireValue = this.$wire.get(this.model);

                    if (
                        livewireValue !== undefined &&
                        livewireValue !== null &&
                        livewireValue !== ''
                    ) {
                        this.value = String(livewireValue);
                    }
                }

                /*
                 * Sinkronisasi perubahan Livewire -> dropdown.
                 */
                if (this.model && typeof this.$wire !== 'undefined') {

                    this.$watch(
                        'value',
                        value => {
                            if (this.model) {
                                this.$wire.set(this.model, value);
                            }
                        }
                    );
                }
            },


            get selected() {
                return this.options.find(
                    option => String(option.value) === String(this.value)
                ) ?? null;
            },


            get filteredOptions() {
                if (!this.search.trim()) {
                    return this.options;
                }

                const keyword = this.search.toLowerCase();

                return this.options.filter(option => {
                    return (
                        String(option.label)
                            .toLowerCase()
                            .includes(keyword)
                        ||
                        String(option.description ?? '')
                            .toLowerCase()
                            .includes(keyword)
                    );
                });
            },


            toggle() {
                if (this.disabled) {
                    return;
                }

                this.open
                    ? this.close()
                    : this.openDropdown();
            },


            openDropdown() {
                if (this.disabled) {
                    return;
                }

                this.open = true;

                this.keyboardIndex = -1;

                this.$nextTick(() => {
                    if (this.searchable && this.$refs.search) {
                        this.$refs.search.focus();
                    }
                });
            },


            close() {
                this.open = false;

                this.search = '';

                this.keyboardIndex = -1;
            },


            select(option) {
                if (option.disabled) {
                    return;
                }

                this.value = option.value;

                this.close();
            },


            isSelected(option) {
                return String(option.value) === String(this.value);
            },


            keyboardMove(direction) {
                if (!this.open) {
                    this.openDropdown();
                    return;
                }

                const available = this.filteredOptions.filter(
                    option => !option.disabled
                );

                if (!available.length) {
                    return;
                }

                this.keyboardIndex += direction;

                if (this.keyboardIndex < 0) {
                    this.keyboardIndex = available.length - 1;
                }

                if (this.keyboardIndex >= available.length) {
                    this.keyboardIndex = 0;
                }
            },


            keyboardSelect() {
                if (!this.open) {
                    this.openDropdown();
                    return;
                }

                const available = this.filteredOptions.filter(
                    option => !option.disabled
                );

                const option = available[this.keyboardIndex];

                if (option) {
                    this.select(option);
                }
            },


            getInitial(label) {
                if (!label) {
                    return '?';
                }

                return String(label)
                    .trim()
                    .charAt(0)
                    .toUpperCase();
            },


            colorClass(color) {

                const classes = {
                    red: 'bg-red-500 text-white',
                    dark: 'bg-zinc-900 text-white',
                    amber: 'bg-amber-100 text-amber-700',
                    green: 'bg-emerald-100 text-emerald-700',
                    blue: 'bg-blue-100 text-blue-700',
                    purple: 'bg-violet-100 text-violet-700',
                    gray: 'bg-zinc-100 text-zinc-600'
                };

                return classes[color] ?? classes.gray;
            }
        }
    }
</script>