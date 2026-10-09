<?php

namespace App\Livewire\Concerns;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Auth;

trait ManagesAiProviderSettings
{
    public string $activeProvider = 'vercel';
    public string $fallbackProvider = '';
    public bool $allowProviderFallback = false;
    public bool $emergencyFallback = false;

    public function loadAiProviderSettings(): void
    {
        $setting = AiProviderSetting::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [Auth::id()])
            ->first();

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
            'activeProvider' => ['required', 'in:vercel,agentkit'],
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

        $this->saved = true;
        $this->dispatch(
            'toast',
            type: 'success',
            title: 'AI provider updated',
            message: 'Provider aktif berhasil diperbarui.'
        );
    }
}
