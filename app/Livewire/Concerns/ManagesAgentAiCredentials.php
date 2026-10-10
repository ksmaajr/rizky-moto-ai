<?php

namespace App\Livewire\Concerns;

use App\Models\AgentAiCredential;
use App\Services\AI\Providers\AgentKitProvider;
use App\Services\AgentKitWorkerManager;
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
    public array $agentAiRuntimeStatus = [];
    public bool $agentAiRuntimeBusy = false;

    public function loadAgentAiCredentials(): void
    {
        $this->codexCliAvailable = $this->codexCliIsAvailable();
        $this->refreshAgentAiRuntimeStatus();

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

    public function refreshAgentAiRuntimeStatus(): void
    {
        try {
            $this->agentAiRuntimeStatus = app(AgentKitWorkerManager::class)->status();
        } catch (\Throwable $e) {
            report($e);
            $this->agentAiRuntimeStatus = [
                'success' => false,
                'running' => false,
                'status' => 'unknown',
                'message' => 'Runtime status belum dapat dibaca. Periksa konfigurasi worker.',
            ];
        }
    }

    public function controlAgentAiRuntime(string $action): void
    {
        $action = strtolower(trim($action));
        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            $this->dispatch('toast', type: 'error', title: 'Aksi tidak valid', message: 'Gunakan Start, Stop, atau Restart.');
            return;
        }

        if ($this->agentAiRuntimeBusy) {
            return;
        }

        $this->agentAiRuntimeBusy = true;

        try {
            $manager = app(AgentKitWorkerManager::class);
            $result = match ($action) {
                'start' => $manager->start(),
                'stop' => $manager->stop(),
                'restart' => $manager->restart(),
            };

            $this->refreshAgentAiRuntimeStatus();
            $success = (bool) ($result['success'] ?? false);
            $this->dispatch(
                'toast',
                type: $success ? 'success' : 'error',
                title: $success ? 'Agent AI runtime diperbarui' : 'Kontrol runtime gagal',
                message: (string) ($result['message'] ?? 'Perintah runtime selesai.')
            );
        } catch (\Throwable $e) {
            report($e);
            $this->refreshAgentAiRuntimeStatus();
            $this->dispatch('toast', type: 'error', title: 'Kontrol runtime gagal', message: 'Runtime tidak dapat dikendalikan. Periksa konfigurasi driver dan log worker.');
        } finally {
            $this->agentAiRuntimeBusy = false;
        }
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

        $userId = (int) Auth::id();
        $accountName = trim($this->newAgentCredentialName) !== ''
            ? mb_substr(trim($this->newAgentCredentialName), 0, 120)
            : 'Codex Account';
        $safeName = str_replace('"', '', $accountName);
        $command = sprintf(
            'php artisan agent-ai:codex-import --user-id=%d --name="%s"',
            $userId,
            $safeName
        );

        $this->dispatch(
            'toast',
            type: 'info',
            title: 'Sesi Codex siap diimpor',
            message: 'Login resmi tetap dilakukan di terminal host ini: jalankan codex login, lalu jalankan perintah berikut: ' . $command . '. Akun akan Pending Validation sampai Test Token berhasil. Bridge file-based ini eksperimental.'
        );
    }

    private function codexCliIsAvailable(): bool
    {
        try {
            return Process::timeout(5)->run(['codex', '--version'])->successful();
        } catch (\Throwable) {
            return false;
        }
    }
    /**
     * Raw token entry is intentionally disabled. New accounts must pass through
     * the guarded Codex importer and explicit compatibility validation.
     */
    public function addAgentAiCredential(): void
    {
        $this->dispatch(
            'toast',
            type: 'warning',
            title: 'Import melalui Codex CLI',
            message: 'Input token manual dinonaktifkan. Gunakan codex login dan agent-ai:codex-import, lalu validasi credential sebelum diaktifkan.'
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

        if ($credential->status === 'pending_validation') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Credential belum tervalidasi',
                message: 'Jalankan Test Token terlebih dahulu. Credential baru hanya dapat aktif setelah live validation berhasil.'
            );
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
