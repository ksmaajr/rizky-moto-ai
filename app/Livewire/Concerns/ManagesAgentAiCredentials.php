<?php

namespace App\Livewire\Concerns;

use App\Models\AgentAiCredential;
use App\Services\AI\Providers\AgentKitProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;

trait ManagesAgentAiCredentials
{
    public string $newAgentCredentialName = '';
    public string $newAgentCredentialToken = '';
    public bool $codexCliAvailable = false;
    public bool $showAgentCredentialForm = false;
    public array $agentAiCredentials = [];
    public int $agentAiCredentialCount = 0;
    public int $agentAiActiveCredentialCount = 0;

    public function loadAgentAiCredentials(): void
    {
        $this->codexCliAvailable = $this->codexCliIsAvailable();

        $query = AgentAiCredential::query()
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->orderBy('last_used_at')
            ->orderBy('id');

        $rows = $query->get();

        $this->agentAiCredentials = $rows->map(fn (AgentAiCredential $credential): array => [
            'id' => $credential->id,
            'name' => $credential->name,
            'status' => $credential->status,
            'is_active' => (bool) $credential->is_active,
            'request_count' => (int) $credential->request_count,
            'success_count' => (int) $credential->success_count,
            'failure_count' => (int) $credential->failure_count,
            'last_used_at' => $credential->last_used_at?->diffForHumans(),
            'last_success_at' => $credential->last_success_at?->diffForHumans(),
            'last_failure_at' => $credential->last_failure_at?->diffForHumans(),
            'last_error_type' => $credential->last_error_type,
            'cooldown_until' => $credential->cooldown_until?->toIso8601String(),
        ])->all();

        $this->agentAiCredentialCount = count($this->agentAiCredentials);
        $this->agentAiActiveCredentialCount = collect($this->agentAiCredentials)
            ->where('is_active', true)
            ->whereNotIn('status', ['disabled', 'invalid', 'exhausted'])
            ->count();
    }

    /**
     * Codex CLI owns the official OAuth flow. Do not claim login succeeded or
     * import an undocumented auth.json format from the Laravel web process.
     */
    public function beginCodexLogin(): void
    {
        if (! $this->codexCliIsAvailable()) {
            $this->dispatch('toast', type: 'warning', title: 'Codex CLI belum tersedia', message: 'Install Codex CLI pada host AgentKit, lalu jalankan codex login. Akun belum ditambahkan.');
            return;
        }

        $this->dispatch('toast', type: 'info', title: 'Satu langkah lagi: import sesi Codex', message: 'Jalankan codex login pada terminal mesin yang sama dengan Laravel, lalu jalankan php artisan agent-ai:codex-import --user-id=ID_USER --name="Nama Akun". Akun masuk sebagai Pending Validation dan baru aktif setelah Test Token berhasil. Bridge file-based ini eksperimental.');
    }

    private function codexCliIsAvailable(): bool
    {
        try {
            return Process::timeout(5)->run(['codex', '--version'])->successful();
        } catch (\\Throwable) {
            return false;
        }
    }
    public function addAgentAiCredential(): void
    {
        $this->validate([
            'newAgentCredentialName' => ['required', 'string', 'max:120'],
            'newAgentCredentialToken' => ['required', 'string', 'min:20', 'max:10000'],
        ]);

        AgentAiCredential::create([
            'user_id' => Auth::id(),
            'name' => trim($this->newAgentCredentialName),
            'access_token' => trim($this->newAgentCredentialToken),
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->newAgentCredentialName = '';
        $this->newAgentCredentialToken = '';
        $this->showAgentCredentialForm = false;
        $this->loadAgentAiCredentials();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Agent credential added',
            message: 'Credential tersimpan terenkripsi dan siap masuk pool.'
        );
    }

    public function testAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        if (! $credential->is_active && $credential->status !== 'pending_validation') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Credential tidak siap diuji',
                message: 'Credential harus aktif atau berstatus Pending Validation.'
            );
            return;
        }

        try {
            $result = app(AgentKitProvider::class)->testCredential($credential);
            $this->loadAgentAiCredentials();

            $this->dispatch(
                'toast',
                type: ($result['success'] ?? false) ? 'success' : 'error',
                title: ($result['success'] ?? false) ? 'Agent credential valid' : 'Agent credential test gagal',
                message: (string) ($result['message'] ?? 'Test selesai.'),
            );
        } catch (\Throwable $e) {
            report($e);
            $this->loadAgentAiCredentials();

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Agent credential test gagal',
                message: $e->getMessage(),
            );
        }
    }

    public function toggleAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        $credential->update([
            'is_active' => ! $credential->is_active,
            'status' => $credential->is_active ? 'disabled' : 'active',
            'cooldown_until' => null,
        ]);

        $this->loadAgentAiCredentials();
    }

    public function deleteAgentAiCredential(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            return;
        }

        $credential->delete();
        $this->loadAgentAiCredentials();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Agent credential removed',
            message: 'Credential dihapus dari pool.'
        );
    }

    private function agentCredentialForCurrentUser(int $credentialId): ?AgentAiCredential
    {
        return AgentAiCredential::query()
            ->whereKey($credentialId)
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereNull('user_id');
            })
            ->first();
    }
}
