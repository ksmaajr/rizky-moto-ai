<div class="space-y-6" wire:key="settings-api-keys-panel">
    <section class="rms-settings-panel-head">
        <div class="rms-settings-panel-brand">
            <span class="rms-settings-panel-mark rms-openai-mark">API</span>
            <div>
                <span>DEVELOPER ACCESS</span>
                <strong>API Key Management</strong>
                <small>Kelola akses aplikasi eksternal ke Image Generation API.</small>
            </div>
        </div>
        <div class="rms-api-connection-badge"><i></i> SECURE ACCESS</div>
    </section>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Create new API key</h3>
                <p class="mt-1 text-sm text-zinc-500">Buat credential untuk Codex atau aplikasi eksternal.</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">60 requests / minute</span>
        </div>

        <form wire:submit="createApiKey" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <flux:input wire:model="keyName" label="Key name" placeholder="Codex local client" required />
            </div>
            <flux:select wire:model="selectedStoreId" label="Store access">
                <option value="">All active Stores</option>
                @foreach ($this->stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="expiresAt" type="date" label="Expiry date (optional)" />
            <div class="flex items-center justify-end md:col-span-2">
                <flux:button type="submit" variant="primary" icon="key" wire:loading.attr="disabled" wire:target="createApiKey">Create API key</flux:button>
            </div>
        </form>
    </section>

    @if ($newlyCreatedSecret !== '')
        <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Your new API key is ready</h3>
                    <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">Secret lengkap hanya ditampilkan sekali. Simpan di password manager atau environment variable.</p>
                </div>
                <flux:button size="sm" variant="ghost" wire:click="dismissSecret">Dismiss</flux:button>
            </div>
            <code class="mt-4 block break-all rounded-xl border border-emerald-200 bg-white p-4 text-xs text-zinc-800 dark:border-emerald-900 dark:bg-zinc-950 dark:text-zinc-100">{{ $newlyCreatedSecret }}</code>
            <flux:button class="mt-3" size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText(@js($newlyCreatedSecret))">Copy API key</flux:button>
        </section>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Your API keys</h3>
                <p class="mt-1 text-sm text-zinc-500">Secret tidak disimpan dalam bentuk teks dan tidak dapat dilihat kembali.</p>
            </div>
            <span class="text-xs font-medium text-zinc-500">{{ $this->keys->count() }} keys</span>
        </div>

        <div class="space-y-3">
            @forelse ($this->keys as $key)
                @php
                    $expired = $key->expires_at && $key->expires_at->isPast();
                    $revoked = $key->revoked_at !== null;
                    $status = $revoked ? 'Revoked' : ($expired ? 'Expired' : 'Active');
                @endphp
                <article wire:key="api-key-{{ $key->id }}" class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong class="text-sm text-zinc-900 dark:text-white">{{ $key->name }}</strong>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $revoked || $expired ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">{{ strtoupper($status) }}</span>
                        </div>
                        <code class="mt-2 block text-xs text-zinc-500">{{ $key->prefix }}••••••••</code>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500">
                            <span>{{ $key->store?->name ?? 'All active Stores' }}</span>
                            <span>{{ number_format($key->usage_count) }} requests</span>
                            <span>{{ $key->last_used_at?->diffForHumans() ?? 'Never used' }}</span>
                            @if ($key->expires_at)<span>Expires {{ $key->expires_at->format('d M Y') }}</span>@endif
                        </div>
                    </div>
                    @if (! $revoked)
                        <flux:button size="sm" variant="danger" icon="ban" wire:click="revokeApiKey({{ $key->id }})" wire:confirm="Nonaktifkan API key ini? Aplikasi yang menggunakan key ini akan kehilangan akses.">Revoke</flux:button>
                    @else
                        <span class="text-xs text-zinc-400">Access disabled</span>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-zinc-700">
                    <div class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">No API keys yet</div>
                    <p class="mt-1 text-xs text-zinc-500">Buat API key pertama untuk menghubungkan aplikasi eksternal.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-xs leading-6 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
        <strong class="text-zinc-800 dark:text-zinc-100">Security notes</strong>
        <ul class="mt-1 list-disc space-y-1 pl-4">
            <li>API key hanya ditampilkan saat pertama kali dibuat.</li>
            <li>Server menyimpan SHA-256 hash, bukan secret aslinya.</li>
            <li>Setiap key memiliki scope generation, read, dan cancel dengan batas default 60 request per menit.</li>
            <li>Batasi akses ke Store tertentu jika key hanya digunakan untuk satu Store.</li>
        </ul>
    </div>
</div>
