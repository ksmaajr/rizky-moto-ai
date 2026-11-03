<?php

use App\Models\ApiKey;
use App\Models\Store;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('API Keys')] class extends Component
{
    public string $keyName = '';
    public string $selectedStoreId = '';
    public string $expiresAt = '';
    public string $newlyCreatedSecret = '';

    public function getStoresProperty()
    {
        return Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function getKeysProperty()
    {
        return ApiKey::query()->where('user_id', auth()->id())->with('store:id,name')->latest()->get();
    }

    public function createApiKey(): void
    {
        $this->validate([
            'keyName' => ['required', 'string', 'max:120'],
            'selectedStoreId' => ['nullable', 'integer', 'exists:stores,id'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ]);

        $secret = 'rms_live_' . Str::random(48);
        ApiKey::query()->create([
            'user_id' => auth()->id(),
            'store_id' => $this->selectedStoreId !== '' ? (int) $this->selectedStoreId : null,
            'name' => trim($this->keyName),
            'prefix' => substr($secret, 0, 16),
            'key_hash' => hash('sha256', $secret),
            'scopes' => ['images:generate', 'images:read', 'images:cancel'],
            'rate_limit' => 60,
            'expires_at' => $this->expiresAt !== '' ? $this->expiresAt : null,
        ]);

        $this->newlyCreatedSecret = $secret;
        $this->keyName = '';
        $this->selectedStoreId = '';
        $this->expiresAt = '';
        $this->resetValidation();
        $this->dispatch('toast', type: 'success', title: 'API key berhasil dibuat', message: 'Salin secret sekarang. Nilai lengkap tidak akan ditampilkan lagi.');
    }

    public function revokeApiKey(int $id): void
    {
        $key = ApiKey::query()->where('user_id', auth()->id())->whereKey($id)->firstOrFail();
        if ($key->revoked_at === null) $key->update(['revoked_at' => now()]);
        $this->dispatch('toast', type: 'success', title: 'API key dinonaktifkan', message: $key->name);
    }

    public function dismissSecret(): void
    {
        $this->newlyCreatedSecret = '';
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')
    <x-pages::settings.layout :heading="__('API Keys')" :subheading="__('Kelola akses aplikasi eksternal ke Image Generation API.')">
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Create API key</h3>
                <p class="mt-1 mb-4 text-sm text-zinc-500">Secret hanya ditampilkan satu kali. Server menyimpan hash SHA-256, bukan secret aslinya.</p>
                <form wire:submit="createApiKey" class="space-y-4">
                    <flux:input wire:model="keyName" label="Key name" placeholder="Codex local client" required />
                    <flux:select wire:model="selectedStoreId" label="Store access">
                        <option value="">All active Stores</option>
                        @foreach ($this->stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="expiresAt" type="date" label="Expiry date (optional)" />
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-zinc-500">Default limit: 60 requests/minute per key.</p>
                        <flux:button type="submit" variant="primary" icon="key">Create key</flux:button>
                    </div>
                </form>
            </div>

            @if ($newlyCreatedSecret !== '')
                <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
                    <div class="flex items-center justify-between gap-3">
                        <div><h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Copy your new API key</h3><p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">This is the only time the complete secret is shown.</p></div>
                        <flux:button size="sm" variant="ghost" wire:click="dismissSecret">Dismiss</flux:button>
                    </div>
                    <code class="mt-4 block break-all rounded-lg border border-emerald-200 bg-white p-3 text-xs text-zinc-800 dark:border-emerald-900 dark:bg-zinc-950 dark:text-zinc-100">{{ $newlyCreatedSecret }}</code>
                    <flux:button class="mt-3" size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText(@js($newlyCreatedSecret))">Copy API key</flux:button>
                </div>
            @endif

            <div>
                <div class="mb-3 flex items-center justify-between"><h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Your API keys</h3><span class="text-xs text-zinc-500">{{ $this->keys->count() }} total</span></div>
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800"><tr><th class="px-4 py-3">Key</th><th class="px-4 py-3">Store</th><th class="px-4 py-3">Usage</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr></thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($this->keys as $key)
                                <tr>
                                    <td class="px-4 py-3"><div class="font-medium text-zinc-900 dark:text-white">{{ $key->name }}</div><code class="text-xs text-zinc-500">{{ $key->prefix }}••••••••</code><div class="mt-1 text-[11px] text-zinc-400">Created {{ $key->created_at?->diffForHumans() }} · {{ $key->expires_at ? 'Expires '.$key->expires_at->toDateString() : 'No expiry' }}</div></td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $key->store?->name ?? 'All Stores' }}</td>
                                    <td class="px-4 py-3 tabular-nums text-zinc-600 dark:text-zinc-300">{{ number_format($key->usage_count) }}</td>
                                    <td class="px-4 py-3">
                                        @if ($key->revoked_at)<span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Revoked</span>
                                        @elseif ($key->expires_at && $key->expires_at->isPast())<span class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-800">Expired</span>
                                        @else<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-800">Active</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right">@if (!$key->revoked_at)<flux:button size="xs" variant="ghost" color="danger" wire:click="revokeApiKey({{ $key->id }})" wire:confirm="Revoke this API key?">Revoke</flux:button>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">No API keys created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl bg-zinc-50 p-4 text-xs leading-5 text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                <strong>API endpoint</strong><br><code>POST /api/v1/images/generations</code><br>
                Use <code>Authorization: Bearer YOUR_API_KEY</code> and multipart fields <code>store_id</code>, <code>template_id</code>, <code>product_image</code>, plus optional <code>installed_image</code>, <code>model</code>, <code>custom_title</code>, and <code>image_count</code>.
            </div>
        </div>
    </x-pages::settings.layout>
</section>
