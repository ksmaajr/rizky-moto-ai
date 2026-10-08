<?php

namespace App\Livewire\Dashboard\Settings;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'general';

    public string $activeProvider = 'vercel';
    public string $fallbackProvider = '';
    public bool $allowProviderFallback = false;
    public bool $emergencyFallback = false;

    public string $apiKey = '';

    public string $imageModel = 'OpenAI Image Generation';

    public string $defaultAspectRatio = '1:1';

    public string $defaultQuality = 'high';

    public bool $showApiKey = false;

    public bool $testingConnection = false;

    public bool $saved = false;

    public function mount(string $initialTab = 'general'): void
    {
        $this->activeTab = in_array($initialTab, ['provider', 'openai', 'activity'], true)
            ? ($initialTab === 'openai' ? 'provider' : $initialTab)
            : 'general';

        $this->loadAiProviderSettings();
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['general', 'provider', 'openai', 'activity'], true)
            ? ($tab === 'openai' ? 'provider' : $tab)
            : 'general';
    }

    #[On('settings-tab-changed')]
    public function syncTab(string $tab): void
    {
        $this->selectTab($tab);
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

        $this->saved = true;
        $this->dispatch(
            'toast',
            type: 'success',
            title: 'AI provider updated',
            message: 'Provider aktif berhasil diperbarui.'
        );
    }

    public function saveGeneral(): void
    {
        $this->saved = true;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Pengaturan tersimpan',
            message: 'Preferensi workspace berhasil diperbarui.'
        );
    }

    public function saveOpenAi(): void
    {
        $this->saved = true;

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'OpenAI API disimpan',
            message: 'Konfigurasi API berhasil disimpan.'
        );
    }

    public function testConnection(): void
    {
        $this->testingConnection = true;

        // Backend OpenAI connection will be wired in the next step.
        $this->dispatch(
            'toast',
            type: 'info',
            title: 'Connection test',
            message: 'UI test connection siap dihubungkan ke OpenAI API.'
        );

        $this->testingConnection = false;
    }

    public function render()
    {
        return view('livewire.dashboard.settings.index');
    }
}
