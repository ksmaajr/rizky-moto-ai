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
    public bool $agentCredentialMonitoringOpen = false;
    public array $selectedAgentCredentialMonitoring = [];
    public array $agentCredentialMonitoringLogs = [];
    public int $agentCredentialCooldownMinutes = 1440;

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

    public function openAgentCredentialMonitoring(int $credentialId): void
    {
        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            $this->dispatch('toast', type: 'error', title: 'Account tidak ditemukan', message: 'Credential tidak tersedia atau aksesnya tidak diizinkan.');
            return;
        }

        $this->agentCredentialMonitoringOpen = true;
        $this->refreshAgentCredentialMonitoring($credentialId);
    }

    public function refreshAgentCredentialMonitoring(?int $credentialId = null): void
    {
        $credentialId ??= (int) ($this->selectedAgentCredentialMonitoring['id'] ?? 0);

        if ($credentialId <= 0 || ! $this->agentCredentialMonitoringOpen) {
            return;
        }

        $credential = $this->agentCredentialForCurrentUser($credentialId);

        if (! $credential) {
            $this->closeAgentCredentialMonitoring();
            return;
        }

        // Backfill a timer for rate-limit events recorded before the configurable
        // duration existed. The countdown is anchored to last_failure_at.
        if ($credential->last_error_type === 'rate_limited' && ! $credential->cooldown_until && $credential->last_failure_at) {
            $durationMinutes = max(1, min(10080, (int) ($credential->cooldown_duration_minutes ?: 1440)));
            $until = $credential->last_failure_at->copy()->addMinutes($durationMinutes);
            $credential->forceFill([
                'status' => $until->isFuture() ? 'cooldown' : 'error',
                'cooldown_until' => $until,
            ])->save();
            $credential->refresh();
        }

        $this->agentCredentialCooldownMinutes = max(1, min(1440, (int) ($credential->cooldown_duration_minutes ?: 300)));

        $this->selectedAgentCredentialMonitoring = [
            'id' => $credential->id,
            'name' => $credential->name,
            'status' => $credential->status,
            'is_active' => (bool) $credential->is_active,
            'request_count' => (int) $credential->request_count,
            'success_count' => (int) $credential->success_count,
            'failure_count' => (int) $credential->failure_count,
            'last_used_at' => $credential->last_used_at?->toIso8601String(),
            'last_success_at' => $credential->last_success_at?->toIso8601String(),
            'last_failure_at' => $credential->last_failure_at?->toIso8601String(),
            'cooldown_until' => $credential->cooldown_until?->toIso8601String(),
            'cooldown_duration_minutes' => (int) ($credential->cooldown_duration_minutes ?: 300),
            'last_error_type' => $credential->last_error_type,
            'last_error' => $credential->last_error,
            'last_exit_code' => $credential->last_exit_code,
            'created_at' => $credential->created_at?->toIso8601String(),
        ];

        $this->agentCredentialMonitoringLogs = \App\Models\ActivityLog::query()
            ->where('metadata->credential_id', $credentialId)
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(function (\App\Models\ActivityLog $log): array {
                return [
                    'id' => $log->id,
                    'title' => $log->title ?: ($log->action ?: 'AgentKit activity'),
                    'description' => $log->description ?: '',
                    'status' => $log->status ?: 'info',
                    'action' => $log->action ?: 'activity',
                    'created_at' => $log->created_at?->toIso8601String(),
                    'http_status' => data_get($log->metadata, 'http_status'),
                    'duration_ms' => data_get($log->metadata, 'duration_ms'),
                    'exit_code' => data_get($log->metadata, 'exit_code'),
                ];
            })
            ->values()
            ->all();
    }

    public function closeAgentCredentialMonitoring(): void
    {
        $this->agentCredentialMonitoringOpen = false;
        $this->selectedAgentCredentialMonitoring = [];
        $this->agentCredentialMonitoringLogs = [];
    }

    public function saveAgentCredentialCooldownPreference(): void
    {
        $this->validate([
            'agentCredentialCooldownMinutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        $credentialId = (int) ($this->selectedAgentCredentialMonitoring['id'] ?? 0);
        $credential = $credentialId > 0 ? $this->agentCredentialForCurrentUser($credentialId) : null;

        if (! $credential) {
            $this->dispatch('toast', type: 'error', title: 'Account tidak ditemukan', message: 'Pilih credential yang valid sebelum menyimpan durasi cooldown.');
            return;
        }

        $durationMinutes = max(1, min(10080, (int) $this->agentCredentialCooldownMinutes));
        $updates = ['cooldown_duration_minutes' => $durationMinutes];

        // If this account is already rate-limited, recalculate from the original
        // detection time; changing the preference must not restart the clock.
        if ($credential->last_error_type === 'rate_limited' && $credential->last_failure_at) {
            $until = $credential->last_failure_at->copy()->addMinutes($durationMinutes);
            $updates['cooldown_until'] = $until;
            $updates['status'] = $until->isFuture() ? 'cooldown' : 'error';
        }

        $credential->forceFill($updates)->save();
        $this->refreshAgentCredentialMonitoring($credential->id);
        $this->loadAgentAiCredentials();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Durasi cooldown tersimpan',
            message: 'Durasi ' . $durationMinutes . ' menit akan diterapkan otomatis saat rate limit terdeteksi. Countdown dihitung dari waktu limit terdeteksi.'
        );
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
     * Start the official Codex CLI browser login in a separate local PowerShell
     * window, then import the resulting file-based ChatGPT session.
     *
     * This bridge is intentionally limited to local Windows development. A
     * remote web server cannot safely open a browser on the user's workstation.
     */
    public function beginCodexLogin(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Login perlu dilakukan di host AgentKit',
                message: 'Login browser otomatis saat ini hanya didukung pada development lokal Windows. Jalankan codex login pada host AgentKit, lalu import sesi melalui prosedur host tersebut.'
            );
            return;
        }

        if (! $this->codexCliIsAvailable()) {
            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Codex CLI tidak terdeteksi',
                message: 'Atur CODEX_CLI_BINARY ke path codex.cmd yang valid, lalu muat ulang Settings.'
            );
            return;
        }

        $userId = (int) Auth::id();
        $accountName = trim($this->newAgentCredentialName) !== ''
            ? mb_substr(trim($this->newAgentCredentialName), 0, 120)
            : 'Codex Account';

        $binary = trim((string) config('services.agent_ai.codex_cli_binary', ''), " \\t\\n\\r\\0\\x0B\\\"'");
        if ($binary === '') {
            $binary = 'codex.cmd';
        }

        $escape = static fn (string $value): string => str_replace("'", "''", $value);
        $scriptPath = storage_path('framework/agentkit-login-' . $userId . '-' . bin2hex(random_bytes(5)) . '.ps1');
        $phpBinary = PHP_BINARY;
        $basePath = base_path();
        $importCommand = sprintf(
            "& '%s' artisan agent-ai:codex-import --user-id=%d --name '%s'",
            $escape($phpBinary),
            $userId,
            $escape($accountName)
        );

        $script = implode("\r\n", [
            "\$ErrorActionPreference = 'Stop'",
            "Set-Location '" . $escape($basePath) . "'",
            "Write-Host 'Rizky Moto AI - ChatGPT / Codex login' -ForegroundColor Cyan",
            "Write-Host 'Selesaikan login pada browser yang dibuka Codex CLI.'",
            "\$codex = '" . $escape($binary) . "'",
            "& \$codex login",
            "if (\$LASTEXITCODE -ne 0) { Write-Host 'Login gagal atau dibatalkan. Credential tidak diimpor.' -ForegroundColor Red; Read-Host 'Tekan Enter untuk menutup'; exit \$LASTEXITCODE }",
            $importCommand,
            "if (\$LASTEXITCODE -eq 0) { Write-Host 'Import selesai. Buka Settings dan jalankan Test Token.' -ForegroundColor Green }",
            "Read-Host 'Tekan Enter untuk menutup jendela ini'",
            "",
        ]);

        if (! is_dir(dirname($scriptPath))) {
            @mkdir(dirname($scriptPath), 0770, true);
        }

        if (file_put_contents($scriptPath, $script, LOCK_EX) === false) {
            $this->dispatch('toast', type: 'error', title: 'Tidak dapat menyiapkan login', message: 'Script login tidak dapat ditulis ke storage/framework.');
            return;
        }

        try {
            $result = Process::timeout(5)->run([
                'powershell.exe',
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-Command',
                "Start-Process -FilePath 'powershell.exe' -ArgumentList @('-NoProfile','-ExecutionPolicy','Bypass','-File','" . str_replace("'", "''", $scriptPath) . "')",
            ]);

            if ($result->failed()) {
                @unlink($scriptPath);
                report(new \RuntimeException(trim($result->errorOutput() ?: $result->output())));
                $this->dispatch('toast', type: 'error', title: 'Jendela login gagal dibuka', message: 'PowerShell tidak dapat membuka jendela login Codex. Jalankan codex login secara manual pada terminal host lokal.');
                return;
            }

            $this->dispatch(
                'toast',
                type: 'success',
                title: 'Jendela login Codex dibuka',
                message: 'Selesaikan login ChatGPT pada jendela PowerShell yang baru. Setelah login berhasil, sesi akan diimpor sebagai Pending Validation; jalankan Test Token sebelum mengaktifkannya.'
            );
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', title: 'Login Codex gagal dimulai', message: 'Periksa log aplikasi dan pastikan aplikasi berjalan pada sesi Windows interaktif yang sama.');
        }
    }

    private function codexCliIsAvailable(): bool
    {
        // Avoid spawning a subprocess on every Livewire render: process startup
        // can block the settings page, especially on Windows.
        $binary = trim((string) config('services.agent_ai.codex_cli_binary', ''), " \\t\\n\\r\\0\\x0B\\\"'");
        if ($binary === '') {
            $binary = PHP_OS_FAMILY === 'Windows' ? 'codex.cmd' : 'codex';
        }

        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return is_file($binary);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $appData = (string) getenv('APPDATA');
            return $appData !== '' && is_file(rtrim($appData, '\\\\/') . DIRECTORY_SEPARATOR . 'npm' . DIRECTORY_SEPARATOR . $binary);
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $directory) {
            if (is_file(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binary)) {
                return true;
            }
        }

        return false;
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
