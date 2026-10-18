<?php

namespace App\Livewire\Concerns;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Auth;

trait ManagesAiProviderSettings
{
    public string $activeProvider = 'vercel';
    public bool $nvidiaApiKeyConfigured = false;
    public string $nvidiaApiKey = '';
    public bool $showNvidiaApiKey = false;
    public array $nvidiaApiKeyStatus = [];
    public string $fallbackProvider = '';
    public bool $allowProviderFallback = false;
    public bool $emergencyFallback = false;
    public int $generationProviderVersion = 0;


    public function saveNvidiaApiKey(): void
    {
        $this->validate(['nvidiaApiKey' => ['required', 'string', 'min:10', 'max:2000']]);

        $setting = AiProviderSetting::firstOrNew(['user_id' => Auth::id()]);
        $setting->nvidia_api_key = trim($this->nvidiaApiKey);
        $setting->save();

        $this->nvidiaApiKeyConfigured = true;
        $this->nvidiaApiKey = '';
        $this->showNvidiaApiKey = false;

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'provider_credential_added',
            'category' => 'system',
            'status' => 'success',
            'title' => 'NVIDIA API key saved',
            'description' => 'NVIDIA NIM credential tersimpan terenkripsi.',
            'metadata' => ['provider' => 'nvidia', 'credential_type' => 'api_key'],
        ]);

        $this->dispatch('toast', type: 'success', title: 'NVIDIA API key tersimpan', message: 'API key berhasil disimpan secara terenkripsi.');
    }

    public function testNvidiaConnection(): void
    {
        $setting = AiProviderSetting::query()
            ->where(fn ($query) => $query->where('user_id', Auth::id())->orWhereNull('user_id'))
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [Auth::id()])
            ->first();

        $key = trim((string) ($setting?->nvidia_api_key ?? ''));
        if ($key === '') {
            $this->nvidiaApiKeyStatus = ['ok' => false, 'message' => 'Simpan NVIDIA API key terlebih dahulu.'];
            $this->dispatch('toast', type: 'error', title: 'API key belum tersedia', message: $this->nvidiaApiKeyStatus['message']);
            return;
        }

        $started = microtime(true);
        try {
            $response = Http::withToken($key)->acceptJson()->asJson()->connectTimeout(10)->timeout(120)
                ->post('https://ai.api.nvidia.com/v1/genai/black-forest-labs/flux.2-klein-4b', [
                    'mode' => 'Image Generation',
                    'prompt' => 'A simple studio product photograph of a motorcycle spark plug on a clean neutral background.',
                    'height' => 1024, 'width' => 1024, 'cfg_scale' => 0, 'samples' => 1, 'seed' => 0, 'steps' => 4,
                ]);

            $ok = $response->successful() && (
                is_string($response->json('artifacts.0.base64')) ||
                is_string($response->json('data.0.b64_json')) ||
                is_string($response->json('images.0.url'))
            );
            $detail = $ok ? 'NVIDIA image generation test berhasil.' : mb_substr((string) ($response->json('detail') ?? $response->json('error.message') ?? $response->body()), 0, 700);
            $this->nvidiaApiKeyStatus = ['ok' => $ok, 'http_status' => $response->status(), 'message' => $ok ? 'Test generate berhasil.' : 'Test generate gagal: ' . $detail];
            ActivityLog::create([
                'user_id' => Auth::id(), 'action' => 'provider_connection_test', 'category' => 'system',
                'status' => $ok ? 'success' : 'error',
                'title' => 'NVIDIA image generation test ' . ($ok ? 'passed' : 'failed'),
                'description' => $detail,
                'metadata' => ['provider' => 'nvidia', 'http_status' => $response->status(), 'duration_ms' => (int) round((microtime(true) - $started) * 1000)],
            ]);
            $this->dispatch('toast', type: $ok ? 'success' : 'error', title: $ok ? 'NVIDIA test berhasil' : 'NVIDIA test gagal', message: $this->nvidiaApiKeyStatus['message']);
        } catch (\\Throwable $exception) {
            $this->nvidiaApiKeyStatus = ['ok' => false, 'message' => 'NVIDIA test gagal: ' . mb_substr($exception->getMessage(), 0, 700)];
            $this->dispatch('toast', type: 'error', title: 'NVIDIA test gagal', message: $this->nvidiaApiKeyStatus['message']);
        }
    }

    public function loadAiProviderSettings(): void
    {
        $setting = AiProviderSetting::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [Auth::id()])
            ->first();

        $this->nvidiaApiKeyConfigured = filled($setting?->nvidia_api_key);

        if ($setting) {
            $this->activeProvider = $setting->active_provider ?: 'vercel';
            $this->fallbackProvider = (string) ($setting->fallback_provider ?? '');
            $this->allowProviderFallback = (bool) $setting->allow_provider_fallback;
            $this->emergencyFallback = (bool) $setting->emergency_fallback;
        }
    }

    public function saveAiProviderConfiguration(): void
    {
        $this->validate([
            'activeProvider' => ['required', 'in:vercel,agentkit,nvidia'],
            'fallbackProvider' => ['nullable', 'in:vercel,agentkit'],
        ]);

        if ($this->fallbackProvider === $this->activeProvider) {
            $this->fallbackProvider = '';
        }

        AiProviderSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'active_provider' => $this->activeProvider,
                'fallback_provider' => $this->fallbackProvider ?: null,
                'allow_provider_fallback' => $this->allowProviderFallback,
                'emergency_fallback' => $this->emergencyFallback,
            ]
        );

        if (method_exists($this, 'syncGenerationModelToProvider')) {
            $this->syncGenerationModelToProvider();
        }

        // Force provider-aware Generator children to remount after a saved
        // provider change, so their model catalog cannot remain stale.
        $this->generationProviderVersion++;

        $this->dispatch('ai-provider-configuration-updated');

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'AI provider updated',
            message: 'Provider aktif berhasil diperbarui.'
        );
    }
}
