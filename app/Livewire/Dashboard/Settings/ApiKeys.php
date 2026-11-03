<?php

namespace App\Livewire\Dashboard\Settings;

use App\Models\ApiKey;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class ApiKeys extends Component
{
    public string $keyName = '';
    public string $selectedStoreId = '';
    public string $expiresAt = '';
    public string $newlyCreatedSecret = '';
    public ?int $deleteKeyId = null;
    public string $deleteKeyName = '';

    public function getStoresProperty()
    {
        return Store::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getKeysProperty()
    {
        return ApiKey::query()
            ->where('user_id', Auth::id())
            ->with('store:id,name')
            ->latest()
            ->get();
    }

    public function createApiKey(): void
    {
        $this->validate([
            'keyName' => ['required', 'string', 'max:120'],
            'selectedStoreId' => ['nullable', 'integer'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ]);

        $storeId = null;
        if ($this->selectedStoreId !== '') {
            $store = Store::query()
                ->whereKey((int) $this->selectedStoreId)
                ->where('is_active', true)
                ->first();

            if (! $store) {
                $this->addError('selectedStoreId', 'Store tidak ditemukan atau sedang nonaktif.');
                return;
            }

            $storeId = (int) $store->id;
        }

        $secret = 'rms_live_' . Str::random(48);

        ApiKey::query()->create([
            'user_id' => Auth::id(),
            'store_id' => $storeId,
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

        $this->writeActivity('api_key_created', 'API key created', 'API key baru dibuat untuk akses Image Generation API.', 'success');
        $this->dispatch('toast', type: 'success', title: 'API key berhasil dibuat', message: 'Salin secret sekarang. Nilai lengkap tidak akan ditampilkan lagi.');
    }

    public function revokeApiKey(int $id): void
    {
        $key = ApiKey::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->firstOrFail();

        if ($key->revoked_at === null) {
            $key->update(['revoked_at' => now()]);
            try {
                $this->writeActivity('api_key_revoked', 'API key revoked', 'API key ' . $key->name . ' dinonaktifkan.', 'warning');
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->dispatch('api-key-revoked', keyId: $id);
        $this->dispatch('toast', type: 'success', title: 'API key dinonaktifkan', message: $key->name);
    }

    public function requestDeleteApiKey(int $id): void
    {
        $key = ApiKey::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->firstOrFail();

        $this->deleteKeyId = (int) $key->id;
        $this->deleteKeyName = $key->name;
    }

    public function cancelDeleteApiKey(): void
    {
        $this->deleteKeyId = null;
        $this->deleteKeyName = '';
    }

    public function confirmDeleteApiKey(): void
    {
        if (! $this->deleteKeyId) {
            return;
        }

        $key = ApiKey::query()
            ->where('user_id', Auth::id())
            ->whereKey($this->deleteKeyId)
            ->firstOrFail();

        $id = (int) $key->id;
        $name = $key->name;

        // Deleting the row immediately invalidates the credential, whether active or revoked.
        $key->delete();
        $this->cancelDeleteApiKey();

        // Audit failures must not undo or mask a completed deletion.
        try {
            $this->writeActivity('api_key_deleted', 'API key deleted', 'API key ' . $name . ' dihapus permanen.', 'warning');
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->dispatch('api-key-deleted', keyId: $id);
        $this->dispatch('toast', type: 'success', title: 'API key dihapus permanen', message: $name);
    }

    public function dismissSecret(): void
    {
        $this->newlyCreatedSecret = '';
    }

    private function writeActivity(string $action, string $title, string $description, string $status): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        \App\Models\ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'category' => 'api',
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'metadata' => ['source' => 'settings_api_keys'],
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.settings.api-keys');
    }
}
