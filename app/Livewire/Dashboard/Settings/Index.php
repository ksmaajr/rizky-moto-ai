<?php

namespace App\Livewire\Dashboard\Settings;

use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'general';

    public string $apiKey = '';

    public string $imageModel = 'OpenAI Image Generation';

    public string $defaultAspectRatio = '1:1';

    public string $defaultQuality = 'high';

    public bool $showApiKey = false;

    public bool $testingConnection = false;

    public bool $saved = false;

    public function mount(string $initialTab = 'general'): void
    {
        $this->activeTab = $initialTab === 'openai' ? 'openai' : 'general';
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['general', 'openai'], true)
            ? $tab
            : 'general';
    }

    #[On('settings-tab-changed')]
    public function syncTab(string $tab): void
    {
        $this->selectTab($tab);
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
