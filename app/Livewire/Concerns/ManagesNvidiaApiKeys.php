<?php

namespace App\Livewire\Concerns;

use App\Models\NvidiaApiKey;
use App\Services\ActivityLogService;
use App\Services\NvidiaApiKeyPool;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

trait ManagesNvidiaApiKeys
{
    public string $newNvidiaKeyName = '';
    public string $newNvidiaApiKey = '';
    public array $nvidiaApiKeyLogs = [];

    public function getNvidiaApiKeysProperty(): array
    {
        return NvidiaApiKey::query()
            ->where('user_id', auth()->id())
            ->orderBy('priority')->orderBy('id')->get()
            ->map(fn (NvidiaApiKey $key) => [
                'id' => $key->id,
                'name' => $key->name,
                'status' => $this->effectiveNvidiaKeyStatus($key),
                'masked_key' => $key->masked_key,
                'request_count' => $key->request_count,
                'success_count' => $key->success_count,
                'failure_count' => $key->failure_count,
                'last_error_type' => $key->last_error_type,
                'last_error' => $key->last_error,
                'last_http_status' => $key->last_http_status,
                'last_used_at' => $key->last_used_at?->diffForHumans(),
                'cooldown_until' => $key->cooldown_until?->diffForHumans(),
            ])->all();
    }

    private function legacyNvidiaKeyConfigured(): bool
    {
        $setting = \App\Models\AiProviderSetting::query()
            ->where(fn ($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [auth()->id()])
            ->first();

        return filled($setting?->nvidia_api_key);
    }

    private function effectiveNvidiaKeyStatus(NvidiaApiKey $key): string
    {
        if (!$key->is_active || $key->status === 'disabled') return 'disabled';
        if ($key->cooldown_until?->isFuture()) return 'rate_limited';
        if (in_array($key->status, ['cooldown', 'rate_limited'], true)) return 'active';
        return $key->status ?: 'active';
    }

    public function addNvidiaApiKey(): void
    {
        $this->validate([
            'newNvidiaKeyName' => ['required', 'string', 'max:120'],
            'newNvidiaApiKey' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'newNvidiaKeyName.required' => 'Nama NVIDIA API key wajib diisi.',
            'newNvidiaApiKey.required' => 'NVIDIA API key wajib diisi.',
        ]);

        $name = trim($this->newNvidiaKeyName);
        $apiKey = trim($this->newNvidiaApiKey);
        if ($apiKey === '') {
            throw ValidationException::withMessages(['newNvidiaApiKey' => 'NVIDIA API key wajib diisi.']);
        }

        $existing = NvidiaApiKey::query()
            ->where('user_id', auth()->id())
            ->get();
        if ($existing->contains(fn (NvidiaApiKey $key) => hash_equals((string) $key->api_key, $apiKey))) {
            throw ValidationException::withMessages(['newNvidiaApiKey' => 'API key ini sudah ada di NVIDIA pool.']);
        }

        $key = NvidiaApiKey::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'api_key' => $apiKey,
            'status' => 'active',
            'is_active' => true,
            'priority' => ((int) ($existing->max('priority') ?? 0)) + 1,
        ]);

        app(ActivityLogService::class)->success(
            action: 'nvidia_api_key_added',
            category: 'api',
            title: 'NVIDIA NIM API key ditambahkan.',
            description: "{$name} berhasil disimpan terenkripsi ke NVIDIA key pool.",
            metadata: ['provider' => 'nvidia', 'nvidia_key_id' => $key->id, 'nvidia_key_name' => $name, 'user_id' => auth()->id()],
        );

        $this->refreshNvidiaPoolReadiness();
        $this->newNvidiaKeyName = '';
        $this->newNvidiaApiKey = '';
        $this->resetValidation(['newNvidiaKeyName', 'newNvidiaApiKey']);
        $this->dispatch('nvidia-key-added', id: $key->id);
        $this->dispatch('toast', type: 'success', title: 'NVIDIA API key ditambahkan', message: "{$name} sudah tersedia untuk worker.");
    }

    public function removeNvidiaApiKey(int $id): void
    {
        $key = $this->ownedNvidiaApiKey($id);
        if (!$key) return;
        $name = $key->name;
        $key->delete();
        $this->refreshNvidiaPoolReadiness();

        app(ActivityLogService::class)->success(
            action: 'nvidia_api_key_removed',
            category: 'api',
            title: 'NVIDIA NIM API key dihapus.',
            description: "{$name} dihapus dari NVIDIA key pool.",
            metadata: ['provider' => 'nvidia', 'nvidia_key_id' => $id, 'user_id' => auth()->id()],
        );
        $this->dispatch('toast', type: 'success', title: 'API key dihapus', message: "{$name} sudah dihapus dari pool.");
    }

    public function toggleNvidiaApiKey(int $id): void
    {
        $key = $this->ownedNvidiaApiKey($id);
        if (!$key) return;
        $key->is_active = !$key->is_active;
        $key->status = $key->is_active ? 'active' : 'disabled';
        $key->cooldown_until = null;
        $key->save();
        $this->refreshNvidiaPoolReadiness();
        $this->dispatch('toast', type: 'success', title: $key->is_active ? 'API key diaktifkan' : 'API key dinonaktifkan', message: $key->name . ($key->is_active ? ' tersedia untuk worker.' : ' tidak akan digunakan worker.'));
    }

    public function resetNvidiaApiKey(int $id): void
    {
        $key = $this->ownedNvidiaApiKey($id);
        if (!$key) return;
        $key->update([
            'status' => $key->is_active ? 'active' : 'disabled',
            'cooldown_until' => null,
            'last_error_type' => null,
            'last_error' => null,
            'last_http_status' => null,
        ]);
        $this->refreshNvidiaPoolReadiness();
        $this->dispatch('toast', type: 'success', title: 'Status di-reset', message: $key->name . ' siap dicoba kembali.');
    }

    public function testNvidiaApiKey(int $id): void
    {
        $key = $this->ownedNvidiaApiKey($id);
        if (!$key) return;

        $started = microtime(true);
        $key->increment('request_count');
        $key->update(['last_used_at' => now()]);
        try {
            // NVIDIA does not expose an auth-only endpoint for every hosted NIM;
            // a minimal live image call verifies the credential and model access.
            $response = Http::withToken($key->api_key)->acceptJson()->asJson()
                ->connectTimeout(10)->timeout(120)
                ->post('https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.2-klein-4b', [
                    'mode' => 'Image Generation',
                    'prompt' => 'A single red geometric cube on a plain white studio background.',
                    'height' => 512, 'width' => 512, 'cfg_scale' => 0, 'samples' => 1, 'seed' => 0, 'steps' => 4,
                ]);

            $duration = (int) round((microtime(true) - $started) * 1000);
            $json = $response->json();
            $hasImage = is_string(data_get($json, 'artifacts.0.base64'))
                || is_string(data_get($json, 'data.0.b64_json'))
                || is_string(data_get($json, 'images.0.url'));
            if ($response->successful() && $hasImage) {
                $key->update(['status' => 'active', 'success_count' => $key->success_count + 1, 'last_success_at' => now(), 'last_error_type' => null, 'last_error' => null, 'last_http_status' => $response->status(), 'cooldown_until' => null]);
                $status = 'success';
                $message = 'NVIDIA NIM live image test berhasil.';
                app(ActivityLogService::class)->success(action: 'nvidia_api_key_test', category: 'api', title: 'NVIDIA API key health check berhasil.', description: $key->name . ': ' . $message, metadata: ['provider' => 'nvidia', 'nvidia_key_id' => $key->id, 'http_status' => $response->status(), 'duration_ms' => $duration]);
            } else {
                $detail = mb_substr((string) (data_get($json, 'detail') ?? data_get($json, 'error.message') ?? $response->body()), 0, 1200);
                $classification = app(NvidiaApiKeyPool::class)->reportFailure($key->id, auth()->id(), $response, $detail);
                $status = 'error';
                $message = 'HTTP ' . $response->status() . ': ' . $detail;
                app(ActivityLogService::class)->error(action: 'nvidia_api_key_test', category: 'api', title: 'NVIDIA API key health check gagal.', description: $key->name . ': ' . $message, metadata: ['provider' => 'nvidia', 'nvidia_key_id' => $key->id, 'error_type' => $classification['reason'], 'duration_ms' => $duration], httpStatus: $response->status());
            }
            $this->refreshNvidiaPoolReadiness();
            $this->dispatch('toast', type: $status, title: $status === 'success' ? 'NVIDIA test berhasil' : 'NVIDIA test gagal', message: $message);
        } catch (\Throwable $e) {
            $this->refreshNvidiaPoolReadiness();
            $this->dispatch('toast', type: 'error', title: 'NVIDIA test gagal', message: mb_substr($e->getMessage(), 0, 500));
            app(ActivityLogService::class)->error(action: 'nvidia_api_key_test', category: 'api', title: 'NVIDIA API key health check exception.', description: $key->name . ': ' . mb_substr($e->getMessage(), 0, 1000), metadata: ['provider' => 'nvidia', 'nvidia_key_id' => $key->id, 'exception' => $e::class]);
        }
    }

    public function testAllNvidiaApiKeys(): void
    {
        $keys = NvidiaApiKey::query()->where('user_id', auth()->id())->where('is_active', true)->orderBy('priority')->get();
        if ($keys->isEmpty()) {
            $this->dispatch('toast', type: 'warning', title: 'Belum ada NVIDIA API key', message: 'Tambahkan minimal satu key terlebih dahulu.');
            return;
        }
        $success = 0;
        foreach ($keys as $key) {
            $before = (int) $key->success_count;
            $this->testNvidiaApiKey((int) $key->id);
            if ((int) $key->fresh()->success_count > $before) $success++;
        }
        $this->dispatch('toast', type: $success === $keys->count() ? 'success' : ($success > 0 ? 'warning' : 'error'), title: 'NVIDIA pool health check selesai', message: "{$success}/{$keys->count()} API key berhasil.");
    }

    private function refreshNvidiaPoolReadiness(): void
    {
        $pool = app(NvidiaApiKeyPool::class);
        $this->nvidiaApiKeyConfigured = $pool->totalCount(auth()->id()) > 0
            ? $pool->activeCount(auth()->id()) > 0
            : $this->legacyNvidiaKeyConfigured();
    }

    private function ownedNvidiaApiKey(int $id): ?NvidiaApiKey
    {
        return NvidiaApiKey::query()->whereKey($id)->where('user_id', auth()->id())->first();
    }
}
